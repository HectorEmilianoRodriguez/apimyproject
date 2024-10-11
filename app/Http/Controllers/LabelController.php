<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\label;
use App\Models\relcardlabel;

use Illuminate\Support\Facades\DB;

class LabelController extends Controller
{

    public function getLabels($idWork){

        return label::where('idWorkEnv', $idWork)->where('logicdeleted', 0)->get();

    }

    public function getActivityLabels(Request $request) {
        $idCard = $request->input('idCard');
        $result = DB::select("
            SELECT cat_labels.idLabel, cat_labels.nameL, cat_labels.colorL
            FROM rel_card_labels
            JOIN cat_labels ON rel_card_labels.idLabel = cat_labels.idLabel
            WHERE rel_card_labels.idCard = ?
        ", [$idCard]);
    
        return $result;
    }

    public function getPossibleLabelsForActivity(Request $request)
{
    // Obtén los idLabels excluidos del request
    $excludedLabels = $request->input('idLabels', []); // Asegúrate de que sea un array

    // Verifica que excludedLabels sea un arreglo
    if (!is_array($excludedLabels)) {
        $excludedLabels = [];
    }

    // Obtén las etiquetas que no están en excludedLabels y que no están eliminadas
    $result = label::whereNotIn('idLabel', $excludedLabels)
                   ->where('logicdeleted', 0)
                   ->where('idWorkEnv', $request->input('idWorkEnv'))
                   ->get();

    return response()->json($result);
}

    
public function storeCardLabels(Request $request)
{
    $idCard = $request->input('idCard');
    $idLabels = $request->input('idLabels', []); // Un arreglo de idLabels

    // Verifica que idLabels sea un arreglo
    if (!is_array($idLabels)) {
        return response()->json(['error' => 'idLabels debe ser un arreglo.'], 400);
    }

    // Prepara un arreglo para almacenar las relaciones
    $cardLabelData = [];

    foreach ($idLabels as $idLabel) {
        $cardLabelData[] = [
            'idCard' => $idCard,
            'idLabel' => $idLabel,
            'created_at' => now(), 
            'updated_at' => now(),
        ];
    }

    // Inserta las relaciones en la base de datos
    relcardlabel::insert($cardLabelData);

    return response()->json(['message' => 'Relaciones almacenadas correctamente.'], 201);
}

public function removeLabelFromAct(Request $request)
{
    // Asegúrate de obtener los valores del request
    $idCard = $request->input('idCard');
    $idLabel = $request->input('idLabel');

    // Elimina el registro de la relación
    relcardlabel::where('idCard', $idCard)
                ->where('idLabel', $idLabel)
                ->delete();

    return response()->json(['message' => 'deleted'], 201);
}

public function newLabel(Request $request){
    $l = new Label();
    $l->nameL = $request->input('nameL');
    $l->colorL = $request->input('colorL');
    $l->idWorkEnv = $request->input('idWorkEnv');
    $l->logicdeleted = 0;
    $l->save();
    return response()->json(['message' => 'created'], 201);
}

public function editLabel(Request $request){
    $l = Label::find($request->input('idLabel'));
    $l->nameL = $request->input('nameL');
    $l->colorL = $request->input('colorL');
    $l->idWorkEnv = $request->input('idWorkEnv');
    $l->logicdeleted = 0;
    $l->save();
    return response()->json(['message' => 'updated'], 201);
}

public function deleteLabel(Request $request){
    $l = Label::find($request->input('idLabel'));
    $l->logicdeleted = 1;
    $l->save();
    return response()->json(['message' => 'updated'], 201);
}

}
