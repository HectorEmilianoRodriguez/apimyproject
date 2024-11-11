<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Calendar;
class CalendarController extends Controller
{  
    //Recuerda que el calendario de actividades son las actividades individuales o personales de cada miembro,
    //no de todas las actividades contenidas del espacio de trabajo, considera que vas a requerir el dato idJoinUserWork,
    //ese dato lo obtienes de la route AmIOnWorkEnv (ver api.php)
    public function newActivity(Request $request){ //Recibe los datos en JSON para generar una actividad dentro del calendario.
        $act = new Calendar();
        $act->title = $request->input('title');
        $act->description = $request->input('description');
        $act->color = $request->input('color'); //en hexadecimal se recibe.
        $act->start = $request->input('start');
        $act->end = $request->input('end');
        $act->idJoinUserWork = $request->input('idJoinUserWork'); 
        $act->done = $request->input('done');
        //este campo 'idJoinUserWork' lo obtienes de la route AmIOnWorkEnv (ver api.php)
        $act->logicdeleted = 0;
        $act->save();
        return response()->json(["success" => 'registred'], 202);
    }

    public function editActivity(Request $request){ //Recibe los datos en JSON para editar una actividad dentro del calendario.
        $act = Calendar::find($request->input('idCalendarEvent')); //se edita a través de la ID.
        $act->title = $request->input('title');
        $act->description = $request->input('description');
        $act->color = $request->input('color');
        $act->start = $request->input('start');
        $act->end = $request->input('end');
        $act->done = $request->input('done');
        $act->save();
        return response()->json(["success" => 'updated'], 202);
    }

    public function deleteActivity(Request $request){ //Recibe los datos en JSON para eliminar una actividad dentro del calendario.
        $act = Calendar::find($request->input('idCalendarEvent')); //se elimina a través de la ID.
        $act->logicdeleted = 1; //marcar como eliminado lógico.
        $act->save();
        return response()->json(["success" => 'updated'], 202);
    }

    

    public function getActivities($idJoinUserWork) {
        // Obtener todas las actividades que coinciden con idJoinUserWork y que no están marcadas como eliminadas
        $acts = Calendar::where('idJoinUserWork', $idJoinUserWork)
                        ->where('logicdeleted', 0)
                        ->get();

        // Verificar si no hay actividades
        if ($acts->isEmpty()) {
            return response()->json(['message' => 'none'], 404); // En caso de que no haya ninguna actividad
        }

        return response()->json($acts, 200); // Devuelve las actividades con un código 200
    }

    public function setDoneActivity(Request $request){
        $act = Calendar::find($request->input('idCalendarEvent')); //se edita a través de la ID.
        $act->done = 1;
        $act->save();
        return response()->json(["success" => 'updated'], 202);
    }

    public function setunDoneActivity(Request $request){
        $act = Calendar::find($request->input('idCalendarEvent')); //se edita a través de la ID.
        $act->done = 0;
        $act->save();
        return response()->json(["success" => 'updated'], 202);
    }


}
