<?php

namespace MigrationsUITests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use MigrationsUITests\Util\UsersTableSeeder;

/**
 * @see \DaveJamesMiller\MigrationsUI\Controllers\RunMigrationsController
 */
class RunMigrationsTest extends TestCase
{
    use DatabaseMigrations;

    public function testMigrateSingle()
    {
        // === Arrange ===
        $this->setMigrationPath(__DIR__ . '/migrations/three');

        // === Act ===
        $response = $this->post('/migrations/api/migrate-single/2014_10_12_100000_create_password_resets_table');

        // === Assert ===
        $response->assertOk();

        $this->assertTableExists('password_resets');

        $response->assertJsonStructure([
            'connection',
            'database',
            'migrations',
            'tables',
            'toasts' => [
                ['variant', 'title', 'message', 'runtime'],
            ],
        ]);

        $this->assertIsString($response->json('connection'), 'connection');
        $this->assertIsString($response->json('database'), 'database');
        $this->assertSame('success', $response->json('toasts.0.variant'), 'toasts.0.variant');
        $this->assertSame('Migrated', $response->json('toasts.0.title'), 'toasts.0.title');
        $this->assertSame('2014_10_12_100000_create_password_resets_table', $response->json('toasts.0.message'), 'toasts.0.message');
        $this->assertIsFloat($response->json('toasts.0.runtime'), 'toasts.0.runtime');

        $this->assertSame([
            [
                'name' => '2019_08_19_000000_create_failed_jobs_table',
                'date' => '2019-08-19 00:00:00',
                'title' => 'create failed jobs table',
                'batch' => null,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2019_08_19_000000_create_failed_jobs_table.php',
            ],
            [
                'name' => '2014_10_12_100000_create_password_resets_table',
                'date' => '2014-10-12 10:00:00',
                'title' => 'create password resets table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_100000_create_password_resets_table.php',
            ],
            [
                'name' => '2014_10_12_000000_create_users_table',
                'date' => '2014-10-12 00:00:00',
                'title' => 'create users table',
                'batch' => null,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_000000_create_users_table.php',
            ],
        ], $response->json('migrations'), 'migrations');

        $this->assertSame([
            ['name' => 'migrations', 'rows' => 1],
            ['name' => 'password_resets', 'rows' => 0],
        ], $response->json('tables'), 'tables');
    }

    public function testMigrateAll()
    {
        // === Arrange ===
        $this->setMigrationPath(__DIR__ . '/migrations/three');
        $this->markAsRun('2014_10_12_000000_create_users_table');

        // === Act ===
        $response = $this->post('/migrations/api/migrate-all');

        // === Assert ===
        $response->assertOk();

        $this->assertTableExists('password_resets');
        $this->assertTableExists('failed_jobs');
        $this->assertTableDoesntExist('users');

        $response->assertJsonStructure([
            'connection',
            'database',
            'migrations',
            'tables',
            'toasts' => [
                ['variant', 'title', 'message', 'runtime'],
            ],
        ]);

        $this->assertIsString($response->json('connection'), 'connection');
        $this->assertIsString($response->json('database'), 'database');
        $this->assertSame('success', $response->json('toasts.0.variant'), 'toasts.0.variant');
        $this->assertSame('Migrated', $response->json('toasts.0.title'), 'toasts.0.title');
        $this->assertSame('Ran 2 migrations.', $response->json('toasts.0.message'), 'toasts.0.message');
        $this->assertIsFloat($response->json('toasts.0.runtime'), 'toasts.0.runtime');

        $this->assertSame([
            [
                'name' => '2019_08_19_000000_create_failed_jobs_table',
                'date' => '2019-08-19 00:00:00',
                'title' => 'create failed jobs table',
                'batch' => 2,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2019_08_19_000000_create_failed_jobs_table.php',
            ],
            [
                'name' => '2014_10_12_100000_create_password_resets_table',
                'date' => '2014-10-12 10:00:00',
                'title' => 'create password resets table',
                'batch' => 2,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_100000_create_password_resets_table.php',
            ],
            [
                'name' => '2014_10_12_000000_create_users_table',
                'date' => '2014-10-12 00:00:00',
                'title' => 'create users table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_000000_create_users_table.php',
            ],
        ], $response->json('migrations'), 'migrations');

        $this->assertSame([
            ['name' => 'failed_jobs', 'rows' => 0],
            ['name' => 'migrations', 'rows' => 3],
            ['name' => 'password_resets', 'rows' => 0],
        ], $response->json('tables'), 'tables');
    }

    public function testRollbackSingle()
    {
        // === Arrange ===
        $this->setMigrationPath(__DIR__ . '/migrations/three');
        $this->markAsRun('2014_10_12_000000_create_users_table');
        $this->markAsRun('2014_10_12_100000_create_password_resets_table');

        $this->createTable('users');
        $this->createTable('password_resets');

        // === Act ===
        $response = $this->post('/migrations/api/rollback-single/2014_10_12_000000_create_users_table');

        // === Assert ===
        $response->assertOk();

        $this->assertTableDoesntExist('users');
        $this->assertTableExists('password_resets');

        $response->assertJsonStructure([
            'connection',
            'database',
            'migrations',
            'tables',
            'toasts' => [
                ['variant', 'title', 'message', 'runtime'],
            ],
        ]);

        $this->assertIsString($response->json('connection'), 'connection');
        $this->assertIsString($response->json('database'), 'database');
        $this->assertSame('success', $response->json('toasts.0.variant'), 'toasts.0.variant');
        $this->assertSame('Rolled Back', $response->json('toasts.0.title'), 'toasts.0.title');
        $this->assertSame('2014_10_12_000000_create_users_table', $response->json('toasts.0.message'), 'toasts.0.message');
        $this->assertIsFloat($response->json('toasts.0.runtime'), 'toasts.0.runtime');

        $this->assertSame([
            [
                'name' => '2019_08_19_000000_create_failed_jobs_table',
                'date' => '2019-08-19 00:00:00',
                'title' => 'create failed jobs table',
                'batch' => null,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2019_08_19_000000_create_failed_jobs_table.php',
            ],
            [
                'name' => '2014_10_12_100000_create_password_resets_table',
                'date' => '2014-10-12 10:00:00',
                'title' => 'create password resets table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_100000_create_password_resets_table.php',
            ],
            [
                'name' => '2014_10_12_000000_create_users_table',
                'date' => '2014-10-12 00:00:00',
                'title' => 'create users table',
                'batch' => null,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_000000_create_users_table.php',
            ],
        ], $response->json('migrations'), 'migrations');

        $this->assertSame([
            ['name' => 'migrations', 'rows' => 1],
            ['name' => 'password_resets', 'rows' => 0],
        ], $response->json('tables'), 'tables');
    }

    public function testRollbackBatch()
    {
        // === Arrange ===
        $this->setMigrationPath(__DIR__ . '/migrations/three');
        $this->markAsRun('2014_10_12_000000_create_users_table', 1);
        $this->markAsRun('2014_10_12_100000_create_password_resets_table', 2);
        $this->markAsRun('2019_08_19_000000_create_failed_jobs_table', 2);

        $this->createTable('users');
        $this->createTable('password_resets');
        $this->createTable('failed_jobs');

        // === Act ===
        $response = $this->post('/migrations/api/rollback-batch/2');

        // === Assert ===
        $response->assertOk();

        $this->assertTableExists('users');
        $this->assertTableDoesntExist('password_resets');
        $this->assertTableDoesntExist('failed_jobs');

        $response->assertJsonStructure([
            'connection',
            'database',
            'migrations',
            'tables',
            'toasts' => [
                ['variant', 'title', 'message', 'runtime'],
            ],
        ]);

        $this->assertIsString($response->json('connection'), 'connection');
        $this->assertIsString($response->json('database'), 'database');
        $this->assertSame('success', $response->json('toasts.0.variant'), 'toasts.0.variant');
        $this->assertSame('Rolled Back', $response->json('toasts.0.title'), 'toasts.0.title');
        $this->assertSame('Rolled back 2 migrations.', $response->json('toasts.0.message'), 'toasts.0.message');
        $this->assertIsFloat($response->json('toasts.0.runtime'), 'toasts.0.runtime');

        $this->assertSame([
            [
                'name' => '2019_08_19_000000_create_failed_jobs_table',
                'date' => '2019-08-19 00:00:00',
                'title' => 'create failed jobs table',
                'batch' => null,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2019_08_19_000000_create_failed_jobs_table.php',
            ],
            [
                'name' => '2014_10_12_100000_create_password_resets_table',
                'date' => '2014-10-12 10:00:00',
                'title' => 'create password resets table',
                'batch' => null,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_100000_create_password_resets_table.php',
            ],
            [
                'name' => '2014_10_12_000000_create_users_table',
                'date' => '2014-10-12 00:00:00',
                'title' => 'create users table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_000000_create_users_table.php',
            ],
        ], $response->json('migrations'), 'migrations');

        $this->assertSame([
            ['name' => 'migrations', 'rows' => 1],
            ['name' => 'users', 'rows' => 0],
        ], $response->json('tables'), 'tables');
    }

    public function testRollbackAll()
    {
        // === Arrange ===
        $this->setMigrationPath(__DIR__ . '/migrations/three');
        $this->markAsRun('2014_10_12_000000_create_users_table', 1);
        $this->markAsRun('2014_10_12_100000_create_password_resets_table', 2);

        $this->createTable('users');
        $this->createTable('password_resets');

        // === Act ===
        $response = $this->post('/migrations/api/rollback-all');

        // === Assert ===
        $response->assertOk();

        $this->assertTableDoesntExist('users');
        $this->assertTableDoesntExist('password_resets');
        $this->assertTableDoesntExist('failed_jobs');

        $response->assertJsonStructure([
            'connection',
            'database',
            'migrations',
            'tables',
            'toasts' => [
                ['variant', 'title', 'message', 'runtime'],
            ],
        ]);

        $this->assertIsString($response->json('connection'), 'connection');
        $this->assertIsString($response->json('database'), 'database');
        $this->assertSame('success', $response->json('toasts.0.variant'), 'toasts.0.variant');
        $this->assertSame('Rolled Back', $response->json('toasts.0.title'), 'toasts.0.title');
        $this->assertSame('Rolled back 2 migrations.', $response->json('toasts.0.message'), 'toasts.0.message');
        $this->assertIsFloat($response->json('toasts.0.runtime'), 'toasts.0.runtime');

        $this->assertSame([
            [
                'name' => '2019_08_19_000000_create_failed_jobs_table',
                'date' => '2019-08-19 00:00:00',
                'title' => 'create failed jobs table',
                'batch' => null,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2019_08_19_000000_create_failed_jobs_table.php',
            ],
            [
                'name' => '2014_10_12_100000_create_password_resets_table',
                'date' => '2014-10-12 10:00:00',
                'title' => 'create password resets table',
                'batch' => null,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_100000_create_password_resets_table.php',
            ],
            [
                'name' => '2014_10_12_000000_create_users_table',
                'date' => '2014-10-12 00:00:00',
                'title' => 'create users table',
                'batch' => null,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_000000_create_users_table.php',
            ],
        ], $response->json('migrations'), 'migrations');

        $this->assertSame([
            ['name' => 'migrations', 'rows' => 0],
        ], $response->json('tables'), 'tables');
    }

    public function testFresh()
    {
        // === Arrange ===
        $this->setMigrationPath(__DIR__ . '/migrations/three');

        $this->createTable('dummy');

        // === Act ===
        $response = $this->post('/migrations/api/fresh');

        // === Assert ===
        $response->assertOk();

        $this->assertTableExists('users');
        $this->assertTableExists('password_resets');
        $this->assertTableExists('failed_jobs');
        $this->assertTableDoesntExist('dummy');

        $response->assertJsonStructure([
            'connection',
            'database',
            'migrations',
            'tables',
            'toasts' => [
                ['variant', 'title', 'message', 'runtime'],
            ],
        ]);

        $this->assertIsString($response->json('connection'), 'connection');
        $this->assertIsString($response->json('database'), 'database');
        $this->assertSame('success', $response->json('toasts.0.variant'), 'toasts.0.variant');
        $this->assertSame('Fresh', $response->json('toasts.0.title'), 'toasts.0.title');
        $this->assertSame("Dropped all tables.\nRan 3 migrations.", $response->json('toasts.0.message'), 'toasts.0.message');
        $this->assertIsFloat($response->json('toasts.0.runtime'), 'toasts.0.runtime');

        $this->assertSame([
            [
                'name' => '2019_08_19_000000_create_failed_jobs_table',
                'date' => '2019-08-19 00:00:00',
                'title' => 'create failed jobs table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2019_08_19_000000_create_failed_jobs_table.php',
            ],
            [
                'name' => '2014_10_12_100000_create_password_resets_table',
                'date' => '2014-10-12 10:00:00',
                'title' => 'create password resets table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_100000_create_password_resets_table.php',
            ],
            [
                'name' => '2014_10_12_000000_create_users_table',
                'date' => '2014-10-12 00:00:00',
                'title' => 'create users table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_000000_create_users_table.php',
            ],
        ], $response->json('migrations'), 'migrations');

        $this->assertSame([
            ['name' => 'failed_jobs', 'rows' => 0],
            ['name' => 'migrations', 'rows' => 3],
            ['name' => 'password_resets', 'rows' => 0],
            ['name' => 'users', 'rows' => 0],
        ], $response->json('tables'), 'tables');
    }

    public function testFreshAndSeed()
    {
        // === Arrange ===
        $this->setMigrationPath(__DIR__ . '/migrations/three');

        $this->createTable('dummy');

        config(['migrations-ui.seeder' => UsersTableSeeder::class]);

        // === Act ===
        $response = $this->postJson('/migrations/api/fresh', ['seed' => true]);

        // === Assert ===
        $response->assertOk();

        $this->assertTableExists('users');
        $this->assertTableExists('password_resets');
        $this->assertTableExists('failed_jobs');
        $this->assertTableDoesntExist('dummy');
        $this->assertDatabaseHas('users', ['email' => 'testuser1@example.com']);

        $response->assertJsonStructure([
            'connection',
            'database',
            'migrations',
            'tables',
            'toasts' => [
                ['variant', 'title', 'message', 'runtime'],
            ],
        ]);

        $this->assertIsString($response->json('connection'), 'connection');
        $this->assertIsString($response->json('database'), 'database');
        $this->assertSame('success', $response->json('toasts.0.variant'), 'toasts.0.variant');
        $this->assertSame('Fresh', $response->json('toasts.0.title'), 'toasts.0.title');
        $this->assertSame("Dropped all tables.\nRan 3 migrations.\nSeeded the database.", $response->json('toasts.0.message'), 'toasts.0.message');
        $this->assertIsFloat($response->json('toasts.0.runtime'), 'toasts.0.runtime');

        $this->assertSame([
            [
                'name' => '2019_08_19_000000_create_failed_jobs_table',
                'date' => '2019-08-19 00:00:00',
                'title' => 'create failed jobs table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2019_08_19_000000_create_failed_jobs_table.php',
            ],
            [
                'name' => '2014_10_12_100000_create_password_resets_table',
                'date' => '2014-10-12 10:00:00',
                'title' => 'create password resets table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_100000_create_password_resets_table.php',
            ],
            [
                'name' => '2014_10_12_000000_create_users_table',
                'date' => '2014-10-12 00:00:00',
                'title' => 'create users table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_000000_create_users_table.php',
            ],
        ], $response->json('migrations'), 'migrations');

        $this->assertSame([
            ['name' => 'failed_jobs', 'rows' => 0],
            ['name' => 'migrations', 'rows' => 3],
            ['name' => 'password_resets', 'rows' => 0],
            ['name' => 'users', 'rows' => 5],
        ], $response->json('tables'), 'tables');
    }

    public function testRefresh()
    {
        // === Arrange ===
        $this->setMigrationPath(__DIR__ . '/migrations/three');
        $this->markAsRun('2014_10_12_000000_create_users_table', 1);
        $this->markAsRun('2014_10_12_100000_create_password_resets_table', 2);

        $this->createTable('users');
        $this->createTable('password_resets');
        $this->createTable('dummy');

        // === Act ===
        $response = $this->post('/migrations/api/refresh');

        // === Assert ===
        $response->assertOk();

        $this->assertTableExists('users');
        $this->assertTableExists('password_resets');
        $this->assertTableExists('failed_jobs');
        $this->assertTableExists('dummy');

        $response->assertJsonStructure([
            'connection',
            'database',
            'migrations',
            'tables',
            'toasts' => [
                ['variant', 'title', 'message', 'runtime'],
            ],
        ]);

        $this->assertIsString($response->json('connection'), 'connection');
        $this->assertIsString($response->json('database'), 'database');
        $this->assertSame('success', $response->json('toasts.0.variant'), 'toasts.0.variant');
        $this->assertSame('Refresh', $response->json('toasts.0.title'), 'toasts.0.title');
        $this->assertSame("Rolled back 2 migrations.\nRan 3 migrations.", $response->json('toasts.0.message'), 'toasts.0.message');
        $this->assertIsFloat($response->json('toasts.0.runtime'), 'toasts.0.runtime');

        $this->assertSame([
            [
                'name' => '2019_08_19_000000_create_failed_jobs_table',
                'date' => '2019-08-19 00:00:00',
                'title' => 'create failed jobs table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2019_08_19_000000_create_failed_jobs_table.php',
            ],
            [
                'name' => '2014_10_12_100000_create_password_resets_table',
                'date' => '2014-10-12 10:00:00',
                'title' => 'create password resets table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_100000_create_password_resets_table.php',
            ],
            [
                'name' => '2014_10_12_000000_create_users_table',
                'date' => '2014-10-12 00:00:00',
                'title' => 'create users table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_000000_create_users_table.php',
            ],
        ], $response->json('migrations'), 'migrations');

        $this->assertSame([
            ['name' => 'dummy', 'rows' => 0],
            ['name' => 'failed_jobs', 'rows' => 0],
            ['name' => 'migrations', 'rows' => 3],
            ['name' => 'password_resets', 'rows' => 0],
            ['name' => 'users', 'rows' => 0],
        ], $response->json('tables'), 'tables');
    }

    public function testRefreshAndSeed()
    {
        // === Arrange ===
        $this->setMigrationPath(__DIR__ . '/migrations/three');
        $this->markAsRun('2014_10_12_000000_create_users_table', 1);
        $this->markAsRun('2014_10_12_100000_create_password_resets_table', 2);

        $this->createTable('users');
        $this->createTable('password_resets');
        $this->createTable('dummy');

        config(['migrations-ui.seeder' => UsersTableSeeder::class]);

        // === Act ===
        $response = $this->postJson('/migrations/api/refresh', ['seed' => true]);

        // === Assert ===
        $response->assertOk();

        $this->assertTableExists('users');
        $this->assertTableExists('password_resets');
        $this->assertTableExists('failed_jobs');
        $this->assertTableExists('dummy');
        $this->assertDatabaseHas('users', ['email' => 'testuser1@example.com']);

        $response->assertJsonStructure([
            'connection',
            'database',
            'migrations',
            'tables',
            'toasts' => [
                ['variant', 'title', 'message', 'runtime'],
            ],
        ]);

        $this->assertIsString($response->json('connection'), 'connection');
        $this->assertIsString($response->json('database'), 'database');
        $this->assertSame('success', $response->json('toasts.0.variant'), 'toasts.0.variant');
        $this->assertSame('Refresh', $response->json('toasts.0.title'), 'toasts.0.title');
        $this->assertSame("Rolled back 2 migrations.\nRan 3 migrations.\nSeeded the database.", $response->json('toasts.0.message'), 'toasts.0.message');
        $this->assertIsFloat($response->json('toasts.0.runtime'), 'toasts.0.runtime');

        $this->assertSame([
            [
                'name' => '2019_08_19_000000_create_failed_jobs_table',
                'date' => '2019-08-19 00:00:00',
                'title' => 'create failed jobs table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2019_08_19_000000_create_failed_jobs_table.php',
            ],
            [
                'name' => '2014_10_12_100000_create_password_resets_table',
                'date' => '2014-10-12 10:00:00',
                'title' => 'create password resets table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_100000_create_password_resets_table.php',
            ],
            [
                'name' => '2014_10_12_000000_create_users_table',
                'date' => '2014-10-12 00:00:00',
                'title' => 'create users table',
                'batch' => 1,
                // Absolute path because it's outside the project root
                'relPath' => __DIR__ . '/migrations/three/2014_10_12_000000_create_users_table.php',
            ],
        ], $response->json('migrations'), 'migrations');

        $this->assertSame([
            ['name' => 'dummy', 'rows' => 0],
            ['name' => 'failed_jobs', 'rows' => 0],
            ['name' => 'migrations', 'rows' => 3],
            ['name' => 'password_resets', 'rows' => 0],
            ['name' => 'users', 'rows' => 5],
        ], $response->json('tables'), 'tables');
    }

    public function testSeed()
    {
        // === Arrange ===
        Schema::create('users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
        });

        config(['migrations-ui.seeder' => UsersTableSeeder::class]);

        // === Act ===
        $response = $this->postJson('/migrations/api/seed');

        // === Assert ===
        $response->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'testuser1@example.com']);

        $response->assertJsonStructure([
            'connection',
            'database',
            'migrations',
            'tables',
            'toasts' => [
                ['variant', 'title', 'message', 'runtime'],
            ],
        ]);

        $this->assertIsString($response->json('connection'), 'connection');
        $this->assertIsString($response->json('database'), 'database');
        $this->assertSame('success', $response->json('toasts.0.variant'), 'toasts.0.variant');
        $this->assertSame('Seed', $response->json('toasts.0.title'), 'toasts.0.title');
        $this->assertSame('Database seeded', $response->json('toasts.0.message'), 'toasts.0.message');
        $this->assertIsFloat($response->json('toasts.0.runtime'), 'toasts.0.runtime');

        $this->assertSame([], $response->json('migrations'), 'migrations');

        $this->assertSame([
            ['name' => 'migrations', 'rows' => 0],
            ['name' => 'users', 'rows' => 5],
        ], $response->json('tables'), 'tables');
    }
}