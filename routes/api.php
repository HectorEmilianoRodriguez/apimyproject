<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\WorkEnvController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CoordinatorController;



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
    Route::get('joinOnWorkEnv/{codeWork}', [WorkEnvController::class, 'joinOnWorkEnv']); //para solicitar unirse a un entorno de trabajo.
    Route::get('searchRequests/{text}', [WorkEnvController::class, 'searchRequests']); //para obtener resultados de búsqueda o filtro de solicitudes.
    Route::get('getPendingApprovals', [WorkEnvController::class, 'getPendingApprovals']); //para obtener las solicitudes pendientes del user.
    Route::post('getPhoto', [WorkEnvController::class, 'getPhoto']); //para obtener una foto del servidor.
    Route::get('approbeRequestWorkEnv/{idUser}/{idWorkEnv}', [WorkEnvController::class, 'approbeRequestWorkEnv']); //para aprobar una solicitud pendiente de unión a un entorno.
    Route::get('notapprobeRequestWorkEnv/{idJoinUserWork}', [WorkEnvController::class, 'notapprobeRequestWorkEnv']); //para rechazar una solicitud pendiente de unión a un entorno.
    Route::get('getPendingApprovalsSearch/{searchTerm}', [WorkEnvController::class, 'getPendingApprovalsSearch']); //para buscar solicitudes pendientes.
    //Notificaciones
    Route::get('NotifyUserApprobedOrNot/{workenv}/{idUser}/{flag}', [WorkEnvController::class, 'NotifyUserApprobedOrNot']); //para notificar a el usuario vía correo y sistema que ha sido aceptado o rechazado en un entorno.
    Route::get('NotifyUserNewRequest/{codeWork}', [WorkEnvController::class, 'NotifyUserNewRequest']); //para notificar a el usuario vía correo y sistema sobre una nueva solicitud de unión a un entorno.
    Route::get('getNotifications',[WorkEnvController::class, 'getNotifications']); //para obtener todas la notis de un user.
    Route::get('setSeenNotificationn/{idNoti}',[WorkEnvController::class, 'setSeenNotificationn']); //para indicar que la noti ha sido visto por el user.
    Route::get('countMyNotis',[WorkEnvController::class, 'countMyNotis']); //para contar las notis del user.
    



    //CRUD entornos de trabajo
    Route::put('updateWorkEnv/{id}', [WorkEnvController::class, 'updateWorkEnv']); Route::post('newWorkEnv', [WorkEnvController::class, 'newWorkEnv']); //para registrar un nuevo entorno de trabajo
    Route::delete('deleteWorkEnv/{idWorkEnv}',[WorkEnvController::class, 'deleteWorkEnv']); //para eliminar logicamente un entorno de trabajo
    
    Route::get('getWorkEnvOwner/{idWorkEnv}',[WorkEnvController::class, 'getWorkEnvOwner']); //para obtener el líder del entorno.
    Route::get('getMyArchivedWorkEnvs',[WorkEnvController::class, 'getMyArchivedWorkEnvs']); //para obtener los entornos archivados de un user.
    Route::put('undeleteWorkEnv/{idWorkEnv}',[WorkEnvController::class, 'undeleteWorkEnv']); //para desarchivar un entorno.
    
    // Ruta para generar el respaldo y descargar el archivo .sql
    Route::get('/database/backup', [BackupController::class, 'backupDatabase']);
    // Ruta para restaurar la base de datos desde un archivo .sql
    Route::post('/database/restore', [BackupController::class, 'restoreDatabase']);

    //logica de generación de reportes
    Route::post('/pdf/ParticipantReport', [ReportsController::class, 'ParticipantReport']); // reporte de partipacion
    Route::post('/pdf/ProductivityReport', [ReportsController::class, 'ProductivityReport']); // reporte de productividad
    Route::post('/pdf/DeliveryActivitiesReport', [ReportsController::class, 'DeliveryActivitiesReport']); // reporte de plazos de entrega para miembros
    Route::post('/pdf/DeliveryActivitiesReportCoordinator', [ReportsController::class, 'DeliveryActivitiesReportCoordinator']); // reporte de plazos de entrega para coordinadores
    Route::post('/pdf/PendingActivitiesReport', [ReportsController::class, 'PendingActivitiesReport']); // reporte de actividades pendientes de un entorno.
    Route::post('/pdf/CompletedActivitiesReport', [ReportsController::class, 'CompletedActivitiesReport']); // reporte de actividades completadas de un entorno.
    
    //CRUD Miembros
    Route::get('/inviteMember/{email}/{workenv}/{idwork}', [MembersController::class, 'inviteMember']); // aceptar invitacion de un entorno.

    Route::get('/acceptInvitationMember/{token}/{idwork}/', [MembersController::class, 'acceptInvitationMember']); // aceptar invitacion de un entorno.
    Route::get('/getMembers/{idWorkEnv}', [MembersController::class, 'getMembers']); // devolver los miembros de un entorno de trabajo.
    Route::delete('/deleteMember/{idUser}/{nameUser}/{emailmember}/{idWorkEnv}/{nameWork}', [MembersController::class, 'deleteMember']); // expulsar un miembro de un entorno.
    Route::put('/updateMember/{idUser}/{idWorkEnv}/{privilege}', [MembersController::class, 'updateMember']); // actualizar privilegio de un miembro de un entorno.
    Route::post('/getUsersPhotosByCard', [MembersController::class, 'getUsersPhotosByCard']); // devolver los miembros de un entorno de trabajo junto a su foto de perfil.
    Route::post('/getPossibleMembersByCard', [MembersController::class, 'getPossibleMembersByCard']); // devolver posibles miembros para ser asignados a una actividad.
    Route::post('/storeCardMembers', [MembersController::class, 'storeCardMembers']); // almacenar la asignacion de miembros y actividades.
    Route::post('/DeleteMemberByCard', [MembersController::class, 'DeleteMemberByCard']); // eliminar una asignación a un miembro.
    Route::get('/getMembersShareFile/{idWorkEnv}', [MembersController::class, 'getMembersShareFile']); // obtener miembros a los cuales se les puede compartir carpetas.
    Route::get('/getMembersSharedFile/{idWorkEnv}/{idFolder}', [MembersController::class, 'getMembersSharedFile']); // obtener miembros a los cuales ya tienen carpeta compartida.





    //CRUD Etiquetas
    Route::get('/getLabels/{idWork}', [LabelController::class, 'getLabels']); // obtener etiquetas de un entorno.
    Route::post('/getActivityLabels', [LabelController::class, 'getActivityLabels']); // obtener etiquetas de una actividad.
    Route::post('/getPossibleLabelsForActivity', [LabelController::class, 'getPossibleLabelsForActivity']); // obtener etiquetas posibles para ser utilizadas en una actividad.
    Route::post('/storeCardLabels', [LabelController::class, 'storeCardLabels']); // para etiquetar una actividad.
    Route::post('/removeLabelFromAct', [LabelController::class, 'removeLabelFromAct']); // para remover una etiqueta de una actividad.
    Route::post('/newLabel', [LabelController::class, 'newLabel']); // para crear una nueva etiqueta
    Route::post('/editLabel', [LabelController::class, 'editLabel']); // para editar una  etiqueta
    Route::post('/deleteLabel', [LabelController::class, 'deleteLabel']); // para eliminar una  etiqueta




    //CRUD Tableros
    Route::get('/getBoards/{idWork}', [BoardController::class, 'getBoards']); // obtener tableros de un entorno.
    Route::post('/newBoard', [BoardController::class, 'newBoard']); // crear un nuevo tablero.
    Route::post('/getBoard', [BoardController::class, 'getBoard']); // obtener data de un tablero.
    Route::put('/editBoard', [BoardController::class, 'editBoard']); // actualizar data de un tablero.
    Route::post('/deleteBoard', [BoardController::class, 'deleteBoard']); // archivar un tablero.
    Route::post('/undeleteBoard', [BoardController::class, 'undeleteBoard']); // desarchivar un tablero.

    //CRUD Listas
    Route::post('/getListsDetails', [ListController::class, 'getListsDetails']); // obtener listas junto a las actividades.
    Route::post('/createList', [ListController::class, 'createList']); // crear nueva lista.
    Route::put('/updateList', [ListController::class, 'updateList']); // actualizar lista.
    Route::post('/deleteList', [ListController::class, 'deleteList']); // eliminar logicamente la lista.

    //CRUD Actividades
    Route::post('/newCard', [CardController::class, 'newCard']); // crear nueva actividad.
    Route::put('/updateCard', [CardController::class, 'updateCard']); // actualizar actividad.
    Route::post('/deleteCard', [CardController::class, 'deleteCard']); // eliminar logicamente actividad.
    Route::post('/updateActivity', [CardController::class, 'updateActivity']); // guardar el estado de una actividad a otra lista.



    //CRUD Aprobacion de actividades
    Route::post('/endCard', [CardController::class, 'endCard']); // marcar como completada una actividad.
    Route::post('/approbeCard', [CardController::class, 'approbeCard']); // aprobar una actividad.
    Route::post('/desapprobeCard', [CardController::class, 'desapprobeCard']); // desaprobar una actividad.


    //Lógica administrador de archivos

    Route::get('/photos/{filename}', [FilesController::class, 'getPhoto']); //para descargar fotos de perfil
    Route::post('/storageEvidence', [FilesController::class, 'storageEvidence']);  //para almacenar evidencia de las actividades
    Route::get('/downloadEvidence/{file}', [FilesController::class, 'downloadEvidence']); //para descargar la evidencia de la actividad.
    Route::post('/newFolder', [FilesController::class, 'newFolder']); //para agregar una carpeta.
    Route::get('/getFolders/{idWorkEnv}', [FilesController::class, 'getFolders']); //obtener carpetas de entorno.
    Route::post('/editFolder', [FilesController::class, 'editFolder']); //actualizar una carpeta.
    Route::post('/deleteFolder', [FilesController::class, 'deleteFolder']); //eliminar una carpeta.
    Route::post('/shareFile', [FilesController::class, 'shareFile']); //compartir la carpeta a varios miembros.
    Route::post('/removeShare', [FilesController::class, 'removeShare']); //dejar de compartir una carpeta con miembros.
    Route::get('/getFolderInfo/{idf}/{idj}', [FilesController::class, 'getFolderInfo']); //obtener datos de un archivo compartido.
    Route::post('/uploadFile', [FilesController::class, 'uploadFile']); //guardar archivo en una carpeta específica.

    Route::post('/getFilesByFolder', [FilesController::class, 'getFilesByFolder']); //obtener archivos de una carpeta.

    Route::get('/downloadFile/{folderName}/{fileName}', [FilesController::class, 'downloadFile']); //descargar archivo.
    Route::get('/deleteFile/{idFile}', [FilesController::class, 'deleteFile']); //eliminar archivo.





    //CRUD Grupo de tareas de coordinador
    Route::get('/getGroups/{idJoinUserWork}', [CoordinatorController::class, 'getGroups']); //para obtener los grupos de tareas de un coordinador
    Route::get('/getActivitiesOfGroup/{idgrouptaskcl}', [CoordinatorController::class, 'getActivitiesOfGroup']); //para obtener las actividades de un grupo de tareas.
    Route::post('/newGroup', [CoordinatorController::class, 'newGroup']); //para agregar un grupo de tareas.
    Route::post('/editGroup', [CoordinatorController::class, 'editGroup']); //para editar un grupo de tareas.
    Route::post('/deleteGroup', [CoordinatorController::class, 'deleteGroup']); //para eliminar un grupo de tareas.
    Route::post('/newActCoordinator', [CoordinatorController::class, 'newActCoordinator']); //para  agregar un actividad de un grupo de tareas.
    Route::post('/editActCoordinator', [CoordinatorController::class, 'editActCoordinator']); //para  editar un actividad de un grupo de tareas.
    Route::post('/deleteActCoordinator', [CoordinatorController::class, 'deleteActCoordinator']); //para  eliminar un actividad de un grupo de tareas.










    //CRUD Comentarios
    Route::post('/getComments', [CommentController::class, 'getComments']); //para obtener los comentarios de una actividad de un entorno.
    Route::post('/deleteComment', [CommentController::class, 'deleteComment']); //para eliminar un comentario de una actividad.
    Route::post('/newComment', [CommentController::class, 'newComment']); //para agregar un comentario de una actividad.
    Route::post('/editComment', [CommentController::class, 'editComment']); //actualizar comentario de una actividad.
    Route::post('/setSeenComment', [CommentController::class, 'setSeenComment']); //marcar como vista el comentario.





    


});


