<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    private function getDatabasePath(): string
    {
        $dbPath = config('database.connections.sqlite.database');

        if (! $dbPath || ! File::exists($dbPath)) {
            $dbPath = database_path('database.sqlite');
        }

        return $dbPath;
    }

    public function index(): Response
    {
        $dbPath = $this->getDatabasePath();
        $exists = File::exists($dbPath);

        $fileSize = $exists ? File::size($dbPath) : 0;
        $lastModified = $exists ? date('Y-m-d H:i:s', File::lastModified($dbPath)) : '-';

        $tableCount = 0;
        if ($exists) {
            try {
                $tables = DB::select("SELECT count(*) as count FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tableCount = $tables[0]->count ?? 0;
            } catch (\Throwable $e) {
                $tableCount = 0;
            }
        }

        $backupDir = storage_path('app/backups');
        $backups = [];
        if (File::exists($backupDir)) {
            $files = File::files($backupDir);
            foreach ($files as $file) {
                if (in_array($file->getExtension(), ['sqlite', 'db'])) {
                    $itemTableCount = 0;
                    try {
                        $pdo = new \PDO('sqlite:'.$file->getRealPath());
                        $itemTableCount = (int) $pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchColumn();
                        unset($pdo);
                    } catch (\Throwable $e) {
                        $itemTableCount = 0;
                    }

                    $backups[] = [
                        'name' => $file->getFilename(),
                        'size' => $file->getSize(),
                        'table_count' => $itemTableCount,
                        'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
            }
            usort($backups, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));
        }

        return Inertia::render('settings/database', [
            'info' => [
                'driver' => 'SQLite',
                'path' => basename($dbPath),
                'full_path' => $dbPath,
                'file_size' => $fileSize,
                'last_modified' => $lastModified,
                'table_count' => $tableCount,
                'upload_max_filesize' => ini_get('upload_max_filesize') ?: '128M',
                'post_max_size' => ini_get('post_max_size') ?: '128M',
            ],
            'backups' => array_slice($backups, 0, 10),
        ]);
    }

    public function export(): BinaryFileResponse
    {
        $dbPath = $this->getDatabasePath();

        if (! File::exists($dbPath)) {
            abort(404, 'File database SQLite tidak ditemukan.');
        }

        // Flush WAL frames to main database file before copying
        try {
            DB::statement('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (\Throwable $e) {
            // Ignore if in-memory or non-WAL
        }

        $timestamp = now()->format('Y-m-d_His');
        $exportFileName = "vilt-pos-bengkel-backup-{$timestamp}.sqlite";

        // Create temporary copy for clean download
        $tempPath = storage_path("app/temp-{$exportFileName}");
        File::copy($dbPath, $tempPath);

        return response()->download($tempPath, $exportFileName)->deleteFileAfterSend(true);
    }

    public function import(Request $request): RedirectResponse
    {
        $maxUpload = ini_get('upload_max_filesize') ?: '128M';
        $maxPost = ini_get('post_max_size') ?: '128M';

        if ($request->header('CONTENT_LENGTH') && empty($_POST) && empty($_FILES) && empty($request->getContent())) {
            return back()->with('error', "Permintaan upload tidak diterima oleh server (Batas post_max_size: {$maxPost}). Silakan refresh halaman browser Anda (Ctrl + F5) untuk memuat sistem upload terbaru.");
        }

        $tempUploadedPath = null;
        $isTempGenerated = false;

        // Jalur 1: Base64 payload langsung (100% kebal terhadap kendala upload_tmp_dir pada Windows)
        if ($request->filled('database_content')) {
            $base64Data = $request->input('database_content');
            $fileName = $request->input('database_file_name', 'database.sqlite');
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (! in_array($extension, ['sqlite', 'db'])) {
                return back()->with('error', 'Format file harus bertipe .sqlite atau .db');
            }

            $binaryData = base64_decode($base64Data, true);
            if ($binaryData === false || strlen($binaryData) === 0) {
                return back()->with('error', 'Format berkas database tidak valid atau rusak.');
            }

            if (strlen($binaryData) > 128 * 1024 * 1024) {
                return back()->with('error', 'Ukuran data database melebihi batas maksimum 128MB.');
            }

            // Validasi SQLite Magic Header "SQLite format 3\0"
            if (substr($binaryData, 0, 16) !== "SQLite format 3\0") {
                return back()->with('error', 'File yang diunggah bukan merupakan database SQLite3 yang valid.');
            }

            $tempDir = storage_path('app/temp');
            if (! File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            $tempUploadedPath = "{$tempDir}/import-".uniqid().'.sqlite';
            File::put($tempUploadedPath, $binaryData);
            $isTempGenerated = true;
        } else {
            // Jalur 2: Standar multipart file upload
            if (isset($_FILES['database_file']) && $_FILES['database_file']['error'] !== UPLOAD_ERR_OK) {
                $errorCode = $_FILES['database_file']['error'];
                $errorMessage = match ($errorCode) {
                    UPLOAD_ERR_INI_SIZE => "Ukuran file melebihi batas upload_max_filesize ({$maxUpload}) pada konfigurasi PHP server.",
                    UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form size yang diizinkan.',
                    UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian. Silakan periksa jaringan dan coba lagi.',
                    UPLOAD_ERR_NO_FILE => 'Tidak ada berkas file yang terdeteksi untuk diunggah.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Server tidak menemukan folder sementara untuk proses upload.',
                    UPLOAD_ERR_CANT_WRITE => 'Gagal menulis berkas sementara ke penyimpanan disk server.',
                    default => "Gagal mengunggah file ke server (Error code: {$errorCode}).",
                };

                return back()->with('error', $errorMessage);
            }

            $request->validate([
                'database_file' => ['required', 'file', 'max:131072'], // max 128MB
            ], [
                'database_file.uploaded' => "File gagal diunggah ke server. Ukuran file kemungkinan melebihi batas konfigurasi PHP server (upload_max_filesize: {$maxUpload}).",
                'database_file.required' => 'Silakan pilih file database terlebih dahulu.',
                'database_file.file' => 'Berkas yang diunggah harus berupa file yang valid.',
                'database_file.max' => 'Ukuran file database tidak boleh lebih dari 128MB.',
            ]);

            $file = $request->file('database_file');

            if (! $file || ! $file->isValid()) {
                return back()->with('error', 'File yang diunggah tidak valid.');
            }

            $extension = strtolower($file->getClientOriginalExtension());
            if (! in_array($extension, ['sqlite', 'db'])) {
                return back()->with('error', 'Format file harus bertipe .sqlite atau .db');
            }

            // Validasi SQLite Magic Header "SQLite format 3\0"
            $uploadedPath = $file->getRealPath() ?: $file->getPathname();
            $handle = fopen($uploadedPath, 'rb');
            $header = fread($handle, 16);
            fclose($handle);

            if ($header !== "SQLite format 3\0") {
                return back()->with('error', 'File yang diunggah bukan merupakan database SQLite3 yang valid.');
            }

            $tempUploadedPath = $uploadedPath;
        }

        return $this->performDatabaseReplacement($tempUploadedPath, $request, $isTempGenerated);
    }

    public function restoreBackup(Request $request): RedirectResponse
    {
        $request->validate([
            'filename' => ['required', 'string'],
        ]);

        $safeFileName = basename($request->input('filename'));
        $filePath = storage_path("app/backups/{$safeFileName}");

        if (! File::exists($filePath)) {
            return back()->with('error', 'File backup tidak ditemukan di penyimpanan server.');
        }

        return $this->performDatabaseReplacement($filePath, $request, false);
    }

    public function downloadBackup(string $fileName): BinaryFileResponse
    {
        $safeFileName = basename($fileName);
        $filePath = storage_path("app/backups/{$safeFileName}");

        if (! File::exists($filePath)) {
            abort(404, 'File backup tidak ditemukan.');
        }

        return response()->download($filePath, $safeFileName);
    }

    public function deleteBackup(string $fileName): RedirectResponse
    {
        $safeFileName = basename($fileName);
        $filePath = storage_path("app/backups/{$safeFileName}");

        if (File::exists($filePath)) {
            File::delete($filePath);

            return back()->with('success', "File backup {$safeFileName} berhasil dihapus.");
        }

        return back()->with('error', 'File backup tidak ditemukan.');
    }

    public function uploadChunk(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'file_name' => ['required', 'string', 'max:255'],
            'chunk_data' => ['required', 'string'],
        ]);

        $fileName = $request->input('file_name');
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (! in_array($extension, ['sqlite', 'db'])) {
            return response()->json([
                'success' => false,
                'message' => 'Format file harus bertipe .sqlite atau .db',
            ], 422);
        }

        $uploadId = $request->input('upload_id');
        $chunkIndex = (int) $request->input('chunk_index');
        $totalChunks = (int) $request->input('total_chunks');

        $chunkBinary = base64_decode($request->input('chunk_data'), true);
        if ($chunkBinary === false) {
            return response()->json([
                'success' => false,
                'message' => "Data potongan ke-{$chunkIndex} rusak atau tidak valid.",
            ], 422);
        }

        // Simpan chunk langsung di storage internal project menggunakan Storage::
        $chunkRelativePath = "temp/uploads/{$uploadId}/chunk_{$chunkIndex}.part";
        Storage::disk('local')->put($chunkRelativePath, $chunkBinary);

        // Jika belum mencapai chunk terakhir, laporkan progress
        if ($chunkIndex < $totalChunks - 1) {
            $progressPercent = (int) round((($chunkIndex + 1) / $totalChunks) * 100);

            return response()->json([
                'success' => true,
                'chunk_index' => $chunkIndex,
                'total_chunks' => $totalChunks,
                'progress' => $progressPercent,
                'is_completed' => false,
            ]);
        }

        // CHUNK TERAKHIR: Gabungkan seluruh potongan part di dalam storage internal project
        $mergedRelativePath = "temp/uploads/{$uploadId}/merged_database.sqlite";
        $mergedAbsolutePath = Storage::disk('local')->path($mergedRelativePath);

        // Pastikan folder tujuan ada
        File::ensureDirectoryExists(dirname($mergedAbsolutePath));

        $mergedHandle = fopen($mergedAbsolutePath, 'wb');
        if (! $mergedHandle) {
            Storage::disk('local')->deleteDirectory("temp/uploads/{$uploadId}");

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat file gabungan sementara di storage project.',
            ], 500);
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $partRelativePath = "temp/uploads/{$uploadId}/chunk_{$i}.part";
            if (! Storage::disk('local')->exists($partRelativePath)) {
                fclose($mergedHandle);
                Storage::disk('local')->deleteDirectory("temp/uploads/{$uploadId}");

                return response()->json([
                    'success' => false,
                    'message' => "Potongan file ke-{$i} tidak ditemukan di storage server. Silakan coba unggah ulang.",
                ], 422);
            }

            $partAbsolutePath = Storage::disk('local')->path($partRelativePath);
            $partHandle = fopen($partAbsolutePath, 'rb');
            if ($partHandle) {
                stream_copy_to_stream($partHandle, $mergedHandle);
                fclose($partHandle);
            }
        }
        fclose($mergedHandle);

        // Verifikasi Magic Header SQLite "SQLite format 3\0"
        $magicHandle = fopen($mergedAbsolutePath, 'rb');
        $magicHeader = fread($magicHandle, 16);
        fclose($magicHandle);

        if ($magicHeader !== "SQLite format 3\0") {
            Storage::disk('local')->deleteDirectory("temp/uploads/{$uploadId}");

            return response()->json([
                'success' => false,
                'message' => 'File yang diunggah bukan merupakan database SQLite3 yang valid.',
            ], 422);
        }

        // Eksekusi pemulihan database
        $replacementResult = $this->executeReplacement($mergedAbsolutePath, $request, false);

        // Bersihkan folder sementara di storage project
        Storage::disk('local')->deleteDirectory("temp/uploads/{$uploadId}");

        if ($replacementResult['success']) {
            $request->session()->flash('success', $replacementResult['message']);

            return response()->json([
                'success' => true,
                'progress' => 100,
                'is_completed' => true,
                'message' => $replacementResult['message'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $replacementResult['message'],
        ], 422);
    }

    private function performDatabaseReplacement(string $sourcePath, Request $request, bool $isTempGenerated = false): RedirectResponse
    {
        $result = $this->executeReplacement($sourcePath, $request, $isTempGenerated);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function executeReplacement(string $sourcePath, Request $request, bool $isTempGenerated = false): array
    {
        // 1. Validate SQLite Database Integrity via PDO PRAGMA
        try {
            $testPdo = new \PDO('sqlite:'.$sourcePath);
            $testPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $check = $testPdo->query('PRAGMA integrity_check')->fetchColumn();
            $tableCount = (int) $testPdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchColumn();
            unset($testPdo); // Critical: Close PDO handle immediately on Windows

            if ($check !== 'ok') {
                if ($isTempGenerated && File::exists($sourcePath)) {
                    @unlink($sourcePath);
                }

                return [
                    'success' => false,
                    'message' => 'File database SQLite rusak atau tidak memenuhi integritas data.',
                ];
            }

            if ($tableCount === 0) {
                if ($isTempGenerated && File::exists($sourcePath)) {
                    @unlink($sourcePath);
                }

                return [
                    'success' => false,
                    'message' => 'Berkas database SQLite ini kosong (tidak memiliki tabel data apa pun).',
                ];
            }
        } catch (\Throwable $e) {
            unset($testPdo);
            if ($isTempGenerated && File::exists($sourcePath)) {
                @unlink($sourcePath);
            }

            return [
                'success' => false,
                'message' => 'Gagal memverifikasi struktur file SQLite: '.$e->getMessage(),
            ];
        }

        $dbPath = $this->getDatabasePath();

        try {
            // 2. Capture current user session data before database replacement if using database sessions
            $currentSessionId = $request->session()->getId();
            $currentSessionRecord = null;
            if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
                try {
                    $currentSessionRecord = DB::table('sessions')->where('id', $currentSessionId)->first();
                } catch (\Throwable $e) {
                    $currentSessionRecord = null;
                }
            }

            // 3. Checkpoint WAL of current database before safety backup
            try {
                DB::statement('PRAGMA wal_checkpoint(TRUNCATE)');
            } catch (\Throwable $e) {
                // Ignore if not in WAL
            }

            // 4. Create safety backup of existing database
            $backupDir = storage_path('app/backups');
            if (! File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            if (File::exists($dbPath)) {
                $safetyName = 'auto-safety-backup-'.now()->format('Y-m-d_His').'.sqlite';
                File::copy($dbPath, "{$backupDir}/{$safetyName}");
            }

            // 5. Temporarily disconnect SQLite connection
            if (! app()->environment('testing')) {
                DB::disconnect('sqlite');
                File::copy($sourcePath, $dbPath);

                // Clean up orphaned WAL and SHM journal files from the old database
                if (File::exists($dbPath.'-wal')) {
                    @unlink($dbPath.'-wal');
                }
                if (File::exists($dbPath.'-shm')) {
                    @unlink($dbPath.'-shm');
                }

                DB::reconnect('sqlite');
            } else {
                File::copy($sourcePath, $dbPath);
            }

            // Hapus file temporary yang di-generate jika ada
            if ($isTempGenerated && File::exists($sourcePath)) {
                @unlink($sourcePath);
            }

            // 6. Jalankan migrasi untuk memastikan tabel-tabel esensial (seperti tabel sessions) selalu tersedia
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                // Ignore
            }

            // 7. Re-apply WAL mode on the restored database
            try {
                DB::statement('PRAGMA journal_mode=WAL');
            } catch (\Throwable $e) {
                // Ignore
            }

            // 7. Restore current user's session record so user remains logged in
            if ($currentSessionRecord && Schema::hasTable('sessions')) {
                try {
                    DB::table('sessions')->updateOrInsert(
                        ['id' => $currentSessionId],
                        (array) $currentSessionRecord
                    );
                } catch (\Throwable $e) {
                    // Ignore session re-insertion failure
                }
            }

            // 8. Clear caches to ensure permission and schema caches match new database
            try {
                Artisan::call('cache:clear');
                Artisan::call('permission:cache-reset');
            } catch (\Throwable $e) {
                // Ignore cache clearing failure
            }

            return [
                'success' => true,
                'message' => 'Database berhasil dipulihkan! Salinan data lama telah dicadangkan secara otomatis.',
            ];
        } catch (\Throwable $e) {
            if ($isTempGenerated && File::exists($sourcePath)) {
                @unlink($sourcePath);
            }

            return [
                'success' => false,
                'message' => 'Gagal memulihkan database: '.$e->getMessage(),
            ];
        }
    }
}
