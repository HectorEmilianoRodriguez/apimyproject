<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\label;
class LabelController extends Controller
{

    public function getLabels($idWork){

        return label::where('idWorkEnv', $idWork)->where('logicdeleted', 0)->get();

    }
}
