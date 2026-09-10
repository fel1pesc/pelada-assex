<?php

declare(strict_types=1);

final class PeladaRepository
{
    public function __construct(
        private readonly Database $database,
    ) {
    }

    /** @return list<array{id: string, data: string, observacao: string, partidas: list<array<string, mixed>>}> */
    public function all(): array
    {
        $peladas = $this->database->read()['peladas'] ?? [];

        if (!is_array($peladas)) {
            return [];
        }

        $normalized = [];

        foreach ($peladas as $pelada) {
            if (!is_array($pelada)) {
                continue;
            }

            $normalized[] = $this->normalizePelada($pelada);
        }

        usort(
            $normalized,
            static fn (array $a, array $b): int => strcmp($b['data'], $a['data']) ?: strcmp($b['id'], $a['id'])
        );

        return $normalized;
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $pelada) {
            if ($pelada['id'] === $id) {
                return $pelada;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $input */
    public function save(array $input, ?string $id = null): array
    {
        $pelada = $this->normalizePelada([
            'id' => $id ?: bin2hex(random_bytes(8)),
            'data' => $input['data'] ?? '',
            'observacao' => $input['observacao'] ?? '',
            'partidas' => $input['partidas'] ?? [],
        ]);

        $this->assertValidPelada($pelada);

        $data = $this->database->read();
        $peladas = is_array($data['peladas'] ?? null) ? $data['peladas'] : [];
        $updated = false;

        foreach ($peladas as $index => $existing) {
            if (($existing['id'] ?? null) === $pelada['id']) {
                $pelada['partidas'] = $this->normalizePelada($existing)['partidas'];
                $peladas[$index] = $pelada;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $peladas[] = $pelada;
        }

        $data['peladas'] = $peladas;
        $this->database->write($data);

        return $pelada;
    }

    public function delete(string $id): bool
    {
        $data = $this->database->read();
        $peladas = is_array($data['peladas'] ?? null) ? $data['peladas'] : [];
        $players = is_array($data['players'] ?? null) ? $data['players'] : [];
        $remaining = [];
        $found = false;

        foreach ($peladas as $pelada) {
            if (!is_array($pelada) || ($pelada['id'] ?? null) !== $id) {
                $remaining[] = $pelada;
                continue;
            }

            $found = true;
            $normalized = $this->normalizePelada($pelada);

            foreach ($normalized['partidas'] as $partida) {
                $this->applyMatchDeltas($players, $partida, -1);
            }
        }

        if (!$found) {
            return false;
        }

        $data['players'] = $players;
        $data['peladas'] = $remaining;
        $this->database->write($data);

        return true;
    }

    public function findPartida(string $peladaId, string $partidaId): ?array
    {
        $pelada = $this->find($peladaId);

        if ($pelada === null) {
            return null;
        }

        foreach ($pelada['partidas'] as $partida) {
            if ($partida['id'] === $partidaId) {
                return $partida;
            }
        }

        return null;
    }

    /**
     * @param array{partidas: list<array<string, mixed>>} $pelada
     * @param array<string, array{id?: string, nome?: string}> $playersById
     * @return array{
     *     gols: list<array{id: string, nome: string, gols: int, vitorias: int}>,
     *     vitorias: list<array{id: string, nome: string, gols: int, vitorias: int}>
     * }
     */
    public function ranking(array $pelada, array $playersById): array
    {
        $totais = [];

        foreach ($pelada['partidas'] as $partida) {
            if (!is_array($partida)) {
                continue;
            }

            foreach (['time_a', 'time_b'] as $teamKey) {
                $team = is_array($partida[$teamKey] ?? null) ? $partida[$teamKey] : [];
                $gols = is_array($team['gols'] ?? null) ? $team['gols'] : [];
                $won = ($partida['vencedor'] === 'a' && $teamKey === 'time_a')
                    || ($partida['vencedor'] === 'b' && $teamKey === 'time_b');

                $ids = [];

                foreach ([...($team['jogadores'] ?? []), $team['goleiro'] ?? null] as $playerId) {
                    if ($playerId === null || $playerId === '') {
                        continue;
                    }

                    $ids[(string) $playerId] = true;
                }

                foreach (array_keys($ids) as $playerId) {
                    $nome = trim((string) ($playersById[$playerId]['nome'] ?? 'Jogador removido'));

                    if ($nome === '') {
                        $nome = 'Jogador removido';
                    }

                    if (mb_stripos($nome, 'AVULSO') !== false) {
                        continue;
                    }

                    if (!isset($totais[$playerId])) {
                        $totais[$playerId] = [
                            'id' => $playerId,
                            'nome' => $nome,
                            'gols' => 0,
                            'vitorias' => 0,
                        ];
                    }

                    $totais[$playerId]['gols'] += max(0, (int) ($gols[$playerId] ?? 0));

                    if ($won) {
                        $totais[$playerId]['vitorias']++;
                    }
                }
            }
        }

        $list = array_values($totais);

        return [
            'gols' => $this->sortRanking($list, 'gols'),
            'vitorias' => $this->sortRanking($list, 'vitorias'),
        ];
    }

    /**
     * @param list<array{id: string, nome: string, gols: int, vitorias: int}> $list
     * @param 'gols'|'vitorias' $stat
     * @return list<array{id: string, nome: string, gols: int, vitorias: int}>
     */
    private function sortRanking(array $list, string $stat): array
    {
        usort($list, static function (array $a, array $b) use ($stat): int {
            $diff = $b[$stat] <=> $a[$stat];

            if ($diff !== 0) {
                return $diff;
            }

            return strcasecmp($a['nome'], $b['nome']);
        });

        return $list;
    }

    /** @param array<string, mixed> $input */
    public function savePartida(string $peladaId, array $input, ?string $partidaId = null): array
    {
        $data = $this->database->read();
        $peladas = is_array($data['peladas'] ?? null) ? $data['peladas'] : [];
        $players = is_array($data['players'] ?? null) ? $data['players'] : [];

        $peladaIndex = null;

        foreach ($peladas as $index => $existing) {
            if (is_array($existing) && ($existing['id'] ?? null) === $peladaId) {
                $peladaIndex = $index;
                break;
            }
        }

        if ($peladaIndex === null) {
            throw new InvalidArgumentException('Pelada não encontrada.');
        }

        $timeA = $this->resolveTeamInput(is_array($input['time_a'] ?? null) ? $input['time_a'] : [], $players, 'Time A');
        $timeB = $this->resolveTeamInput(is_array($input['time_b'] ?? null) ? $input['time_b'] : [], $players, 'Time B');
        $playerIds = $this->playerIdMap($players);

        $pelada = $this->normalizePelada($peladas[$peladaIndex]);
        $partida = $this->normalizePartida([
            'id' => $partidaId ?: bin2hex(random_bytes(8)),
            'time_a' => $timeA,
            'time_b' => $timeB,
            'vencedor' => $input['vencedor'] ?? null,
            'observacao' => $input['observacao'] ?? '',
        ], $playerIds);

        $this->assertValidPartida($partida, $playerIds);

        $oldPartida = null;
        $partidaIndex = null;

        foreach ($pelada['partidas'] as $index => $existingPartida) {
            if ($existingPartida['id'] === $partida['id']) {
                $oldPartida = $existingPartida;
                $partidaIndex = $index;
                break;
            }
        }

        if ($oldPartida !== null) {
            $this->applyMatchDeltas($players, $oldPartida, -1);
        }

        $this->applyMatchDeltas($players, $partida, 1);

        if ($partidaIndex !== null) {
            $pelada['partidas'][$partidaIndex] = $partida;
        } else {
            $pelada['partidas'][] = $partida;
        }

        $peladas[$peladaIndex] = $pelada;
        $data['players'] = $players;
        $data['peladas'] = $peladas;
        $this->database->write($data);

        return $partida;
    }

    public function deletePartida(string $peladaId, string $partidaId): bool
    {
        $data = $this->database->read();
        $peladas = is_array($data['peladas'] ?? null) ? $data['peladas'] : [];
        $players = is_array($data['players'] ?? null) ? $data['players'] : [];
        $peladaIndex = null;

        foreach ($peladas as $index => $existing) {
            if (is_array($existing) && ($existing['id'] ?? null) === $peladaId) {
                $peladaIndex = $index;
                break;
            }
        }

        if ($peladaIndex === null) {
            return false;
        }

        $pelada = $this->normalizePelada($peladas[$peladaIndex]);
        $remaining = [];
        $found = false;

        foreach ($pelada['partidas'] as $partida) {
            if ($partida['id'] !== $partidaId) {
                $remaining[] = $partida;
                continue;
            }

            $found = true;
            $this->applyMatchDeltas($players, $partida, -1);
        }

        if (!$found) {
            return false;
        }

        $pelada['partidas'] = $remaining;
        $peladas[$peladaIndex] = $pelada;
        $data['players'] = $players;
        $data['peladas'] = $peladas;
        $this->database->write($data);

        return true;
    }

    /**
     * @param array<string, mixed> $teamInput
     * @param list<mixed> $players
     * @return array{jogadores: list<string>, goleiro: ?string, gols: array<string, int>}
     */
    private function resolveTeamInput(array $teamInput, array &$players, string $label): array
    {
        $modos = is_array($teamInput['modos'] ?? null) ? $teamInput['modos'] : [];
        $ids = is_array($teamInput['jogadores'] ?? null) ? $teamInput['jogadores'] : [];
        $diaristas = is_array($teamInput['diaristas'] ?? null) ? $teamInput['diaristas'] : [];
        $golsLinha = is_array($teamInput['gols_linha'] ?? null) ? $teamInput['gols_linha'] : [];
        $jogadores = [];
        $gols = [];

        for ($i = 0; $i < 5; $i++) {
            $modo = (($modos[$i] ?? '') === 'diarista') ? 'diarista' : 'cadastro';

            if ($modo === 'diarista') {
                $nome = trim((string) ($diaristas[$i] ?? ''));

                if ($nome === '') {
                    throw new InvalidArgumentException("Informe o nome do diarista no {$label} (jogador " . ($i + 1) . ').');
                }

                $playerId = $this->findOrCreateDiarista($players, $nome);
            } else {
                $playerId = trim((string) ($ids[$i] ?? ''));
            }

            $jogadores[] = $playerId;

            if ($playerId !== '') {
                $gols[$playerId] = max(0, (int) ($golsLinha[$i] ?? 0));
            }
        }

        $goleiroModo = (($teamInput['goleiro_modo'] ?? '') === 'diarista') ? 'diarista' : 'cadastro';
        $goleiro = null;

        if ($goleiroModo === 'diarista') {
            $nome = trim((string) ($teamInput['goleiro_diarista'] ?? ''));

            if ($nome === '') {
                throw new InvalidArgumentException("Informe o nome do goleiro diarista no {$label}.");
            }

            $goleiro = $this->findOrCreateDiarista($players, $nome);
            $gols[$goleiro] = max(0, (int) ($teamInput['gols_goleiro'] ?? 0));
        } else {
            $goleiroId = trim((string) ($teamInput['goleiro'] ?? ''));

            if ($goleiroId !== '') {
                $goleiro = $goleiroId;
                $gols[$goleiro] = max(0, (int) ($teamInput['gols_goleiro'] ?? 0));
            }
        }

        return [
            'jogadores' => $jogadores,
            'goleiro' => $goleiro,
            'gols' => $gols,
        ];
    }

    /** @param list<mixed> $players */
    private function findOrCreateDiarista(array &$players, string $nome): string
    {
        $needle = mb_strtolower($nome);

        foreach ($players as $player) {
            if (!is_array($player)) {
                continue;
            }

            if (mb_strtolower(trim((string) ($player['nome'] ?? ''))) === $needle) {
                return (string) $player['id'];
            }
        }

        $id = bin2hex(random_bytes(8));
        $players[] = [
            'id' => $id,
            'nome' => $nome,
            'mensalista' => false,
            'gols' => 0,
            'assistencias' => 0,
            'vitorias' => 0,
        ];

        return $id;
    }

    /** @param array<string, mixed> $pelada */
    private function normalizePelada(array $pelada): array
    {
        $partidas = [];

        if (is_array($pelada['partidas'] ?? null)) {
            foreach ($pelada['partidas'] as $partida) {
                if (!is_array($partida)) {
                    continue;
                }

                $partidas[] = $this->normalizePartida($partida);
            }
        }

        return [
            'id' => (string) ($pelada['id'] ?? ''),
            'data' => (string) ($pelada['data'] ?? ''),
            'observacao' => $this->normalizeObservacao($pelada['observacao'] ?? ''),
            'partidas' => $partidas,
        ];
    }

    /**
     * @param array<string, mixed> $partida
     * @param array<string, true>|null $playerIds
     */
    private function normalizePartida(array $partida, ?array $playerIds = null): array
    {
        $timeA = $this->normalizeTeam($partida['time_a'] ?? [], $playerIds);
        $timeB = $this->normalizeTeam($partida['time_b'] ?? [], $playerIds);
        $golsA = $this->teamGoals($timeA);
        $golsB = $this->teamGoals($timeB);

        if ($golsA > $golsB) {
            $vencedor = 'a';
        } elseif ($golsB > $golsA) {
            $vencedor = 'b';
        } else {
            $manual = (string) ($partida['vencedor'] ?? 'empate');
            $vencedor = in_array($manual, ['a', 'b', 'empate'], true) ? $manual : 'empate';
        }

        return [
            'id' => (string) ($partida['id'] ?? ''),
            'time_a' => $timeA,
            'time_b' => $timeB,
            'placar_a' => $golsA,
            'placar_b' => $golsB,
            'vencedor' => $vencedor,
            'observacao' => $this->normalizeObservacao($partida['observacao'] ?? ''),
        ];
    }

    /**
     * @param mixed $team
     * @param array<string, true>|null $playerIds
     * @return array{jogadores: list<string>, goleiro: ?string, gols: array<string, int>}
     */
    private function normalizeTeam(mixed $team, ?array $playerIds = null): array
    {
        if (!is_array($team)) {
            return ['jogadores' => ['', '', '', '', ''], 'goleiro' => null, 'gols' => []];
        }

        $jogadores = [];

        for ($i = 0; $i < 5; $i++) {
            $id = trim((string) (($team['jogadores'][$i] ?? '') ?: ''));
            $jogadores[] = $id;
        }

        $goleiro = trim((string) ($team['goleiro'] ?? ''));
        $goleiro = $goleiro === '' ? null : $goleiro;

        $rawGoals = is_array($team['gols'] ?? null) ? $team['gols'] : [];
        $gols = [];

        foreach ([...$jogadores, $goleiro] as $playerId) {
            if ($playerId === null || $playerId === '') {
                continue;
            }

            if ($playerIds !== null && !isset($playerIds[$playerId])) {
                continue;
            }

            $gols[$playerId] = max(0, (int) ($rawGoals[$playerId] ?? 0));
        }

        return [
            'jogadores' => $jogadores,
            'goleiro' => $goleiro,
            'gols' => $gols,
        ];
    }

    /** @param array{jogadores: list<string>, goleiro: ?string, gols: array<string, int>} $team */
    private function teamGoals(array $team): int
    {
        return array_sum($team['gols']);
    }

    /** @param array{data: string, observacao: string} $pelada */
    private function assertValidPelada(array $pelada): void
    {
        if ($pelada['data'] === '') {
            throw new InvalidArgumentException('Informe a data da pelada.');
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $pelada['data']);

        if ($date === false || $date->format('Y-m-d') !== $pelada['data']) {
            throw new InvalidArgumentException('Data da pelada inválida.');
        }

        $this->assertValidObservacao($pelada['observacao']);
    }

    /**
     * @param array{time_a: array, time_b: array, observacao: string} $partida
     * @param array<string, true> $playerIds
     */
    private function assertValidPartida(array $partida, array $playerIds): void
    {
        $this->assertValidObservacao($partida['observacao']);

        $ids = [];

        foreach (['time_a' => 'Time A', 'time_b' => 'Time B'] as $key => $label) {
            $team = $partida[$key];

            foreach ($team['jogadores'] as $playerId) {
                if ($playerId === '') {
                    throw new InvalidArgumentException("Selecione os 5 jogadores do {$label}.");
                }

                if (!isset($playerIds[$playerId])) {
                    throw new InvalidArgumentException("Jogador inválido no {$label}.");
                }

                if (isset($ids[$playerId])) {
                    throw new InvalidArgumentException('O mesmo jogador não pode estar em mais de uma posição na partida.');
                }

                $ids[$playerId] = true;
            }

            $goleiro = $team['goleiro'];

            if ($goleiro !== null) {
                if (!isset($playerIds[$goleiro])) {
                    throw new InvalidArgumentException("Goleiro inválido no {$label}.");
                }

                if (isset($ids[$goleiro])) {
                    throw new InvalidArgumentException('O goleiro não pode ser um jogador de linha da partida.');
                }

                $ids[$goleiro] = true;
            }
        }
    }

    private function normalizeObservacao(mixed $value): string
    {
        return trim((string) $value);
    }

    private function assertValidObservacao(string $observacao): void
    {
        if (mb_strlen($observacao) > 500) {
            throw new InvalidArgumentException('A observação deve ter no máximo 500 caracteres.');
        }
    }

    /** @param list<mixed> $players @return array<string, true> */
    private function playerIdMap(array $players): array
    {
        $map = [];

        foreach ($players as $player) {
            if (!is_array($player)) {
                continue;
            }

            $id = (string) ($player['id'] ?? '');

            if ($id !== '') {
                $map[$id] = true;
            }
        }

        return $map;
    }

    /**
     * @param list<mixed> $players
     * @param array{time_a: array, time_b: array, vencedor: string} $partida
     */
    private function applyMatchDeltas(array &$players, array $partida, int $direction): void
    {
        $goalDeltas = [];
        $winDeltas = [];

        foreach (['time_a', 'time_b'] as $teamKey) {
            $team = $partida[$teamKey];

            foreach ($team['gols'] as $playerId => $goals) {
                $goalDeltas[$playerId] = ($goalDeltas[$playerId] ?? 0) + ((int) $goals * $direction);
            }

            $won = ($partida['vencedor'] === 'a' && $teamKey === 'time_a')
                || ($partida['vencedor'] === 'b' && $teamKey === 'time_b');

            if (!$won) {
                continue;
            }

            foreach ([...$team['jogadores'], $team['goleiro']] as $playerId) {
                if ($playerId === null || $playerId === '') {
                    continue;
                }

                $winDeltas[$playerId] = ($winDeltas[$playerId] ?? 0) + $direction;
            }
        }

        foreach ($players as $index => $player) {
            if (!is_array($player)) {
                continue;
            }

            $id = (string) ($player['id'] ?? '');

            if ($id === '') {
                continue;
            }

            if (isset($goalDeltas[$id])) {
                $players[$index]['gols'] = max(0, (int) ($player['gols'] ?? 0) + $goalDeltas[$id]);
            }

            if (isset($winDeltas[$id])) {
                $players[$index]['vitorias'] = max(0, (int) ($player['vitorias'] ?? 0) + $winDeltas[$id]);
            }
        }
    }
}
