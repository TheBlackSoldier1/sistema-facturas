<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\User;
use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoUsersSeeder::class);
    }

    public function test_guests_cannot_list_upload_or_download(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->post('/facturas')->assertRedirect('/login');
        $this->get('/facturas/1/pdf')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Iniciar sesión');
    }

    public function test_all_demo_accounts_can_login_and_logout(): void
    {
        foreach (['jefe' => 'JefeDemo!2026', 'informatica' => 'InformaticaDemo!2026', 'finanzas' => 'FinanzasDemo!2026', 'vivienda' => 'ViviendaDemo!2026'] as $name => $password) {
            $this->post('/login', ['email' => strtoupper($name).'@EXAMPLE.TEST', 'password' => $password])->assertRedirect('/');
            $this->assertAuthenticatedAs(User::where('email', $name.'@example.test')->firstOrFail());
            $this->get('/')->assertOk()->assertSee('Cerrar sesión');
            $this->get('/login')->assertRedirect('/');
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
            $this->get('/')->assertRedirect('/login');
        }
    }

    public function test_validation_and_wrong_password_never_authenticate(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
        $this->post('/login', ['email' => 'incorrecto', 'password' => 'x'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'jefe@example.test', 'password' => 'incorrecta'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertArrayNotHasKey('password', session()->getOldInput());
    }

    public function test_five_failed_attempts_block_then_expire(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'jefe@example.test', 'password' => 'incorrecta'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => 'jefe@example.test', 'password' => 'JefeDemo!2026'])->assertSessionHasErrors('email');
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/login', ['email' => 'jefe@example.test', 'password' => 'JefeDemo!2026'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_area_is_enforced_for_listing_download_and_upload(): void
    {
        Storage::fake('local');
        $finanzas = User::where('email', 'finanzas@example.test')->firstOrFail();
        $vivienda = User::where('email', 'vivienda@example.test')->firstOrFail();
        $own = Factura::create(['nombre_original' => 'finanzas.pdf', 'ruta_pdf' => 'facturas/finanzas.pdf', 'tamano_bytes' => 10, 'area_id' => $finanzas->area_id]);
        $other = Factura::create(['nombre_original' => 'vivienda.pdf', 'ruta_pdf' => 'facturas/vivienda.pdf', 'tamano_bytes' => 10, 'area_id' => $vivienda->area_id]);
        $legacy = Factura::create(['nombre_original' => 'anterior.pdf', 'ruta_pdf' => 'facturas/anterior.pdf', 'tamano_bytes' => 10]);
        $own->update(['estado' => 'por_pagar']);
        $other->update(['estado' => 'por_pagar']);
        foreach ([$own, $other, $legacy] as $factura) {
            Storage::disk('local')->put($factura->ruta_pdf, '%PDF-1.4');
        }
        $this->actingAs($finanzas)->get('/')->assertSee('finanzas.pdf')->assertDontSee('vivienda.pdf')->assertDontSee('anterior.pdf');
        $this->get(route('facturas.download', $own))->assertOk();
        $this->get(route('facturas.download', $other))->assertForbidden();
        $this->get(route('facturas.download', $legacy))->assertForbidden();
        $this->post('/facturas', [
            'pdf' => UploadedFile::fake()->createWithContent('nuevo.pdf', "%PDF-1.4\n%%EOF"),
            'area_id' => $vivienda->area_id,
            'uploaded_by' => $vivienda->id,
        ])->assertForbidden();
        $this->assertDatabaseMissing('facturas', ['nombre_original' => 'nuevo.pdf']);
        $jefe = User::where('email', 'jefe@example.test')->firstOrFail();
        $this->actingAs($jefe)->get('/')->assertSee('finanzas.pdf')->assertSee('vivienda.pdf')->assertSee('anterior.pdf');
        $this->get(route('facturas.download', $other))->assertOk();
        $this->get(route('facturas.download', $legacy))->assertOk();
    }

    public function test_user_without_area_cannot_access_documents(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertForbidden();
        $this->post('/facturas')->assertForbidden();
    }

    public function test_seed_is_repeatable_and_does_not_reset_passwords(): void
    {
        $jefe = User::where('email', 'jefe@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('JefeDemo!2026', $jefe->password));
        $jefe->password = 'OtraClave!2026';
        $jefe->save();
        $this->seed(DemoUsersSeeder::class);
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('areas', 3);
        $this->assertTrue(Hash::check('OtraClave!2026', $jefe->fresh()->password));
    }
}
