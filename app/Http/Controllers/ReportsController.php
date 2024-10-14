<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lists;
use App\Models\label;
use App\Models\JoinWorkEnvUser;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Http;
class ReportsController extends Controller
{
    //

    public function ParticipantReport(Request $request)
{
    // Obtener el id del user actual
    $idUser = Auth::id();

    // Verificar si el user está dentro del entorno y es coordinador o líder.
    if (!JoinWorkEnvUser::where('idUser', $idUser)->where('idWorkEnv', $request->input('idWorkEnv'))->whereIn('privilege', [1, 2])->first()) {
        return response()->json(['error' => 'not found user']);
    }

    // Verificar si el miembro a sacar sus estadísticas existe
    if (!User::find($request->input('idUser'))) {
        return response()->json(['error' => 'not found user member']);
    }

    $User = User::find($request->input('idUser'));
    $nameUser = $User['name'];

    // Obtener todas las actividades totales que posee el miembro
    $totalActivities = DB::table('users')
    ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
    ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
    ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
    ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
    ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
    ->select(DB::raw('COUNT(cat_cards.idCard) as totalActivities'))
    ->where('users.idUser', '=', $request->input('idUser'))
    ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
    ->where('rel_cards_users.logicdeleted', '!=', 1)
    ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])  // Rango de fechas
    ->groupBy('users.name')
    ->first();

    if (!$totalActivities) {
        $totalActivities = (object) ['totalActivities' => 0]; // Inicializar como un objeto para evitar errores
    }
  
    $cardDetails = DB::table('users')
    ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
    ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
    ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
    ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
    ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
    ->select('cat_cards.idCard', 'cat_cards.nameC', 'cat_cards.descriptionC', 'cat_cards.important', 'cat_cards.end_date', 'cat_cards.done')
    ->where('users.idUser', '=', $request->input('idUser'))
    ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
    ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])  // Rango de fecha
    ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
    ->get();


    // Obtener todas las id de las tarjetas del miembro
    $idCards = $cardDetails->pluck('idCard')->toArray();

    // Obtener las etiquetas seleccionadas por el usuario
    $idLabels = $request->input('idLabels');

    // Obtener la cantidad de actividades etiquetadas por ciertas etiquetas
    $totalLabels = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->join('rel_card_labels', 'cat_cards.idCard', '=', 'rel_card_labels.idCard')
        ->join('cat_labels', 'rel_card_labels.idLabel', '=', 'cat_labels.idLabel')
        ->select(DB::raw('count(cat_labels.idLabel) as TotalLabel'), 'cat_labels.nameL')
        ->whereIn('cat_cards.idCard', $idCards)
        ->whereIn('cat_labels.idLabel', $idLabels)
        ->where('users.idUser', '=', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
        ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])  // Rango de fechas
        ->groupBy('cat_labels.nameL')
        ->get();


        $importantActivities = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
        ->where('users.idUser', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', $request->input('idWorkEnv'))
        ->where('cat_cards.important', 1)
        ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
        ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])  // Rango de fechas
        ->count('cat_cards.idCard'); // Contar el número de actividades importantes
    

    
        $notimportantActivities = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
        ->where('users.idUser', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', $request->input('idWorkEnv'))
        ->where('cat_cards.important', 0)
        ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
        ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])  // Rango de fecha
        ->count('cat_cards.idCard'); // Contar el número de actividades no importantes
    

         // Datos para el gráfico de pastel de actividades completadas vs no completadas
    $pieChartUrl = "https://quickchart.io/chart";
    $pieChartData = [
        'type' => 'pie',
        'data' => [
            'labels' => ['Urgencia', 'No Urgencia'],
            'datasets' => [
                [
                    'label' => 'Actividades',
                    'data' => [$importantActivities, $notimportantActivities],
                    'backgroundColor' => ['#36A2EB', '#FF6384'],
                ]
            ]
        ],
        'options' => [
            'responsive' => true,
            'title' => [
                'display' => true,
                'text' => 'Actividades de urgencia vs de no urgencia'
            ]
        ]
    ];

    // Hacer la petición HTTP para generar la imagen del gráfico de pastel en base64
    $responsePie = Http::withOptions(['verify' => false])
        ->get($pieChartUrl, ['c' => json_encode($pieChartData), 'format' => 'png']);
    
    $pieChartBase64 = base64_encode($responsePie->body());

    // Extraer datos de etiquetas y cantidades para la gráfica
    $labels = $totalLabels->pluck('nameL');
    $counts = $totalLabels->pluck('TotalLabel');

    // Crear la gráfica usando QuickChart
    $chartUrl = "https://quickchart.io/chart";
    $chartData = [
        'type' => 'bar',
        'data' => [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total de Etiquetas',
                    'data' => $counts
                ]
            ]
        ]
    ];

    // Hacer la petición HTTP para generar la imagen en base64
    $response = Http::withOptions(['verify' => false])
    ->get($chartUrl, ['c' => json_encode($chartData), 'format' => 'png']);


    $chartBase64 = base64_encode($response->body());


    // Preparar la data a enviar a la vista
    $data = [
        'user' => $nameUser,
        'totalActivities' => $totalActivities,
        'cardDetails' => $cardDetails,
        'totalLabels' => $totalLabels,
        'chartBase64' => $chartBase64,
        'importantActivities' => $importantActivities,
        'notimportantActivities' => $notimportantActivities,
        'pieChartBase64' => $pieChartBase64, // Gráfico de pastel de actividades completadas vs no completadas
        'date1' => $request->input('date1'),
        'date2' => $request->input('date1')
    ];

    // Generar el PDF utilizando una vista
    $pdf = Pdf::loadView('pdfs.ParticipantReport', $data);

    // Retornar el PDF como respuesta
    return $pdf->download('participant_report_' .''.$nameUser.'.pdf');
}

public function ProductivityReport(Request $request)
{
    // Obtener el id del user actual
    $idUser = Auth::id();

    // Verificar si el user está dentro del entorno y es coordinador o líder.
    if (!JoinWorkEnvUser::where('idUser', $idUser)->where('idWorkEnv', $request->input('idWorkEnv'))->whereIn('privilege', [1, 2])->first()) {
        return response()->json(['error' => 'not found user']);
    }

    // Verificar si el miembro a sacar sus estadísticas existe
    if (!User::find($request->input('idUser'))) {
        return response()->json(['error' => 'not found user member']);
    }

    $User = User::find($request->input('idUser'));
    $nameUser = $User['name'];
    
    $completedActivities = DB::table('users')
    ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
    ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
    ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
    ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
    ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
    ->where('users.idUser', $request->input('idUser'))
    ->where('cat_workenvs.idWorkEnv', $request->input('idWorkEnv'))
    ->where('cat_cards.done', 1)  // Actividades completadas
    ->where('cat_cards.approbed', 1)  // Actividades aprobadas
    ->whereRaw('DATEDIFF(cat_cards.updated_at, cat_cards.end_date) <= 0')  // Completadas antes de la fecha límite
    ->whereBetween('cat_cards.updated_at', [$request->input('date1'), $request->input('date2')])  // Rango de fechas
    ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
    ->count('cat_cards.idCard'); // Contar el número de actividades completadas

    
    $notcompletedActivities = DB::table('users')
    ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
    ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
    ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
    ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
    ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
    ->where('users.idUser', $request->input('idUser'))
    ->where('cat_workenvs.idWorkEnv', $request->input('idWorkEnv'))
    ->where('cat_cards.done', 1)  // Actividades no completadas
    ->where('cat_cards.approbed', 1)  // Actividades aprobadas
    ->whereRaw('DATEDIFF(cat_cards.updated_at, cat_cards.end_date) > 0')  // No completadas después de la fecha límite
    ->whereBetween('cat_cards.updated_at', [$request->input('date1'), $request->input('date2')])  // Rango de fechas
    ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
    ->count('cat_cards.idCard'); // Contar el número de actividades no completadas

    
    // Datos para el gráfico de pastel de actividades completadas vs no completadas
    $pieChartUrl = "https://quickchart.io/chart";
    $pieChartData = [
        'type' => 'pie',
        'data' => [
            'labels' => ['Completadas', 'Completadas a destiempo'],
            'datasets' => [
                [
                    'label' => 'Actividades',
                    'data' => [$completedActivities, $notcompletedActivities],
                    'backgroundColor' => ['#36A2EB', '#FF6384'],
                ]
            ]
        ],
        'options' => [
            'responsive' => true,
            'title' => [
                'display' => true,
                'text' => 'Porcentaje de Actividades Completadas a tiempo vs Completadas a destiempo'
            ]
        ]
    ];
    $responsePie = Http::withOptions(['verify' => false])
        ->get($pieChartUrl, ['c' => json_encode($pieChartData), 'format' => 'png']);
    $pieChartBase64 = base64_encode($responsePie->body());

    $cardDetails = DB::table('users')
    ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
    ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
    ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
    ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
    ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
    ->select(
        'cat_cards.idCard',
        'cat_cards.nameC',
        'cat_cards.descriptionC',
        'cat_cards.important',
        'cat_cards.end_date',
        'cat_cards.updated_at',
        DB::raw('DATEDIFF(cat_cards.updated_at, cat_cards.end_date) as days_late')
    )
    ->where('users.idUser', '=', $request->input('idUser'))
    ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
    ->where('cat_cards.done', 1)  // Actividades completadas
    ->where('cat_cards.approbed', 1)  // Actividades aprobadas
    ->whereBetween('cat_cards.updated_at', [$request->input('date1'), $request->input('date2')])  // Rango de fechas
    ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
    ->get(); // Obtener los detalles de las actividades

    $idCards = $cardDetails->pluck('idCard')->toArray();

    // Obtener etiquetas seleccionadas por el usuario
    $idLabels = $request->input('idLabels');
    
    // Obtener la cantidad de actividades etiquetadas por ciertas etiquetas
    $totalLabels = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->join('rel_card_labels', 'cat_cards.idCard', '=', 'rel_card_labels.idCard')
        ->join('cat_labels', 'rel_card_labels.idLabel', '=', 'cat_labels.idLabel')
        ->select(DB::raw('count(cat_labels.idLabel) as TotalLabel'), 'cat_labels.nameL')
        ->whereIn('cat_cards.idCard', $idCards)
        ->whereIn('cat_labels.idLabel', $idLabels)
        ->where('users.idUser', '=', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
        ->where('cat_cards.done', 1)
        ->where('cat_cards.approbed', 1)
        ->whereBetween('cat_cards.updated_at', [$request->input('date1'), $request->input('date2')])
        ->groupBy('cat_labels.nameL')
        ->get();
    $labels = $totalLabels->pluck('nameL');
    $counts = $totalLabels->pluck('TotalLabel');

    // Crear la gráfica de barras para etiquetas
    $chartUrl = "https://quickchart.io/chart";
    $chartData = [
        'type' => 'bar',
        'data' => [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total de Etiquetas',
                    'data' => $counts
                ]
            ]
        ]
    ];
    $response = Http::withOptions(['verify' => false])
        ->get($chartUrl, ['c' => json_encode($chartData), 'format' => 'png']);
    $chartBase64 = base64_encode($response->body());

    $evolutionData = DB::table('users')
    ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
    ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
    ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
    ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
    ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
    ->select(
        DB::raw('DATE_FORMAT(cat_cards.updated_at, "%Y-%m-%d") as date'),
        DB::raw('COUNT(DISTINCT CASE WHEN DATEDIFF(cat_cards.updated_at, cat_cards.end_date) <= 0 THEN cat_cards.idCard END) as on_time'),
        DB::raw('COUNT(DISTINCT CASE WHEN DATEDIFF(cat_cards.updated_at, cat_cards.end_date) > 0 THEN cat_cards.idCard END) as late')
    )
    ->where('users.idUser', '=', $request->input('idUser'))
    ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
    ->where('cat_cards.done', 1)  // Actividades completadas
    ->where('cat_cards.approbed', 1)  // Actividades aprobadas
    ->whereBetween('cat_cards.updated_at', [$request->input('date1'), $request->input('date2')])  // Rango de fechas
    ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
    ->groupBy(DB::raw('DATE_FORMAT(cat_cards.updated_at, "%Y-%m-%d")'))  // Agrupar por fecha
    ->get(); // Obtener datos para la gráfica

    
    $dates = $evolutionData->pluck('date');
    $onTimeCounts = $evolutionData->pluck('on_time');
    $lateCounts = $evolutionData->pluck('late');

    $lineChartUrl = "https://quickchart.io/chart";
    $lineChartData = [
        'type' => 'line',
        'data' => [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'A Tiempo',
                    'data' => $onTimeCounts,
                    'borderColor' => '#36A2EB',
                    'fill' => false
                ],
                [
                    'label' => 'Atrasado',
                    'data' => $lateCounts,
                    'borderColor' => '#FF6384',
                    'fill' => false
                ]
            ]
        ],
        'options' => [
            'responsive' => true,
            'title' => [
                'display' => true,
                'text' => 'Evolución del Cumplimiento de Plazos'
            ]
        ]
    ];
    $responseLine = Http::withOptions(['verify' => false])
        ->get($lineChartUrl, ['c' => json_encode($lineChartData), 'format' => 'png']);
    $lineChartBase64 = base64_encode($responseLine->body());

    // Generar el PDF con dompdf
    $pdf = PDF::loadView('pdfs.ProductivityReport', [
        'user' => $nameUser,
        'pieChartBase64' => $pieChartBase64,
        'chartBase64' => $chartBase64,
        'lineChartBase64' => $lineChartBase64,
        'cardDetails' => $cardDetails,
        'totalLabels' => $totalLabels,
        'date1' => $request->input('date1'),
        'date2' => $request->input('date2')
    ]);

    return $pdf->download('productivity_report'.$nameUser.'pdf');
}

public function DeliveryActivitiesReport(Request $request)
{
    // Obtener el id del user actual
    $idUser = Auth::id();

    // Verificar si el user está dentro del entorno
    if (!JoinWorkEnvUser::where('idUser', $idUser)->where('idWorkEnv', $request->input('idWorkEnv'))->first()) {
        return response()->json(['error' => 'User not found in environment'], 404);
    }

    // Obtener el nombre del usuario
    $User = User::find($idUser);
    $nameUser = $User ? $User->name : 'Unknown User';

    $cardDetails = DB::table('users')
    ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
    ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
    ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
    ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
    ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
    ->select(
        'cat_cards.idCard',
        'cat_cards.nameC',
        'cat_cards.descriptionC',
        'cat_cards.important',
        'cat_cards.end_date',
        'cat_cards.updated_at',
        DB::raw('DATEDIFF(cat_cards.end_date, NOW()) as left_days')
    )
    ->where('users.idUser', '=', $idUser)
    ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
    ->where('cat_cards.approbed', 0)  // Actividades no aprobadas
    ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])
    ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
    ->get();


    $totalActivities = DB::table('users')
    ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
    ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
    ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
    ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
    ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')  // Relación con rel_cards_users
    ->select(DB::raw('COUNT(cat_cards.idCard) as totalActivities'))
    ->where('users.idUser', '=', $idUser)
    ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
    ->where('cat_cards.approbed', 0)  // Actividades no aprobadas
    ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])
    ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
    ->groupBy('users.idUser')  // Agrupar por ID de usuario
    ->first();

    if (!$totalActivities) {
        $totalActivities = (object) ['totalActivities' => 0]; // Inicializar como un objeto para evitar errores
    }

    if (!$totalActivities) {
        return response()->json(['error' => 'No activities found for the user'], 404);
    }

   // Obtener las actividades casi expiradas o expiradas desde rel_cards_users
    $almostExpiredActivities = DB::table('rel_cards_users')
    ->select(
        'cat_workenvs.idWorkEnv AS idWorkEnv',
        'cat_workenvs.nameW',
        DB::raw('COUNT(DISTINCT CASE 
                    WHEN TIMESTAMPDIFF(DAY, cat_cards.end_date, NOW()) <= 7 
                        AND TIMESTAMPDIFF(DAY, cat_cards.end_date, NOW()) >= 0
                    OR cat_cards.end_date < NOW()
                    THEN cat_cards.idCard 
                END) AS AlmostExpiredOrExpiredActivities')
    )
    ->join('cat_cards', 'rel_cards_users.idCard', '=', 'cat_cards.idCard')
    ->join('cat_lists', 'cat_cards.idList', '=', 'cat_lists.idList')
    ->join('cat_boards', 'cat_lists.idBoard', '=', 'cat_boards.idBoard')
    ->join('cat_workenvs', 'cat_boards.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('rel_join_workenv_users', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
    ->join('users', 'rel_join_workenv_users.idUser', '=', 'users.idUser')
    ->where('rel_join_workenv_users.logicdeleted', '!=', 1)
    ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
    ->where('users.idUser', '=', $idUser)
    ->where('cat_workenvs.logicdeleted', '!=', 1)
    ->where('cat_cards.approbed', 0)
    ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])
    ->groupBy('cat_workenvs.idWorkEnv', 'cat_workenvs.nameW')
    ->first();


    $almostExpiredActivitiesCount = $almostExpiredActivities ? $almostExpiredActivities->AlmostExpiredOrExpiredActivities : 0;
    $totalActivitiesCount = $totalActivities ? $totalActivities->totalActivities : 0;

    // Calcular actividades no casi expiradas
    $notAlmostExpiredActivities = $totalActivitiesCount - $almostExpiredActivitiesCount;

    // Datos para el gráfico de pastel
    $pieChartUrl = "https://quickchart.io/chart";
    $pieChartData = [
        'type' => 'pie',
        'data' => [
            'labels' => ['A punto de expirar o expiradas', 'En tiempo'],
            'datasets' => [
                [
                    'label' => 'Actividades',
                    'data' => [$almostExpiredActivitiesCount, $notAlmostExpiredActivities],
                    'backgroundColor' => ['#36A2EB', '#FF6384'],
                ]
            ]
        ],
        'options' => [
            'responsive' => true,
            'title' => [
                'display' => true,
                'text' => 'Actividades a punto de expirar o expiradas vs en tiempo'
            ]
        ]
    ];

    // Solicitar el gráfico a QuickChart
    $responsePie = Http::withOptions(['verify' => false])
        ->get($pieChartUrl, ['c' => json_encode($pieChartData), 'format' => 'png']);

    if (!$responsePie->ok()) {
        return response()->json(['error' => 'Error generating pie chart'], 500);
    }

    $pieChartBase64 = base64_encode($responsePie->body());


  // Contar actividades por fecha dentro del rango específico del entorno y del usuario
    $activitiesByDate = DB::table('rel_cards_users')
    ->join('cat_cards', 'rel_cards_users.idCard', '=', 'cat_cards.idCard')
    ->join('cat_lists', 'cat_cards.idList', '=', 'cat_lists.idList')
    ->join('cat_boards', 'cat_lists.idBoard', '=', 'cat_boards.idBoard')
    ->join('cat_workenvs', 'cat_boards.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('rel_join_workenv_users', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv') // Join para el entorno
    ->where('rel_join_workenv_users.idUser', $idUser) // Filtrar por el usuario
    ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv')) // Filtrar por entorno
    ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')]) // Rango de fechas
    ->groupBy(DB::raw('DATE(cat_cards.end_date)'))
    ->select(DB::raw('DATE(cat_cards.end_date) as delivery_date'), DB::raw('COUNT(*) as task_count'))
    ->get();



    // Preparar datos para el gráfico de barras
    $labels = [];
    $data = [];

    foreach ($activitiesByDate as $activity) {
        $labels[] = $activity->delivery_date;
        $data[] = $activity->task_count;
    }

    // Gráfico de barras
    $barChartUrl = "https://quickchart.io/chart";
    $barChartData = [
        'type' => 'bar',
        'data' => [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Tareas a entregar',
                    'data' => $data,
                    'backgroundColor' => '#4CAF50'
                ]
            ]
        ],
        'options' => [
            'responsive' => true,
            'title' => [
                'display' => true,
                'text' => 'Tareas por fecha de entrega'
            ],
            'scales' => [
                'yAxes' => [[
                    'ticks' => [
                        'beginAtZero' => true
                    ]
                ]]
            ]
        ]
    ];

    // Solicitar el gráfico a QuickChart
    $responseBar = Http::withOptions(['verify' => false])
        ->get($barChartUrl, ['c' => json_encode($barChartData), 'format' => 'png']);

    if (!$responseBar->ok()) {
        return response()->json(['error' => 'Error generating bar chart'], 500);
    }

    $barChartBase64 = base64_encode($responseBar->body());


    // Preparar datos para la vista del PDF
    $data = [
        'user' => $nameUser,
        'totalActivities' => $totalActivities,
        'notAlmostExpiredActivities' => $notAlmostExpiredActivities,
        'almostExpiredActivities' => $almostExpiredActivitiesCount,
        'cardDetails' => $cardDetails,
        'pieChartBase64' => $pieChartBase64,
        'date1' => $request->input('date1'),
        'date2' => $request->input('date2'),
        'barChartBase64'=> $barChartBase64
    ];

    // Generar el PDF utilizando una vista
    $pdf = Pdf::loadView('pdfs.DeliveryActivitiesReport', $data);

    // Retornar el PDF como respuesta
    return $pdf->download('deliveryactivities_report_' . $nameUser . '.pdf');
}


public function DeliveryActivitiesReportCoordinator(Request $request){

     // Obtener el id del user actual
     $idUser = Auth::id();

     // Verificar si el user está dentro del entorno y es coordinador
     if (!JoinWorkEnvUser::where('idUser', $idUser)->where('idWorkEnv', $request->input('idWorkEnv'))->whereIn('privilege', [1,2])->first()) {
         return response()->json(['error' => 'User not found in environment'], 404);
     }
 
     // Obtener el nombre del usuario
     $User = User::find($idUser);
     $nameUser = $User ? $User->name : 'Unknown User';

      // Obtener el detalle de cada actividad
      $activities = DB::table('cat_activity_coordinatorleaders')
      ->join('cat_grouptasks_coordinatorleaders', 'cat_activity_coordinatorleaders.idgrouptaskcl', '=', 'cat_grouptasks_coordinatorleaders.idgrouptaskcl')
      ->join('rel_join_workenv_users', 'cat_grouptasks_coordinatorleaders.idjoinuserwork', '=', 'rel_join_workenv_users.idjoinuserwork')
      ->join('cat_workenvs', 'rel_join_workenv_users.idworkenv', '=', 'cat_workenvs.idworkenv')
      ->join('users', 'rel_join_workenv_users.iduser', '=', 'users.iduser')
      ->select(
          'cat_activity_coordinatorleaders.nameT',
          'cat_activity_coordinatorleaders.descriptionT',
          'cat_activity_coordinatorleaders.end_date',
          DB::raw('DATEDIFF(cat_activity_coordinatorleaders.end_date, NOW()) as left_days')
      )
      ->where('users.iduser', '=', $idUser)
      ->where('cat_workenvs.idworkenv', '=',  $request->input('idWorkEnv'))
      ->where('cat_activity_coordinatorleaders.done', 0)
      ->whereBetween('cat_activity_coordinatorleaders.end_date', [$request->input('date1'), $request->input('date2')])
      ->get();


    $almostExpired = DB::table('cat_activity_coordinatorleaders')
        ->join('cat_grouptasks_coordinatorleaders', 'cat_activity_coordinatorleaders.idgrouptaskcl', '=', 'cat_grouptasks_coordinatorleaders.idgrouptaskcl')
        ->join('rel_join_workenv_users', 'cat_grouptasks_coordinatorleaders.idjoinuserwork', '=', 'rel_join_workenv_users.idjoinuserwork')
        ->join('cat_workenvs', 'rel_join_workenv_users.idworkenv', '=', 'cat_workenvs.idworkenv')
        ->join('users', 'rel_join_workenv_users.iduser', '=', 'users.iduser')
        ->select(
            'cat_workenvs.idWorkEnv AS idWorkEnv',
            'cat_workenvs.nameW',
            DB::raw('COUNT(DISTINCT CASE 
                        WHEN TIMESTAMPDIFF(DAY,cat_activity_coordinatorleaders.end_date, NOW()) <= 7 
                             AND TIMESTAMPDIFF(DAY, cat_activity_coordinatorleaders.end_date, NOW()) >= 0
                        OR cat_activity_coordinatorleaders.end_date < NOW()
                        THEN cat_activity_coordinatorleaders.idactivitycl
                    END) AS AlmostExpiredOrExpiredActivities')
        )
        ->where('users.iduser', '=', $idUser)
        ->where('cat_workenvs.idworkenv', '=',  $request->input('idWorkEnv'))
        ->where('cat_activity_coordinatorleaders.done', 0)
        ->whereBetween('cat_activity_coordinatorleaders.end_date', [$request->input('date1'), $request->input('date2')])
        ->groupBy('users.name')
        ->first();

        $totalActivities = DB::table('cat_activity_coordinatorleaders')
        ->join('cat_grouptasks_coordinatorleaders', 'cat_activity_coordinatorleaders.idgrouptaskcl', '=', 'cat_grouptasks_coordinatorleaders.idgrouptaskcl')
        ->join('rel_join_workenv_users', 'cat_grouptasks_coordinatorleaders.idjoinuserwork', '=', 'rel_join_workenv_users.idjoinuserwork')
        ->join('cat_workenvs', 'rel_join_workenv_users.idworkenv', '=', 'cat_workenvs.idworkenv')
        ->join('users', 'rel_join_workenv_users.iduser', '=', 'users.iduser')
        ->select(
            DB::raw('COUNT(cat_activity_coordinatorleaders.idactivitycl)
                     AS totalActivities')
        )
        ->where('users.iduser', '=', $idUser)
        ->where('cat_workenvs.idworkenv', '=', $request->input('idWorkEnv'))
        ->where('cat_activity_coordinatorleaders.done', 0)
        ->whereBetween('cat_activity_coordinatorleaders.end_date', [$request->input('date1'), $request->input('date2')])
        ->groupBy('users.name')
        ->first();

        if (!$totalActivities) {
            $totalActivities = (object) ['totalActivities' => 0]; // Inicializar como un objeto para evitar errores
        }

        $almostExpiredActivitiesCount = $almostExpired ? $almostExpired->AlmostExpiredOrExpiredActivities : 0;
        $totalActivitiesCount = $totalActivities ? $totalActivities->totalActivities : 0;
    
        // Calcular actividades no casi expiradas
        $notAlmostExpiredActivities = $totalActivitiesCount - $almostExpiredActivitiesCount;
    
        // Datos para el gráfico de pastel
        $pieChartUrl = "https://quickchart.io/chart";
        $pieChartData = [
            'type' => 'pie',
            'data' => [
                'labels' => ['A punto de expirar o expiradas', 'En tiempo'],
                'datasets' => [
                    [
                        'label' => 'Actividades',
                        'data' => [$almostExpiredActivitiesCount, $notAlmostExpiredActivities],
                        'backgroundColor' => ['#36A2EB', '#FF6384'],
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'title' => [
                    'display' => true,
                    'text' => 'Actividades a punto de expirar o expiradas vs en tiempo'
                ]
            ]
        ];
    
        // Solicitar el gráfico a QuickChart
        $responsePie = Http::withOptions(['verify' => false])
            ->get($pieChartUrl, ['c' => json_encode($pieChartData), 'format' => 'png']);
    
        if (!$responsePie->ok()) {
            return response()->json(['error' => 'Error generating pie chart'], 500);
        }
    
        $pieChartBase64 = base64_encode($responsePie->body());

         // Consulta para el gráfico de barras (cantidad de actividades por fecha)
    $activitiesByDate = DB::table('cat_activity_coordinatorleaders')
    ->join('cat_grouptasks_coordinatorleaders', 'cat_activity_coordinatorleaders.idgrouptaskcl', '=', 'cat_grouptasks_coordinatorleaders.idgrouptaskcl')
    ->join('rel_join_workenv_users', 'cat_grouptasks_coordinatorleaders.idjoinuserwork', '=', 'rel_join_workenv_users.idjoinuserwork')
    ->join('cat_workenvs', 'rel_join_workenv_users.idworkenv', '=', 'cat_workenvs.idworkenv')
    ->join('users', 'rel_join_workenv_users.iduser', '=', 'users.iduser')
    ->select(
        DB::raw('DATE(cat_activity_coordinatorleaders.end_date) as delivery_date'),
        DB::raw('COUNT(cat_activity_coordinatorleaders.idactivitycl) as activity_count')
    )
    ->where('users.iduser', '=', $idUser)
    ->where('cat_workenvs.idworkenv', '=', $request->input('idWorkEnv'))
    ->where('cat_activity_coordinatorleaders.done', 0)
    ->whereBetween('cat_activity_coordinatorleaders.end_date', [$request->input('date1'), $request->input('date2')])
    ->groupBy(DB::raw('DATE(cat_activity_coordinatorleaders.end_date)'))
    ->get();

    // Preparar datos para el gráfico de barras
    $dates = [];
    $activityCounts = [];

    foreach ($activitiesByDate as $activity) {
        $dates[] = $activity->delivery_date;
        $activityCounts[] = $activity->activity_count;
    }

    // Datos para el gráfico de barras d
    $barChartUrl = "https://quickchart.io/chart";
    $barChartData = [
        'type' => 'bar',
        'data' => [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Cantidad de Actividades',
                    'data' => $activityCounts,
                    'backgroundColor' => '#36A2EB',
                ]
            ]
        ],
        'options' => [
            'responsive' => true,
            'title' => [
                'display' => true,
                'text' => 'Actividades por Fecha de Entrega'
            ],
            'scales' => [
                'yAxes' => [[
                    'ticks' => ['beginAtZero' => true]
                ]]
            ]
        ]
    ];

    // Solicitar el gráfico de barras a QuickChart
    $responseBar = Http::withOptions(['verify' => false])
        ->get($barChartUrl, ['c' => json_encode($barChartData), 'format' => 'png']);

    if (!$responseBar->ok()) {
        return response()->json(['error' => 'Error generating bar chart'], 500);
    }

    $barChartBase64 = base64_encode($responseBar->body());

    $data = [
        'barChartBase64' => $barChartBase64,
        'pieChartBase64' => $pieChartBase64,
        'user' => $nameUser,
        'cardDetails' => $activities,
        'date1' => $request->input('date1'),
        'date2' => $request->input('date2'),
        'totalActivities' => $totalActivities

    ];

      // Generar el PDF utilizando una vista
      $pdf = Pdf::loadView('pdfs.DeliveryActivitiesCoordinatorReport', $data);

      // Retornar el PDF como respuesta
      return $pdf->download('deliveryactivitiescoordinator_report_' . $nameUser . '.pdf');


}


public function PendingActivitiesReport(Request $request)
{
    $idUsers = $request->input('idUsers');
    $idWorkEnv = $request->input('idWorkEnv');
    $date1 = $request->input('date1');
    $date2 = $request->input('date2');

    // Obtener el total de actividades pendientes de cada usuario
    $totalActivities = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
        ->select('users.name', DB::raw('COUNT(cat_cards.idCard) as totalActivities'))
        ->whereIn('users.idUser', $idUsers)
        ->where('cat_workenvs.idWorkEnv', '=', $idWorkEnv)
        ->where('cat_cards.approbed', 0)
        ->whereBetween('cat_cards.end_date', [$date1, $date2])
        ->where('rel_cards_users.logicdeleted', '!=', 1)
        ->groupBy('users.idUser', 'users.name')
        ->get();



    $userNames = $totalActivities->pluck('name')->toArray();
    $activityCounts = $totalActivities->pluck('totalActivities')->toArray();

   // Obtener las actividades pendientes por fecha de entrega
    $activitiesByDate = DB::table('cat_cards')
    ->join('cat_lists', 'cat_cards.idList', '=', 'cat_lists.idList')
    ->join('cat_boards', 'cat_lists.idBoard', '=', 'cat_boards.idBoard')
    ->join('cat_workenvs', 'cat_boards.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard') // Relación con rel_cards_users
    ->join('rel_join_workenv_users', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv') // Relación con workenv
    ->join('users', 'rel_join_workenv_users.idUser', '=', 'users.idUser') // Obtener el usuario
    ->select(
        DB::raw('DATE(cat_cards.end_date) as delivery_date'), // Fecha de entrega
        DB::raw('COUNT(cat_cards.idCard) as task_count')      // Contar actividades
    )
    ->whereIn('users.idUser', $idUsers) // Filtrar por los usuarios específicos
    ->where('cat_workenvs.idWorkEnv', '=', $idWorkEnv) // Filtrar por el entorno de trabajo
    ->where('cat_cards.approbed', 0)    // Solo actividades no aprobadas
    ->whereBetween('cat_cards.end_date', [$date1, $date2]) // Filtrar por el rango de fechas
    ->where('rel_cards_users.logicdeleted', '!=', 1) // Excluir actividades eliminadas lógicamente
    ->groupBy(DB::raw('DATE(cat_cards.end_date)')) // Agrupar por fecha de entrega
    ->get();



    $dates = $activitiesByDate->pluck('delivery_date')->toArray();
    $tasksByDate = $activitiesByDate->pluck('task_count')->toArray();

    // Gráfico de barras con las actividades por usuario
    $barChartData = [
        'type' => 'bar',
        'data' => [
            'labels' => $userNames,
            'datasets' => [
                [
                    'label' => 'Actividades Pendientes',
                    'data' => $activityCounts,
                    'backgroundColor' => '#FF6384',
                ]
            ]
        ],
        'options' => [
            'responsive' => true,
            'title' => [
                'display' => true,
                'text' => 'Actividades Pendientes por Usuario'
            ],
            'scales' => [
                'yAxes' => [[
                    'ticks' => ['beginAtZero' => true]
                ]]
            ]
        ]
    ];

    // Gráfico de líneas con las actividades por fecha
    $lineChartData = [
        'type' => 'line',
        'data' => [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Actividades Pendientes',
                    'data' => $tasksByDate,
                    'fill' => false,
                    'borderColor' => '#36A2EB',
                    'lineTension' => 0.1
                ]
            ]
        ],
        'options' => [
            'responsive' => true,
            'title' => [
                'display' => true,
                'text' => 'Actividades Pendientes por Fecha'
            ],
            'scales' => [
                'yAxes' => [[
                    'ticks' => ['beginAtZero' => true]
                ]]
            ]
        ]
    ];

    // Solicitar el gráfico de barras en formato PNG y convertirlo a base64
    $barChartResponse = Http::withOptions(['verify' => false])
        ->get("https://quickchart.io/chart", ['c' => json_encode($barChartData), 'format' => 'png']);
    
    $barChartBase64 =  base64_encode($barChartResponse->body());

    // Solicitar el gráfico de líneas en formato PNG y convertirlo a base64
    $lineChartResponse = Http::withOptions(['verify' => false])
        ->get("https://quickchart.io/chart", ['c' => json_encode($lineChartData), 'format' => 'png']);
    
    $lineChartBase64 = base64_encode($lineChartResponse->body());
    
    $cardDetails = DB::table('users')
    ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
    ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
    ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
    ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
    ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
    ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
    ->select(
        'cat_cards.idCard',
        'cat_cards.nameC',
        'cat_cards.descriptionC',
        'cat_cards.important',
        'cat_cards.end_date',
        'cat_cards.updated_at',
        DB::raw('DATEDIFF(cat_cards.end_date, NOW()) as left_days')
    )
    ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
    ->where('cat_cards.done', 0)  // Actividades no completadas
    ->where('cat_cards.approbed', 0)  // Actividades no aprobadas
    ->whereIn('users.idUser', $idUsers)
    ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])  // Rango de fechas
    ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
    ->get(); // Obtener los detalles de las actividades

    $data = [
        "barChartBase64" => $barChartBase64,
        "lineChartBase64" => $lineChartBase64,
        "cardDetails" => $cardDetails,
        "date1" => $date1,
        "date2" => $date2,
        "totalActivities" => $cardDetails->count()
    ];

    $pdf = Pdf::loadView('pdfs.PendingActivitiesReport',$data);
    return $pdf->download('PendingActivitiesReport.pdf');
    
}




    public function CompletedActivitiesReport(Request $request){
        $idUsers = $request->input('idUsers');
        $idWorkEnv = $request->input('idWorkEnv');
        $date1 = $request->input('date1');
        $date2 = $request->input('date2');
    
        // Obtener el total de actividades pendientes de cada usuario
        $totalActivities = DB::table('users')
            ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
            ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
            ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
            ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
            ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
            ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
            ->select('users.name', DB::raw('COUNT(cat_cards.idCard) as totalActivities'))
            ->whereIn('users.idUser', $idUsers)
            ->where('cat_workenvs.idWorkEnv', '=', $idWorkEnv)
            ->where('cat_cards.done', 1)
            ->where('cat_cards.approbed', 1)
            ->whereBetween('cat_cards.end_date', [$date1, $date2])
            ->where('rel_cards_users.logicdeleted', '!=', 1)
            ->groupBy('users.idUser', 'users.name')
            ->get();
    
    
    
        $userNames = $totalActivities->pluck('name')->toArray();
        $activityCounts = $totalActivities->pluck('totalActivities')->toArray();
    
       // Obtener las actividades pendientes por fecha de entrega
        $activitiesByDate = DB::table('cat_cards')
        ->join('cat_lists', 'cat_cards.idList', '=', 'cat_lists.idList')
        ->join('cat_boards', 'cat_lists.idBoard', '=', 'cat_boards.idBoard')
        ->join('cat_workenvs', 'cat_boards.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard') // Relación con rel_cards_users
        ->join('rel_join_workenv_users', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv') // Relación con workenv
        ->join('users', 'rel_join_workenv_users.idUser', '=', 'users.idUser') // Obtener el usuario
        ->select(
            DB::raw('DATE(cat_cards.end_date) as delivery_date'), // Fecha de entrega
            DB::raw('COUNT(cat_cards.idCard) as task_count')      // Contar actividades
        )
        ->whereIn('users.idUser', $idUsers) // Filtrar por los usuarios específicos
        ->where('cat_workenvs.idWorkEnv', '=', $idWorkEnv) // Filtrar por el entorno de trabajo
        ->where('cat_cards.approbed', 1)
        ->where('cat_cards.done', 1)        // Solo actividades no aprobadas
        ->whereBetween('cat_cards.end_date', [$date1, $date2]) // Filtrar por el rango de fechas
        ->where('rel_cards_users.logicdeleted', '!=', 1) // Excluir actividades eliminadas lógicamente
        ->groupBy(DB::raw('DATE(cat_cards.end_date)')) // Agrupar por fecha de entrega
        ->get();
    
    
    
        $dates = $activitiesByDate->pluck('delivery_date')->toArray();
        $tasksByDate = $activitiesByDate->pluck('task_count')->toArray();
    
        // Gráfico de barras con las actividades por usuario
        $barChartData = [
            'type' => 'bar',
            'data' => [
                'labels' => $userNames,
                'datasets' => [
                    [
                        'label' => 'Actividades Completadas',
                        'data' => $activityCounts,
                        'backgroundColor' => '#FF6384',
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'title' => [
                    'display' => true,
                    'text' => 'Actividades Completadas por Usuario'
                ],
                'scales' => [
                    'yAxes' => [[
                        'ticks' => ['beginAtZero' => true]
                    ]]
                ]
            ]
        ];
    
        // Gráfico de líneas con las actividades por fecha
        $lineChartData = [
            'type' => 'line',
            'data' => [
                'labels' => $dates,
                'datasets' => [
                    [
                        'label' => 'Actividades Completadas',
                        'data' => $tasksByDate,
                        'fill' => false,
                        'borderColor' => '#36A2EB',
                        'lineTension' => 0.1
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'title' => [
                    'display' => true,
                    'text' => 'Actividades Completadas por Fecha'
                ],
                'scales' => [
                    'yAxes' => [[
                        'ticks' => ['beginAtZero' => true]
                    ]]
                ]
            ]
        ];
    
        // Solicitar el gráfico de barras en formato PNG y convertirlo a base64
        $barChartResponse = Http::withOptions(['verify' => false])
            ->get("https://quickchart.io/chart", ['c' => json_encode($barChartData), 'format' => 'png']);
        
        $barChartBase64 =  base64_encode($barChartResponse->body());
    
        // Solicitar el gráfico de líneas en formato PNG y convertirlo a base64
        $lineChartResponse = Http::withOptions(['verify' => false])
            ->get("https://quickchart.io/chart", ['c' => json_encode($lineChartData), 'format' => 'png']);
        
        $lineChartBase64 = base64_encode($lineChartResponse->body());
        
        $cardDetails = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->join('rel_cards_users', 'cat_cards.idCard', '=', 'rel_cards_users.idCard')
        ->select(
            'cat_cards.idCard',
            'cat_cards.nameC',
            'cat_cards.descriptionC',
            'cat_cards.important',
            'cat_cards.end_date',
            'cat_cards.updated_at',
            DB::raw('DATEDIFF(cat_cards.updated_at, cat_cards.end_date) as days_late')
        )
        ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
        ->where('cat_cards.done', 1)  // Actividades no completadas
        ->where('cat_cards.approbed', 1)  // Actividades no aprobadas
        ->whereIn('users.idUser', $idUsers)
        ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])  // Rango de fechas
        ->where('rel_cards_users.logicdeleted', '!=', 1)  // Excluir tarjetas eliminadas lógicamente
        ->get(); // Obtener los detalles de las actividades
    
        $data = [
            "barChartBase64" => $barChartBase64,
            "lineChartBase64" => $lineChartBase64,
            "cardDetails" => $cardDetails,
            "date1" => $date1,
            "date2" => $date2,
            "totalActivities" => $cardDetails->count()
        ];
    
        $pdf = Pdf::loadView('pdfs.CompletedActivitiesReport',$data);
        return $pdf->download('CompletedActivitiesReport.pdf');
        
    }

}
