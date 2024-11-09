<?php

// app/Models/Pago.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    public $timestamps = false;

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'idPedido');
    }
}
