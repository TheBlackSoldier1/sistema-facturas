<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factura extends Model
{
    use SoftDeletes;

    protected $fillable = ['nombre_original', 'ruta_pdf', 'tamano_bytes', 'area_id', 'uploaded_by', 'estado', 'proveedor', 'folio'];

    public const ESTADOS = ['recibida' => 'Recibida', 'por_pagar' => 'Por pagar', 'en_revision' => 'En revisión', 'correccion' => 'Requiere corrección', 'confirmada' => 'Confirmada'];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }

    public function eventos()
    {
        return $this->hasMany(Evento::class)->orderByDesc('id');
    }
}
