<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_pagos_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaPagos extends Migration
{
    public function up()
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id('idPago');
            $table->unsignedBigInteger('idPedido');
            $table->decimal('monto', 10, 2);
            $table->enum('metodo_pago', ['tarjeta', 'paypal', 'transferencia']);
            $table->enum('estado_pago', ['pendiente', 'completado'])->default('pendiente');

            // Clave foránea
            $table->foreign('idPedido')->references('idPedido')->on('pedidos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pagos');
    }
}
