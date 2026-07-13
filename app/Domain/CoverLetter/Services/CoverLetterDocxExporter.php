<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CoverLetter\Services;

/**
 * Minimal DOCX (OOXML) exporter using an uncompressed ZIP writer.
 */
final class CoverLetterDocxExporter
{
    public function export(string $title, string $body): string
    {
        $document = $this->documentXml($title, $body);
        $files = [
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => $this->rootRels(),
            'word/document.xml' => $document,
            'word/_rels/document.xml.rels' => $this->documentRels(),
        ];

        return $this->buildZip($files);
    }

    private function documentXml(string $title, string $body): string
    {
        $paras = [];
        $paras[] = $this->paragraph($title, true);
        $paras[] = $this->paragraph('');
        foreach (preg_split("/\r\n|\n|\r/", $body) ?: [] as $line) {
            $paras[] = $this->paragraph($line);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . implode('', $paras)
            . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/>'
            . '<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/>'
            . '</w:sectPr></w:body></w:document>';
    }

    private function paragraph(string $text, bool $bold = false): string
    {
        $rPr = $bold ? '<w:rPr><w:b/></w:rPr>' : '';
        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<w:p><w:r>' . $rPr . '<w:t xml:space="preserve">' . $escaped . '</w:t></w:r></w:p>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';
    }

    private function documentRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
    }

    /**
     * @param  array<string, string>  $files
     */
    private function buildZip(array $files): string
    {
        $local = '';
        $central = '';
        $offset = 0;
        $count = 0;
        foreach ($files as $name => $data) {
            $nameBytes = $name;
            $crc = crc32($data);
            if ($crc < 0) {
                $crc = $crc + 0x100000000;
            }
            $size = strlen($data);
            $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $size, $size, strlen($nameBytes), 0)
                . $nameBytes . $data;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $size, $size, strlen($nameBytes), 0, 0, 0, 0, 0, $offset)
                . $nameBytes;
            $local .= $localHeader;
            $offset += strlen($localHeader);
            $count++;
        }
        $end = pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), strlen($local), 0);

        return $local . $central . $end;
    }
}
