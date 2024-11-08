<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Especialidad;
use App\Models\Grado;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\AlumnoMatriculado;
use App\Models\EspecialidadDocente;
use App\Models\AsignacionAulaDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

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
    
}
