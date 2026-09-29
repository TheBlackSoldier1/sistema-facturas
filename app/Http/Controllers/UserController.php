<?php

namespace App\Http\Controllers;

use App\Mail\UserMessage;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'users' => User::with('area')->orderBy('name')->orderBy('id')->paginate(20),
            'areas' => Area::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['bail', 'required', 'email', 'max:255', function ($attribute, $value, $fail) {
                if (User::whereRaw('LOWER(email) = ?', [$value])->exists()) {
                    $fail('Ya existe un usuario con ese correo.');
                }
            }],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'],
            'role' => ['required', Rule::in(['usuario', 'jefe', 'admin'])],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'email.email' => 'Introduce un correo válido.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'role.in' => 'Selecciona un rol válido.',
            'area_id.exists' => 'Selecciona un área existente.',
            'max' => 'El campo :attribute supera el largo permitido.',
        ], ['name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña', 'role' => 'rol', 'area_id' => 'área']);

        $user = new User;
        foreach ($data as $field => $value) {
            $user->{$field} = $value;
        }
        // User ya aplica el cast hashed a la contraseña.
        try {
            $user->save();
        } catch (\Illuminate\Database\UniqueConstraintViolationException $exception) {
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'Ya existe un usuario con ese correo.']);
        }

        return to_route('users.index')->with('success', 'Usuario registrado correctamente.');
    }

    public function compose(User $user)
    {
        return view('users.mail', compact('user'));
    }

    public function send(Request $request, User $user)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150', 'not_regex:/[\r\n]/'],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'max' => 'El campo :attribute supera el largo permitido.',
            'subject.not_regex' => 'El asunto debe ocupar una sola línea.',
        ], ['subject' => 'asunto', 'message' => 'mensaje']);

        try {
            // El destinatario procede del registro, nunca del formulario.
            Mail::to($user->email)->send(new UserMessage($data['subject'], $data['message'], $request->user()->name));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['mail' => 'No se pudo confirmar el envío. Revisa la configuración MAIL y el servicio de correo antes de reintentar.']);
        }

        $message = match (config('mail.default')) {
            'log' => 'Correo de prueba guardado en el registro de Laravel; no se envió a una bandeja real.',
            'array' => 'Correo simulado en memoria; no se envió a una bandeja real.',
            default => 'Correo procesado por el servicio configurado. Esto no garantiza su entrega en la bandeja del destinatario.',
        };

        return to_route('users.mail', $user)->with('success', $message);
    }
}
