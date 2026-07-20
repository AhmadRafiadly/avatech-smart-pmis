<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Throwable;

class RequirementDocumentTextExtractor
{
    public const MAX_EXTRACTED_CHARACTERS = 50000;

    public function __construct(private readonly Parser $parser)
    {
    }

    public function extract(string $path, string $extension): array
    {
        try {
            $text = match (strtolower($extension)) {
                'pdf' => $this->parser->parseFile($path)->getText(),
                'txt' => file_get_contents($path),
                default => null,
            };

            if ($text === null || $text === false) {
                return ['status' => 'failed', 'text' => null];
            }

            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
            $text = preg_replace('/[^\P{C}\n\t]/u', '', $text);
            $text = preg_replace('/[ \t]+/u', ' ', $text);
            $text = preg_replace('/ *\n */u', "\n", $text);
            $text = preg_replace('/\n{3,}/u', "\n\n", $text);
            $text = trim((string) $text);

            if ($text === '') {
                return ['status' => 'no_text', 'text' => null];
            }

            return [
                'status' => 'extracted',
                'text' => mb_substr($text, 0, self::MAX_EXTRACTED_CHARACTERS),
            ];
        } catch (Throwable) {
            return ['status' => 'failed', 'text' => null];
        }
    }
}
