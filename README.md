# Piscis API

Backend de Piscis. Un caso pertenece al equipo IDONEO. Quien sube los análisis no tiene cuenta: entra con un token.

La base es Postgres (`piscis`). En local, Herd sirve [http://piscis-api.test](http://piscis-api.test).

## API

`POST /api/cases`

Crea el caso y devuelve el token. Hay que guardarlo: no se vuelve a mostrar.

`GET /api/cases/{token}`

Estado del caso y de cada PDF.

`POST /api/cases/{token}/documents`

`multipart/form-data` con `documents[]`. Hasta 20 PDF, 15 MB cada uno.

El job `ReadAnalysisDocument` extrae el texto en el servidor y retira correo, teléfono, documento, historia y dirección. La respuesta trae ese texto ya depurado. El PDF original queda en `storage/app/private/analysis-cases` y no se publica.

En local la cola está en `sync`, así que la lectura ocurre dentro de la petición. Para procesar en segundo plano, `QUEUE_CONNECTION=database` y `php artisan queue:work`.

`laravel/ai` está instalado para el siguiente corte: ordenar marcadores y redactar el informe a partir del texto ya depurado. El PDF no se envía al modelo.
