<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
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

    

}
