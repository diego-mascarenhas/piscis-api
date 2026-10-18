<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\PdfFixture;
use Tests\TestCase;

class AnalysisCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_case_token_accepts_a_pdf_and_returns_redacted_text(): void
    {
        Storage::fake('analysis');

        $opened = $this->postJson('/api/cases');
        $opened->assertCreated();
        $token = $opened->json('case.token');
        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));

        $pdf = UploadedFile::fake()->createWithContent(
            'laboratorio.pdf',
            PdfFixture::withText('Paciente: Ana Perez. Correo: ana@example.com. EGFR L858R'),
        );

        $stored = $this->post('/api/cases/'.$token.'/documents', [
            'documents' => [$pdf],
        ], ['Accept' => 'application/json']);

        $stored->assertCreated();
        $stored->assertJsonPath('case.documents.0.status', 'read');
        $stored->assertJsonPath('case.documents.0.name', 'laboratorio.pdf');
        $text = $stored->json('case.documents.0.extractedText');
        $this->assertIsString($text);
        $this->assertStringContainsString('EGFR L858R', $text);
        $this->assertStringNotContainsString('ana@example.com', $text);
        $this->assertStringNotContainsString('Ana Perez', $text);

        $this->getJson('/api/cases/'.$token)
            ->assertOk()
            ->assertJsonPath('case.documents.0.status', 'read')
            ->assertJsonMissingPath('case.token');
    }

    public function test_an_unknown_token_is_rejected(): void
    {
        $this->getJson('/api/cases/'.str_repeat('a', 64))
            ->assertNotFound();
    }

    public function test_the_same_pdf_is_stored_once(): void
    {
        Storage::fake('analysis');

        $token = $this->postJson('/api/cases')->json('case.token');
        $contents = PdfFixture::withText('CEA 4.2 ng/mL');

        $this->post('/api/cases/'.$token.'/documents', [
            'documents' => [UploadedFile::fake()->createWithContent('uno.pdf', $contents)],
        ], ['Accept' => 'application/json'])->assertCreated();

        $again = $this->post('/api/cases/'.$token.'/documents', [
            'documents' => [UploadedFile::fake()->createWithContent('dos.pdf', $contents)],
        ], ['Accept' => 'application/json']);

        $again->assertCreated();
        $again->assertJsonCount(1, 'case.documents');
        $again->assertJsonPath('case.documents.0.name', 'uno.pdf');
    }

    public function test_audio_without_a_transcription_key_is_marked_failed(): void
    {
        Storage::fake('analysis');
        config(['ai.providers.openai.key' => null]);

        $token = $this->postJson('/api/cases')->json('case.token');
        $stored = $this->post('/api/cases/'.$token.'/documents', [
            'documents' => [UploadedFile::fake()->create('nota.mp3', 20, 'audio/mpeg')],
        ], ['Accept' => 'application/json']);

        $stored->assertCreated();
        $stored->assertJsonPath('case.documents.0.status', 'failed');
        $stored->assertJsonPath('case.documents.0.detail', 'No se pudo transcribir el audio. Hace falta configurar el proveedor de transcripción.');
    }

    public function test_a_text_file_is_rejected(): void
    {
        Storage::fake('analysis');

        $token = $this->postJson('/api/cases')->json('case.token');
        $this->post('/api/cases/'.$token.'/documents', [
            'documents' => [UploadedFile::fake()->create('nota.txt', 1, 'text/plain')],
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }
}
