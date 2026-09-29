<?php

namespace Tests\Feature;

use App\Mail\InvoiceAssigned;
use App\Models\Area;
use App\Models\Aviso;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceMailTest extends TestCase
{
    use RefreshDatabase;

    private User $chief;
    private Area $area;
    private Factura $invoice;
    private string $pdf = "%PDF-1.4\nPDF de prueba\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->chief = User::factory()->create(['role' => 'jefe']);
        $this->area = Area::create(['nombre' => 'Finanzas']);
        $this->actingAs($this->chief)->post('/facturas', ['pdf' => UploadedFile::fake()->createWithContent('original.pdf', $this->pdf)])->assertSessionHasNoErrors();
        $this->invoice = Factura::sole();
    }

    private function recipient(string $email): User
    {
        return User::factory()->create(['role' => 'usuario', 'area_id' => $this->area->id, 'email' => $email]);
    }

    private function assign(array $extra = [])
    {
        return $this->post(route('facturas.action', $this->invoice), array_replace([
            'accion' => 'asignar', 'version' => $this->invoice->fresh()->version,
            'area_id' => $this->area->id, 'motivo' => 'Adjuntar número de operación <script>no</script>',
        ], $extra));
    }

    public function test_individual_emails_contain_exact_current_pdf_and_instructions(): void
    {
        $this->recipient('uno@example.test');
        $this->recipient('dos@example.test');
        User::factory()->create(['role' => 'admin', 'area_id' => $this->area->id]);
        User::factory()->create(['role' => 'usuario', 'area_id' => Area::create(['nombre' => 'Otra'])->id]);
        config(['mail.default' => 'array', 'app.url' => 'https://facturas.example.test']);
        $this->assign()->assertSessionHasNoErrors()->assertSessionHas('success', fn ($s) => str_contains($s, 'Correos de prueba con PDF: 2'));
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(2, $messages);
        $addresses = [];
        foreach ($messages as $sent) {
            $message = $sent->getOriginalMessage();
            $this->assertCount(1, $message->getTo());
            $this->assertEmpty($message->getCc());
            $addresses[] = $message->getTo()[0]->getAddress();
            $this->assertCount(1, $message->getAttachments());
            $attachment = $message->getAttachments()[0];
            $this->assertSame($this->pdf, $attachment->getBody());
            $this->assertSame('pdf', $attachment->getMediaSubtype());
            $this->assertSame('factura-'.$this->invoice->id.'.pdf', $attachment->getFilename());
            $this->assertStringContainsString('Adjuntar número de operación', $message->getHtmlBody());
            $this->assertStringNotContainsString('<script>', $message->getHtmlBody());
            $this->assertStringContainsString('https://facturas.example.test/facturas/'.$this->invoice->id, $message->getHtmlBody());
        }
        $this->assertEqualsCanonicalizing(['uno@example.test', 'dos@example.test'], $addresses);
        $this->assertDatabaseCount('avisos', 2);
        $this->assertSame('por_pagar', $this->invoice->fresh()->estado);
    }

    public function test_reassignment_only_sends_to_new_area_and_stale_request_sends_nothing(): void
    {
        Mail::fake();
        $this->recipient('anterior@example.test');
        $this->assign()->assertSessionHasNoErrors();
        Mail::fake();
        $other = Area::create(['nombre' => 'Vivienda']);
        $user = User::factory()->create(['role' => 'usuario', 'area_id' => $other->id]);
        $this->assign(['area_id' => $other->id])->assertSessionHasNoErrors();
        Mail::assertSent(InvoiceAssigned::class, fn ($mail) => $mail->hasTo($user->email));
        Mail::assertSentCount(1);
        Mail::fake();
        $this->assign(['version' => 0])->assertSessionHasErrors('version');
        Mail::assertNothingSent();
    }

    public function test_partial_failure_preserves_assignment_and_attempts_other_recipients(): void
    {
        $this->recipient('fallo@example.test');
        $this->recipient('ok@example.test');
        $failed = \Mockery::mock(\Illuminate\Mail\PendingMail::class);
        $failed->shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP privado'));
        $ok = \Mockery::mock(\Illuminate\Mail\PendingMail::class);
        $ok->shouldReceive('send')->once()->withArgs(function ($mail) {
            $this->assertSame('por_pagar', $this->invoice->fresh()->estado);
            $this->assertDatabaseCount('avisos', 2);
            return $mail instanceof InvoiceAssigned;
        });
        Mail::shouldReceive('to')->with('fallo@example.test')->once()->andReturn($failed);
        Mail::shouldReceive('to')->with('ok@example.test')->once()->andReturn($ok);
        $this->followingRedirects()->assign()->assertOk()->assertSee('fallo@example.test')->assertSee('Correos de prueba con PDF: 1')->assertDontSee('SMTP privado');
        $this->assertSame('por_pagar', $this->invoice->fresh()->estado);
    }

    public function test_missing_pdf_reports_error_without_sending_or_reverting_assignment(): void
    {
        Mail::fake();
        $this->recipient('uno@example.test');
        Storage::disk('local')->delete($this->invoice->ruta_pdf);
        $this->assign()->assertSessionHasErrors('mail');
        Mail::assertNothingSent();
        $this->assertDatabaseCount('avisos', 1);
        $this->assertSame('por_pagar', $this->invoice->fresh()->estado);
    }

    public function test_unauthorized_and_empty_area_do_not_send_mail(): void
    {
        Mail::fake();
        $this->assign()->assertSessionHasErrors('area_id');
        $user = $this->recipient('uno@example.test');
        $this->actingAs($user);
        $this->assign()->assertForbidden();
        $this->assertSame('recibida', $this->invoice->fresh()->estado);
        Mail::assertNothingSent();
    }

    public function test_rollback_never_sends_email(): void
    {
        Mail::fake();
        $this->recipient('uno@example.test');
        Aviso::creating(fn () => throw new \RuntimeException('Fallo de aviso'));
        $this->withoutExceptionHandling();
        try {
            $this->assign();
            $this->fail('Se esperaba una excepción.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Fallo de aviso', $exception->getMessage());
        } finally {
            Aviso::flushEventListeners();
        }
        $this->assertSame('recibida', $this->invoice->fresh()->estado);
        $this->assertDatabaseCount('avisos', 0);
        Mail::assertNothingSent();
    }
}
