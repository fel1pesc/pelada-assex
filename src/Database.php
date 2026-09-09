<?php

declare(strict_types=1);

final class Database
{
    public function __construct(
        private readonly string $path,
    ) {
        $this->ensureFile();
    }

    public function read(): array
    {
        $handle = fopen($this->path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Não foi possível ler o arquivo de dados.');
        }

        try {
            flock($handle, LOCK_SH);
            $contents = stream_get_contents($handle);
            $contents = preg_replace('/^\xEF\xBB\xBF/', '', (string) $contents);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $data = json_decode((string) $contents, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            return ['players' => [], 'peladas' => []];
        }

        $data['players'] ??= [];
        $data['peladas'] ??= [];

        return $data;
    }

    public function write(array $data): void
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ) . PHP_EOL;

        $handle = fopen($this->path, 'c+b');

        if ($handle === false) {
            throw new RuntimeException('Não foi possível gravar o arquivo de dados.');
        }

        try {
            flock($handle, LOCK_EX);
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $json);
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function ensureFile(): void
    {
        if (is_file($this->path)) {
            return;
        }

        $created = file_put_contents(
            $this->path,
            json_encode(['players' => [], 'peladas' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL,
            LOCK_EX
        );

        if ($created === false) {
            throw new RuntimeException('Não foi possível criar o arquivo de dados.');
        }
    }
}
