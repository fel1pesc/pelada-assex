<?php

declare(strict_types=1);

final class DbSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = Mysql::pdo()->prepare(
            'SELECT data FROM sessions WHERE id = :id AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return '';
        }

        return (string) $row['data'];
    }

    public function write(string $id, string $data): bool
    {
        $lifetime = (int) ini_get('session.gc_maxlifetime');

        if ($lifetime <= 0) {
            $lifetime = 1440;
        }

        $lifetime = max(60, $lifetime);
        $stmt = Mysql::pdo()->prepare(
            "INSERT INTO sessions (id, data, expires_at)
             VALUES (:id, :data, DATE_ADD(NOW(), INTERVAL {$lifetime} SECOND))
             ON DUPLICATE KEY UPDATE
                data = :data_update,
                expires_at = DATE_ADD(NOW(), INTERVAL {$lifetime} SECOND)"
        );

        return $stmt->execute([
            'id' => $id,
            'data' => $data,
            'data_update' => $data,
        ]);
    }

    public function destroy(string $id): bool
    {
        $stmt = Mysql::pdo()->prepare('DELETE FROM sessions WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $deleted = Mysql::pdo()->exec('DELETE FROM sessions WHERE expires_at <= NOW()');

        return is_int($deleted) ? $deleted : 0;
    }
}
