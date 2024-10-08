<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkEnv;
use App\Models\JoinWorkEnvUser;
use App\Models\Card;
use App\Models\User;
use App\Models\Notifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApprobedMailable;
use App\Mail\NotApprobedMailable;
use App\Mail\RequestWorkEnvMailable;
use Illuminate\Notifications\Notification;
use Termwind\Components\Raw;

class WorkEnvController extends Controller
{

    public function getAlmostExpiredActivities(){
            // Obtener el ID del usuario actual
    $currentUserId = Auth::id();

    // Consultar el privilegio del usuario actual
    $userPrivilege = DB::table('rel_join_workenv_users')
        ->where('idUser', $currentUserId)
        ->value('privilege');

    // Verificar si el privilegio es 1 o 2
    if (!in_array($userPrivilege, [1, 2])) {
        return response()->json(['message' => 'notLeader or Coordinator']);
    }

    // Obtener los IDs de los entornos de trabajo en los que participa el usuario y tiene privilegio 1 o 2
    $workEnvIds = DB::table('rel_join_workenv_users')
        ->where('idUser', $currentUserId)
        ->whereIn('privilege', [1, 2])
        ->pluck('idWorkEnv');

    // Verificar que el usuario participe en al menos un entorno de trabajo
    if ($workEnvIds->isEmpty()) {
        return response()->json(['message' => 'this user is not on any workenv yet']);
    }

    // Definir las fechas para las condiciones
    $today = Carbon::now();
    $oneWeekLater = $today->copy()->addDays(7);

    // Realizar la consulta en Eloquent para obtener las tarjetas que expiran en 7 días o que ya expiraron
    $cards = Card::select(
            'cat_cards.nameC',
            'cat_workenvs.nameW',
            'cat_boards.nameB',
            'cat_workenvs.idWorkEnv',
            'cat_cards.idCard',
            'cat_lists.idList',
            'cat_boards.idBoard'
        )
        ->join('cat_lists', 'cat_cards.idList', '=', 'cat_lists.idList')
        ->join('cat_boards', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_workenvs', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->where('cat_workenvs.logicdeleted', "!=", 1)
        ->whereIn('cat_workenvs.idWorkEnv', $workEnvIds)
        ->where(function ($query) use ($today, $oneWeekLater) {
            $query->whereBetween('cat_cards.end_date', [$today, $oneWeekLater])
                ->orWhere('cat_cards.end_date', '<', $today);
        })
        ->get();

    // Retornar los resultados
    return response()->json($cards);
}
public function CountMyWorkEnvs()
{
    $currentUser = Auth::id();

    // Contar los entornos donde el usuario es dueño (privilege = 2) y no han sido archivados
    $countOwner = JoinWorkEnvUser::join('cat_workenvs', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
        ->where('rel_join_workenv_users.idUser', $currentUser)
        ->where('rel_join_workenv_users.privilege', 2)
        ->where('cat_workenvs.logicdeleted', '!=', 1)
        ->where('rel_join_workenv_users.approbed', '=', 1)
        ->count();

    // Contar los entornos donde el usuario es participante (privilege != 2) y no han sido archivados
    $countParticipant = JoinWorkEnvUser::join('cat_workenvs', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
        ->where('rel_join_workenv_users.idUser', $currentUser)
        ->where('rel_join_workenv_users.privilege', '!=', 2)
        ->where('cat_workenvs.logicdeleted', '!=', 1)
        ->where('rel_join_workenv_users.approbed', '=', 1)
        ->count();

    return response()->json([
        "owner" => $countOwner,
        "participant" => $countParticipant
    ]);
}

public function getAllStatsUser() {
    // Obtener el ID del usuario actual
    $currentUserId = Auth::id();

    // Consultar el privilegio del usuario actual
    $userPrivilege = DB::table('rel_join_workenv_users')
        ->where('idUser', $currentUserId)
        ->value('privilege');

    // Verificar si el privilegio es 1 o 2
    if (!in_array($userPrivilege, [1, 2])) {
        return response()->json(['message' => 'notLeader or Coordinator']);
    }

    // Obtener los entornos de trabajo en los que participa el usuario y tiene privilege 1 o 2
    $workEnvIds = DB::table('rel_join_workenv_users')
        ->where('idUser', $currentUserId)
        ->whereIn('privilege', [1, 2])
        ->pluck('idWorkEnv');

    // Verificar que el usuario participe en al menos un entorno de trabajo
    if ($workEnvIds->isEmpty()) {
        return response()->json(['message' => 'this user is not on any workenv yet']);
    }

    // Realizar la consulta para sumar los comentarios no vistos, solicitudes pendientes, actividades expiradas,
    // actividades a punto de expirar, y actividades por evaluar de todos los entornos de trabajo
    $results = DB::table('cat_workenvs')
        ->select(
            DB::raw('
                SUM(CASE WHEN cat_comments.seen = 0 THEN 1 ELSE 0 END) AS NotSeenComments
            '),
            DB::raw('
                SUM(CASE WHEN rel_join_workenv_users.approbed = 0 THEN 1 ELSE 0 END) AS requests
            '),
            DB::raw('
                SUM(CASE 
                    WHEN TIMESTAMPDIFF(DAY, cat_cards.end_date, NOW()) <= 7 
                         AND TIMESTAMPDIFF(DAY, cat_cards.end_date, NOW()) >= 0
                    OR cat_cards.end_date < NOW()
                    THEN 1 ELSE 0 
                END) AS AlmostExpiredOrExpiredActivities
            '),
            DB::raw('
                SUM(CASE 
                    WHEN cat_cards.done = 1 AND cat_cards.approbed = 0 THEN 1 ELSE 0 
                END) AS PendingApprovalActivities
            ')
        )
        ->leftJoin('rel_join_workenv_users', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
        ->leftJoin('cat_comments', 'rel_join_workenv_users.idJoinUserWork', '=', 'cat_comments.idJoinUserWork')
        ->leftJoin('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->leftJoin('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->leftJoin('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->where('rel_join_workenv_users.logicdeleted', '!=', 1)
        ->whereIn('cat_workenvs.idWorkEnv', $workEnvIds)
        ->where('cat_workenvs.logicdeleted', "!=", 1)
        ->first();  // Usar first() en lugar de get() para obtener una única fila con los totales

    // Devolver los resultados
    return response()->json($results);
}


    public function newWorkEnv(Request $request){

        $workenv = new WorkEnv();
        $workenv->nameW = $request->input('nameW');
        $workenv->descriptionW = $request->input('descriptionW');
        $workenv->type = $request->input('type');
        $workenv->date_start = $request->input('date_start');
        $workenv->date_end = $request->input('date_end');
        $workenv->logicdeleted = 0;
        

        if(WorkEnv::where('nameW', $request->input('nameW'))->first()){

          return response()->json(['error' => 'there is already another workenv called']);
        }


        $workenv->save();

        $idWorkEnv = WorkEnv::select('idWorkEnv')->where('nameW', $request->input('nameW'))->first();

        $idWorkEnvv = $idWorkEnv['idWorkEnv'];

        $joinwork = new JoinWorkEnvUser();

        $userid = Auth::id();

        $joinwork->idWorkEnv = $idWorkEnvv;
        $joinwork->idUser = $userid;
        $joinwork->approbed = 1;
        $joinwork->privilege = 2;
        $joinwork->logicdeleted = 0;

        $joinwork->save();

        return response()->json(['message' => 'ok']);

    }

    public function  updateWorkEnv(Request $request){


        if(!WorkEnv::find($request->input('idWorkEnv'))){
            return response()->json(['message' => 'workenv not found']);
        }

        $workenv = WorkEnv::find($request->input('idWorkEnv'));

        $workenv->nameW = $request->input('nameW');
        $workenv->descriptionW = $request->input('descriptionW');
        $workenv->type = $request->input('type');
        $workenv->date_start = $request->input('date_start');
        $workenv->date_end = $request->input('date_end');
        $workenv->logicdeleted = $request->input(0);

        $workenv->save();
        return response()->json(['message' => 'ok']);

    }

    public function deleteWorkEnv($idWorkEnv){

        if(WorkEnv::where('idWorkEnv', $idWorkEnv)->update(['logicdeleted' => 1])){
            return response()->json(['message' => 'ok']);
        }
        return response()->json(['error' => 'workenv not found']);
    }

    public function undeleteWorkEnv($idWorkEnv){
        if(WorkEnv::where('idWorkEnv', $idWorkEnv)->update(['logicdeleted' => 0])){
            return response()->json(['message' => 'ok']);
        }
        return response()->json(['error' => 'workenv not found']);
    }

    
    public function getMyWorkEnvs(){
        $idUser = Auth::id();

        
        // Obtener entornos donde privilege es igual a 2
        $results = WorkEnv::select('cat_workenvs.nameW as title', 'cat_workenvs.type', 'cat_workenvs.descriptionW', 'cat_workenvs.date_start', 'cat_workenvs.date_end', 'rel_join_workenv_users.privilege', 'cat_workenvs.idWorkEnv')
        ->join('rel_join_workenv_users', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
        ->where('rel_join_workenv_users.idUser', $idUser)
        ->where('rel_join_workenv_users.privilege', 2)
        ->where('rel_join_workenv_users.approbed', 1)
        ->where('rel_join_workenv_users.logicdeleted', '!=', 1) 
        ->where('cat_workenvs.logicdeleted', "!=", 1)
        ->get();

        // Obtener entornos donde privilege es diferente de 2
        $results2 = WorkEnv::select('cat_workenvs.nameW as title', 'cat_workenvs.type', 'cat_workenvs.descriptionW', 'cat_workenvs.date_start', 'cat_workenvs.date_end', 'rel_join_workenv_users.privilege', 'cat_workenvs.idWorkEnv')
            ->join('rel_join_workenv_users', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
            ->where('rel_join_workenv_users.idUser', $idUser)
            ->where('cat_workenvs.logicdeleted', "!=", 1)
            ->where('rel_join_workenv_users.approbed', 1)
            ->where('rel_join_workenv_users.privilege', '!=', 2) // Filtrar donde privilege no es igual a 2
            ->where('rel_join_workenv_users.logicdeleted', '!=', 1) 
            ->get();

        return ['owner' => $results, 'participant' => $results2];

    }

    public function getMyArchivedWorkEnvs(){
        $idUser = Auth::id();

        
        // Obtener entornos donde privilege es igual a 2
        $results = WorkEnv::select('cat_workenvs.nameW as title', 'cat_workenvs.type', 'cat_workenvs.descriptionW', 'cat_workenvs.date_start', 'cat_workenvs.date_end', 'rel_join_workenv_users.privilege', 'cat_workenvs.idWorkEnv')
        ->join('rel_join_workenv_users', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
        ->where('rel_join_workenv_users.idUser', $idUser)
        ->where('rel_join_workenv_users.privilege', 2)
        ->where('rel_join_workenv_users.approbed', 1)
        ->where('rel_join_workenv_users.logicdeleted', '!=', 1) 
        ->where('cat_workenvs.logicdeleted', 1)
        ->get();


        return response()->json($results);

    }


    public function AmIOnWorkEnv($idWorkEnv)
    {
        $idUser = Auth::id();
    
        // Realiza la consulta para verificar si el usuario está en el entorno de trabajo
        $result = WorkEnv::select('cat_workenvs.nameW as title', 'cat_workenvs.type', 'cat_workenvs.descriptionW', 'cat_workenvs.date_start', 'cat_workenvs.date_end', 'rel_join_workenv_users.privilege', 'cat_workenvs.idWorkEnv', 'cat_workenvs.logicdeleted', 'rel_join_workenv_users.idJoinUserWork')
            ->join('rel_join_workenv_users', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
            ->where('rel_join_workenv_users.idUser', $idUser)
            ->where('cat_workenvs.idWorkEnv', $idWorkEnv)
            ->where('rel_join_workenv_users.approbed', '=', 1)
            ->where('rel_join_workenv_users.logicdeleted', '!=', 1)
            ->first();
    
        // Si el resultado es null, significa que el usuario no está en ese entorno de trabajo
        if (!$result) {
            return response()->json(["error" => "you are not in this workenv"], 403); // Código de estado 403 Forbidden
        }
    
        // Si el usuario está en el entorno, retorna el resultado
        return response()->json($result);
    }
    

    public function getWorkEnvOwner($idWorkEnv){

        $idUser = JoinWorkEnvUser::select('idUser')->where('idWorkEnv', $idWorkEnv)->where('privilege', 2)->first();

        $idUser = $idUser['idUser'];

        $owner = User::where('idUser', $idUser)->first();
        return response()->json($owner);

    }

    public function getNotApprobedActivities(){

                // Obtener el ID del usuario actual
        $currentUserId = Auth::id();

        // Consultar el privilegio del usuario actual
        $userPrivilege = DB::table('rel_join_workenv_users')
            ->where('idUser', $currentUserId)
            ->value('privilege');

        // Verificar si el privilegio es 1 o 2
        if (!in_array($userPrivilege, [1, 2])) {
            return response()->json(['message' => 'notLeader or Coordinator']);
        }

        // Obtener los IDs de los entornos de trabajo en los que participa el usuario y tiene privilegio 1 o 2
        $workEnvIds = DB::table('rel_join_workenv_users')
            ->where('idUser', $currentUserId)
            ->whereIn('privilege', [1, 2])
            ->pluck('idWorkEnv');

        // Verificar que el usuario participe en al menos un entorno de trabajo
        if ($workEnvIds->isEmpty()) {
            return response()->json([ ]);
        }

        // Realizar la consulta en Eloquent para obtener las tarjetas con 'approbed' = 0 y 'done' = 1
        $cards = Card::select(
                'cat_cards.nameC',
                'cat_workenvs.nameW',
                'cat_boards.nameB',
                'cat_workenvs.idWorkEnv',
                'cat_cards.idCard',
                'cat_lists.idList',
                'cat_boards.idBoard'
            )
            ->join('cat_lists', 'cat_cards.idList', '=', 'cat_lists.idList')
            ->join('cat_boards', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
            ->join('cat_workenvs', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
            ->where('cat_workenvs.logicdeleted', "!=", 1)
            ->whereIn('cat_workenvs.idWorkEnv', $workEnvIds)
            ->where('cat_cards.approbed', 0)
            ->where('cat_cards.done', 1)
            ->get();

        // Retornar los resultados
        return response()->json($cards);


    }


    public function getNotSeenComments(){
        // Obtener el ID del usuario actual
        $currentUserId = Auth::id();
    
        // Consultar los entornos de trabajo donde el usuario tiene privilegio 1 o 2
        $workEnvIds = DB::table('rel_join_workenv_users')
            ->where('idUser', $currentUserId)
            ->whereIn('privilege', [1, 2])
            ->where('logicdeleted', '!=', 1) // Excluir registros eliminados
            ->pluck('idWorkEnv');
    
        // Verificar que el usuario participe en al menos un entorno de trabajo
        if ($workEnvIds->isEmpty()) {
            return response()->json(['message' => 'This user is not in any work environment with required privileges.']);
        }
    
        // Realizar la consulta en Eloquent
        $comments = Card::select(
                'cat_comments.idComment',       // ID del comentario
                'cat_cards.idCard',             // ID de la tarjeta
                'rel_join_workenv_users.idJoinUserWork', // ID del usuario relacionado al comentario
                'cat_cards.nameC',              // Nombre de la tarjeta
                'cat_workenvs.nameW',           // Nombre del entorno de trabajo
                'cat_boards.nameB',             // Nombre del tablero
                'users.name', // Nombre del usuario que hizo el comentario
                'cat_comments.text',            // Texto del comentario
                'cat_comments.seen'             // Estado del comentario (visto o no)
            )
            ->distinct()  // Evitar duplicados
            ->join('cat_lists', 'cat_cards.idList', '=', 'cat_lists.idList')
            ->join('cat_boards', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
            ->join('cat_workenvs', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
            ->join('cat_comments', 'cat_comments.idCard', '=', 'cat_cards.idCard')
            ->join('rel_join_workenv_users', function ($join) {
                $join->on('rel_join_workenv_users.idJoinUserWork', '=', 'cat_comments.idJoinUserWork')
                     ->on('rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv'); // Asegurar que el comentario está relacionado al entorno correcto
            })
            ->join('users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
            ->where('cat_comments.seen', 0) // Solo comentarios no vistos
            ->where('cat_workenvs.logicdeleted', "!=", 1)
            ->whereIn('cat_workenvs.idWorkEnv', $workEnvIds) // Solo los entornos de trabajo del usuario
            ->where('rel_join_workenv_users.logicdeleted', '!=', 1) // Excluir usuarios eliminados lógicamente
            ->get();
    
        // Retornar los resultados
        return response()->json($comments);
    }
    
    

    public function getPendingApprovals()
{
    // Obtener el ID del usuario actual
    $currentUserId = Auth::id();
    
    // Obtener los entornos de trabajo en los que participa el usuario y tiene privilegio 1 o 2
    $workEnvIds = DB::table('rel_join_workenv_users')
        ->where('idUser', $currentUserId)
        ->whereIn('privilege', [1, 2])
        ->pluck('idWorkEnv');
    
    
    // Crear la consulta en Eloquent
    $results = DB::table('rel_join_workenv_users')
        ->join('users', 'rel_join_workenv_users.idUser', '=', 'users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->select(
            'cat_workenvs.idWorkEnv',
            'users.name',
            'users.idUser',
            'users.photo', // Incluimos el campo de la foto
            'cat_workenvs.nameW',
            'rel_join_workenv_users.idJoinUserWork',
            'cat_workenvs.type'
        )
        ->where('rel_join_workenv_users.approbed', 0)
        ->where('cat_workenvs.logicdeleted', "!=", 1)
        ->where('rel_join_workenv_users.logicdeleted', '!=', 1) 
        ->whereIn('rel_join_workenv_users.idWorkEnv', $workEnvIds) // Filtrar por los entornos en los que el usuario tiene privilegio 1 o 2
        ->get();

   
    return response()->json($results);
}



public function getPossibleRequests()
{
    // Obtener el ID del usuario actual
    $currentUserId = Auth::id();

    // Obtener los IDs de los entornos de trabajo en los que el usuario participa
    $workEnvIds = DB::table('rel_join_workenv_users')
        ->where('idUser', $currentUserId)
        ->pluck('idWorkEnv')
        ->toArray();  // Convertir a array para facilitar la comparación
    
    // Verificar si el usuario participa en al menos un entorno
    $participatesInAnyWorkEnv = !empty($workEnvIds);

    // Crear la consulta en Eloquent
    $query = DB::table('cat_workenvs')
        ->leftJoin('rel_join_workenv_users', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
        ->leftJoin('users', 'rel_join_workenv_users.idUser', '=', 'users.idUser')
        ->select(
            'users.name',
            'users.email',  // Project Manager
            'cat_workenvs.nameW',
            'cat_workenvs.date_start',
            'cat_workenvs.date_end',
            'cat_workenvs.idWorkEnv',
            'users.idUser',
            DB::raw('COUNT(rel_join_workenv_users.idUser) as Miembros')
        )
        ->where('rel_join_workenv_users.privilege', 2) // Incluir solo entornos donde privilege es 2
        ->where('cat_workenvs.logicdeleted', "!=", 1)
        ->where('rel_join_workenv_users.logicdeleted', '!=', 1) // Asegurar que no esté marcado como eliminado
        ->groupBy('cat_workenvs.idWorkEnv', 'users.name', 'users.email', 'cat_workenvs.nameW', 'cat_workenvs.date_start', 'cat_workenvs.date_end', 'users.idUser') // Asegurar que todos los campos están en el grupo
        ->when($participatesInAnyWorkEnv, function($query) use ($workEnvIds) {
            return $query->whereNotIn('cat_workenvs.idWorkEnv', $workEnvIds); // Filtrar por entornos donde el usuario no participa
        });

    // Ejecutar la consulta y retornar los resultados como respuesta JSON
    $results = $query->get();
    return response()->json($results);
}

    public function joinOnWorkEnv($idWorkEnv){

        // Obtener el ID del usuario actual
        $currentUserId = Auth::id();

        if(JoinWorkEnvUser::where('idWorkEnv', $idWorkEnv)->where('idUser', $currentUserId)->first()){
            return response()->json(['message' => 'this user is already on this workenv']);
        }
        $JoinWorkEnv = new JoinWorkEnvUser();

        $JoinWorkEnv->approbed = 0;
        $JoinWorkEnv->logicdeleted = 0;
        $JoinWorkEnv->privilege = 0;
        $JoinWorkEnv->idWorkEnv = $idWorkEnv;
        $JoinWorkEnv->idUser = $currentUserId;

        $JoinWorkEnv->save();

        return response()->json(['message' => 'success']);

    }

        public function searchRequests($text)
    {
        // Obtener el ID del usuario actual
        $currentUserId = Auth::id();

        // Obtener los IDs de los entornos de trabajo en los que el usuario participa
        $workEnvIds = DB::table('rel_join_workenv_users')
            ->where('idUser', $currentUserId)
            ->pluck('idWorkEnv');

        // Crear la consulta en Eloquent para obtener los entornos donde NO participa el usuario y el privilege es 2
        $results = DB::table('cat_workenvs')
            ->leftJoin('rel_join_workenv_users', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
            ->leftJoin('users', 'rel_join_workenv_users.idUser', '=', 'users.idUser')
            ->select(
                'users.name',
                'users.email',  // Project Manager
                'cat_workenvs.nameW',
                'cat_workenvs.date_start',
                'cat_workenvs.date_end',
                'cat_workenvs.idWorkEnv',
                'users.idUser',
                DB::raw('COUNT(rel_join_workenv_users.idUser) as Miembros')
            )
            ->whereNotIn('cat_workenvs.idWorkEnv', $workEnvIds) // Filtrar por entornos donde el usuario no participa
            ->where('rel_join_workenv_users.privilege', 2) // Incluir solo entornos donde privilege es 2
            ->where('rel_join_workenv_users.logicdeleted', '!=', 1)
            ->where('cat_workenvs.logicdeleted', "!=", 1) // Asegurar que no esté marcado como eliminado
            ->where(function($query) use ($text) {
                $query->where('cat_workenvs.nameW', 'like', '%' . $text . '%') // Filtrar por nameW
                    ->orWhere('users.email', 'like', '%' . $text . '%') // Filtrar por email
                    ->orWhere('cat_workenvs.date_start', 'like', '%' . $text . '%') // Filtrar por date_start
                    ->orWhere('cat_workenvs.date_end', 'like', '%' . $text . '%') // Filtrar por date_end
                    ->orWhere('users.name', 'like', '%' . $text . '%'); // Filtrar por name


            })
            ->groupBy('cat_workenvs.idWorkEnv')  // Agrupar por entorno de trabajo
            ->get();

        // Retornar los resultados como respuesta JSON
        return response()->json($results);
    }

    public function getPhoto(Request $request)
    {
        $filename = $request->input('filename');

        // Definir la ruta completa del archivo
        $photoPath = storage_path('app/private/' . $filename);

        // Verificar si el archivo existe y devolverlo
        if (file_exists($photoPath)) {
            return response()->file($photoPath);
        } else {
            return response()->json(["error" => 'Image does not exist'], 404);
        }
    }

    public function approbeRequestWorkEnv($idUser, $idWorkEnv){
        
        if(JoinWorkEnvUser::where('idWorkEnv', $idWorkEnv)->where('idUser', $idUser)
        ->update(['approbed' => 1])){

            return response()->json(['success' => 'updated']);
        }else{
            return response()->json(['error' => 'not found'], 404);

        }
           
    }

    public function notapprobeRequestWorkEnv($idJoinUserWork){
            
        if(JoinWorkEnvUser::where('idJoinUserWork', $idJoinUserWork)
        ->update(['logicdeleted' => 1])){

            return response()->json(['success' => 'deleted']);
        }else{
            return response()->json(['error' => 'not found'], 404);

        }
        
    }

    public function getPendingApprovalsSearch($searchTerm)
    {
        // Obtener el ID del usuario actual
        $currentUserId = Auth::id();
        
        // Consultar el privilegio del usuario actual
        $userPrivilege = DB::table('rel_join_workenv_users')
            ->where('idUser', $currentUserId)
            ->value('privilege');
        
        // Verificar si el privilegio es 1 o 2
        if (!in_array($userPrivilege, [1, 2])) {
            return response()->json(['message' => 'notLeader or Coordinator']);
        }
        
        // Obtener los entornos de trabajo en los que participa el usuario y tiene privilegio 1 o 2
        $workEnvIds = DB::table('rel_join_workenv_users')
            ->where('idUser', $currentUserId)
            ->whereIn('privilege', [1, 2])
            ->pluck('idWorkEnv');
        
        // Verificar que el usuario participe en al menos un entorno de trabajo
        if ($workEnvIds->isEmpty()) {
            return response()->json(['message' => 'this user is not on any workenv yet']);
        }
    //
        // Crear la consulta en Eloquent con un WHERE LIKE para la búsqueda en los campos 'nameW' y 'name'
        $results = DB::table('rel_join_workenv_users')
            ->join('users', 'rel_join_workenv_users.idUser', '=', 'users.idUser')
            ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
            ->select(
                'cat_workenvs.idWorkEnv',
                'users.name',
                'users.idUser',
                'users.photo', // Incluimos el campo de la foto
                'cat_workenvs.nameW',
                'rel_join_workenv_users.idJoinUserWork',
                'cat_workenvs.type'
            )
            ->where('rel_join_workenv_users.approbed', 0)
            ->where('rel_join_workenv_users.logicdeleted', '!=', 1) 
            ->where('cat_workenvs.logicdeleted', "!=", 1)
            ->whereIn('rel_join_workenv_users.idWorkEnv', $workEnvIds) // Filtrar por los entornos en los que el usuario tiene privilegio 1 o 2
            ->where(function($query) use ($searchTerm) {
                $query->where('users.name', 'LIKE','%' .  $searchTerm . '%')
                      ->orWhere('cat_workenvs.nameW', 'LIKE', '%' . $searchTerm . '%');
            }) // Filtro LIKE para 'name' o 'nameW' que comiencen con el término de búsqueda
            ->get();
    
        return response()->json($results);
    }
    
    public function NotifyUserApprobedOrNot($workenv, $idUser, $flag){

        $owner = Auth::user()->name;
        $member = User::find($idUser)->name;
        $emailmember = User::find($idUser)->email;
        $newNotification = new Notifications();
        $fechaActual = date('Y-m-d');
        if(!User::find($idUser)->name){
            return response()->json(['error' => 'user not found']);

        }
        if($flag==1){
            $title = "Solicitud de unión aceptada";
            $newNotification->title = $title;
            $newNotification->description =  "El usuario ".$owner. " ha aceptado tu solicitud al entorno ".$workenv;
            $newNotification->content = "Aceptado en: ".$fechaActual;
            Mail::to($emailmember)->send(new ApprobedMailable($member, $workenv, $owner));
        }else{
            $title = "Solicitud de unión rechazada"; 
            $newNotification->title = $title;
            $newNotification->description =  "El usuario ".$owner. " ha rechazado tu solicitud al entorno ".$workenv;
            $newNotification->content = "Rechazado en: ".$fechaActual;
            Mail::to($emailmember)->send(new NotApprobedMailable($member, $workenv, $owner));

        }

        $newNotification->seen = 0;
        $newNotification->logicdeleted = 0;
        $newNotification->idUser = $idUser;
        $newNotification->save();

        return response()->json(['success' => 'ok']);

    }
    public function NotifyUserNewRequest($workenv, $idUser){

        $member = Auth::user()->name;
        $owner = User::find($idUser)->name;
        $emailowner = User::find($idUser)->email;
        $newNotification = new Notifications();
        $fechaActual = date('Y-m-d');
        if(!User::find($idUser)->name){
            return response()->json(['error' => 'user not found']);

        }
       
    
        Mail::to($emailowner)->send(new RequestWorkEnvMailable($workenv, $owner, $member));
        $newNotification->title = "Solicitud de unión al entorno ".$workenv;
        $newNotification->description = "El usuario ".$member." desea unirse al entorno ".$workenv;
        $newNotification->content = "Solicitud enviada el: ". $fechaActual;
        $newNotification->seen = 0;
        $newNotification->logicdeleted = 0;
        $newNotification->idUser = $idUser;
        $newNotification->save();

        return response()->json(['success' => 'ok']);

    }

    public function getNotifications(){
        $iduser = Auth::id();
        return response()->json(Notifications::all()->where('idUser', $iduser)->where('logicdeleted', '!=', 1)->where('seen', '!=', 1));
    }  

    public function setSeenNotificationn($idNoti){
        $iduser = Auth::id();
        if(Notifications::where('idUser', $iduser)->where('idNotification',$idNoti)->update(['seen' => 1])){
            return response()->json(['message' => 'ok']);
        }
    }

    public function countMyNotis(){
        $iduser = Auth::id();
        $total = Notifications::where('idUser', $iduser)->where('logicdeleted', '!=', 1)->where('seen', '!=', 1)->count();
        return response()->json(['total' => $total]);
    }


}
