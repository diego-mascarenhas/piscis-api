<?php

namespace App\Jobs;

use App\Enums\AnalysisDocumentStatus;
use App\Models\AnalysisDocument;
use App\Services\AnalysisRedactor;
use App\Services\PdfTextExtractor;
use App\Support\AnalysisUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Transcription;
use RuntimeException;
use Throwable;

class ReadAnalysisDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $documentId) {}

    public function handle(PdfTextExtractor $extractor, AnalysisRedactor $redactor): void
    {
        $document = AnalysisDocument::query()->find($this->documentId);
        if ($document === null)
        {
            return;
        }

        $disk = Storage::disk('analysis');
        if (! $disk->exists($document->stored_path))
        {
            $this->markFailed($document, $this->isAudio($document) ? $this->audioFailure() : 'No se pudo leer el PDF.');

            return;
        }

        $audio = $this->isAudio($document);

        try
        {
            $raw = $audio
                ? $this->transcribe($disk->path($document->stored_path), (string) $document->mime)
                : $extractor->extract($disk->path($document->stored_path));
        } catch (Throwable $exception)
        {
            Log::warning('analysis document read failed', [
                'document_id' => $document->id,
                'error' => $exception::class,
            ]);
            $this->markFailed($document, $audio ? $this->audioFailure() : 'No se pudo leer el PDF.');

            return;
        }

        $clean = $redactor->redact($raw);
        if ($clean['text'] === '')
        {
            $document->update([
                'status' => AnalysisDocumentStatus::Empty,
                'extracted_text' => null,
                'redaction_notes' => $clean['notes'],
                'failure_reason' => $audio
                    ? 'El audio no dejó texto.'
                    : 'El PDF no tiene texto. Si es un escaneo, hace falta OCR.',
            ]);

            return;
        }

        $document->update([
            'status' => AnalysisDocumentStatus::Read,
            'extracted_text' => $clean['text'],
            'redaction_notes' => $clean['notes'],
            'failure_reason' => null,
        ]);
    }

    private function isAudio(AnalysisDocument $document): bool
    {
        $mime = (string) $document->mime;
        if (str_starts_with($mime, 'audio/'))
        {
            return true;
        }

        $extension = strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION));

        return in_array($extension, AnalysisUpload::AUDIO_EXTENSIONS, true);
    }

    private function transcribe(string $path, string $mime): string
    {
        $provider = (string) config('ai.default_for_transcription', 'openai');
        $key = config('ai.providers.'.$provider.'.key');
        if (! is_string($key) || $key === '')
        {
            throw new RuntimeException('missing transcription key');
        }

        return Transcription::fromPath($path, $mime === '' ? null : $mime)
            ->language('es')
            ->timeout(120)
            ->generate()
            ->text;
    }

    private function audioFailure(): string
    {
        return 'No se pudo transcribir el audio. Hace falta configurar el proveedor de transcripción.';
    }

    private function markFailed(AnalysisDocument $document, string $reason): void
    {
        $document->update([
            'status' => AnalysisDocumentStatus::Failed,
            'failure_reason' => $reason,
        ]);
    }
}
