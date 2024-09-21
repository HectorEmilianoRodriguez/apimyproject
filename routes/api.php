<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\WorkEnvController;
use App\Http\Controllers\BackupController;
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
    //Notificaciones
    Route::get('NotifyUserApprobedOrNot/{workenv}/{idUser}/{flag}', [WorkEnvController::class, 'NotifyUserApprobedOrNot']); //para notificar a el usuario vía correo y sistema que ha sido aceptado o rechazado en un entorno.
    Route::get('NotifyUserNewRequest/{workenv}/{idUser}', [WorkEnvController::class, 'NotifyUserNewRequest']); //para notificar a el usuario vía correo y sistema sobre una nueva solicitud de unión a un entorno.
    Route::get('getNotifications',[WorkEnvController::class, 'getNotifications']); //para obtener todas la notis de un user.
    Route::get('setSeenNotificationn/{idNoti}',[WorkEnvController::class, 'setSeenNotificationn']); //para indicar que la noti ha sido visto por el user.
    Route::get('countMyNotis',[WorkEnvController::class, 'countMyNotis']); //para contar las notis del user.




    //CRUD entornos de trabajo
    Route::put('updateWorkEnv', [WorkEnvController::class, 'updateWorkEnv']); //para actualizar un entorno de trabajo
    Route::post('newWorkEnv', [WorkEnvController::class, 'newWorkEnv']); //para registrar un nuevo entorno de trabajo
    Route::delete('deleteWorkEnv/{idWorkEnv}',[WorkEnvController::class, 'deleteWorkEnv']); //para eliminar logicamente un entorno de trabajo
    
    Route::get('getWorkEnvOwner/{idWorkEnv}',[WorkEnvController::class, 'getWorkEnvOwner']); //para obtener el líder del entorno.
    Route::get('getMyArchivedWorkEnvs',[WorkEnvController::class, 'getMyArchivedWorkEnvs']); //para obtener los entornos archivados de un user.
    Route::put('undeleteWorkEnv/{idWorkEnv}',[WorkEnvController::class, 'undeleteWorkEnv']); //para desarchivar un entorno.
    
    // Ruta para generar el respaldo y descargar el archivo .sql
    Route::get('/database/backup', [BackupController::class, 'backupDatabase']);
    // Ruta para restaurar la base de datos desde un archivo .sql
    Route::post('/database/restore', [BackupController::class, 'restoreDatabase']);

    //logica de generación de reportes
    Route::get('/pdf/ParticipantReport', [ReportsController::class, 'ParticipantReport']); // reporte de partipacion
    Route::get('/pdf/ProductivityReport', [ReportsController::class, 'ProductivityReport']); // reporte de productividad
    Route::get('/pdf/DeliveryActivitiesReport', [ReportsController::class, 'DeliveryActivitiesReport']); // reporte de plazos de entrega para miembros
    Route::get('/pdf/DeliveryActivitiesReportCoordinator', [ReportsController::class, 'DeliveryActivitiesReportCoordinator']); // reporte de plazos de entrega para coordinadores
    Route::get('/pdf/PendingActivitiesReport', [ReportsController::class, 'PendingActivitiesReport']); // reporte de actividades pendientes de un entorno.
    Route::get('/pdf/CompletedActivitiesReport', [ReportsController::class, 'CompletedActivitiesReport']); // reporte de actividades completadas de un entorno.
    




});


