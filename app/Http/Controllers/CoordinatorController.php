<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\activitycoordinatorleader;
use App\Models\grouptaskscoordinatorleaders;
class CoordinatorController extends Controller
{  //recuerda que cada coordinador de un espacio tiene sus propios grupos, no funciona como el tablero, que es todo público.
    //también no olvides que en el método "input" del objeto "request" indican exactamente como deben ser enviados los datos
    //tiene que ser exactamente el mismo nombre.
    public function getGroups($idJoinUserWork){ //obtener grupos de tareas de algun coordinador
        $groups = grouptaskscoordinatorleaders::where('idJoinUserWork', $idJoinUserWork)->where('logicdeleted', 0)->get();

        if(!$groups){
            return response()->json(['message ' => 'none'], 404); //si no hay ningún grupo encontrado del coordinador
        }

        return response()->json($groups, 201); //si encuentra los grupos, los enviará en formato JSON.

    }

    public function getActivitiesOfGroup($idgrouptaskcl)
    {
        $activities = activitycoordinatorleader::with('label')
            ->where('idgrouptaskcl', $idgrouptaskcl)
            ->where('logicdeleted', 0)
            ->get();
    
        if ($activities->isEmpty()) {
            return response()->json(['message' => 'none'], 404); 
        }
    
        return response()->json($activities, 200); 
    
    }

    
    public function newGroup(Request $request){ //crear un nuevo grupo de tareas de un coordinador.

        $group = new grouptaskscoordinatorleaders();
        $group->name = $request->input('name'); //nombre del grupo de tareas
        $group->startdate = $request->input('startdate'); //fecha de inicio del grupo de tareas
        $group->enddate = $request->input('endate'); //fecha de fin del grupo de tareas.
        $group->logicdeleted = 0; //borrado logico establecido como "no borrado"
        $group->idJoinUserWork = $request->input('idJoinUserWork'); //llave primaria de la tabla "rel_join_workenv_users", 
        //la puedes obtener del método "getWorkEnv" del service "WorkEnvServiceM", ojo, es el service que he creado yo distinto al que has creado tú.
        $group->save(); ///guardar en la BD
        
        return response()->json(['message' => 'success'], 201); //indica que se ha almacenado.

    }

    public function editGroup(Request $request){//editar un groupo de tareas de un coordinador.
        $group = grouptaskscoordinatorleaders::find($request->input('idgrouptaskcl')); //se tiene que enviar la llave primaria de la tabla.
        $group->name = $request->input('name'); //nombre del grupo de tareas
        $group->startdate = $request->input('startdate'); //fecha de inicio del grupo de tareas
        $group->enddate = $request->input('endate'); //fecha de fin del grupo de tareas.
        //la puedes obtener del método "getWorkEnv" del service "WorkEnvServiceM", ojo, es el service que he creado yo distinto al que has creado tú.
        $group->save(); ///guardar en la BD
        
        return response()->json(['message' => 'success'], 201); //indica que se ha editado.
    }

    public function deleteGroup(Request $request){
        $group = grouptaskscoordinatorleaders::find($request->input('idgrouptaskcl')); //se tiene que enviar la llave primaria de la tabla.
        $group->logicdeleted = 1; //se marca como eliminado lógico.
        $group->save();
        return response()->json(['message' => 'success'], 201); //indica que se ha eliminado.
    }

    public function newActCoordinator(Request $request){ //agregar nueva actividad de coordinador.
        $act = new activitycoordinatorleader();
        $act->nameT = $request->input('nameT'); //nombre de la actividad
        $act->descriptionT = $request->input('descriptionT'); //una descripcion de la actividad
        $act->end_date = $request->input('end_date'); //indica cuando se debe entregar la actividad
        $act->logicdeleted = 0; //establecer como no borrado lógico
        $act->important = $request->input('important'); //indica si es urgente o no, recibe "1" -> si o "0" -> no.
        $act->done = 0; //se establece como "no terminado"
        $act->idgrouptaskcl = $request->input('idgrouptaskcl'); //relacion de la tabla de grupos de tareas, a cual grupo pertenece dicha actividad.
        $act->idLabel = $request->input('idLabel'); //sirve para etiquetar la actividad. puedes obtener las etiquetass
        //disponibles con ell método "getLabels" (api.php)
        $act->save();
        return response()->json(['message' => 'success'], 201); //indica que se ha almacenado.
    }

    public function editActCoordinator(Request $request){ //agregar editar actividad de coordinador.
        $act = activitycoordinatorleader::find($request->input('idactivitycl')); //llave primaria de la tabla de actividades.
        $act->nameT = $request->input('nameT'); //nombre de la actividad
        $act->descriptionT = $request->input('descriptionT'); //una descripcion de la actividad
        $act->end_date = $request->input('end_date'); //indica cuando se debe entregar la actividad
        $act->important = $request->input('important'); //indica si es urgente o no, recibe "1" -> si o "0" -> no.
        $act->done = 0; //se establece como "no terminado"
        $act->idgrouptaskcl = $request->input('idgrouptaskcl'); //relacion de la tabla de grupos de tareas, a cual grupo pertenece dicha actividad.
        $act->idLabel = $request->input('idLabel'); //sirve para etiquetar la actividad. puedes obtener las etiquetass
        //disponibles con ell método "getLabels" (api.php)
        $act->save();
        return response()->json(['message' => 'success'], 201); //indica que se ha actualizado.
    }

    public function deleteActCoordinator(Request $request){//agregar eliminar actividad de coordinador.
        $act = activitycoordinatorleader::find($request->input('idactivitycl')); //llave primaria de la tabla de actividades.
        $act->logicdeleted = 1; //indicar como eliminado.
        $act->save();
        return response()->json(['message' => 'success'], 201); //indica que se ha eliminado.
    }

    

    


}
