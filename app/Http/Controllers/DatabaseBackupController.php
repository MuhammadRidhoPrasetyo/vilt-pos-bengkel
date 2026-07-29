<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
                    $backups[] = [
                        'name' => $file->getFilename(),
                        'size' => $file->getSize(),
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

        $timestamp = now()->format('Y-m-d_His');
        $exportFileName = "vilt-pos-bengkel-backup-{$timestamp}.sqlite";

        // Create temporary copy for clean download
        $tempPath = storage_path("app/temp-{$exportFileName}");
        File::copy($dbPath, $tempPath);

        return response()->download($tempPath, $exportFileName)->deleteFileAfterSend(true);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'database_file' => ['required', 'file', 'max:102400'], // max 100MB
        ]);

        $file = $request->file('database_file');

        if (! $file || ! $file->isValid()) {
            return back()->with('error', 'File yang diunggah tidak valid.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['sqlite', 'db'])) {
            return back()->with('error', 'Format file harus bertipe .sqlite atau .db');
        }

        // Validate SQLite Magic Header "SQLite format 3\0"
        $handle = fopen($file->getRealPath(), 'rb');
        $header = fread($handle, 16);
        fclose($handle);

        if ($header !== "SQLite format 3\0") {
            return back()->with('error', 'File yang diunggah bukan merupakan database SQLite3 yang valid.');
        }

        // Validate SQLite Database Integrity via PDO PRAGMA
        try {
            $testPdo = new \PDO('sqlite:'.$file->getRealPath());
            $testPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $check = $testPdo->query('PRAGMA integrity_check')->fetchColumn();
            if ($check !== 'ok') {
                return back()->with('error', 'File database SQLite rusak atau tidak memenuhi integritas data.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memverifikasi struktur file SQLite: '.$e->getMessage());
        }

        $dbPath = $this->getDatabasePath();

        try {
            // 1. Create safety backup of existing database
            $backupDir = storage_path('app/backups');
            if (! File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            if (File::exists($dbPath)) {
                $safetyName = 'auto-safety-backup-'.now()->format('Y-m-d_His').'.sqlite';
                File::copy($dbPath, "{$backupDir}/{$safetyName}");
            }

            // 2. Temporarily disconnect SQLite connection (skip in testing to preserve in-memory test DB)
            if (! app()->environment('testing')) {
                DB::disconnect('sqlite');
                File::copy($file->getRealPath(), $dbPath);
                DB::reconnect('sqlite');
            } else {
                File::copy($file->getRealPath(), $dbPath);
            }

            return back()->with('success', 'Database berhasil di-restore! Data lama telah dicadangkan secara otomatis.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memulihkan database: '.$e->getMessage());
        }
    }
}
