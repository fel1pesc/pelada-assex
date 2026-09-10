<?php

declare(strict_types=1);

final class RankingPdf
{
    private const PAGE_W = 595.28;
    private const PAGE_H = 841.89;
    private const MARGIN = 42.0;

    /** @var list<string> */
    private array $pages = [];

    private string $buf = '';
    private float $y = 0.0;
    private int $pageNumber = 0;

    /**
     * @param list<array{id: string, nome: string, mensalista: bool, gols: int, assistencias: int, vitorias: int}> $ranking
     */
    public function output(string $titulo, string $colunaValor, string $stat, array $ranking, string $filtro): never
    {
        $date = date("d-m-Y");
        $binary = $this->build($titulo, $colunaValor, $stat, $ranking, $filtro);
        $filename = "ranking-{$stat}-{$date}.pdf";

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($binary));
        echo $binary;
        exit;
    }

    /**
     * @param list<array{id: string, nome: string, mensalista: bool, gols: int, assistencias: int, vitorias: int}> $ranking
     */
    public function build(string $titulo, string $colunaValor, string $stat, array $ranking, string $filtro): string
    {
        $this->pages = [];
        $this->buf = '';
        $this->pageNumber = 0;
        $this->startPage($titulo, $filtro);
        $this->tableHeader($colunaValor);

        if ($ranking === []) {
            $this->emptyState();
        } else {
            foreach ($ranking as $posicao => $item) {
                $this->ensureRowSpace($titulo, $filtro, $colunaValor);
                $this->tableRow($posicao + 1, $item, $stat);
            }
        }

        $this->flushPage();

        return $this->assemble();
    }

    private function startPage(string $titulo, string $filtro): void
    {
        $this->buf = '';
        $this->pageNumber++;
        $this->y = self::PAGE_H - self::MARGIN;

        $this->rect(0, 0, self::PAGE_W, self::PAGE_H, [1, 1, 1]);
        $this->rect(0, self::PAGE_H - 8, self::PAGE_W, 8, [0.882, 0.024, 0.0]);

        $this->text(self::MARGIN, $this->y - 4, 'ASSEX', 11, true, [0.45, 0.45, 0.45]);
        $this->y -= 28;
        $this->text(self::MARGIN, $this->y, $titulo, 22, true, [0.07, 0.07, 0.07]);
        $this->y -= 18;
//        $this->text(self::MARGIN, $this->y, 'Filtro: ' . $filtro, 11, false, [0.4, 0.4, 0.4]);
        $this->text(self::PAGE_W - self::MARGIN - 160, $this->y, 'Emitido em ' . date('d/m/Y'), 11, false, [0.4, 0.4, 0.4]);
        $this->y -= 22;
    }

    private function tableHeader(string $colunaValor): void
    {
        $x = self::MARGIN;
        $w = self::PAGE_W - (self::MARGIN * 2);
        $h = 22.0;
        $rowY = $this->y - $h;

        $this->rect($x, $rowY, $w, $h, [0.12, 0.12, 0.12]);
        $this->text($x + 10, $rowY + 7, '#', 10, true, [1, 1, 1]);
        $this->text($x + 42, $rowY + 7, 'Jogador', 10, true, [1, 1, 1]);
//        $this->text($x + 330, $rowY + 7, 'Mensalista', 10, true, [1, 1, 1]);
        $this->textRight($x + $w - 12, $rowY + 7, $colunaValor, 10, true, [1, 1, 1]);

        $this->y = $rowY - 2;
    }

    /**
     * @param array{id: string, nome: string, mensalista: bool, gols: int, assistencias: int, vitorias: int} $item
     */
    private function tableRow(int $lugar, array $item, string $stat): void
    {
        $x = self::MARGIN;
        $w = self::PAGE_W - (self::MARGIN * 2);
        $h = 26.0;
        $rowY = $this->y - $h;
        $alt = $lugar % 2 === 0;

        $this->rect($x, $rowY, $w, $h, $alt ? [0.96, 0.96, 0.96] : [1, 1, 1]);

        $badge = match ($lugar) {
            1 => ['fill' => [0.831, 0.686, 0.216], 'ink' => [0.1, 0.08, 0.0]],
            2 => ['fill' => [0.753, 0.753, 0.753], 'ink' => [0.07, 0.07, 0.07]],
            3 => ['fill' => [0.804, 0.498, 0.196], 'ink' => [0.1, 0.05, 0.0]],
            default => ['fill' => [0.11, 0.11, 0.11], 'ink' => [0.96, 0.96, 0.96]],
        };

        $badgeSize = 16.0;
        $badgeX = $x + 8;
        $badgeY = $rowY + (($h - $badgeSize) / 2);
        $this->rect($badgeX, $badgeY, $badgeSize, $badgeSize, $badge['fill']);
        $this->textCentered($badgeX + ($badgeSize / 2), $badgeY + 4.2, (string) $lugar, 9, true, $badge['ink']);

        $this->text($x + 42, $rowY + 8, $this->fit($item['nome'], 270, 11), 11, false, [0.08, 0.08, 0.08]);
//        $this->text($x + 330, $rowY + 8, $item['mensalista'] ? 'Sim' : 'Não', 11, false, [0.25, 0.25, 0.25]);
        $this->textRight($x + $w - 12, $rowY + 8, (string) (int) ($item[$stat] ?? 0), 12, true, [0.08, 0.08, 0.08]);

        $this->stroke($x, $rowY, $w, 0.4, [0.88, 0.88, 0.88]);
        $this->y = $rowY;
    }

    private function emptyState(): void
    {
        $this->y -= 36;
        $this->text(self::MARGIN, $this->y, 'Nenhum jogador neste filtro.', 12, false, [0.4, 0.4, 0.4]);
    }

    private function ensureRowSpace(string $titulo, string $filtro, string $colunaValor): void
    {
        if ($this->y - 26 >= 56) {
            return;
        }

        $this->flushPage();
        $this->startPage($titulo, $filtro);
        $this->tableHeader($colunaValor);
    }

    private function flushPage(): void
    {
        $label = 'Página ' . $this->pageNumber;
        $this->text(self::MARGIN, 28, $label, 9, false, [0.5, 0.5, 0.5]);
        $this->pages[] = $this->buf;
        $this->buf = '';
    }

    /** @param array{0: float, 1: float, 2: float} $rgb */
    private function rect(float $x, float $y, float $w, float $h, array $rgb): void
    {
        $this->cmd(sprintf(
            '%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f',
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $x,
            $y,
            $w,
            $h
        ));
    }

    /** @param array{0: float, 1: float, 2: float} $rgb */
    private function stroke(float $x, float $y, float $w, float $thickness, array $rgb): void
    {
        $this->cmd(sprintf(
            '%.3f %.3f %.3f RG %.2f w %.2f %.2f %.2f %.2f re S',
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $thickness,
            $x,
            $y,
            $w,
            $thickness
        ));
    }

    /** @param array{0: float, 1: float, 2: float} $rgb */
    private function text(float $x, float $y, string $value, float $size, bool $bold, array $rgb): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->cmd(sprintf(
            'BT /%s %.1f Tf %.3f %.3f %.3f rg %.2f %.2f Td (%s) Tj ET',
            $font,
            $size,
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $x,
            $y,
            $this->escape($value)
        ));
    }

    /** @param array{0: float, 1: float, 2: float} $rgb */
    private function textRight(float $xRight, float $y, string $value, float $size, bool $bold, array $rgb): void
    {
        $width = $this->textWidth($value, $size);
        $this->text($xRight - $width, $y, $value, $size, $bold, $rgb);
    }

    /** @param array{0: float, 1: float, 2: float} $rgb */
    private function textCentered(float $cx, float $y, string $value, float $size, bool $bold, array $rgb): void
    {
        $width = $this->textWidth($value, $size);
        $this->text($cx - ($width / 2), $y, $value, $size, $bold, $rgb);
    }

    private function fit(string $text, float $maxWidth, float $size): string
    {
        if ($this->textWidth($text, $size) <= $maxWidth) {
            return $text;
        }

        $ellipsis = '...';
        $cut = mb_strlen($text);

        while ($cut > 0 && $this->textWidth(mb_substr($text, 0, $cut) . $ellipsis, $size) > $maxWidth) {
            $cut--;
        }

        return mb_substr($text, 0, $cut) . $ellipsis;
    }

    private function textWidth(string $text, float $size): float
    {
        return strlen($this->toWinAnsi($text)) * $size * 0.5;
    }

    private function escape(string $text): string
    {
        return strtr($this->toWinAnsi($text), [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
        ]);
    }

    private function toWinAnsi(string $text): string
    {
        $map = [
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'É' => 'E', 'Ê' => 'E', 'È' => 'E',
            'é' => 'e', 'ê' => 'e', 'è' => 'e',
            'Í' => 'I', 'Î' => 'I', 'Ì' => 'I',
            'í' => 'i', 'î' => 'i', 'ì' => 'i',
            'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ò' => 'O',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ò' => 'o',
            'Ú' => 'U', 'Ü' => 'U', 'Ù' => 'U',
            'ú' => 'u', 'ü' => 'u', 'ù' => 'u',
            'Ç' => 'C', 'ç' => 'c',
            'Ñ' => 'N', 'ñ' => 'n',
        ];

        $replaced = strtr($text, $map);

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'Windows-1252//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        $converted = @mb_convert_encoding($replaced, 'ISO-8859-1', 'UTF-8');

        return is_string($converted) ? $converted : $replaced;
    }

    private function cmd(string $command): void
    {
        $this->buf .= $command . "\n";
    }

    private function assemble(): string
    {
        $n = count($this->pages);
        $fontRegular = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $fontBold = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $pageIds = [];
        for ($i = 0; $i < $n; $i++) {
            $pageIds[] = 5 + (2 * $i);
        }

        $kids = implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageIds));
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [' . $kids . '] /Count ' . $n . ' >>',
            3 => $fontRegular,
            4 => $fontBold,
        ];

        for ($i = 0; $i < $n; $i++) {
            $pageId = 5 + (2 * $i);
            $contentId = 6 + (2 * $i);
            $stream = $this->pages[$i];
            $length = strlen($stream);
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_W,
                self::PAGE_H,
                $contentId
            );
            $objects[$contentId] = "<< /Length {$length} >>\nstream\n{$stream}endstream";
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        ksort($objects);
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $maxId = (int) max(array_keys($objects));
        $pdf .= 'xref' . "\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $maxId; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= 'trailer << /Size ' . ($maxId + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF\n";

        return $pdf;
    }
}
