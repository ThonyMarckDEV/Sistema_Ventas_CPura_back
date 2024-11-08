<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaArchivos extends Migration
{
    public function up()
    {
        Schema::create('archivos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 255);
            $table->string('tipo', 255);
            $table->string('ruta');
            $table->unsignedBigInteger('idModulo');
            
            $table->foreign('idModulo')->references('idModulo')->on('modulos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('archivos');
    }
}
