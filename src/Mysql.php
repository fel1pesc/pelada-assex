<?php

declare(strict_types=1);

final class Mysql
{
    private static ?PDO $pdo = null;
    private static bool $schemaReady = false;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            if (!self::$schemaReady) {
                self::ensureSchema(self::$pdo);
            }

            return self::$pdo;
        }

        $host = self::env('DB_HOST', 'mysql8');
        $port = self::env('DB_PORT', '3306');
        $name = self::env('DB_NAME', 'assex');
        $user = self::env('DB_USER', 'root');
        $pass = self::env('DB_PASS', 'ar7711');

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if (self::env('DB_SSL', '') === '1') {
            $ca = self::env('DB_SSL_CA', '');

            if ($ca === '') {
                foreach ([
                    '/etc/ssl/certs/ca-certificates.crt',
                    '/etc/pki/tls/certs/ca-bundle.crt',
                    'C:\\Program Files\\Git\\mingw64\\ssl\\certs\\ca-bundle.crt',
                ] as $candidate) {
                    if (is_file($candidate)) {
                        $ca = $candidate;
                        break;
                    }
                }
            }

            if ($ca !== '') {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            } else {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        try {
            self::$pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
                $user,
                $pass,
                $options
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Não foi possível conectar ao MySQL. Confira DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASS.',
                0,
                $exception
            );
        }

        self::ensureSchema(self::$pdo);

        return self::$pdo;
    }

    public static function env(string $key, string $default): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    private static function ensureSchema(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `nome` VARCHAR(80) NOT NULL,
                `email` VARCHAR(191) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `admin` TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_users_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS `app_state` (
                `id` TINYINT UNSIGNED NOT NULL,
                `payload` LONGTEXT NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS `sessions` (
                `id` VARCHAR(128) NOT NULL,
                `data` MEDIUMTEXT NOT NULL,
                `expires_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_sessions_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
        self::seedAppStateIfEmpty($pdo);
        self::bootstrapAdminIfEmpty($pdo);
    }

    private static function seedAppStateIfEmpty(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM app_state WHERE id = 1')->fetchColumn();

        if ($count > 0) {
            return;
        }

        $path = dirname(__DIR__) . '/database.json';
        $payload = json_encode(['players' => [], 'peladas' => []], JSON_UNESCAPED_UNICODE);

        if (is_file($path)) {
            $contents = file_get_contents($path);
            $decoded = json_decode((string) $contents, true);

            if (is_array($decoded)) {
                $payload = json_encode([
                    'players' => $decoded['players'] ?? [],
                    'peladas' => $decoded['peladas'] ?? [],
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            }
        }

        $stmt = $pdo->prepare('INSERT INTO app_state (id, payload) VALUES (1, :payload)');
        $stmt->execute(['payload' => $payload]);
    }

    private static function bootstrapAdminIfEmpty(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        if ($count > 0) {
            return;
        }

        $email = strtolower(trim(self::env('BOOTSTRAP_ADMIN_EMAIL', '')));
        $password = self::env('BOOTSTRAP_ADMIN_PASSWORD', '');
        $nome = trim(self::env('BOOTSTRAP_ADMIN_NAME', 'Admin'));

        if ($email === '' || $password === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($hash === false) {
            throw new RuntimeException('Não foi possível criar o usuário administrador inicial.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO users (nome, email, password, admin)
             VALUES (:nome, :email, :password, 1)'
        );
        $stmt->execute([
            'nome' => $nome !== '' ? $nome : 'Admin',
            'email' => $email,
            'password' => $hash,
        ]);
    }
}
