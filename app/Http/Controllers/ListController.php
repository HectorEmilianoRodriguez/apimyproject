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

        // Buscar todas las listas que pertenecen al idBoard y cargar las cards relacionadas
        $lists = Lists::where('idBoard', $idBoard)
                      ->with('cards') // Cargar las actividades (cards) relacionadas con las listas
                      ->get();

        // Retornar las listas con las cards como JSON
        return response()->json($lists);
    }

    
}
