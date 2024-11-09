<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Carrito;
use App\Models\CarritoDetalle;
use Illuminate\Http\Request;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ClienteController extends Controller
{
    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:usuarios,username',
            'rol' => 'required|string',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'dni' => 'required|string',
            'correo' => 'required|email|unique:usuarios,correo',
            'telefono' => 'required|string',
            'password' => 'required|string',
            'edad' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Crear el usuario
            $user = Usuario::create([
                'username' => $request->username,
                'rol' => $request->rol,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'dni' => $request->dni,
                'correo' => $request->correo,
                'telefono' => $request->telefono,
                'password' => bcrypt($request->password),
                'edad' => $request->edad,
                'status' => 'loggedOff', // Establece el status por defecto
            ]);

            // Crear el carrito solo si el rol es cliente
            if ($request->rol === 'cliente') {
                $carrito = Carrito::create([
                    'idUsuario' => $user->idUsuario
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Usuario registrado exitosamente'], 201);

        } catch (\Exception $e) {
            Log::error("Error en el registro de usuario: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // En EstudianteController.php
    public function perfilCliente()
    {
        $usuario = Auth::user();
        $profileUrl = $usuario->perfil ? url("storage/{$usuario->perfil}") : null;

        return response()->json([
            'success' => true,
            'data' => [
                'idUsuario' => $usuario->idUsuario,
                'username' => $usuario->username,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'dni' => $usuario->dni,
                'correo' => $usuario->correo,
                'edad' => $usuario->edad,
                'nacimiento' => $usuario->nacimiento,
                'sexo' => $usuario->sexo,
                'direccion' => $usuario->direccion,
                'telefono' => $usuario->telefono,
                'departamento' => $usuario->departamento,
                'perfil' => $profileUrl,  // URL completa de la imagen de perfil
            ]
        ]);
    }

    public function uploadProfileImageCliente(Request $request, $idUsuario)
    {
        $docente = Usuario::find($idUsuario);
        if (!$docente) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Verifica si hay un archivo en la solicitud
        if ($request->hasFile('perfil')) {
            $path = "profiles/$idUsuario";

            // Si hay una imagen de perfil existente, elimínala antes de guardar la nueva
            if ($docente->perfil && Storage::disk('public')->exists($docente->perfil)) {
                Storage::disk('public')->delete($docente->perfil);
            }

            // Guarda la nueva imagen de perfil en el disco 'public'
            $filename = $request->file('perfil')->store($path, 'public');
            $docente->perfil = $filename; // Actualiza la ruta en el campo `perfil` del usuario
            $docente->save();

            return response()->json(['success' => true, 'filename' => basename($filename)]);
        }

        return response()->json(['success' => false, 'message' => 'No se cargó la imagen'], 400);
    }

    public function updateCliente(Request $request, $idUsuario)
    {
        $docente = Usuario::find($idUsuario);
        if (!$docente || $docente->rol !== 'cliente') {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $docente->update($request->only([
            'nombres', 'apellidos', 'dni', 'correo', 'edad', 'nacimiento',
            'sexo', 'direccion', 'telefono', 'departamento'
        ]));

        return response()->json(['success' => true, 'message' => 'Datos actualizados correctamente']);
    }

    // En tu controlador, por ejemplo ProductoController.php

    public function listarProductos()
    {
        // Cargar productos con la relación de categoría y obtener el nombre de la categoría
        $productos = Producto::with('categoria:idCategoria,nombreCategoria')->get();

        // Transformar los datos para incluir el nombre de la categoría en la respuesta
        $productos = $productos->map(function($producto) {
            return [
                'idProducto' => $producto->idProducto,
                'nombreProducto' => $producto->nombreProducto,
                'descripcion' => $producto->descripcion,
                'nombreCategoria' => $producto->categoria ? $producto->categoria->nombreCategoria : 'Sin Categoría',
                'precio' => $producto->precio,
                'stock' => $producto->stock,
                'imagen' => $producto->imagen,
                
            ];
        });

        return response()->json(['data' => $productos], 200);
    }

    public function agregarAlCarrito(Request $request)
    {
        $validatedData = $request->validate([
            'idProducto' => 'required|exists:productos,idProducto',
            'cantidad' => 'required|integer|min:1'
        ]);
    
        $userId = auth()->id(); // Obtén el ID del usuario autenticado
    
        try {
            // Encuentra el carrito del usuario
            $carrito = Carrito::where('idUsuario', $userId)->firstOrFail();
    
            // Crea un nuevo detalle en el carrito
            CarritoDetalle::create([
                'idCarrito' => $carrito->idCarrito,
                'idProducto' => $validatedData['idProducto'],
                'cantidad' => $validatedData['cantidad'],
                'precio' => Producto::find($validatedData['idProducto'])->precio
            ]);
    
            return response()->json(['success' => true, 'message' => 'Producto agregado al carrito'], 201);
        } catch (\Exception $e) {
            \Log::error("Error al agregar producto al carrito: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al agregar al carrito'], 500);
        }
    }


    public function listarCarrito()
    {
        try {
            $userId = Auth::id();
            Log::info("Obteniendo carrito para el usuario ID: " . $userId);

            // Obtener los productos en el carrito del usuario autenticado
            $carritoDetalles = CarritoDetalle::with('producto')
                ->whereHas('carrito', function($query) use ($userId) {
                    Log::info("Aplicando whereHas en CarritoDetalle para idUsuario: " . $userId);
                    $query->where('idUsuario', $userId);
                })
                ->get();

            Log::info("Productos en el carrito encontrados: " . $carritoDetalles->count());

            $productos = $carritoDetalles->map(function($detalle) {
                Log::info("Procesando detalle del producto ID: " . $detalle->producto->idProducto);
                return [
                    'idProducto' => $detalle->producto->idProducto,
                    'nombreProducto' => $detalle->producto->nombreProducto,
                    'descripcion' => $detalle->producto->descripcion,
                    'cantidad' => $detalle->cantidad,
                    'precio' => (float) $detalle->precio, // Asegura que sea un float
                    'subtotal' => (float) ($detalle->precio * $detalle->cantidad),
                ];
            });

            Log::info("Lista de productos procesada con éxito.");
            
            return response()->json(['success' => true, 'data' => $productos], 200);
        } catch (\Exception $e) {
            Log::error("Error al obtener el carrito: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener el carrito'], 500);
        }
    }

 
     // Actualizar la cantidad de un producto en el carrito
     public function actualizarCantidad(Request $request, $idProducto)
     {
         $userId = Auth::id();
         $cantidad = $request->input('cantidad');
 
         // Buscar el detalle del carrito que corresponde al producto y usuario autenticado
         $detalle = CarritoDetalle::whereHas('carrito', function($query) use ($userId) {
                 $query->where('carrito.idUsuario', $userId); // Cambiar `carrito.id` por `carrito.idUsuario`
             })
             ->where('idProducto', $idProducto)
             ->first();
 
         if (!$detalle) {
             return response()->json(['success' => false, 'message' => 'Producto no encontrado en el carrito'], 404);
         }
 
         // Actualizar la cantidad
         $detalle->cantidad = $cantidad;
         $detalle->save();
 
         return response()->json(['success' => true, 'message' => 'Cantidad actualizada'], 200);
     }
 
     // Eliminar un producto del carrito
     public function eliminarProducto($idProducto)
     {
         $userId = Auth::id();
 
         // Buscar el detalle del carrito que corresponde al producto y usuario autenticado
         $detalle = CarritoDetalle::whereHas('carrito', function($query) use ($userId) {
                 $query->where('carrito.idUsuario', $userId); // Cambiar `carrito.id` por `carrito.idUsuario`
             })
             ->where('idProducto', $idProducto)
             ->first();
 
         if (!$detalle) {
             return response()->json(['success' => false, 'message' => 'Producto no encontrado en el carrito'], 404);
         }
 
         // Eliminar el detalle del carrito
         $detalle->delete();
 
         return response()->json(['success' => true, 'message' => 'Producto eliminado del carrito'], 200);
     }

}
