<?php

namespace Tests\Feature;

use App\Mail\UserMessage;
use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function manager(string $role = 'jefe'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function data(): array
    {
        return ['name' => 'Nueva Cuenta', 'email' => 'nueva@example.test', 'password' => 'UnaClave!2026', 'password_confirmation' => 'UnaClave!2026', 'role' => 'usuario', 'area_id' => Area::firstOrCreate(['nombre' => 'Finanzas'])->id];
    }

    public function test_both_managers_can_list_create_and_send_to_registered_address(): void
    {
        Mail::fake();
        foreach (['jefe', 'admin'] as $role) {
            $this->actingAs($this->manager($role))->get('/usuarios')->assertOk();
            $data = $this->data();
            $data['email'] = $role.'@example.test';
            $this->post('/usuarios', $data)->assertRedirect('/usuarios')->assertSessionHasNoErrors();
            $user = User::where('email', $data['email'])->firstOrFail();
            $this->assertTrue(Hash::check($data['password'], $user->password));
            $this->assertSame('usuario', $user->role);
            $this->assertSame($data['area_id'], $user->area_id);
            $this->get('/usuarios')->assertSee($user->email)->assertDontSee($user->password);
            $this->get(route('users.mail', $user))->assertOk();
            $this->post(route('users.mail.send', $user), ['subject' => 'Aviso', 'message' => 'Revisar factura', 'email' => 'otro@example.test'])->assertSessionHasNoErrors();
            Mail::assertSent(UserMessage::class, fn ($mail) => $mail->hasTo($user->email) && ! $mail->hasTo('otro@example.test') && $mail->body === 'Revisar factura' && $mail->envelope()->subject === 'Aviso');
        }
    }

    public function test_guest_and_normal_user_cannot_access_any_management_route(): void
    {
        Mail::fake();
        $recipient = $this->manager('usuario');
        foreach ([['GET', '/usuarios'], ['POST', '/usuarios'], ['GET', route('users.mail', $recipient)], ['POST', route('users.mail.send', $recipient)]] as [$method, $url]) {
            $this->call($method, $url)->assertRedirect('/login');
        }
        $this->actingAs($recipient);
        $this->get('/')->assertDontSee('Gestión de usuarios')->assertDontSee('href="'.route('users.index').'"', false);
        $this->get('/usuarios')->assertForbidden();
        $this->post('/usuarios', $this->data())->assertForbidden();
        $this->get(route('users.mail', $recipient))->assertForbidden();
        $this->post(route('users.mail.send', $recipient), ['subject' => 'No', 'message' => 'No'])->assertForbidden();
        $this->assertDatabaseCount('users', 1);
        Mail::assertNothingSent();
    }

    public function test_validation_rejects_duplicate_email_password_role_and_area(): void
    {
        $this->actingAs($this->manager());
        $data = $this->data();
        User::factory()->create(['email' => 'NUEVA@example.test']);
        foreach (['email' => 'nueva@example.test', 'password_confirmation' => 'distinta', 'role' => 'superadmin', 'area_id' => 99999] as $field => $value) {
            $input = array_replace($data, ['email' => 'unica@example.test'], [$field => $value]);
            $this->post('/usuarios', $input)->assertSessionHasErrors($field === 'password_confirmation' ? 'password' : $field);
        }
        $this->post('/usuarios', array_replace($data, ['email' => 'correo-invalido']))->assertSessionHasErrors('email');
        $this->post('/usuarios', array_replace($data, ['password' => 'corta', 'password_confirmation' => 'corta']))->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 2);
    }

    public function test_mail_failure_is_reported_without_exposing_transport_details(): void
    {
        $user = $this->manager();
        Mail::shouldReceive('to')->with($user->email)->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('smtp-secret-detail'));
        $this->actingAs($user)->followingRedirects()->from(route('users.mail', $user))
            ->post(route('users.mail.send', $user), ['subject' => 'Aviso', 'message' => 'Texto'])
            ->assertOk()->assertSee('No se pudo confirmar')->assertDontSee('smtp-secret-detail');
    }

    public function test_mail_validation_missing_recipient_and_escaped_content(): void
    {
        Mail::fake();
        $user = $this->manager();
        $this->actingAs($user)->post(route('users.mail.send', $user), ['subject' => "Hola\r\nBcc: otro@example.test", 'message' => 'Texto'])->assertSessionHasErrors('subject');
        $this->post(route('users.mail.send', $user), ['subject' => 'Hola', 'message' => ''])->assertSessionHasErrors('message');
        $this->post('/usuarios/99999/correo', ['subject' => 'Hola', 'message' => 'Texto'])->assertNotFound();
        Mail::assertNothingSent();
        $html = (new UserMessage('<script>alert(1)</script>', '<b>Texto</b>', 'Jefe'))->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;b&gt;Texto&lt;/b&gt;', $html);
    }

    public function test_log_transport_reports_simulation(): void
    {
        config(['mail.default' => 'log']);
        $user = $this->manager();
        $this->actingAs($user)->post(route('users.mail.send', $user), ['subject' => 'Prueba local', 'message' => 'Sin entrega real'])->assertSessionHas('success', 'Correo de prueba guardado en el registro de Laravel; no se envió a una bandeja real.');
    }
}


