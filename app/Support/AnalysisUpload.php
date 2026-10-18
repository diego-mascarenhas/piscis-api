<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class AnalysisUpload
{
    /**
     * @var list<string>
     */
    public const AUDIO_EXTENSIONS = ['mp3', 'mpeg', 'mpga', 'm4a', 'wav', 'webm', 'ogg', 'aac', 'mp4'];

    public static function kind(string $contents, UploadedFile $file): ?string
    {
        if (str_contains(substr($contents, 0, 1024), '%PDF-'))
        {
            return 'pdf';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();
        if (in_array($extension, self::AUDIO_EXTENSIONS, true) || str_starts_with($mime, 'audio/'))
        {
            return 'audio';
        }

        return null;
    }

    public static function extension(UploadedFile $file, string $kind): string
    {
        if ($kind === 'pdf')
        {
            return 'pdf';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::AUDIO_EXTENSIONS, true))
        {
            return $extension;
        }

        return 'audio';
    }
}
