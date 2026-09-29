<?php

namespace Tests\Feature;

use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = \App\Models\User::factory()->create();
        $user->role = 'jefe';
        $user->save();
        $this->actingAs($user);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('factura.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    public function test_upload_persists_and_download_returns_identical_bytes(): void
    {
        Storage::fake('local');
        $pdf = $this->pdf();
        $bytes = file_get_contents($pdf->getPathname());
        $this->post('/facturas', ['pdf' => $pdf])->assertRedirect('/')->assertSessionHas('success');
        $factura = Factura::sole();
        Storage::disk('local')->assertExists($factura->ruta_pdf);
        $this->assertSame($bytes, Storage::disk('local')->get($factura->ruta_pdf));
        $this->get('/')->assertOk()->assertSee('factura.pdf');
        $response = $this->get(route('facturas.download', $factura));
        $response->assertOk()->assertDownload('factura.pdf');
        $this->assertSame($bytes, $response->streamedContent());
    }

    public function test_equal_names_do_not_overwrite_files(): void
    {
        Storage::fake('local');
        $this->post('/facturas', ['pdf' => $this->pdf()])->assertSessionHasNoErrors();
        $this->post('/facturas', ['pdf' => $this->pdf()])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('facturas', 2);
        $this->assertCount(2, Storage::disk('local')->allFiles('facturas'));
    }

    public function test_invalid_missing_and_oversized_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $this->post('/facturas')->assertSessionHasErrors('pdf');
        $path = tempnam(sys_get_temp_dir(), 'invoice-test-');
        file_put_contents($path, 'Esto es texto');
        try {
            $this->post('/facturas', ['pdf' => new UploadedFile($path, 'engano.pdf', 'application/pdf', null, true)])->assertSessionHasErrors('pdf');
        } finally {
            if (file_exists($path)) { unlink($path); }
        }
        $this->post('/facturas', ['pdf' => UploadedFile::fake()->create('grande.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('pdf');
        $this->assertDatabaseCount('facturas', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles('facturas'));
    }

    public function test_unknown_or_missing_document_returns_404(): void
    {
        Storage::fake('local');
        $this->get('/facturas/999/pdf')->assertNotFound();
        $factura = Factura::create(['nombre_original' => 'falta.pdf', 'ruta_pdf' => 'facturas/falta.pdf', 'tamano_bytes' => 100]);
        $this->get(route('facturas.download', $factura))->assertNotFound();
    }

    public function test_database_failure_removes_stored_pdf(): void
    {
        Storage::fake('local');
        Factura::creating(function () { throw new \RuntimeException('Fallo simulado'); });
        try {
            $this->post('/facturas', ['pdf' => $this->pdf()])->assertSessionHasErrors('pdf');
            $this->assertCount(0, Storage::disk('local')->allFiles('facturas'));
            $this->assertDatabaseCount('facturas', 0);
        } finally {
            Factura::flushEventListeners();
        }
    }
}
