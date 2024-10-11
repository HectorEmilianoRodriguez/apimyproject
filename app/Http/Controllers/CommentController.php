<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;


class CommentController extends Controller
{
    
    public function getComments(Request $request) {
        // Obtener los comentarios
        $data = DB::table('cat_comments')->select(
            'cat_comments.text',
            'users.name',
            'rel_join_workenv_users.privilege',
            'users.photo',
            'cat_comments.seen',
            'cat_comments.created_at',
            'cat_comments.idComment',
            'cat_comments.logicdeleted'
        )
        ->join('rel_join_workenv_users', 'rel_join_workenv_users.idJoinUserWork', '=', 'cat_comments.idJoinUserWork')
        ->join('users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->where('rel_join_workenv_users.idWorkEnv', '=', $request->input('idWorkEnv'))
        ->where('cat_comments.logicdeleted', '=', 0)
        ->where('cat_comments.idCard', '=', $request->input('idCard'))
        ->get();
    
        // Mapear las fotos
        $data = $data->map(function ($user) {
            // Genera la URL completa para la foto
            if ($user->photo) {
                $user->photo = url('api/' . $user->photo); // Genera la URL completa
            } else {
                $user->photo = url('api/photos/test.jpg'); // Imagen por defecto
            }
            return $user;
        });
    
        // Retornar los datos
        return response()->json($data);
    }

    public function deleteComment(Request $request){
        $comm = Comment::find($request->input('idComment'));
        $comm->logicdeleted = 1;
        $comm->save();
        return response()->json(["success" => 'deleted'], 201);
    }
    
    public function newComment(Request $request){
        $com = new Comment();
        $com->idCard = $request->input('idCard');
        $com->idJoinUserWork = $request->input('idJoinUserWork');
        $com->seen = 0;
        $com->logicdeleted = 0;
        $com->text = $request->input('text');
        $com->save();
        return response()->json(["success" => 'created'], 201);
    }

    public function editComment(Request $request){
        $com = Comment::find($request->input('idComment'));
        $com->text = $request->input('text');
        $com->save();
        return response()->json(["success" => 'updated'], 201);
    }  

    public function setSeenComment(Request $request){
        $com = Comment::find($request->input('idComment'));
        $com->seen = 1;
        $com->save();
        return response()->json(["success" => 'updated'], 201);
    }

}
