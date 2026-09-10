<?php

declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

$filtro = (string) ($_GET['mensalista'] ?? 'todos');

$mensalista = match ($filtro) {
    'sim' => true,
    'nao' => false,
    default => null,
};

$rankingGols = $players->ranking('gols', $mensalista);
$rankingVitorias = $players->ranking('vitorias', $mensalista);

$filtroLabel = match ($filtro) {
    'sim' => 'Mensalista: sim',
    'nao' => 'Mensalista: não',
    default => 'Todos',
};

$export = (string) ($_GET['export'] ?? '');

if ($export === 'gols' || $export === 'vitorias') {
    require_once __DIR__ . '/src/RankingPdf.php';
    $pdf = new RankingPdf();

    if ($export === 'gols') {
        $pdf->output('Ranking de Gols', 'Gols', 'gols', $rankingGols, $filtroLabel);
    }

    $pdf->output('Ranking de Vitórias', 'Vitórias', 'vitorias', $rankingVitorias, $filtroLabel);
}

$title = 'Relatório';
ob_start();
require __DIR__ . '/views/relatorio.php';
$content = ob_get_clean();
require __DIR__ . '/views/layout.php';
