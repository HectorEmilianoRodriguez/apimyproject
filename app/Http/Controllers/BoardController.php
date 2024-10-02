<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Board;
class BoardController extends Controller
{

    
    public function getBoards($idwork){
        $data = Board::where('idWorkEnv', $idwork)->get();
        return response()->json($data);
    }

    public function newBoard(Request $request){
       
        $board = new Board();
        $board->nameB = $request->input('nameB');
        $board->descriptionB = $request->input('descriptionB');
        $board->logicdeleted = 0;
        $board->idWorkEnv = $request->input('idWorkEnv');
        $board->save();
        return response()->json(['message' => 'success']);

    }

    public function getBoard(Request $request){
        $data = Board::where('idWorkEnv', $request->input('idWorkEnv'))->where('idBoard', $request->input('idBoard'))->first();

        return $data;
    }
    public function editBoard(Request $request)
    {
        $board = Board::where('idWorkEnv', $request->input('idWorkEnv'))
                      ->where('idBoard', $request->input('idBoard'))
                      ->first();
    
        if (!$board) {
            return response()->json(['message' => 'Board not found'], 404);
        }
    

        $board->nameB = $request->input('nameB');
        $board->descriptionB = $request->input('descriptionB');
        $board->save();

        return response()->json(['message' => 'Board updated successfully'], 200);
    }

    public function deleteBoard(Request $request){
                $board = Board::where('idWorkEnv', $request->input('idWorkEnv'))
                ->where('idBoard', $request->input('idBoard'))
                ->first();

        if (!$board) {
        return response()->json(['message' => 'Board not found'], 404);
        }


        $board->logicdeleted = 1;
        $board->save();

        return response()->json(['message' => 'Board deleted successfully'], 200);
    }


    public function undeleteBoard(Request $request){
    $board = Board::where('idWorkEnv', $request->input('idWorkEnv'))
            ->where('idBoard', $request->input('idBoard'))
            ->first();

    if (!$board) {
    return response()->json(['message' => 'Board not found'], 404);
    }


    $board->logicdeleted = 0;
    $board->save();

    return response()->json(['message' => 'Board deleted successfully'], 200);
    }
    
    
    
}
