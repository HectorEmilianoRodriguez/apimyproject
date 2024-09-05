<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkEnvController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Lógica para el servicio de autenticación
Route::post('register', [AuthController::class, 'register']); //cuando se registra
Route::get('verify/{token}', [AuthController::class,'verify']); //cuando se verifica la cuenta por vía correo electrónico y su token
Route::post('login', [AuthController::class, 'login']); //cuando el usuario intenta autenticarse
Route::get('recoversent/{email}' ,[AuthController::class, 'recoversent']); //Envio de correo electrónico y token cuando se desea recuperar la cuenta.
Route::get('recover/{token}' ,[AuthController::class, 'recover']); //cuando el usuario desea recuperar su cuenta.
Route::get('changePassUser/{token}/{email}/{pass}' ,[AuthController::class, 'changePassUser']); //realiza el cambio de password.



Route::middleware('auth:sanctum')->group(function (){ //Manejar la sesión del usuario mediante el middleware sanctum
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('updateUser',[AuthController::class, 'updateUser']);
    Route::get('getUserPhoto', [AuthController::class, 'getUserPhoto']);


    //Lógica para el dashboard
    Route::get('CountMyWorkEnvs', [WorkEnvController::class, 'CountMyWorkEnvs']); //para conocer la cantidad de entornos de trabajo en los que participa y es lider el user
    Route::get('getAllStatsUser', [WorkEnvController::class, 'getAllStatsUser']); //para conocer la cantidad de comentarios no vistos, solicitudes pendientes, actividades por evaluar y aprobar por entorno
    Route::post('newWorkEnv', [WorkEnvController::class, 'newWorkEnv']); //para registrar un nuevo entorno de trabajo
    Route::get('getMyWorkEnvs', [WorkEnvController::class, 'getMyWorkEnvs']); //para obtener los entornos de trabajo del user
    Route::get('AmIOnWorkEnv/{idWorkEnv}',[WorkEnvController::class, 'AmIOnWorkEnv']); //para verificar si pertenece al workenv.
    Route::get('getNotApprobedActivities', [WorkEnvController::class, 'getNotApprobedActivities']); //para obtener las actividades aún no aprobadas
    Route::get('getAlmostExpiredActivities', [WorkEnvController::class, 'getAlmostExpiredActivities']); //para obtener las actividades a punto de expirar o ya expiradas
    Route::get('getNotSeenComments', [WorkEnvController::class, 'getNotSeenComments']); //para obtener los comentarios no vistos
    Route::get('getPendingApprovals', [WorkEnvController::class, 'getPendingApprovals']); //para obtener las solicitudes pendientes
    Route::get('getPossibleRequests', [WorkEnvController::class, 'getPossibleRequests']); //para obtener las solicitudess de unión posibles de un user
    Route::get('joinOnWorkEnv/{idWorkEnv}', [WorkEnvController::class, 'joinOnWorkEnv']); //para solicitar unirse a un entorno de trabajo.
    Route::get('searchRequests/{text}', [WorkEnvController::class, 'searchRequests']); //para obtener resultados de búsqueda o filtro de solicitudes.
    Route::get('getPendingApprovals', [WorkEnvController::class, 'getPendingApprovals']); //para obtener las solicitudes pendientes del user.

    Route::post('getPhoto', [WorkEnvController::class, 'getPhoto']); //para obtener una foto del servidor.
    Route::get('approbeRequestWorkEnv/{idUser}/{idWorkEnv}', [WorkEnvController::class, 'approbeRequestWorkEnv']); //para aprobar una solicitud pendiente de unión a un entorno.
    Route::get('notapprobeRequestWorkEnv/{idJoinUserWork}', [WorkEnvController::class, 'notapprobeRequestWorkEnv']); //para rechazar una solicitud pendiente de unión a un entorno.
    Route::get('getPendingApprovalsSearch/{searchTerm}', [WorkEnvController::class, 'getPendingApprovalsSearch']); //para buscar solicitudes pendientes.
    



});


