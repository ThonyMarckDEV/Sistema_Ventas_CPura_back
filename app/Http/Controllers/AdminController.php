<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


class AdminController extends Controller
{
    // FUNCION PARA REGISTRAR UN USUARIO
public function register(Request $request)
{
    // Validación de los datos
    $validator = Validator::make($request->all(), [
        'username' => 'required|string|max:255|unique:usuarios',
        'rol' => 'required|string|max:255',
        'nombres' => 'required|string|max:255',
        'apellidos' => 'required|string|max:255',
        'dni' => 'required|string|size:8|unique:usuarios', // Verifica longitud exacta
        'correo' => 'required|string|email|max:255|unique:usuarios',
        'edad' => 'nullable|integer|between:0,150', // Valida edad dentro de un rango
        'nacimiento' => 'nullable|date|before:today', // Valida que la fecha de nacimiento sea anterior a hoy
        'sexo' => 'nullable|string|in:masculino,femenino,otro', // Opciones válidas de sexo
        'direccion' => 'nullable|string|max:255',
        'telefono' => 'nullable|string|max:9', // Teléfono con longitud restringida
        'departamento' => 'nullable|string|max:255',
        'password' => 'required|string|min:6|confirmed',
        'perfil' => 'nullable|string',
    ]);

    // En caso de validación fallida
    if ($validator->fails()) {
        $errors = $validator->errors();
        $messages = [
            'username' => 'El nombre de usuario ya está en uso o es inválido.',
            'dni' => 'El DNI ya está registrado o es inválido.',
            'correo' => 'El correo electrónico ya está en uso o es inválido.',
            'edad' => 'La edad ingresada no es válida.',
            'nacimiento' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'sexo' => 'El valor de sexo es inválido. Debe ser masculino, femenino u otro.',
            'telefono' => 'El teléfono ingresado es inválido.',
        ];

        $message = 'Error en la validación de los datos: ';
        foreach ($messages as $field => $errorMessage) {
            if ($errors->has($field)) {
                $message .= $errorMessage . ' ';
            }
        }

        return response()->json([
            'success' => false,
            'message' => trim($message),
            'errors' => $errors
        ], 400);
    }

    try {
        // Creación del usuario con status "loggedOff"
        $user = Usuario::create([
            'username' => $request->username,
            'rol' => $request->rol,
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'dni' => $request->dni,
            'correo' => $request->correo,
            'edad' => $request->edad,
            'nacimiento' => $request->nacimiento,
            'sexo' => $request->sexo,
            'direccion' => $request->direccion,
            'telefono' => $request->telefono,
            'departamento' => $request->departamento,
            'password' => bcrypt($request->password),
            'status' => 'loggedOff', // Establece el status por defecto
            'perfil' => $request->perfil,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente'
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al registrar el usuario',
            'error' => $e->getMessage()
        ], 500);
    }
}

    
    // Listar usuarios
    public function listarUsuarios()
    {
        $usuarios = Usuario::select('idUsuario', 'username', 'rol', 'correo')
                    ->where('rol', '!=', 'admin') // Excluir usuarios con rol "admin"
                    ->get();
        return response()->json(['success' => true, 'data' => $usuarios]);
    }

    // Eliminar usuario
    public function eliminarUsuario($id)
    {
        $usuario = Usuario::find($id);
        if ($usuario) {
            $usuario->delete();
            return response()->json(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        }
        return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
    }

    // Actualizar usuario
    public function actualizarUsuario(Request $request, $id)
    {
        $usuario = Usuario::find($id);
        if ($usuario) {
            $usuario->update($request->only('username', 'rol', 'correo'));
            return response()->json(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        }
        return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
    }


     // Obtener todas las categorías
     public function listarCategorias()
     {
         $categorias = Categoria::all();
         return response()->json(['success' => true, 'data' => $categorias], 200);
     }


// Listar todos los productos con el nombre de la categoría y URL completa de la imagen
public function listarProductos()
{
    $productos = Producto::with('categoria:idCategoria,nombreCategoria')->get();

    // Mapeo para agregar el nombre de la categoría y la URL completa de la imagen
    $productos = $productos->map(function ($producto) {
        return [
            'idProducto' => $producto->idProducto,
            'nombreProducto' => $producto->nombreProducto,
            'descripcion' => $producto->descripcion,
            'precio' => $producto->precio,
            'stock' => $producto->stock,
            'imagen' => $producto->imagen ? url("storage/{$producto->imagen}") : null, // URL completa de la imagen
            'idCategoria' => $producto->idCategoria,
            'nombreCategoria' => $producto->categoria ? $producto->categoria->nombreCategoria : null,
        ];
    });

    return response()->json(['success' => true, 'data' => $productos], 200);
}

    // Crear un nuevo producto
    public function agregarProducto(Request $request)
    {
        // Validar los datos de entrada, incluyendo el tipo de archivo de imagen
        $request->validate([
            'nombreProducto' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
            'imagen' => 'nullable|mimes:jpeg,jpg,png,gif|max:2048', // Solo formatos de imagen permitidos
            'idCategoria' => 'required|exists:categorias,idCategoria',
        ]);

        // Crear un nuevo producto sin la imagen
        $productoData = $request->except('imagen');

        // Guardar la imagen si se proporciona
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('imagenes', 'public');
            $productoData['imagen'] = $path;
        }

        // Crear el producto con los datos obtenidos
        $producto = Producto::create($productoData);

        return response()->json([
            'success' => true, 
            'message' => 'Producto creado exitosamente', 
            'data' => $producto
        ], 201);
    }

        // Actualizar un producto
        public function actualizarProducto(Request $request, $id)
        {
            // Validación de los datos entrantes, incluyendo los tipos de archivo de imagen
            $request->validate([
                'nombreProducto' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric',
                'stock' => 'required|integer',
                'imagen' => 'nullable|mimes:jpeg,jpg,png,gif|max:2048', // Solo formatos de imagen permitidos
                'idCategoria' => 'required|exists:categorias,idCategoria',
            ]);

            // Buscar el producto por ID
            $producto = Producto::findOrFail($id);

            // Procesar la nueva imagen si se proporciona
            if ($request->hasFile('imagen')) {
                // Eliminar la imagen anterior si existe
                if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                    Storage::disk('public')->delete($producto->imagen);
                }

                // Guardar la nueva imagen y actualizar la ruta en el producto
                $path = $request->file('imagen')->store('imagenes', 'public');
                $producto->imagen = $path;
            }

            // Actualizar otros campos del producto
            $producto->nombreProducto = $request->nombreProducto;
            $producto->descripcion = $request->descripcion;
            $producto->precio = $request->precio;
            $producto->stock = $request->stock;
            $producto->idCategoria = $request->idCategoria;
            
            // Guardar los cambios
            $producto->save();

            return response()->json([
                'success' => true, 
                'message' => 'Producto actualizado exitosamente', 
                'data' => $producto
            ], 200);
        }


    // Eliminar un producto
    public function eliminarProducto($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();
        return response()->json(['success' => true, 'message' => 'Producto eliminado exitosamente'], 200);
    }


    public function agregarCategoria(Request $request)
    {
        $request->validate([
            'nombreCategoria' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $categoria = Categoria::create([
            'nombreCategoria' => $request->nombreCategoria,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Categoría agregada exitosamente',
            'data' => $categoria
        ]);
    }

      // Método para actualizar una categoría
      public function actualizarCategoria(Request $request, $id)
      {
          $request->validate([
              'nombreCategoria' => 'required|string|max:255',
              'descripcion' => 'nullable|string|max:500',
          ]);
  
          $categoria = Categoria::find($id);
          if (!$categoria) {
              return response()->json([
                  'success' => false,
                  'message' => 'Categoría no encontrada'
              ], 404);
          }
  
          $categoria->update([
              'nombreCategoria' => $request->nombreCategoria,
              'descripcion' => $request->descripcion,
          ]);
  
          return response()->json([
              'success' => true,
              'message' => 'Categoría actualizada exitosamente',
              'data' => $categoria
          ]);
      }
  
      // Método para eliminar una categoría
      public function eliminarCategoria($id)
      {
          $categoria = Categoria::find($id);
          if (!$categoria) {
              return response()->json([
                  'success' => false,
                  'message' => 'Categoría no encontrada'
              ], 404);
          }
  
          $categoria->delete();
  
          return response()->json([
              'success' => true,
              'message' => 'Categoría eliminada exitosamente'
          ]);
      }
    
}
