<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CoverLetter\Services;

/**
 * Minimal PDF writer (Helvetica text) — no external libraries.
 */
final class CoverLetterPdfExporter
{
    public function export(string $title, string $body): string
    {
        $lines = $this->wrapLines($title . "\n\n" . $body, 90);
        $content = "BT /F1 11 Tf 50 780 Td 14 TL\n";
        $first = true;
        foreach ($lines as $line) {
            $escaped = $this->escape($line);
            if ($first) {
                $content .= "({$escaped}) Tj\n";
                $first = false;
            } else {
                $content .= "T* ({$escaped}) Tj\n";
            }
        }
        $content .= "ET";

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
        $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    /**
     * @return list<string>
     */
    private function wrapLines(string $text, int $width): array
    {
        $out = [];
        foreach (preg_split("/\r\n|\n|\r/", $text) ?: [] as $paragraph) {
            if (trim($paragraph) === '') {
                $out[] = '';
                continue;
            }
            $wrapped = wordwrap($paragraph, $width, "\n", true);
            foreach (explode("\n", $wrapped) as $line) {
                $out[] = $line;
            }
        }

        return array_slice($out, 0, 55);
    }

    private function escape(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
