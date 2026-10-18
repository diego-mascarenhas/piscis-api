<?php

namespace App\Services;

use App\Enums\AnalysisCaseStatus;
use App\Enums\AnalysisDocumentStatus;
use App\Jobs\ReadAnalysisDocument;
use App\Models\AnalysisCase;
use App\Models\AnalysisDocument;
use App\Support\AnalysisUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AnalysisCaseService
{
    public function __construct(private DefaultTeam $teams) {}

    /**
     * @return array{token: string, case: AnalysisCase}
     */
    public function open(): array
    {
        $token = bin2hex(random_bytes(32));
        $case = AnalysisCase::query()->create([
            'team_id' => $this->teams->id(),
            'token_hash' => hash('sha256', $token),
            'status' => AnalysisCaseStatus::Open,
        ]);

        return [
            'token' => $token,
            'case' => $case,
        ];
    }

    public function findByToken(string $token): ?AnalysisCase
    {
        return AnalysisCase::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return array{documents: list<AnalysisDocument>, unchanged: list<string>}
     */
    public function storeDocuments(AnalysisCase $case, array $files): array
    {
        $max = (int) config('piscis.max_documents');
        $known = $case->documents()->pluck('sha256')->all();
        $newCount = 0;
        foreach ($files as $file)
        {
            $hash = hash('sha256', $file->getContent());
            if (! in_array($hash, $known, true))
            {
                $newCount++;
                $known[] = $hash;
            }
        }
        if ($case->documents()->count() + $newCount > $max)
        {
            throw ValidationException::withMessages([
                'documents' => "Este caso admite hasta {$max} archivos.",
            ]);
        }

        $stored = [];
        $unchanged = [];
        foreach ($files as $file)
        {
            $result = $this->storeOne($case, $file);
            $stored[] = $result['document'];
            if ($result['unchanged'])
            {
                $unchanged[] = $result['document']->original_name;
            }
        }

        return [
            'documents' => $stored,
            'unchanged' => $unchanged,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function present(AnalysisCase $case, ?string $token = null): array
    {
        $case->load(['documents' => function ($query)
        {
            $query->orderByRaw("case when status = 'pending' then 1 else 0 end")
                ->orderBy('updated_at')
                ->orderBy('id');
        }]);

        $payload = [
            'status' => $case->status->value,
            'documents' => $case->documents->map(fn (AnalysisDocument $document) => $this->presentDocument($document))->all(),
        ];

        if ($token !== null)
        {
            $payload = ['token' => $token] + $payload;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function presentDocument(AnalysisDocument $document): array
    {
        return [
            'id' => $document->id,
            'name' => $document->original_name,
            'size' => $document->size,
            'status' => $document->status->value,
            'redactionNotes' => $document->redaction_notes ?? [],
            'extractedText' => $document->extracted_text,
            'detail' => $document->failure_reason,
            'processedAt' => $document->status === AnalysisDocumentStatus::Pending
                ? null
                : $document->updated_at?->toIso8601String(),
        ];
    }

    private function storeOne(AnalysisCase $case, UploadedFile $file): AnalysisDocument
    {
        $contents = $file->getContent();
        $kind = AnalysisUpload::kind($contents, $file);
        if ($kind === null)
        {
            throw ValidationException::withMessages([
                'documents' => 'Cada archivo tiene que ser un PDF o un audio.',
            ]);
        }

        $hash = hash('sha256', $contents);
        $existing = $case->documents()->where('sha256', $hash)->first();
        if ($existing !== null)
        {
            if ($existing->status === AnalysisDocumentStatus::Failed)
            {
                $existing->update([
                    'status' => AnalysisDocumentStatus::Pending,
                    'failure_reason' => null,
                ]);
                ReadAnalysisDocument::dispatch($existing->id);

                return ['document' => $existing->refresh(), 'unchanged' => false];
            }

            return ['document' => $existing, 'unchanged' => true];
        }

        $path = $case->id.'/'.$hash.'.'.AnalysisUpload::extension($file, $kind);
        Storage::disk('analysis')->put($path, $contents);

        $name = basename($file->getClientOriginalName());
        $fallback = $kind === 'pdf' ? 'informe.pdf' : 'grabacion.audio';
        $name = $name !== '' ? mb_substr($name, 0, 180) : $fallback;
        $mime = $kind === 'pdf' ? 'application/pdf' : ((string) $file->getMimeType() ?: 'application/octet-stream');

        $document = $case->documents()->create([
            'original_name' => $name,
            'stored_path' => $path,
            'sha256' => $hash,
            'mime' => $mime,
            'size' => strlen($contents),
            'status' => AnalysisDocumentStatus::Pending,
        ]);

        ReadAnalysisDocument::dispatch($document->id);

        return ['document' => $document->refresh(), 'unchanged' => false];
    }
}
