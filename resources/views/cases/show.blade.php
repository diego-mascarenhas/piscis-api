<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Caso · Piscis</title>
    <style>
        :root { color-scheme: light; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f6f1ea; color: #241c16; }
        main { max-width: 46rem; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
        a { color: #8c3d2f; }
        h1 { font-size: 2rem; line-height: 1.15; font-weight: 560; }
        code { display: block; margin: 0.6rem 0 0; padding: 0.8rem; background: #fff; border: 1px solid #e4d8cc; border-radius: 0.7rem; word-break: break-all; }
        form, article { margin-top: 1.25rem; padding: 1rem; background: #fff; border: 1px solid #e4d8cc; border-radius: 1rem; }
        button { margin-top: 0.8rem; border: 0; border-radius: 999px; padding: 0.7rem 1.1rem; background: #8c3d2f; color: #fff; font: inherit; cursor: pointer; }
        .error { color: #8c3d2f; }
        pre { white-space: pre-wrap; font: inherit; margin: 0.5rem 0 0; }
        .muted { color: #6d5c50; }
    </style>
</head>
<body>
    <main>
        <p><a href="{{ route('cases.home') }}">Piscis</a></p>
        <h1>Caso abierto</h1>
        <p>Guarda este enlace. Es la llave del caso. No vuelve a mostrarse en otro sitio.</p>
        <code>{{ url('/cases/'.$token) }}</code>

        <form method="post" action="{{ route('cases.documents.store', ['token' => $token]) }}" enctype="multipart/form-data">
            @csrf
            <label for="documents">PDF o audio</label>
            <input id="documents" name="documents[]" type="file" accept="application/pdf,.pdf,audio/*,.mp3,.m4a,.wav,.webm,.ogg,.aac" multiple required>
            @if ($errors->any())
                <p class="error">{{ $errors->first() }}</p>
            @endif
            <button type="submit">Subir</button>
        </form>

        @forelse ($case->documents as $document)
            <article>
                <strong>{{ $document->original_name }}</strong>
                <p class="muted">{{ $document->status->value }}@if ($document->failure_reason) · {{ $document->failure_reason }}@endif</p>
                @if ($document->redaction_notes)
                    <p class="muted">{{ implode(' ', $document->redaction_notes) }}</p>
                @endif
                @if ($document->extracted_text)
                    <pre>{{ $document->extracted_text }}</pre>
                @endif
            </article>
        @empty
            <p class="muted">Todavía no hay PDF en este caso.</p>
        @endforelse
    </main>
</body>
</html>
