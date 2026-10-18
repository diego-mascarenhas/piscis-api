<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnalysisDocumentsRequest;
use App\Services\AnalysisCaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class CasePageController extends Controller
{
    public function __construct(private AnalysisCaseService $cases) {}

    public function home(): View
    {
        return view('cases.home');
    }

    public function store(): RedirectResponse
    {
        $opened = $this->cases->open();

        return redirect()->route('cases.show', ['token' => $opened['token']]);
    }

    public function show(string $token): View
    {
        $case = $this->cases->findByToken($token);
        if ($case === null)
        {
            abort(404, 'No hay un caso con ese token.');
        }

        $case->load(['documents' => function ($query)
        {
            $query->orderBy('id');
        }]);

        return view('cases.show', [
            'token' => $token,
            'case' => $case,
        ]);
    }

    public function storeDocuments(StoreAnalysisDocumentsRequest $request, string $token): RedirectResponse
    {
        $case = $this->cases->findByToken($token);
        if ($case === null)
        {
            abort(404, 'No hay un caso con ese token.');
        }

        $uploaded = $request->file('documents', []);
        /** @var list<UploadedFile> $files */
        $files = array_values(is_array($uploaded) ? $uploaded : [$uploaded]);
        $this->cases->storeDocuments($case, $files);

        return redirect()->route('cases.show', ['token' => $token]);
    }
}
