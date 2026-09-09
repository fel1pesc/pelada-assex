$ErrorActionPreference = 'Stop'

$jsonPath = Join-Path $PSScriptRoot '..\database.json'
$sqlPath = Join-Path $PSScriptRoot 'assex.sql'

function Escape-Sql([string]$value) {
    return ($value -replace '\\', '\\') -replace "'", "''"
}

$raw = Get-Content -Path $jsonPath -Raw -Encoding UTF8
$data = $raw | ConvertFrom-Json

$playerMap = @{}
$playerId = 0
$playerRows = New-Object System.Collections.Generic.List[string]

foreach ($player in $data.players) {
    $playerId++
    $playerMap[[string]$player.id] = $playerId
    $nome = Escape-Sql ([string]$player.nome)
    $mensalista = if ([bool]$player.mensalista) { 1 } else { 0 }
    $gols = [int]$player.gols
    $assistencias = [int]$player.assistencias
    $vitorias = [int]$player.vitorias
    $playerRows.Add("($playerId, '$nome', $mensalista, $gols, $assistencias, $vitorias)")
}

$peladaId = 0
$partidaId = 0
$pjId = 0
$peladaRows = New-Object System.Collections.Generic.List[string]
$partidaRows = New-Object System.Collections.Generic.List[string]
$pjRows = New-Object System.Collections.Generic.List[string]

foreach ($pelada in $data.peladas) {
    $peladaId++
    $dataPelada = Escape-Sql ([string]$pelada.data)
    $observacaoPelada = ''
    if ($null -ne $pelada.PSObject.Properties['observacao']) {
        $observacaoPelada = Escape-Sql ([string]$pelada.observacao)
    }
    $peladaRows.Add("($peladaId, '$dataPelada', '$observacaoPelada')")

    $partidas = @()
    if ($null -ne $pelada.partidas) {
        $partidas = @($pelada.partidas)
    }

    foreach ($partida in $partidas) {
        $partidaId++
        $placarA = [int]$partida.placar_a
        $placarB = [int]$partida.placar_b
        $vencedor = Escape-Sql ([string]$partida.vencedor)
        $observacaoPartida = ''
        if ($null -ne $partida.PSObject.Properties['observacao']) {
            $observacaoPartida = Escape-Sql ([string]$partida.observacao)
        }
        $partidaRows.Add("($partidaId, $peladaId, $placarA, $placarB, '$vencedor', '$observacaoPartida')")

        foreach ($timeKey in @('time_a', 'time_b')) {
            $timeLetra = if ($timeKey -eq 'time_a') { 'a' } else { 'b' }
            $team = $partida.$timeKey
            $golsMap = @{}

            if ($null -ne $team.gols) {
                foreach ($prop in $team.gols.PSObject.Properties) {
                    $golsMap[[string]$prop.Name] = [int]$prop.Value
                }
            }

            $posicao = 0
            foreach ($hashId in @($team.jogadores)) {
                $posicao++
                $hash = [string]$hashId
                if (-not $playerMap.ContainsKey($hash)) {
                    throw "Jogador de linha nao mapeado: $hash (partida hash $($partida.id))"
                }
                $jogadorId = $playerMap[$hash]
                $golsJogador = 0
                if ($golsMap.ContainsKey($hash)) {
                    $golsJogador = $golsMap[$hash]
                }
                $pjId++
                $pjRows.Add("($pjId, $partidaId, $jogadorId, '$timeLetra', $posicao, 0, $golsJogador)")
            }

            $goleiroHash = $null
            if ($null -ne $team.goleiro -and [string]$team.goleiro -ne '') {
                $goleiroHash = [string]$team.goleiro
            }

            if ($null -ne $goleiroHash) {
                if (-not $playerMap.ContainsKey($goleiroHash)) {
                    throw "Goleiro nao mapeado: $goleiroHash (partida hash $($partida.id))"
                }
                $jogadorId = $playerMap[$goleiroHash]
                $golsGoleiro = 0
                if ($golsMap.ContainsKey($goleiroHash)) {
                    $golsGoleiro = $golsMap[$goleiroHash]
                }
                $pjId++
                $pjRows.Add("($pjId, $partidaId, $jogadorId, '$timeLetra', 6, 1, $golsGoleiro)")
            }
        }
    }
}

$nextPlayer = $playerId + 1
$nextPelada = $peladaId + 1
$nextPartida = $partidaId + 1
$nextPj = $pjId + 1

$sql = @"
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS ``assex``;
CREATE DATABASE ``assex`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ``assex``;

CREATE TABLE ``jogadores`` (
  ``id`` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ``nome`` VARCHAR(80) NOT NULL,
  ``mensalista`` TINYINT(1) NOT NULL DEFAULT 0,
  ``gols`` INT UNSIGNED NOT NULL DEFAULT 0,
  ``assistencias`` INT UNSIGNED NOT NULL DEFAULT 0,
  ``vitorias`` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (``id``),
  KEY ``idx_jogadores_mensalista`` (``mensalista``),
  KEY ``idx_jogadores_nome`` (``nome``)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ``peladas`` (
  ``id`` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ``data`` DATE NOT NULL,
  ``observacao`` VARCHAR(500) NOT NULL DEFAULT '',
  PRIMARY KEY (``id``),
  KEY ``idx_peladas_data`` (``data``)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ``partidas`` (
  ``id`` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ``pelada_id`` INT UNSIGNED NOT NULL,
  ``placar_a`` INT UNSIGNED NOT NULL DEFAULT 0,
  ``placar_b`` INT UNSIGNED NOT NULL DEFAULT 0,
  ``vencedor`` ENUM('a','b','empate') NOT NULL DEFAULT 'empate',
  ``observacao`` VARCHAR(500) NOT NULL DEFAULT '',
  PRIMARY KEY (``id``),
  KEY ``idx_partidas_pelada`` (``pelada_id``),
  CONSTRAINT ``fk_partidas_pelada`` FOREIGN KEY (``pelada_id``) REFERENCES ``peladas`` (``id``) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ``partida_jogadores`` (
  ``id`` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ``partida_id`` INT UNSIGNED NOT NULL,
  ``jogador_id`` INT UNSIGNED NOT NULL,
  ``time`` ENUM('a','b') NOT NULL,
  ``posicao`` TINYINT UNSIGNED NOT NULL COMMENT '1-5 linha, 6 goleiro',
  ``is_goleiro`` TINYINT(1) NOT NULL DEFAULT 0,
  ``gols`` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (``id``),
  UNIQUE KEY ``uk_partida_jogador`` (``partida_id``, ``jogador_id``),
  KEY ``idx_pj_jogador`` (``jogador_id``),
  KEY ``idx_pj_time`` (``partida_id``, ``time``),
  CONSTRAINT ``fk_pj_partida`` FOREIGN KEY (``partida_id``) REFERENCES ``partidas`` (``id``) ON DELETE CASCADE,
  CONSTRAINT ``fk_pj_jogador`` FOREIGN KEY (``jogador_id``) REFERENCES ``jogadores`` (``id``)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ``jogadores`` (``id``, ``nome``, ``mensalista``, ``gols``, ``assistencias``, ``vitorias``) VALUES
$($playerRows -join ",`n");

INSERT INTO ``peladas`` (``id``, ``data``, ``observacao``) VALUES
$($peladaRows -join ",`n");

INSERT INTO ``partidas`` (``id``, ``pelada_id``, ``placar_a``, ``placar_b``, ``vencedor``, ``observacao``) VALUES
$($partidaRows -join ",`n");

INSERT INTO ``partida_jogadores`` (``id``, ``partida_id``, ``jogador_id``, ``time``, ``posicao``, ``is_goleiro``, ``gols``) VALUES
$($pjRows -join ",`n");

ALTER TABLE ``jogadores`` AUTO_INCREMENT = $nextPlayer;
ALTER TABLE ``peladas`` AUTO_INCREMENT = $nextPelada;
ALTER TABLE ``partidas`` AUTO_INCREMENT = $nextPartida;
ALTER TABLE ``partida_jogadores`` AUTO_INCREMENT = $nextPj;

SET FOREIGN_KEY_CHECKS = 1;
"@

[System.IO.File]::WriteAllText($sqlPath, $sql, [System.Text.UTF8Encoding]::new($false))

Write-Host "SQL gerado em $sqlPath"
Write-Host "jogadores=$playerId peladas=$peladaId partidas=$partidaId partida_jogadores=$pjId"
