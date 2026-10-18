<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnalysisDocumentsRequest;
use App\Services\AnalysisCaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class AnalysisCaseController extends Controller
{
    public function __construct(private AnalysisCaseService $cases) {}

    public function store(): JsonResponse
    {
        $opened = $this->cases->open();

        return response()->json([
            'message' => 'Guarda este token. Con él vuelves a ver el caso y puedes añadir PDF o audio.',
            'case' => $this->cases->present($opened['case'], $opened['token']),
        ], 201);
    }

    public function show(string $token): JsonResponse
    {
        $case = $this->cases->findByToken($token);
        if ($case === null)
        {
            return response()->json([
                'message' => 'No hay un caso con ese token.',
            ], 404);
        }

        return response()->json([
            'case' => $this->cases->present($case),
        ]);
    }

    public function storeDocuments(StoreAnalysisDocumentsRequest $request, string $token): JsonResponse
    {
        $case = $this->cases->findByToken($token);
        if ($case === null)
        {
            return response()->json([
                'message' => 'No hay un caso con ese token.',
            ], 404);
        }

        $uploaded = $request->file('documents', []);
        /** @var list<UploadedFile> $files */
        $files = array_values(is_array($uploaded) ? $uploaded : [$uploaded]);
        $stored = $this->cases->storeDocuments($case, $files);

        return response()->json([
            'case' => $this->cases->present($case->refresh()),
            'unchanged' => $stored['unchanged'],
        ], 201);
    }
}
