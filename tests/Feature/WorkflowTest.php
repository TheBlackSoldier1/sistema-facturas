<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    public function test_assignment_notice_includes_instructions_and_document_actions(): void
    {
        $f = $this->invoice();
        $this->act($f, 'asignar', ['area_id' => $this->area->area_id, 'motivo' => 'Adjuntar número de operación'])->assertSessionHasNoErrors();
        $this->actingAs($this->area)->get('/notificaciones')
            ->assertSee('Adjuntar número de operación')
            ->assertSee('Descargar PDF')
            ->assertSee('Subir comprobante')
            ->assertSee(route('facturas.download', $f), false)
            ->assertSee(route('facturas.show', $f).'#subir-comprobante', false);
        $this->get(route('facturas.download', $f))->assertOk();
        $this->actingAs($this->chief);
        $this->act($f, 'asignar', ['area_id' => $this->other->area_id])->assertSessionHasNoErrors();
        $this->actingAs($this->area)->get('/notificaciones')->assertDontSee('Descargar PDF')->assertDontSee('Subir comprobante');
        $this->get(route('facturas.download', $f))->assertForbidden();
        $this->actingAs($this->other)->get('/notificaciones')->assertSee('Descargar PDF')->assertSee('Subir comprobante');
    }

    use RefreshDatabase;

    private User $chief;

    private User $area;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(DemoUsersSeeder::class);
        $this->chief = User::where('role', 'jefe')->firstOrFail();
        $this->area = User::where('email', 'finanzas@example.test')->firstOrFail();
        $this->other = User::where('email', 'vivienda@example.test')->firstOrFail();
    }

    private function pdf(string $name = 'factura.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    private function invoice(): Factura
    {
        $this->actingAs($this->chief)->post('/facturas', ['pdf' => $this->pdf()])->assertSessionHasNoErrors();

        return Factura::latest('id')->firstOrFail();
    }

    private function act(Factura $f, string $action, array $extra = [])
    {
        return $this->post(route('facturas.action', $f), array_merge(['version' => $f->fresh()->version, 'accion' => $action], $extra));
    }

    private function assign(Factura $f): void
    {
        $this->actingAs($this->chief);
        $this->act($f, 'asignar', ['area_id' => $this->area->area_id])->assertSessionHasNoErrors();
    }

    private function receipt(Factura $f)
    {
        return $this->actingAs($this->area)->post(route('facturas.upload', $f), ['version' => $f->fresh()->version, 'pdf' => $this->pdf('comprobante.pdf')]);
    }

    public function test_complete_payment_with_correction_and_readonly_history(): void
    {
        $f = $this->invoice();
        $this->assertSame('recibida', $f->estado);
        $this->assertNull($f->area_id);
        $this->assign($f);
        $this->assertDatabaseHas('avisos', ['user_id' => $this->area->id, 'factura_id' => $f->id]);
        $this->actingAs($this->area)->get(route('facturas.show', $f))->assertOk()->assertSee('Enviar a revisión');
        $this->receipt($f)->assertSessionHasNoErrors();
        $this->assertSame('en_revision', $f->fresh()->estado);
        $this->assertDatabaseHas('avisos', ['user_id' => $this->chief->id, 'factura_id' => $f->id]);
        $this->actingAs($this->chief)->get(route('facturas.show', $f))->assertOk()->assertSee('Confirmar pago');
        $this->act($f, 'corregir', ['motivo' => 'Falta el número de operación'])->assertSessionHasNoErrors();
        $this->assertSame('correccion', $f->fresh()->estado);
        $this->actingAs($this->area)->get(route('facturas.show', $f))->assertSee('Falta el número de operación');
        $this->receipt($f)->assertSessionHasNoErrors();
        $this->actingAs($this->chief);
        $this->act($f, 'confirmar')->assertSessionHasNoErrors();
        $this->assertSame('confirmada', $f->fresh()->estado);
        $this->assertSame(3, $f->documentos()->count());
        $this->assertCount(3, Storage::disk('local')->allFiles('facturas'));
        $this->assertSame(6, $f->eventos()->count());
        $this->assertDatabaseHas('eventos', ['factura_id' => $f->id, 'accion' => 'Pago confirmado', 'user_id' => $this->chief->id]);
        $this->actingAs($this->area)->get('/?estado=confirmada')->assertSee('factura.pdf');
        $this->get(route('facturas.show', $f))->assertSee('Pago confirmado')->assertDontSee('Enviar a revisión')->assertDontSee('Guardar datos')->assertDontSee('Historial de esta factura');
        $doc = $f->documentos()->latest('id')->firstOrFail();
        $this->get(route('facturas.document', [$f, $doc]))->assertOk()->assertDownload('comprobante.pdf');
        $response = $this->get(route('facturas.document', [$f, $doc, 'preview' => 1]));
        $response->assertOk();
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->receipt($f)->assertStatus(422);
        $this->assertCount(3, Storage::disk('local')->allFiles('facturas'));
        $this->get('/notificaciones')->assertOk()->assertSee('confirmó el pago');
    }

    public function test_state_permissions_and_stale_forms_do_not_write_events(): void
    {
        $f = $this->invoice();
        $this->act($f, 'confirmar')->assertStatus(422);
        $this->assign($f);
        $this->post(route('facturas.action', $f), ['version' => 0, 'accion' => 'asignar', 'area_id' => $this->other->area_id])->assertSessionHasErrors('version');
        $this->assertSame($this->area->area_id, $f->fresh()->area_id);
        $this->receipt($f)->assertSessionHasNoErrors();
        $count = $f->eventos()->count();
        $this->actingAs($this->chief);
        $this->act($f, 'corregir')->assertSessionHasErrors('motivo');
        $this->act($f, 'asignar', ['area_id' => $this->other->area_id])->assertStatus(422);
        $this->assertSame($count, $f->eventos()->count());
        $this->assertSame('en_revision', $f->fresh()->estado);
    }

    public function test_area_cannot_administer_other_documents_or_read_global_audit(): void
    {
        $f = $this->invoice();
        $this->assign($f);
        $doc = $f->documentos()->firstOrFail();
        $this->actingAs($this->other)->get(route('facturas.show', $f))->assertForbidden();
        $this->get(route('facturas.document', [$f, $doc]))->assertForbidden();
        $this->post(route('facturas.upload', $f), ['version' => $f->fresh()->version, 'pdf' => $this->pdf()])->assertForbidden();
        $this->actingAs($this->area)->get('/historial')->assertForbidden();
        foreach (['confirmar', 'eliminar', 'asignar', 'restaurar'] as $action) {
            $this->act($f, $action)->assertForbidden();
        }
        $this->patch(route('facturas.update', $f), ['version' => $f->fresh()->version, 'proveedor' => 'Manipulado'])->assertForbidden();
        $aviso = Aviso::where('user_id', $this->area->id)->firstOrFail();
        $this->actingAs($this->other)->post(route('notifications.read', $aviso))->assertForbidden();
        $this->actingAs($this->area)->post(route('notifications.read', $aviso))->assertRedirect();
        $this->assertNotNull($aviso->fresh()->read_at);
    }

    public function test_crud_preserves_pdf_versions_and_history_after_deletion(): void
    {
        $f = $this->invoice();
        $original = $f->ruta_pdf;
        $this->patch(route('facturas.update', $f), ['version' => 0, 'proveedor' => 'Proveedor ejemplo', 'folio' => 'F-123'])->assertSessionHasNoErrors();
        $this->post(route('facturas.upload', $f), ['version' => $f->fresh()->version, 'pdf' => $this->pdf('actualizada.pdf')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists($original);
        $this->assertSame(2, $f->documentos()->count());
        $this->assign($f);
        $this->act($f, 'eliminar', ['motivo' => 'Registro duplicado'])->assertSessionHasNoErrors();
        $this->assertSoftDeleted('facturas', ['id' => $f->id]);
        $this->get('/?papelera=1')->assertSee('actualizada.pdf');
        $this->get(route('facturas.show', $f))->assertOk()->assertSee('Restaurar factura');
        $this->get('/historial')->assertOk()->assertSee('Registro duplicado')->assertSee('Proveedor ejemplo');
        $this->actingAs($this->area)->get(route('facturas.show', $f))->assertForbidden();
        $this->get('/?papelera=1')->assertDontSee('actualizada.pdf');
        $this->actingAs($this->chief);
        $this->post(route('facturas.action', $f), ['version' => Factura::withTrashed()->find($f->id)->version, 'accion' => 'restaurar'])->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted('facturas', ['id' => $f->id]);
        $this->assertDatabaseHas('eventos', ['factura_id' => $f->id, 'accion' => 'Restaurada']);
    }

    public function test_invalid_receipt_does_not_change_state_and_other_document_id_is_rejected(): void
    {
        $f = $this->invoice();
        $second = $this->invoice();
        $this->assign($f);
        $this->actingAs($this->area);
        $this->post(route('facturas.upload', $f), ['version' => $f->fresh()->version, 'pdf' => UploadedFile::fake()->create('no.txt', 1, 'text/plain')])->assertSessionHasErrors('pdf');
        $this->post(route('facturas.upload', $f), ['version' => $f->fresh()->version, 'pdf' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('pdf');
        $this->assertSame('por_pagar', $f->fresh()->estado);
        $this->get(route('facturas.document', [$f, $second->documentos()->first()]))->assertNotFound();
    }

    public function test_notification_failure_rolls_back_receipt_state_and_file(): void
    {
        $f = $this->invoice();
        $this->assign($f);
        Aviso::creating(function () {
            throw new \RuntimeException('Fallo simulado');
        });
        $this->withoutExceptionHandling();
        try {
            $this->receipt($f);
            $this->fail('Se esperaba un fallo simulado.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo simulado', $e->getMessage());
        } finally {
            Aviso::flushEventListeners();
        }
        $this->assertSame('por_pagar',$f->fresh()->estado);
        $this->assertSame(1,$f->documentos()->count());
        $this->assertSame(2,$f->eventos()->count());
        $this->assertCount(1,Storage::disk('local')->allFiles('facturas'));
    }
}
