<?php

declare(strict_types=1);

final class Database
{
    public function read(): array
    {
        $stmt = Mysql::pdo()->query('SELECT payload FROM app_state WHERE id = 1');
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return ['players' => [], 'peladas' => []];
        }

        $data = json_decode((string) ($row['payload'] ?? ''), true);

        if (!is_array($data)) {
            return ['players' => [], 'peladas' => []];
        }

        $data['players'] ??= [];
        $data['peladas'] ??= [];

        return $data;
    }

    public function write(array $data): void
    {
        $data['players'] ??= [];
        $data['peladas'] ??= [];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $pdo = Mysql::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO app_state (id, payload) VALUES (1, :payload)
             ON DUPLICATE KEY UPDATE payload = :payload_update'
        );
        $stmt->execute([
            'payload' => $json,
            'payload_update' => $json,
        ]);
    }
}
