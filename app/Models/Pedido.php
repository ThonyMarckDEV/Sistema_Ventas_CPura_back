<?php

// app/Models/Pedido.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    public $timestamps = false;

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUsuario');
    }

    public function carrito()
    {
        return $this->belongsTo(Carrito::class, 'idCarrito');
    }

    public function pago()
    {
        return $this->hasOne(Pago::class, 'idPedido');
    }
}
