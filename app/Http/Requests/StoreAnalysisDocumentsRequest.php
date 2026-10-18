<?php

namespace App\Http\Requests;

use App\Support\AnalysisUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreAnalysisDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = (int) config('piscis.max_documents');
        $kilobytes = (int) config('piscis.max_upload_kilobytes');

        return [
            'documents' => ['required', 'array', 'min:1', 'max:'.$max],
            'documents.*' => [
                'file',
                'max:'.$kilobytes,
                function (string $attribute, mixed $value, \Closure $fail): void
                {
                    if (! $value instanceof UploadedFile || AnalysisUpload::kind($value->getContent(), $value) === null)
                    {
                        $fail('Cada archivo tiene que ser un PDF o un audio.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documents.required' => 'Adjunta al menos un PDF o un audio.',
            'documents.array' => 'Adjunta los archivos en documents.',
            'documents.max' => 'Este caso admite hasta '.(int) config('piscis.max_documents').' archivos.',
            'documents.*.max' => 'Cada archivo puede pesar hasta 15 MB.',
        ];
    }
}
