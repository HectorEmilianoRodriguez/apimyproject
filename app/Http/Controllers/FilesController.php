<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use Illuminate\Support\Facades\Storage;
use App\Models\Folder;
use Illuminate\Support\Facades\DB;
use App\Models\relsharedfiles;

class FilesController extends Controller
{
    public function getPhoto($filename)
    {
        $path = storage_path('app/private/photos/' . $filename);

        if (file_exists($path)) {
            return response()->file($path);
        }

        // Si el archivo no existe, devuelve la imagen por defecto
        $defaultPhotoPath = storage_path('app/private/photos/test.jpg');
        if (file_exists($defaultPhotoPath)) {
            return response()->file($defaultPhotoPath);
        }

        return response()->json(['error' => 'File not found'], 404);
    }

    public function storageEvidence(Request $request) {
        $act = Card::find($request->input('idCard'));
        $file = $request->file('evidence');
        $filepath = $file->store('evidences', 'private');
    
        if ($act->evidence) {
            Storage::disk('private')->delete($act->evidence);
        }
    
        $act->evidence = $filepath;
        $act->save();
    
        return response()->json(['success' => 'Uploaded'], 201);
    }
    

    public function downloadEvidence($idCard) {
        $act = Card::find($idCard);
        $file = $act->evidence; 
    
        $path = storage_path('app/private/' . $file);
    
        if (file_exists($path)) {
            return response()->file($path);
        }
    
        return response()->json(['error' => 'File not found'], 404); // Manejo de errores si el archivo no se encuentra
    }

    public function newFolder(Request $request) {
        $folder = new Folder();
    
        $folder->nameF = $request->input('nameF');
        $folder->idJoinUserWork = $request->input('idJoinUserWork');
        $folder->logicdeleted = 0;
    
        // Guardar la carpeta
        $folder->save();
    
        // Obtener el ID del folder recién creado
        $folderId = $folder->idFolder;
    
        // Crear la relación en relsharedfiles
        $relshare = new relsharedfiles();
        $relshare->logicdeleted = 0;
        $relshare->idFolder = $folderId; // Usar el ID del folder recién creado
        $relshare->idJoinUserWork = $request->input('idJoinUserWork');
        $relshare->save(); // No olvides guardar la relación
    
        // Crear el directorio en el disco
        Storage::disk('private')->makeDirectory($request->input('nameF'));
    
        return response()->json(['message' => 'Carpeta creada con éxito'], 201);
    }
    
    public function editFolder(Request $request){
        $folder = Folder::find($request->input('idFolder'));
        $oldpath = $folder->nameF;
        $folder->nameF = $request->input('nameF');
        
        $folder->save();
        Storage::disk('private')->move($oldpath,$request->input('nameF'));
        return response()->json(['message' => 'Changed'], 201);
    }

    public function deleteFolder(Request $request){
        $folder = Folder::find($request->input('idFolder'));
        $folder->logicdeleted = 1;
        $folder->save();
        return response()->json(['message' => 'Deleted'], 201);
    }

    public function getFolders($idWorkEnv)
    {
        $folders = DB::table('cat_folders')
            ->select('cat_folders.nameF', 'cat_folders.created_at', 'cat_folders.idFolder')
            ->join('rel_join_workenv_users', 'rel_join_workenv_users.idJoinUserWork', '=', 'cat_folders.idJoinUserWork')
            ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
            ->join('rel_sharedfolder_user', 'cat_folders.idJoinUserWork', '=', 'rel_sharedfolder_user.idJoinUserWork')
            ->where('rel_sharedfolder_user.idJoinUserWork', $idWorkEnv)
            ->where('rel_sharedfolder_user.logicdeleted', 0)
            ->where('cat_folders.logicdeleted', 0)
            ->get();
    
        return response()->json($folders);
    }

    public function shareFile(Request $request) {
      
        $membersIds = $request->idJoinUserWorks;
        $idFolder = $request->idFolder;
    
        // Preparar los datos para la inserción
        $data = [];
        foreach ($membersIds as $memberId) {
            $data[] = [
                'idJoinUserWork' => $memberId,
                'idFolder' => $idFolder,
                'logicdeleted' => 0,
                'created_at' => now(), // Si deseas almacenar la fecha de creación
                'updated_at' => now(), // Si deseas almacenar la fecha de actualización
            ];
        }
    
        // Insertar los datos en la tabla rel_sharedfolder_user
        DB::table('rel_sharedfolder_user')->insert($data);
    
        return response()->json(['message' => 'Archivos compartidos con éxito'], 201);
    }
    
    public function removeShare(Request $request){

        $rel = relsharedfiles::where('idFolder', $request->input('idFolder'))->where('idJoinUserWork', $request->input('idJoinUserWork'))->first();
        
        $rel->delete();

        return response()->json(['message' => 'Deleted'], 201);

    }
    

}
