<?php

use App\Http\Controllers\AdminController;

use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

//RUTAS

//================================================================================================
        //RUTAS  AUTH

        //RUTA PARA QUE LOS USUAIOS SE LOGEEN POR EL CONTROLLADOR AUTHCONTROLLER
        Route::post('login', [AuthController::class, 'login']);

        Route::post('logout', [AuthController::class, 'logout']);

        Route::post('refresh-token', [AuthController::class, 'refreshToken']);

        Route::post('update-activity', [AuthController::class, 'updateLastActivity']);

        Route::get('listarUsuarios', [AdminController::class, 'listarUsuarios']);

        Route::get('/listarCategorias', [AdminController::class, 'listarCategorias']);

        Route::get('/listarProductos', [AdminController::class, 'listarProductos']);

        Route::post('/registerUser', [ClienteController::class, 'registerUser']);
//================================================================================================


//================================================================================================
    //RUTAS PROTEGIDAS A
    // RUTAS PARA ADMINISTRADOR VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
    Route::middleware(['auth.jwt', 'checkRoleMW:admin'])->group(function () { 
        Route::post('register', [AdminController::class, 'register']);
        Route::post('/agregarProducto', [AdminController::class, 'agregarProducto']);
        Route::put('/actualizarUsuario/{id}', [AdminController::class, 'actualizarUsuario']);
        Route::delete('/eliminarUsuario/{id}', [AdminController::class, 'eliminarUsuario']);
        Route::post('/actualizarProducto/{id}', [AdminController::class, 'actualizarProducto']);
        Route::delete('/eliminarProducto/{id}', [AdminController::class, 'eliminarProducto']);
    });

    // RUTAS PARA CLIENTE VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
    Route::middleware(['auth.jwt', 'checkRoleMW:cliente'])->group(function () {
        Route::get('perfilCliente', [ClienteController::class, 'perfilCliente']);
        Route::post('uploadProfileImageCliente/{idUsuario}', [ClienteController::class, 'uploadProfileImageCliente']);
        Route::put('updateCliente/{idUsuario}', [ClienteController::class, 'updateCliente']);
        Route::get('productos', [ClienteController::class, 'listarProductos']);
    });

//================================================================================================

