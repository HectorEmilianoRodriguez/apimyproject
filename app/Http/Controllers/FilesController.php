<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
