<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

test('authenticated user can view database backup settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.database.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/database')
            ->has('info')
            ->has('backups')
        );
});

test('can export sqlite database file download', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('settings.database.export'));

    $response->assertOk()
        ->assertHeaderContains('content-disposition', 'attachment; filename=vilt-pos-bengkel-backup-');
});

test('rejects importing invalid non-sqlite file', function () {
    $user = User::factory()->create();

    $fakeFile = UploadedFile::fake()->create('invalid.txt', 10, 'text/plain');

    $this->actingAs($user)
        ->post(route('settings.database.import'), [
            'database_file' => $fakeFile,
        ])
        ->assertSessionHas('error');
});

test('successfully imports valid sqlite database file', function () {
    $user = User::factory()->create();

    // Create a real valid SQLite binary content
    $validDbPath = storage_path('app/real-valid-import.sqlite');
    if (File::exists($validDbPath)) {
        File::delete($validDbPath);
    }

    $pdo = new PDO('sqlite:'.$validDbPath);
    $pdo->exec('CREATE TABLE test_import (id INTEGER PRIMARY KEY, name TEXT)');
    $pdo->exec("INSERT INTO test_import (name) VALUES ('Sample')");
    unset($pdo);

    $sqliteContent = File::get($validDbPath);
    File::delete($validDbPath);

    $validSqlite = UploadedFile::fake()->createWithContent('valid.sqlite', $sqliteContent);

    // Mock target database path to isolated dummy destination file
    $originalDb = config('database.connections.sqlite.database');
    $mockTargetDb = storage_path('app/mock-target-db.sqlite');
    if (! File::exists($mockTargetDb)) {
        $pdoMock = new PDO('sqlite:'.$mockTargetDb);
        $pdoMock->exec('CREATE TABLE old_table (id INT)');
        unset($pdoMock);
    }

    try {
        config(['database.connections.sqlite.database' => $mockTargetDb]);

        $this->actingAs($user)
            ->post(route('settings.database.import'), [
                'database_file' => $validSqlite,
            ])
            ->assertSessionHas('success');
    } finally {
        config(['database.connections.sqlite.database' => $originalDb]);
    }
});

test('successfully processes chunked upload using internal storage', function () {
    $user = User::factory()->create();

    // Create a real valid SQLite database
    $validDbPath = storage_path('app/test-chunk-source.sqlite');
    if (File::exists($validDbPath)) {
        File::delete($validDbPath);
    }

    $pdo = new PDO('sqlite:'.$validDbPath);
    $pdo->exec('CREATE TABLE chunk_test (id INTEGER PRIMARY KEY, note TEXT)');
    $pdo->exec("INSERT INTO chunk_test (note) VALUES ('Chunked Works!')");
    unset($pdo);

    $sqliteBinary = File::get($validDbPath);
    File::delete($validDbPath);

    // Split into 2 chunks
    $half = (int) ceil(strlen($sqliteBinary) / 2);
    $chunk1 = substr($sqliteBinary, 0, $half);
    $chunk2 = substr($sqliteBinary, $half);

    $uploadId = 'test_upload_'.uniqid();

    // Send chunk 0
    $response1 = $this->actingAs($user)
        ->postJson(route('settings.database.upload-chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 2,
            'file_name' => 'database.sqlite',
            'chunk_data' => base64_encode($chunk1),
        ]);

    $response1->assertOk()
        ->assertJson([
            'success' => true,
            'is_completed' => false,
            'progress' => 50,
        ]);

    // Mock target database
    $originalDb = config('database.connections.sqlite.database');
    $mockTargetDb = storage_path('app/mock-target-chunk-db.sqlite');
    if (! File::exists($mockTargetDb)) {
        $pdoMock = new PDO('sqlite:'.$mockTargetDb);
        $pdoMock->exec('CREATE TABLE old_chunk_table (id INT)');
        unset($pdoMock);
    }

    try {
        config(['database.connections.sqlite.database' => $mockTargetDb]);

        // Send chunk 1 (final)
        $response2 = $this->actingAs($user)
            ->postJson(route('settings.database.upload-chunk'), [
                'upload_id' => $uploadId,
                'chunk_index' => 1,
                'total_chunks' => 2,
                'file_name' => 'database.sqlite',
                'chunk_data' => base64_encode($chunk2),
            ]);

        $response2->assertOk()
            ->assertJson([
                'success' => true,
                'is_completed' => true,
                'progress' => 100,
            ]);
    } finally {
        config(['database.connections.sqlite.database' => $originalDb]);
        if (File::exists($mockTargetDb)) {
            File::delete($mockTargetDb);
        }
    }
});
