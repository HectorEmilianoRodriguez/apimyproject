<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\VerifyMailable;
use App\Mail\RecoverMailable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;


class AuthController extends Controller
{
    
    public function register(Request $request){ //se registra un nuevo usuario sin verificar su cuenta

        
        $exists = User::where("name", $request->input('name'))->first(); //Hay que validar si ya existe ese nombre de usuario registrado previamente


        $token = Str::random(80); //generamos un token básico para que el usuario pueda verificar su cuenta vía correo.


        if($exists){ //si existe, no lo registramos y notificamos
        
            return response()->json(["message" => "ya existe ese usuario"]);
        }else{ //no existe, entonces creamos el usuario y enviamos el correo.

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')), //calculamos la función hash o de resumen en las contraseñas.
                'verified' => 0,
                'token' => $token
            ]);
            //eloquent

            Mail::to($request->input('email'))->send(new VerifyMailable($request->input('name'), $token)); //enviar correo electrónico para verificar la cuenta del usuario
        
            return response()->json(["message" => "success"]);
        }



        
    }

    public function verify($token)
    {
        $user = User::where('token', $token)->first();
    
        if ($user) {
            $user->verified = 1;
            $user->token = null;
            $user->save();
    
            // Redirige al usuario a la vista de verificación exitosa con el nombre del usuario en la URL
            return redirect()->away('http://localhost:4200/verificacion/verifica');
        }
    
        return response()->json(["message" => "El token no es válido."]);
    }
    


    public function login(Request $request)
    {
        // Verificar las credenciales
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciales inválidas']);
        }
    
        // Verificar si el usuario está verificado
        $user = User::where('email', $request->input('email'))->where('verified', 1)->first();
        if (!$user) {
            return response()->json(['message' => 'Por favor, verifica tu cuenta']);
        }
    
        // Generar el token
        $token = $user->createToken('token')->plainTextToken;
    
        // Crear la cookie de sesión
        $cookie = cookie('jwt', $token, 60 * 24, '/', null, false, true, false, 'Lax');

    
        return response()->json(['message' => 'success'])->withCookie($cookie);
    }
    
    public function user(){
                    
        if (Auth::check()) { // Verificar si hay un usuario autenticado
               return response()->json(Auth::user());
        } else {
             return response()->json(["message" => "sin sesion"]);
        }
    }



    public function logout(Request $request)
    {
        // Elimina la cookie 'jwt'
        $cookie = cookie('jwt', '', -1, '/', null, true, true, false, 'None');
    
        return response()->json(['message' => 'success'])->withCookie($cookie);
    }
    
    public function recoversent($email){ //contiene la lógica para recuperar la cuenta.

        if(!$this->exists($email)){ //verificar si  no existe el usuario.
            return response()->json(['message' => 'noexists']);
        }

        $token = $token = Str::random(80); //generamos un token para que el usuario verifique su identidad.

        $user = User::where('email', $email)->first(); //obtenemos los datos del usuario.

        $user->token = $token; //almacenamos el token.

        $name = $user->name;
        $user->save();

        Mail::to($email)->send(new RecoverMailable($token, $name)); //enviamos correo electrónico de verificación.


    }

    private function exists($email){ //verifica si existe el usuario en base al correo

        if(!User::where('email', $email)->first()){
            return false;   
        }

        return true;  

    }
    public function recover($token){

            $user = User::where('token', $token)->first();

            if ($user) {
                // Redirige al usuario a la vista de recuperación de cuenta con el token y el correo en la URL
                return redirect()->away('http://localhost:4200/resetPassword/resetPass?token=' . urlencode($token) . '&email=' . urlencode($user->email));
            }

            return response()->json(["message" => "El token no es válido."]);
    }


    public function changePassUser($token, $email, $pass){ //se utiliza para cambiar de pass a el user.
        $user = User::where('token', $token)->where('email', $email)->first();

        if(!$user){
            return response()->json(["message" => "failure"]);
        }

        $user->token = null; //eliminar token
        $user->password = Hash::make($pass); //se actualiza a una nueva password.

        $user->save();

        return response()->json(["message" => "success"]);
    }

 

    public function updateUser(Request $request)
    {
        \Log::info('Iniciando actualización de usuario');
        \Log::info('Datos recibidos:', $request->all());
        \Log::info('Archivos recibidos:', $request->allFiles());
    
        $request->validate([
            'userName' => 'required|string|max:255',
            'password' => 'nullable|string|min:6',
            'photo' => 'nullable|image|max:2048', // max 2MB
        ]);
    
        // Obtener el usuario autenticado
        $user = $request->user();
        \Log::info('Usuario autenticado:', ['id' => $user->id, 'email' => $user->email]);
    
        // Actualizar el nombre del usuario
        $user->name = $request->input('userName');
        \Log::info('Nombre actualizado:', ['name' => $user->name]);
    
        // Actualizar la contraseña si está presente
        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
            \Log::info('Contraseña actualizada');
        }
    
        // Verificar si hay una nueva foto en el request
        if ($request->hasFile('photo')) {
            \Log::info('Foto recibida');
            try {
                // Si el usuario tiene una foto guardada previamente, eliminarla
                if ($user->photo) {
                    \Log::info('Intentando eliminar foto anterior:', ['path' => $user->photo]);
                    if (Storage::disk('private')->exists($user->photo)) {
                        Storage::disk('private')->delete($user->photo);
                        \Log::info('Foto anterior eliminada');
                    } else {
                        \Log::warning('Foto anterior no encontrada en el almacenamiento');
                    }
                }
                
                // Guardar la nueva foto
                $photo = $request->file('photo');
                \Log::info('Información de la nueva foto:', [
                    'name' => $photo->getClientOriginalName(),
                    'size' => $photo->getSize(),
                    'mime' => $photo->getMimeType()
                ]);
                
                $photoPath = $photo->store('photos', 'private');
                \Log::info('Nueva foto guardada en:', ['path' => $photoPath]);
                
                $user->photo = $photoPath; // Actualizar la ruta en la base de datos
            } catch (\Exception $e) {
                \Log::error('Error al procesar la foto:', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'No se pudo guardar la foto: ' . $e->getMessage()], 500);
            }
        } else {
            \Log::info('No se recibió ninguna foto nueva');
        }
    
        // Guardar los cambios en la base de datos
        try {
            $user->save();
            \Log::info('Cambios guardados en la base de datos');
        } catch (\Exception $e) {
            \Log::error('Error al guardar los cambios en la base de datos:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'No se pudieron guardar los cambios: ' . $e->getMessage()], 500);
        }
    
        \Log::info('Actualización de usuario completada con éxito');
        return response()->json(['message' => 'Perfil actualizado correctamente']);
    }
    
public function getUserPhoto(Request $request)
{
    $user = $request->user();
    \Log::info('Obteniendo foto para el usuario: ' . $user->id);
    \Log::info('Ruta de la foto: ' . $user->photo);



    if (!$user->photo) {
        \Log::info('El usuario no tiene foto');
        return response()->json(['error' => 'No photo uploaded'], 404);
    }
    
    $photoPath = storage_path('app/private/' . $user->photo);

    if (file_exists($photoPath)) {
        \Log::info('Foto encontrada, devolviendo archivo');
       
        return response()->file($photoPath);
    } else {
        \Log::error('User photo not found: ' . $photoPath);
       
        return response()->json(['error' => 'Image does not exist'], 404);
    }
}
    

}
