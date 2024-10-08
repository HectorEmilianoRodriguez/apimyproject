<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use Illuminate\Support\Facades\Storage;

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
    

}
