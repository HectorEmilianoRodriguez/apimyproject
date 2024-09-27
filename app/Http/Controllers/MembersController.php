<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\JoinWorkEnvUser;
use App\Models\User;
use Svg\Tag\Rect;
use App\Models\Notifications;
use Illuminate\Support\Facades\Mail;
use App\Mail\BannedMemberMailable;
use App\Mail\InviteMemberMailable;
use App\Mail\NotFoundMemberMailable;
use Illuminate\Support\Str;
class MembersController extends Controller

{
    public function getMembers($idWorkEnv){

            $members = DB::table('rel_join_workenv_users')
            ->select(
                'users.idUser',
                'users.name',
                'users.email',
                'rel_join_workenv_users.privilege',
                'rel_join_workenv_users.created_at as date'
            )
            ->join('users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
            ->where('rel_join_workenv_users.idWorkEnv', $idWorkEnv)
            ->where('rel_join_workenv_users.logicdeleted', '!=', 1)
            ->where('rel_join_workenv_users.approbed', '=', 1)
            ->get();

        // Retornar los miembros
        return response()->json($members);
            

    }

    public function deleteMember($idUser, $nameuser, $emailmember, $idWorkEnv, $namework){
        $fechaActual = date('Y-m-d');
        $newNotification = new Notifications();
        $title = "Expulsión de un espacio";
        $newNotification->title = $title;
        $newNotification->description =  "Te han expulsado del entorno ".$namework;
        $newNotification->content = "Expulsado el: ".$fechaActual;
        $newNotification->seen = 0;
        $newNotification->logicdeleted = 0;
        $newNotification->idUser = $idUser;
        $newNotification->save();
        Mail::to($emailmember)->send(new BannedMemberMailable($namework, $nameuser));
        return JoinWorkEnvUser::where('idUser', $idUser)->where('idWorkEnv', $idWorkEnv)->update(['logicdeleted' => 1]) ? true:false;
    }

    public function updateMember($idUser, $idWorkEnv, $privilege){
        return JoinWorkEnvUser::where('idUser', $idUser)->where('idWorkEnv', $idWorkEnv)->update(['privilege' => $privilege]) ? true:false;
    }

    public function inviteMember($email, $workenv, $idwork)
    {
        // Buscar el usuario por su email
        $user = User::where('email', $email)->first();
    
        if (!$user) {
            // Si el usuario no existe, enviar un correo informando que no se encontró el miembro
            Mail::to($email)->send(new NotFoundMemberMailable($email, $workenv));
            
        }
    
        // Verificar si el usuario ya está en el entorno de trabajo
        $idu = $user->idUser;
    
        // Verificar si el usuario ya pertenece al entorno de trabajo
        $joinWorkEnvUser = JoinWorkEnvUser::where('idUser', $idu)
                                          ->where('idWorkEnv', $idwork)
                                          ->first();
    
        if ($joinWorkEnvUser) {
            return response()->json(['error' => 'error']);
        }
    
        // Generar un token aleatorio
        $token = Str::random(80);
        
        // Guardar el token en el usuario
        $user->token = $token;
        $user->save();
    
        // Enviar el correo de invitación
        Mail::to($email)->send(new InviteMemberMailable($user->name, $workenv, $token, $idwork));
    
        return response()->json(['message' => 'success']);
    }
    
    
    
    

    public function acceptInvitationMember($token, $idwork)
{
    // Verificar si el token es válido y obtener el usuario
    $user = User::where('token', $token)->first(); // Usar `first()` para obtener el modelo

    if (!$user) {
        return response()->json(['message' => 'invalid token'], 400);
    }

    // Restablecer el token
    $user->token = null;
    $user->save();

    // Unir al usuario al entorno de trabajo
    $newjoin = new JoinWorkEnvUser();
    $newjoin->idUser = $user->idUser; // Acceder al idUser directamente del modelo
    $newjoin->idWorkEnv = $idwork;
    $newjoin->logicdeleted = 0;
    $newjoin->approbed = 1;
    $newjoin->privilege = 0;
    $newjoin->save();

    return redirect()->away('https://localhost:4200/Invitation/invite');
}




}
