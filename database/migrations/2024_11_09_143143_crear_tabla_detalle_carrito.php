<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_carrito_detalle_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaDetalleCarrito extends Migration
{
    public function up()
    {
        Schema::create('carrito_detalle', function (Blueprint $table) {
            $table->id('idDetalle');
            $table->unsignedBigInteger('idCarrito');
            $table->unsignedBigInteger('idProducto');
            $table->integer('cantidad');
            $table->decimal('precio', 10, 2);

            $table->foreign('idCarrito')->references('idCarrito')->on('carrito')->onDelete('cascade');
            $table->foreign('idProducto')->references('idProducto')->on('productos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('carrito_detalle');
    }
}
