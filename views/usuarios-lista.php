<?php
/** @var list<array{id: int, nome: string, email: string, admin: bool}> $lista */
?>
<div class="page-head">
    <div>
        <h1>Usuários</h1>
        <p class="lede"><?= count($lista) ?> usuário<?= count($lista) === 1 ? '' : 's' ?> cadastrado<?= count($lista) === 1 ? '' : 's' ?>.</p>
    </div>
    <a class="btn" href="usuarios.php?acao=novo">Novo usuário</a>
</div>

<div class="panel">
    <?php if ($lista === []): ?>
        <div class="empty">Nenhum usuário ainda. Cadastre o primeiro para acessar o sistema.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Administrador</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $item): ?>
                    <tr>
                        <td><?= h($item['nome']) ?></td>
                        <td><?= h($item['email']) ?></td>
                        <td>
                            <?php if ($item['admin']): ?>
                                <span class="badge badge-yes">Sim</span>
                            <?php else: ?>
                                <span class="badge badge-no">Não</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="usuarios.php?acao=editar&id=<?= (int) $item['id'] ?>">Editar</a>
                                <form method="post" action="usuarios.php" onsubmit="return confirm('Excluir este usuário?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button class="btn danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
