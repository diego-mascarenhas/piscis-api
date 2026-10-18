<?php

namespace Tests\Unit;

use App\Services\AnalysisRedactor;
use Tests\TestCase;

class AnalysisRedactorTest extends TestCase
{
    public function test_it_removes_email_and_labeled_patient_name(): void
    {
        $clean = (new AnalysisRedactor)->redact("Paciente: Ana Perez\nCorreo: ana@example.com\nEGFR L858R");

        $this->assertStringNotContainsString('ana@example.com', $clean['text']);
        $this->assertStringNotContainsString('Ana Perez', $clean['text']);
        $this->assertStringContainsString('EGFR L858R', $clean['text']);
        $this->assertContains('Se retiró un correo electrónico.', $clean['notes']);
        $this->assertContains('Se retiró un nombre asociado a una etiqueta.', $clean['notes']);
    }
}
