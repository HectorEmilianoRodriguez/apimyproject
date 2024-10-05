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
            SELECT cat_labels.nameL, cat_labels.colorL
            FROM rel_card_labels
            JOIN cat_labels ON rel_card_labels.idLabel = cat_labels.idLabel
            WHERE rel_card_labels.idCard = ?
        ", [$idCard]);
    
        return $result;
    }
    
}
