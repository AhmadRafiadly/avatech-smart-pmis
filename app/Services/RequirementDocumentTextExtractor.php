<?php

namespace App\Services;

use DOMDocument;
use DOMNode;
use Smalot\PdfParser\Parser;
use Throwable;
use ZipArchive;

class RequirementDocumentTextExtractor
{
    public const MAX_EXTRACTED_CHARACTERS = 50000;
    public const MAX_DOCX_DOCUMENT_XML_BYTES = 5242880;
    public const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public function __construct(private readonly Parser $parser)
    {
    }

    public function extract(string $path, string $extension): array
    {
        try {
            $text = match (strtolower($extension)) {
                'pdf' => $this->parser->parseFile($path)->getText(),
                'txt' => file_get_contents($path),
                'docx' => $this->extractDocx($path),
                default => false,
            };

            if (is_array($text)) {
                return $text;
            }
            if ($text === false || $text === null) {
                return ['status' => 'failed', 'text' => null];
            }

            return $this->normalize($text);
        } catch (Throwable) {
            return ['status' => 'failed', 'text' => null];
        }
    }

    private function extractDocx(string $path): string|array
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return ['status' => 'invalid', 'text' => null];
        }

        try {
            $contentTypes = $zip->getFromName('[Content_Types].xml');
            $stat = $zip->statName('word/document.xml');
            if ($contentTypes === false || $stat === false || ($stat['size'] ?? PHP_INT_MAX) > self::MAX_DOCX_DOCUMENT_XML_BYTES) {
                return ['status' => 'invalid', 'text' => null];
            }
            if (($stat['encryption_method'] ?? 0) !== 0 || str_contains($contentTypes, '<!DOCTYPE')) {
                return ['status' => 'invalid', 'text' => null];
            }
            if (! preg_match('/ContentType=["\']application\/vnd\.openxmlformats-officedocument\.wordprocessingml\.document\.main\+xml["\']/', $contentTypes)) {
                return ['status' => 'invalid', 'text' => null];
            }

            $xml = $zip->getFromName('word/document.xml');
            if ($xml === false || str_contains($xml, '<!DOCTYPE')) {
                return ['status' => 'invalid', 'text' => null];
            }

            $previous = libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            if (! $loaded) {
                return ['status' => 'invalid', 'text' => null];
            }

            return $this->docxNodeText($dom->documentElement);
        } finally {
            $zip->close();
        }
    }

    private function docxNodeText(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE && $node->parentNode?->localName === 't') {
            return $node->nodeValue ?? '';
        }
        if (in_array($node->localName, ['tab'], true)) {
            return "\t";
        }
        if (in_array($node->localName, ['br', 'cr'], true)) {
            return "\n";
        }

        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= $this->docxNodeText($child);
        }

        return $text . ($node->localName === 'p' ? "\n" : '');
    }

    private function normalize(string $text): array
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        $text = preg_replace('/[^\P{C}\n\t]/u', '', $text);
        $text = preg_replace('/ +/u', ' ', $text);
        $text = preg_replace('/ *\n */u', "\n", $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);
        $text = trim((string) $text);

        return $text === ''
            ? ['status' => 'no_text', 'text' => null]
            : ['status' => 'extracted', 'text' => mb_substr($text, 0, self::MAX_EXTRACTED_CHARACTERS)];
    }
}
