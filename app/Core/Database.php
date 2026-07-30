<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

class Database
{
    private static ?Capsule $capsule = null;

    public static function connection(): Connection
    {
        return self::capsule()->getConnection();
    }

    public static function connect(): PDO
    {
        return self::connection()->getPdo();
    }

    public static function table(string $table): Builder
    {
        return self::connection()->table($table);
    }

    public static function rows(Builder $query): array
    {
        return array_map(
            static fn (object $row): array => (array) $row,
            $query->get()->all()
        );
    }

    public static function row(Builder $query): ?array
    {
        $row = $query->first();

        return $row === null ? null : (array) $row;
    }

    /**
     * Query Builder and legacy PDO repositories share the same connection, so
     * one transaction covers both styles during the incremental migration.
     * Nested operations keep the existing join semantics instead of creating
     * savepoints, as expected by API batches and import rows.
     */
    public static function transaction(callable $callback): mixed
    {
        $connection = self::connection();

        if ($connection->getPdo()->inTransaction()) {
            return $callback();
        }

        return $connection->transaction(static fn () => $callback());
    }

    public static function isIntegrityViolation(Throwable $exception): bool
    {
        return $exception instanceof PDOException
            && (string) $exception->getCode() === '23000';
    }

    public static function violatesConstraint(PDOException $exception, string $constraint): bool
    {
        return self::isIntegrityViolation($exception)
            && str_contains(strtolower($exception->getMessage()), strtolower($constraint));
    }

    private static function capsule(): Capsule
    {
        if (self::$capsule instanceof Capsule) {
            return self::$capsule;
        }

        $config = require __DIR__ . '/../../config/database.php';
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $config['host'],
            'port' => $config['port'] ?? 3306,
            'database' => $config['database'],
            'username' => $config['user'],
            'password' => $config['password'],
            'charset' => $config['charset'],
            'collation' => $config['collation'] ?? 'utf8mb4_unicode_ci',
            'prefix' => '',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES => true,
            ],
        ]);

        return self::$capsule = $capsule;
    }
}
