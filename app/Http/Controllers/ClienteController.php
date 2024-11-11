<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Carrito;
use App\Models\CarritoDetalle;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Pago;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


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
            return response()->json(['success' => false, 'message' => 'Error al agregar al carrito'], 500);
        }
    }


    public function listarCarrito()
    {
        try {
            $userId = Auth::id();

            // Obtener los productos en el carrito del usuario autenticado
            $carritoDetalles = CarritoDetalle::with('producto')
                ->whereHas('carrito', function($query) use ($userId) {
                    $query->where('idUsuario', $userId);
                })
                ->get();

            $productos = $carritoDetalles->map(function($detalle) {
                return [
                    'idProducto' => $detalle->producto->idProducto,
                    'nombreProducto' => $detalle->producto->nombreProducto,
                    'descripcion' => $detalle->producto->descripcion,
                    'cantidad' => $detalle->cantidad,
                    'precio' => (float) $detalle->precio, // Asegura que sea un float
                    'subtotal' => (float) ($detalle->precio * $detalle->cantidad),
                ];
            });
            
            return response()->json(['success' => true, 'data' => $productos], 200);
        } catch (\Exception $e) {
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


     
     public function crearPedido(Request $request)
     {
         // Iniciar una transacción para asegurar la integridad de las operaciones
         DB::beginTransaction();
     
         try {
             // Validar los datos de la solicitud (sin `metodo_pago`)
             $request->validate([
                 'idUsuario' => 'required|integer',
                 'idCarrito' => 'required|integer',
                 'total' => 'required|numeric',
             ]);
     
             // Obtener los datos de la solicitud
             $idUsuario = $request->input('idUsuario');
             $idCarrito = $request->input('idCarrito');
             $total = $request->input('total');
             $estadoPedido = 'pendiente';
             $estadoPago = 'pendiente'; // Estado de pago predeterminado
     
             // Crear el pedido en la tabla 'pedidos'
             $pedidoId = DB::table('pedidos')->insertGetId([
                 'idUsuario' => $idUsuario,
                 'total' => $total,
                 'estado' => $estadoPedido,
             ]);
     
             // Obtener los detalles del carrito desde 'carrito_detalle'
             $detallesCarrito = DB::table('carrito_detalle')
                 ->where('idCarrito', $idCarrito)
                 ->get();
     
             if ($detallesCarrito->isEmpty()) {
                 throw new \Exception('El carrito está vacío.');
             }
     
             // Recorrer cada detalle del carrito para insertar en 'pedido_detalle'
             foreach ($detallesCarrito as $detalle) {
                 $producto = DB::table('productos')->where('idProducto', $detalle->idProducto)->first();
     
                 if (!$producto) {
                     throw new \Exception("Producto con ID {$detalle->idProducto} no encontrado.");
                 }
     
                 // Verificar stock suficiente
                 if ($producto->stock < $detalle->cantidad) {
                     throw new \Exception("Stock insuficiente para el producto: {$producto->nombreProducto}.");
                 }
     
                 // Calcular subtotal y guardar el detalle del pedido
                 $subtotal = $detalle->cantidad * $detalle->precio;
                 DB::table('pedido_detalle')->insert([
                     'idPedido' => $pedidoId,
                     'idProducto' => $detalle->idProducto,
                     'cantidad' => $detalle->cantidad,
                     'precioUnitario' => $detalle->precio,
                     'subtotal' => $subtotal,
                 ]);
             }
     
             // Insertar en la tabla 'pagos' sin `metodo_pago`
             DB::table('pagos')->insert([
                 'idPedido' => $pedidoId,
                 'monto' => $total,
                 'estado_pago' => $estadoPago, // Establecido en 'pendiente'
             ]);
     
             // Borrar los productos del carrito desde 'carrito_detalle'
             DB::table('carrito_detalle')
                 ->where('idCarrito', $idCarrito)
                 ->delete();
     
             // Confirmar la transacción
             DB::commit();
     
             // Devolver una respuesta exitosa
             return response()->json([
                 'success' => true,
                 'message' => 'Pedido y pago creados exitosamente.',
                 'idPedido' => $pedidoId,
             ], 201);
     
         } catch (\Exception $e) {
             DB::rollBack();
             Log::error('Error al crear pedido y pago: ' . $e->getMessage());
             return response()->json([
                 'success' => false,
                 'message' => 'Error al crear el pedido y el pago.',
                 'error' => $e->getMessage(),
             ], 500);
         }
     }
    
     
     public function showBoleta($idPedido)
     {
         // Obtener los datos del pedido
         $pedido = Pedido::find($idPedido);
 
         if (!$pedido) {
             return abort(404, 'Pedido no encontrado');
         }
 
         // Obtener los detalles del pedido desde 'pedido_detalle'
         $detalles = DB::table('pedido_detalle')
             ->where('idPedido', $idPedido)
             ->join('productos', 'pedido_detalle.idProducto', '=', 'productos.idProducto')
             ->select(
                 'productos.nombreProducto',
                 'pedido_detalle.cantidad',
                 'pedido_detalle.precioUnitario',
                 'pedido_detalle.subtotal'
             )
             ->get();
 
         $total = $pedido->total;
 
         // Obtener el usuario asociado al pedido
         $usuario = Usuario::find($pedido->idUsuario);
 
         if (!$usuario) {
             return abort(404, 'Usuario no encontrado');
         }
 
         // Concatenar nombres y apellidos
         $nombreCompleto = $usuario->nombres . ' ' . $usuario->apellidos;
 
         return view('boleta', compact('detalles', 'total', 'nombreCompleto'));
     }


     public function listarPedidos($idUsuario)
     {
         try {
             // Verificar que el idUsuario existe en la tabla 'usuarios'
             $usuarioExiste = DB::table('usuarios')->where('idUsuario', $idUsuario)->exists();
             if (!$usuarioExiste) {
                 return response()->json([
                     'success' => false,
                     'message' => 'Usuario no encontrado.',
                 ], 404);
             }
 
             // Obtener los pedidos del usuario, ordenados por 'idPedido' descendente
             $pedidos = DB::table('pedidos')
                 ->where('idUsuario', $idUsuario)
                 ->orderBy('idPedido', 'desc') // Ordenar por idPedido descendente
                 ->get();
 
             // Para cada pedido, obtener los detalles (productos)
             $pedidosConDetalles = [];
 
             foreach ($pedidos as $pedido) {
                 // Obtener los detalles del pedido desde 'pedido_detalle' y 'productos'
                 $detalles = DB::table('pedido_detalle')
                     ->where('idPedido', $pedido->idPedido)
                     ->join('productos', 'pedido_detalle.idProducto', '=', 'productos.idProducto')
                     ->select(
                         'pedido_detalle.idDetallePedido',
                         'productos.idProducto',
                         'productos.nombreProducto',
                         'pedido_detalle.cantidad',
                         'pedido_detalle.precioUnitario',
                         'pedido_detalle.subtotal'
                     )
                     ->get();
 
                 // Agregar los detalles al pedido
                 $pedidosConDetalles[] = [
                     'idPedido' => $pedido->idPedido,
                     'idUsuario' => $pedido->idUsuario,
                     'total' => $pedido->total,
                     'estado' => $pedido->estado,
                     'detalles' => $detalles,
                 ];
             }
 
             return response()->json([
                 'success' => true,
                 'pedidos' => $pedidosConDetalles,
             ], 200);
 
         } catch (\Exception $e) {
             Log::error('Error al listar pedidos: ' . $e->getMessage());
 
             return response()->json([
                 'success' => false,
                 'message' => 'Error al obtener los pedidos.',
                 'error' => $e->getMessage(),
             ], 500);
         }
     }


     public function procesarPago(Request $request, $idPedido)
     {
         DB::beginTransaction();
     
         try {
             // Obtener el pedido y verificar su existencia y estado
             $pedido = DB::table('pedidos')->where('idPedido', $idPedido)->first();
             if (!$pedido || $pedido->estado === 'pagado') {
                 return response()->json(['success' => false, 'message' => 'Error: Pedido no encontrado o ya pagado.'], 400);
             }
     
             $metodoPago = $request->input('metodo_pago');
             $rutaComprobante = null;
     
             // Verifica si hay un archivo de comprobante y si el método de pago es Yape o Plin
             if (in_array($metodoPago, ['yape', 'plin']) && $request->hasFile('comprobante')) {
                 $path = "pagos/comprobante/{$pedido->idUsuario}/{$idPedido}";
                 $rutaComprobante = $request->file('comprobante')->store($path, 'public');
             }
     
             // Inserta el pago en la tabla 'pagos'
             DB::table('pagos')->insert([
                 'idPedido' => $idPedido,
                 'monto' => $pedido->total,
                 'metodo_pago' => $metodoPago,
                 'estado_pago' => 'pendiente',
                 'ruta_comprobante' => $rutaComprobante,
             ]);
     
             // Cambiar el estado del pedido a 'aprobando'
             DB::table('pedidos')
                 ->where('idPedido', $idPedido)
                 ->update(['estado' => 'aprobando']);
     
             // Eliminar los registros en 'pedido_detalle' correspondientes al 'idPedido'
             DB::table('pedido_detalle')->where('idPedido', $idPedido)->delete();
     
             // Confirmar la transacción
             DB::commit();
             return response()->json(['success' => true, 'message' => 'Pago procesado exitosamente.', 'ruta_comprobante' => $rutaComprobante], 200);
     
         } catch (\Exception $e) {
             DB::rollBack();
             Log::error('Error al procesar el pago: ' . $e->getMessage());
             return response()->json(['success' => false, 'message' => 'Error al procesar el pago.', 'error' => $e->getMessage()], 500);
         }
     }

}
