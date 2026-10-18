<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Piscis</title>
    <style>
        :root { color-scheme: light; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f6f1ea; color: #241c16; }
        main { max-width: 40rem; margin: 0 auto; padding: 3rem 1.25rem; }
        h1 { font-size: 2.4rem; line-height: 1.1; font-weight: 560; }
        p { line-height: 1.5; }
        form, .panel { margin-top: 1.5rem; padding: 1rem; background: #fff; border: 1px solid #e4d8cc; border-radius: 1rem; }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.35rem; }
        input[type="text"] { width: 100%; box-sizing: border-box; padding: 0.7rem 0.8rem; border: 1px solid #d9cbbd; border-radius: 0.6rem; font: inherit; }
        button { margin-top: 0.8rem; border: 0; border-radius: 999px; padding: 0.7rem 1.1rem; background: #8c3d2f; color: #fff; font: inherit; cursor: pointer; }
        .muted { color: #6d5c50; }
    </style>
</head>
<body>
    <main>
        <p class="muted">Informe para llevar a consulta</p>
        <h1>Abre un caso y sube los PDF.</h1>
        <p>No hace falta cuenta. El caso te da un token. Con ese enlace vuelves a ver los informes y el texto ya leído.</p>

        <form method="post" action="{{ route('cases.store') }}">
            @csrf
            <p>Cada caso admite hasta 20 archivos, PDF o audio.</p>
            <button type="submit">Abrir un caso</button>
        </form>

        <form class="panel" method="get" action="{{ url('/cases') }}" onsubmit="event.preventDefault(); var token = this.token.value.trim(); if (token) location.href = '/cases/' + encodeURIComponent(token);">
            <label for="token">Ya tengo un token</label>
            <input id="token" name="token" type="text" autocomplete="off" spellcheck="false" placeholder="Pega aquí el token">
            <button type="submit">Abrir</button>
        </form>
    </main>
</body>
</html>
