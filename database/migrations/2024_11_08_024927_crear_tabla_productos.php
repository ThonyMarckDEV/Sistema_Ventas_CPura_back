<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_productos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaProductos extends Migration
{
    public function up()
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id('idProducto'); // La clave primaria es idProducto
            $table->string('nombreProducto');
            $table->text('descripcion');
            $table->decimal('precio', 8, 2);
            $table->integer('stock');
            $table->string('imagen')->nullable(); // Ruta de la imagen
        });
    }

    public function down()
    {
        Schema::dropIfExists('productos');
    }
}