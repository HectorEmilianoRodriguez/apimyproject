<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\Notifications;
use Illuminate\Notifications\Notification;

class CardController extends Controller
{
    public function newCard(Request $request){
        $card = new Card();
        $card->nameC = $request->input('nameC');
        $card->descriptionC = $request->input('descriptionC');
        $card->end_date = $request->input('end_date');
        $card->approbed = 0;
        $card->done = 0;
        $card->logicdeleted = 0;
        $card->important = $request->input('important');
        $card->idList = $request->input('idList');
        $card->save();
        return response()->json(['message ' => 'success'], 200);
    }

    public function updateCard(Request $request){
        $card = Card::where('idCard', $request->input('idCard'))->first();
        $card->nameC = $request->input('nameC');
        $card->descriptionC = $request->input('descriptionC');;
        $card->end_date = $request->input('end_date');
        $card->approbed = 0;
        $card->done = 0;
        $card->logicdeleted = 0;
        $card->important = $request->input('important');
        $card->save();
        return response()->json(['message ' => 'success'], 200);   
    }

    public function deleteCard(Request $request){
        $card = Card::where('idCard', $request->input('idCard'))->first();
        $card->logicdeleted = 1;
        $card->save();
        return response()->json(['message ' => 'success'], 200);
    }

    public function endCard(Request $request) {
        // Buscar la tarjeta
        $card = Card::where('idCard', $request->input('idCard'))->first();
        
        // Cambiar el estado de la tarjeta
        $card->done = 1;
        $card->approbed = 0;
        $card->save();
    
        // Obtener la fecha actual
        $fechaActual = date('Y-m-d');
    
        // Obtener los IDs de usuario del request
        $userIds = $request->input('idUsers'); // Asumiendo que 'idUsers' es un array en el request
    
        foreach ($userIds as $idUser) {
            // Crear una nueva notificación para cada usuario
            $not = new Notifications();
            $not->title = "Actividad marcada como completada";
            $not->content = "Marcada como completada por " . $request->input('name') . " el " . $fechaActual;
            $not->description = "La actividad " . $request->input('nameC') . " ha sido marcada como completada";
            $not->seen = 0;
            $not->logicdeleted = 0;
            $not->idUser = $idUser; // Usar el ID de usuario actual
    
            // Guardar la notificación
            $not->save();
        }
    
        return response()->json(['message' => 'success'], 200);
    }
    

    public function approbeCard(Request $request) {
        
        // Buscar la tarjeta
        $card = Card::where('idCard', $request->input('idCard'))->first();
        // Cambiar el estado de la tarjeta
        $card->done = 1;
        $card->approbed = 1;
        $card->save();
        
        // Obtener la fecha actual
        $fechaActual = date('Y-m-d');
    
        // Obtener los IDs de usuario del request
        $userIds = $request->input('idUsers'); // Asumiendo que 'idUsers' es un array en el request
    
        foreach ($userIds as $idUser) {
            // Crear una nueva notificación para cada usuario
            $not = new Notifications();
            $not->title = "Actividad aprobada";
            $not->content = "Aprobada por " . $request->input('name') . " el " . $fechaActual;
            $not->description = "La actividad " . $request->input('nameC') . " ha sido aprobada";
            $not->seen = 0;
            $not->logicdeleted = 0;
            $not->idUser = $idUser; // Usar el ID de usuario actual
    
            // Guardar la notificación
            $not->save();
        }
    
        return response()->json(['message' => 'success'], 200);
    }
    


    public function desapprobeCard(Request $request) {
        $card = Card::where('idCard', $request->input('idCard'))->first();
        
        // Cambiar el estado de la tarjeta
        $card->done = 0;
        $card->approbed = 0;
        $card->save();
        
        // Obtener la fecha actual
        $fechaActual = date('Y-m-d');
    
        // Obtener los IDs de usuario del request
        $userIds = $request->input('idUsers'); // Asumiendo que 'idUsers' es un array en el request
    
        foreach ($userIds as $idUser) {
            // Crear una nueva notificación para cada usuario
            $not = new Notifications();
            $not->title = "Actividad no aprobada";
            $not->content = "Desaprovada por " . $request->input('name') . " el " . $fechaActual;
            $not->description = "La actividad " . $request->input('nameC') . " no ha sido aprobada";
            $not->seen = 0;
            $not->logicdeleted = 0;
            $not->idUser = $idUser; // Usar el ID de usuario actual
    
            // Guardar la notificación
            $not->save();
        }
    
        return response()->json(['message' => 'success'], 200);
    }
    


    

}
