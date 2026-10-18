<?php

namespace App\Services;

use RuntimeException;
use Smalot\PdfParser\Parser;

class PdfTextExtractor
{
    public function extract(string $absolutePath): string
    {
        if (! is_file($absolutePath))
        {
            throw new RuntimeException('Missing PDF.');
        }

        $text = (new Parser)->parseFile($absolutePath)->getText();

        return trim($text);
    }
}
