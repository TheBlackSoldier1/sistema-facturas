<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $fillable = [
        'nombre_original',
        'ruta_pdf',
        'tamano_bytes',
        'area_id',
        'uploaded_by',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
