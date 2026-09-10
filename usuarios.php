<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

require_admin('jogadores.php');

$acao = (string) ($_GET['acao'] ?? 'lista');
$id = (int) ($_GET['id'] ?? 0);
$usuario = [
    'id' => 0,
    'nome' => '',
    'email' => '',
    'admin' => false,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $postAcao = (string) ($_POST['acao'] ?? '');
    $postId = (int) ($_POST['id'] ?? 0);

    try {
        if ($postAcao === 'excluir') {
            if (!$users->delete($postId)) {
                throw new InvalidArgumentException('Usuário não encontrado.');
            }

            flash('success', 'Usuário excluído.');
            redirect('usuarios.php');
        }

        $users->save([
            'nome' => $_POST['nome'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'admin' => isset($_POST['admin']),
        ], $postId > 0 ? $postId : null);

        flash('success', $postId > 0 ? 'Usuário atualizado.' : 'Usuário cadastrado.');
        redirect('usuarios.php');
    } catch (InvalidArgumentException $exception) {
        flash('error', $exception->getMessage());
        $query = $postId > 0 ? '?acao=editar&id=' . $postId : '?acao=novo';
        redirect('usuarios.php' . $query);
    }
}

if ($acao === 'editar') {
    $encontrado = $users->find($id);

    if ($encontrado === null) {
        flash('error', 'Usuário não encontrado.');
        redirect('usuarios.php');
    }

    $usuario = $encontrado;
}

$title = match ($acao) {
    'novo' => 'Novo usuário',
    'editar' => 'Editar usuário',
    default => 'Usuários',
};

ob_start();

if ($acao === 'novo' || $acao === 'editar') {
    require __DIR__ . '/views/usuarios-form.php';
} else {
    $lista = $users->all();
    require __DIR__ . '/views/usuarios-lista.php';
}

$content = ob_get_clean();
require __DIR__ . '/views/layout.php';
