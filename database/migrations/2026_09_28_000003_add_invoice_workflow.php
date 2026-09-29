<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $t) {
            $t->string('estado')->default('recibida')->index();
            $t->string('proveedor')->nullable();
            $t->string('folio')->nullable();
            $t->unsignedInteger('version')->default(0);
            $t->softDeletes();
        });
        Schema::create('documentos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('factura_id')->constrained('facturas');
            $t->string('tipo');
            $t->string('nombre');
            $t->string('ruta')->unique();
            $t->unsignedBigInteger('bytes');
            $t->foreignId('user_id')->nullable()->constrained('users');
            $t->timestamps();
        });
        Schema::create('eventos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('factura_id')->constrained('facturas');
            $t->foreignId('user_id')->nullable()->constrained('users');
            $t->string('accion');
            $t->string('anterior')->nullable();
            $t->string('nuevo');
            $t->text('detalle')->nullable();
            $t->timestamps();
        });
        Schema::create('avisos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('factura_id')->constrained('facturas');
            $t->text('mensaje');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        // Los documentos existentes se incorporan sin inventar un historial previo.
        DB::table('facturas')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $f) {
                DB::table('documentos')->insert(['factura_id' => $f->id, 'tipo' => 'factura', 'nombre' => $f->nombre_original, 'ruta' => $f->ruta_pdf, 'bytes' => $f->tamano_bytes, 'user_id' => $f->uploaded_by, 'created_at' => $f->created_at, 'updated_at' => now()]);
                DB::table('eventos')->insert(['factura_id' => $f->id, 'user_id' => null, 'accion' => 'Incorporada al flujo', 'anterior' => null, 'nuevo' => 'recibida', 'detalle' => 'Documento existente. No hay registro de acciones anteriores a esta actualización.', 'created_at' => now(), 'updated_at' => now()]);
                DB::table('facturas')->where('id', $f->id)->update(['area_id' => null]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos');
        Schema::dropIfExists('eventos');
        Schema::dropIfExists('documentos');
        Schema::table('facturas', function (Blueprint $t) {
            $t->dropColumn(['estado', 'proveedor', 'folio', 'version', 'deleted_at']);
        });
    }
};
