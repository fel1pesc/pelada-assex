<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

$acao = $_GET['acao'] ?? 'lista';
$id = (string) ($_GET['id'] ?? '');
$acao = is_string($acao) ? $acao : 'lista';

if (in_array($acao, ['novo', 'editar'], true)) {
    require_admin('jogadores.php');
}

$jogador = [
    'id' => '',
    'nome' => '',
    'mensalista' => false,
    'gols' => 0,
    'assistencias' => 0,
    'vitorias' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin('jogadores.php');
    csrf_verify();

    $postAcao = (string) ($_POST['acao'] ?? '');
    $postId = (string) ($_POST['id'] ?? '');

    try {
        if ($postAcao === 'excluir') {
            if (!$players->delete($postId)) {
                throw new InvalidArgumentException('Jogador não encontrado.');
            }

            flash('success', 'Jogador excluído.');
            redirect('jogadores.php');
        }

        $players->save([
            'nome' => $_POST['nome'] ?? '',
            'mensalista' => isset($_POST['mensalista']),
            'gols' => $_POST['gols'] ?? 0,
            'assistencias' => $_POST['assistencias'] ?? 0,
            'vitorias' => $_POST['vitorias'] ?? 0,
        ], $postId !== '' ? $postId : null);

        flash('success', $postId !== '' ? 'Jogador atualizado.' : 'Jogador cadastrado.');
        redirect('jogadores.php');
    } catch (InvalidArgumentException $exception) {
        flash('error', $exception->getMessage());
        $query = $postId !== '' ? '?acao=editar&id=' . urlencode($postId) : '?acao=novo';
        redirect('jogadores.php' . $query);
    }
}

if ($acao === 'editar') {
    $encontrado = $players->find($id);

    if ($encontrado === null) {
        flash('error', 'Jogador não encontrado.');
        redirect('jogadores.php');
    }

    $jogador = $encontrado;
}

$title = match ($acao) {
    'novo' => 'Novo jogador',
    'editar' => 'Editar jogador',
    default => 'Jogadores',
};

$filtro = (string) ($_GET['mensalista'] ?? 'todos');
$mensalista = match ($filtro) {
    'sim' => true,
    'nao' => false,
    default => null,
};

ob_start();

if ($acao === 'novo' || $acao === 'editar') {
    require __DIR__ . '/views/jogadores-form.php';
} else {
    $elenco = $players->all($mensalista);
    require __DIR__ . '/views/jogadores-lista.php';
}

$content = ob_get_clean();
require __DIR__ . '/views/layout.php';
