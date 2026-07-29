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
