<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_categorias_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaCategorias extends Migration
{
    public function up()
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id('idCategoria');
            $table->string('nombreCategoria');
            $table->unsignedBigInteger('idProducto'); // Asegúrate de que el tipo coincida

            // Configuración de la clave foránea a `productos`
            $table->foreign('idProducto')->references('idProducto')->on('productos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('categorias');
    }
}
