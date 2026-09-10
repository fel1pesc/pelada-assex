<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

$acao = (string) ($_GET['acao'] ?? 'lista');
$id = (string) ($_GET['id'] ?? '');
$partidaId = (string) ($_GET['partida'] ?? '');
$acao = is_string($acao) ? $acao : 'lista';

if (in_array($acao, ['novo', 'editar', 'partida'], true)) {
    require_admin('peladas.php');
}
$pelada = [
    'id' => '',
    'data' => date('Y-m-d'),
    'observacao' => '',
    'partidas' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin('peladas.php');
    csrf_verify();

    $postAcao = (string) ($_POST['acao'] ?? '');
    $postId = (string) ($_POST['id'] ?? '');
    $postPartidaId = (string) ($_POST['partida_id'] ?? '');
    $postPeladaId = (string) ($_POST['pelada_id'] ?? $postId);

    try {
        if ($postAcao === 'excluir') {
            if (!$peladas->delete($postId)) {
                throw new InvalidArgumentException('Pelada não encontrada.');
            }

            flash('success', 'Pelada excluída.');
            redirect('peladas.php');
        }

        if ($postAcao === 'excluir_partida') {
            if (!$peladas->deletePartida($postPeladaId, $postPartidaId)) {
                throw new InvalidArgumentException('Partida não encontrada.');
            }

            flash('success', 'Partida excluída. Gols e vitórias foram revertidos.');
            redirect('peladas.php?acao=editar&id=' . urlencode($postPeladaId));
        }

        if ($postAcao === 'salvar_partida') {
            $peladas->savePartida($postPeladaId, [
                'vencedor' => $_POST['vencedor'] ?? 'empate',
                'observacao' => $_POST['observacao'] ?? '',
                'time_a' => [
                    'modos' => $_POST['time_a']['modos'] ?? [],
                    'jogadores' => $_POST['time_a']['jogadores'] ?? [],
                    'diaristas' => $_POST['time_a']['diaristas'] ?? [],
                    'gols_linha' => $_POST['time_a']['gols_linha'] ?? [],
                    'goleiro_modo' => $_POST['time_a']['goleiro_modo'] ?? 'cadastro',
                    'goleiro' => $_POST['time_a']['goleiro'] ?? '',
                    'goleiro_diarista' => $_POST['time_a']['goleiro_diarista'] ?? '',
                    'gols_goleiro' => $_POST['time_a']['gols_goleiro'] ?? 0,
                ],
                'time_b' => [
                    'modos' => $_POST['time_b']['modos'] ?? [],
                    'jogadores' => $_POST['time_b']['jogadores'] ?? [],
                    'diaristas' => $_POST['time_b']['diaristas'] ?? [],
                    'gols_linha' => $_POST['time_b']['gols_linha'] ?? [],
                    'goleiro_modo' => $_POST['time_b']['goleiro_modo'] ?? 'cadastro',
                    'goleiro' => $_POST['time_b']['goleiro'] ?? '',
                    'goleiro_diarista' => $_POST['time_b']['goleiro_diarista'] ?? '',
                    'gols_goleiro' => $_POST['time_b']['gols_goleiro'] ?? 0,
                ],
            ], $postPartidaId !== '' ? $postPartidaId : null);

            flash('success', $postPartidaId !== '' ? 'Partida atualizada.' : 'Partida cadastrada.');
            redirect('peladas.php?acao=editar&id=' . urlencode($postPeladaId));
        }

        $saved = $peladas->save([
            'data' => $_POST['data'] ?? '',
            'observacao' => $_POST['observacao'] ?? '',
        ], $postId !== '' ? $postId : null);

        flash('success', $postId !== '' ? 'Pelada atualizada.' : 'Pelada cadastrada.');
        redirect('peladas.php?acao=editar&id=' . urlencode($saved['id']));
    } catch (InvalidArgumentException $exception) {
        flash('error', $exception->getMessage());

        if (in_array($postAcao, ['salvar_partida', 'excluir_partida'], true)) {
            $query = $postPartidaId !== ''
                ? '?acao=partida&id=' . urlencode($postPeladaId) . '&partida=' . urlencode($postPartidaId)
                : '?acao=partida&id=' . urlencode($postPeladaId);
            redirect('peladas.php' . $query);
        }

        $query = $postId !== '' ? '?acao=editar&id=' . urlencode($postId) : '?acao=novo';
        redirect('peladas.php' . $query);
    }
}

$elenco = $players->all();
$playersById = [];

foreach ($elenco as $jogador) {
    $playersById[$jogador['id']] = $jogador;
}

if (in_array($acao, ['editar', 'partida', 'ver'], true)) {
    $encontrada = $peladas->find($id);

    if ($encontrada === null) {
        flash('error', 'Pelada não encontrada.');
        redirect('peladas.php');
    }

    $pelada = $encontrada;
}

$partida = [
    'id' => '',
    'time_a' => [
        'jogadores' => ['', '', '', '', ''],
        'goleiro' => null,
        'gols' => [],
    ],
    'time_b' => [
        'jogadores' => ['', '', '', '', ''],
        'goleiro' => null,
        'gols' => [],
    ],
    'placar_a' => 0,
    'placar_b' => 0,
    'vencedor' => 'empate',
    'observacao' => '',
];

if ($acao === 'partida' && $partidaId !== '') {
    $encontradaPartida = $peladas->findPartida($id, $partidaId);

    if ($encontradaPartida === null) {
        flash('error', 'Partida não encontrada.');
        redirect('peladas.php?acao=editar&id=' . urlencode($id));
    }

    $partida = $encontradaPartida;
}

$title = match ($acao) {
    'novo' => 'Nova pelada',
    'editar' => 'Editar pelada',
    'ver' => 'Pelada',
    'partida' => $partidaId !== '' ? 'Editar partida' : 'Nova partida',
    default => 'Peladas',
};

ob_start();

if ($acao === 'novo') {
    require __DIR__ . '/views/peladas-form.php';
} elseif ($acao === 'editar') {
    $rankingPelada = $peladas->ranking($pelada, $playersById);
    require __DIR__ . '/views/peladas-editar.php';
} elseif ($acao === 'ver') {
    require __DIR__ . '/views/peladas-ver.php';
} elseif ($acao === 'partida') {
    require __DIR__ . '/views/partidas-form.php';
} else {
    $lista = $peladas->all();
    require __DIR__ . '/views/peladas-lista.php';
}

$content = ob_get_clean();
require __DIR__ . '/views/layout.php';
