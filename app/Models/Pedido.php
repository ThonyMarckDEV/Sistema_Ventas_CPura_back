<?php

// app/Models/Pedido.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;
    protected $primaryKey = 'idPedido';

    protected $table = 'pedidos';

    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'total',
        'estado',
    ];

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

    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class, 'idPedido', 'idPedido');
    }

}
