<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $fillable = [
        'nombre_original',
        'ruta_pdf',
        'tamano_bytes',
    ];
}
