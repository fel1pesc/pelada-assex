<?php

declare(strict_types=1);

final class PlayerRepository
{
    public function __construct(
        private readonly Database $database,
    ) {
    }

    /** @return list<array{id: string, nome: string, mensalista: bool, gols: int, assistencias: int, vitorias: int}> */
    public function all(?bool $mensalista = null): array
    {
        $players = $this->database->read()['players'] ?? [];

        if (!is_array($players)) {
            return [];
        }

        $normalized = [];

        foreach ($players as $player) {
            if (!is_array($player)) {
                continue;
            }

            $normalized[] = $this->normalize($player);
        }

        if ($mensalista !== null) {
            $normalized = array_values(array_filter(
                $normalized,
                static fn (array $player): bool => $player['mensalista'] === $mensalista
            ));
        }

        usort(
            $normalized,
            static fn (array $a, array $b): int => strcasecmp($a['nome'], $b['nome'])
        );

        return $normalized;
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $player) {
            if ($player['id'] === $id) {
                return $player;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $input */
    public function save(array $input, ?string $id = null): array
    {
        $player = $this->normalize([
            'id' => $id ?: bin2hex(random_bytes(8)),
            'nome' => $input['nome'] ?? '',
            'mensalista' => $input['mensalista'] ?? false,
            'gols' => $input['gols'] ?? 0,
            'assistencias' => $input['assistencias'] ?? 0,
            'vitorias' => $input['vitorias'] ?? 0,
        ]);

        $this->assertValid($player);

        $data = $this->database->read();
        $players = is_array($data['players'] ?? null) ? $data['players'] : [];
        $updated = false;

        foreach ($players as $index => $existing) {
            if (($existing['id'] ?? null) === $player['id']) {
                $players[$index] = $player;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $players[] = $player;
        }

        $data['players'] = $players;
        $this->database->write($data);

        return $player;
    }

    public function delete(string $id): bool
    {
        $data = $this->database->read();
        $players = is_array($data['players'] ?? null) ? $data['players'] : [];
        $filtered = array_values(array_filter(
            $players,
            static fn (mixed $player): bool => !is_array($player) || ($player['id'] ?? null) !== $id
        ));

        if (count($filtered) === count($players)) {
            return false;
        }

        $data['players'] = $filtered;
        $this->database->write($data);

        return true;
    }

    /**
     * @param 'gols'|'assistencias'|'vitorias' $stat
     * @return list<array{id: string, nome: string, mensalista: bool, gols: int, assistencias: int, vitorias: int}>
     */
    public function ranking(string $stat, ?bool $mensalista = null): array
    {
        $list = $this->all($mensalista);

        usort($list, static function (array $a, array $b) use ($stat): int {
            $diff = $b[$stat] <=> $a[$stat];

            if ($diff !== 0) {
                return $diff;
            }

            return strcasecmp($a['nome'], $b['nome']);
        });

        return $list;
    }

    /** @param array<string, mixed> $player */
    private function normalize(array $player): array
    {
        return [
            'id' => (string) ($player['id'] ?? ''),
            'nome' => trim((string) ($player['nome'] ?? '')),
            'mensalista' => filter_var($player['mensalista'] ?? false, FILTER_VALIDATE_BOOL),
            'gols' => max(0, (int) ($player['gols'] ?? 0)),
            'assistencias' => max(0, (int) ($player['assistencias'] ?? 0)),
            'vitorias' => max(0, (int) ($player['vitorias'] ?? 0)),
        ];
    }

    /** @param array{nome: string} $player */
    private function assertValid(array $player): void
    {
        if ($player['nome'] === '') {
            throw new InvalidArgumentException('Informe o nome do jogador.');
        }

        if (mb_strlen($player['nome']) > 80) {
            throw new InvalidArgumentException('O nome deve ter no máximo 80 caracteres.');
        }
    }
}
