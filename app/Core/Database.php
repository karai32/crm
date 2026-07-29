<?php

class Database
{
    public static function connect(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $config = require __DIR__ . '/../../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['database'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES  => true,
        ]);

        return $pdo;
    }

    /**
     * Executes a unit of work in a transaction. When the caller already owns a
     * transaction (API batch or import row), the callback joins it instead of
     * trying to start a nested PDO transaction.
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connect();
        $ownsTransaction = !$pdo->inTransaction();

        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback();

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
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
}
