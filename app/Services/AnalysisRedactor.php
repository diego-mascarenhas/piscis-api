<?php

namespace App\Services;

class AnalysisRedactor
{
    /**
     * @return array{text: string, notes: list<string>}
     */
    public function redact(string $input): array
    {
        $limit = (int) config('piscis.max_extracted_characters');
        $text = mb_substr($input, 0, $limit);
        $notes = [];

        foreach ($this->rules() as $rule)
        {
            $count = 0;
            $replaced = preg_replace($rule['pattern'], ' ', $text, -1, $count);
            if (is_string($replaced) && $count > 0)
            {
                $notes[] = $rule['note'];
                $text = $replaced;
            }
        }

        $stripped = preg_replace(
            '/\b(?:dni|nie|nif|pasaporte|tel[eé]fono|phone|correo electr[oó]nico|correo|email|nhc|nss|paciente|apellidos)\b\s*[:.]?/iu',
            ' ',
            $text,
        );
        $text = is_string($stripped) ? $stripped : $text;
        $text = preg_replace('/[ \t]{2,}/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+([,.;])/', '$1', $text) ?? $text;
        $text = preg_replace('/(?:[,;]\s*){2,}/', ' ', $text) ?? $text;
        $text = preg_replace('/^[\s,.;]+/u', '', $text) ?? $text;
        $text = preg_replace('/[ \t]*\n[ \t]*/', "\n", $text) ?? $text;

        return [
            'text' => trim($text),
            'notes' => array_values(array_unique($notes)),
        ];
    }

    /**
     * @return list<array{note: string, pattern: string}>
     */
    private function rules(): array
    {
        return [
            [
                'note' => 'Se retiró un correo electrónico.',
                'pattern' => '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu',
            ],
            [
                'note' => 'Se retiró un documento de identidad.',
                'pattern' => '/\b(?:\d{8}[A-HJ-NP-TV-Z]|[XYZ]\d{7}[A-Z])\b/u',
            ],
            [
                'note' => 'Se retiró un teléfono.',
                'pattern' => '/(?:\+\d{1,3}[\s.-]?)?(?:\(?\d{2,3}\)?[\s.-]?)?\d{3}[\s.-]\d{2,3}[\s.-]\d{2,4}/u',
            ],
            [
                'note' => 'Se retiró un teléfono.',
                'pattern' => '/\+\d{8,15}\b/u',
            ],
            [
                'note' => 'Se retiró un teléfono.',
                'pattern' => '/\b[67]\d{2}[\s.-]?\d{3}[\s.-]?\d{3}\b/u',
            ],
            [
                'note' => 'Se retiró un nombre asociado a una etiqueta.',
                'pattern' => '/(?:nombre(?:\s+completo)?|paciente|apellidos|name)\s*[:\-]\s*[^\n,;.]{2,80}/iu',
            ],
            [
                'note' => 'Se retiró un identificador de historia o documento.',
                'pattern' => '/(?:dni|nie|nif|pasaporte|nss|n\.?\s*s\.?\s*s\.?|seguridad social|nhc|historia(?:\s+clínica|\s+clinica)?|n[ºo°]\s*historia|medical\s*record|mrn)\s*[:\-]?\s*[A-Z0-9][A-Z0-9.\/-]{2,}/iu',
            ],
            [
                'note' => 'Se retiró una dirección.',
                'pattern' => '/(?:direcci[oó]n|domicilio|calle|avenida|avda\.?|address|c\/)\s*[:\-]?\s*[^.\n]{3,160}/iu',
            ],
        ];
    }
}
