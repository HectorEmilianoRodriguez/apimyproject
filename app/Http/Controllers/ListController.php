<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lists;

class ListController extends Controller
{
    
   
    public function getListsDetails(Request $request)
{
    // Obtener el idBoard del request
    $idBoard = $request->input('idBoard');

    // Buscar todas las listas que pertenecen al idBoard y cargar las cards relacionadas que no están eliminadas
    $lists = Lists::where('idBoard', $idBoard)
                  ->where('logicdeleted', 0)
                  ->with(['cards' => function($query) {
                      $query->where('logicdeleted', 0); // Filtrar las cards que no están eliminadas
                  }])
                  ->get();

    // Retornar las listas con las cards como JSON
    return response()->json($lists);
}


    public function createList(Request $request){

        $newL = new Lists();
        $newL->nameL = $request->input('nameL');
        $newL->descriptionL = $request->input('descriptionL');
        $newL->colorL = $request->input('colorL');
        $newL->logicdeleted = 0;
        $newL->idBoard = $request->input('idBoard');
        $newL->save();
        return response()->json(['message' => 'success'], 200);


    }

    public function updateList(Request $request){

        $updateL = Lists::find($request->input('idList'));
        $updateL->nameL = $request->input('nameL');
        $updateL->descriptionL = $request->input('descriptionL');
        $updateL->colorL = $request->input('colorL');
        $updateL->logicdeleted = 0;
        $updateL->idBoard = $request->input('idBoard');
        $updateL->save();
        return response()->json(['message' => 'success'], 200);
    }

    public function deleteList(Request $request){

        $updateL = Lists::find($request->input('idList'));
        $updateL->logicdeleted = 1;
        $updateL->save();
        return response()->json(['message' => 'success'], 200);

    }
    
}
