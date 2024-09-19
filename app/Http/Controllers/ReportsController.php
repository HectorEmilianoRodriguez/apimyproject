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
        ->select(DB::raw('count(cat_cards.idCard) as totalActivities'))
        ->where('users.idUser', '=', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
        ->groupBy('users.name')
        ->first();

    // Obtener el detalle de cada actividad que tiene asignada el miembro
    $cardDetails = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->select('cat_cards.idCard', 'cat_cards.nameC', 'cat_cards.descriptionC', 'cat_cards.important', 'cat_cards.end_date', 'cat_cards.done')
        ->where('users.idUser', '=', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
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
        ->groupBy('cat_labels.nameL')
        ->get();


    $importantActivities = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->where('users.idUser', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', $request->input('idWorkEnv'))
        ->where('cat_cards.important', 1)
        ->count('cat_cards.idCard'); // Contar el número de actividades importantes  

    
     $notimportantActivities = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->where('users.idUser', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', $request->input('idWorkEnv'))
        ->where('cat_cards.important', 0)
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
        'pieChartBase64' => $pieChartBase64 // Gráfico de pastel de actividades completadas vs no completadas

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
    
    // Obtener estadísticas de actividades completadas y no completadas
    $completedActivities = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->where('users.idUser', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', $request->input('idWorkEnv'))
        ->where('cat_cards.done', 1)
        ->where('cat_cards.approbed', 1)
        ->whereRaw('DATEDIFF(cat_cards.updated_at, cat_cards.end_date) <= 0')
        ->whereBetween('cat_cards.updated_at', [$request->input('date1'), $request->input('date2')])
        ->count('cat_cards.idCard'); // Actividades completadas
    
    $notcompletedActivities = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->where('users.idUser', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', $request->input('idWorkEnv'))
        ->where('cat_cards.done', 1)
        ->where('cat_cards.approbed', 1)
        ->whereRaw('DATEDIFF(cat_cards.updated_at, cat_cards.end_date) > 0')
        ->whereBetween('cat_cards.updated_at', [$request->input('date1'), $request->input('date2')])
        ->count('cat_cards.idCard'); // Actividades no completadas
    
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

    // Obtener el detalle de cada actividad
    $cardDetails = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
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
        ->where('cat_cards.done', 1)
        ->where('cat_cards.approbed', 1)
        ->whereBetween('cat_cards.updated_at', [$request->input('date1'), $request->input('date2')])
        ->get();
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

    // Datos para la gráfica de evolución de cumplimiento de plazos
    $evolutionData = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->select(
            DB::raw('DATE_FORMAT(cat_cards.updated_at, "%Y-%m-%d") as date'),
            DB::raw('COUNT(DISTINCT CASE WHEN DATEDIFF(cat_cards.updated_at, cat_cards.end_date) <= 0 THEN cat_cards.idCard END) as on_time'),
            DB::raw('COUNT(DISTINCT CASE WHEN DATEDIFF(cat_cards.updated_at, cat_cards.end_date) > 0 THEN cat_cards.idCard END) as late')
        )
        ->where('users.idUser', '=', $request->input('idUser'))
        ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
        ->where('cat_cards.done', 1)
        ->where('cat_cards.approbed', 1)
        ->whereBetween('cat_cards.updated_at', [$request->input('date1'), $request->input('date2')])
        ->groupBy(DB::raw('DATE_FORMAT(cat_cards.updated_at, "%Y-%m-%d")'))
        ->get();
    
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

    // Obtener el detalle de cada actividad
    $cardDetails = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->select(
            'cat_cards.idCard',
            'cat_cards.nameC',
            'cat_cards.descriptionC',
            'cat_cards.important',
            'cat_cards.end_date',
            'cat_cards.updated_at',
            DB::raw('DATEDIFF(cat_cards.end_date, now()) as left_days')
        )
        ->where('users.idUser', '=', $idUser)
        ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
        ->where('cat_cards.approbed', 0)
        ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])
        ->get();

    // Obtener todas las actividades totales que posee el miembro
    $totalActivities = DB::table('users')
        ->join('rel_join_workenv_users', 'users.idUser', '=', 'rel_join_workenv_users.idUser')
        ->join('cat_workenvs', 'rel_join_workenv_users.idWorkEnv', '=', 'cat_workenvs.idWorkEnv')
        ->join('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->join('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->join('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->select(DB::raw('COUNT(cat_cards.idCard) as totalActivities'))
        ->where('users.idUser', '=', $idUser)
        ->where('cat_workenvs.idWorkEnv', '=', $request->input('idWorkEnv'))
        ->where('cat_cards.approbed', 0)
        ->whereBetween('cat_cards.end_date', [$request->input('date1'), $request->input('date2')])
        ->groupBy('users.name')
        ->first();

    if (!$totalActivities) {
        return response()->json(['error' => 'No activities found for the user'], 404);
    }

    // Obtener las actividades casi expiradas o expiradas
    $almostExpiredActivities = DB::table('cat_workenvs')
        ->select(
            'cat_workenvs.idWorkEnv AS idWorkEnv',
            'cat_workenvs.nameW',
            DB::raw('COUNT(DISTINCT CASE 
                        WHEN TIMESTAMPDIFF(DAY,cat_cards.end_date, NOW()) <= 7 
                             AND TIMESTAMPDIFF(DAY, cat_cards.end_date, NOW()) >= 0
                        OR cat_cards.end_date < NOW()
                        THEN cat_cards.idCard 
                    END) AS AlmostExpiredOrExpiredActivities')
        )
        ->leftJoin('rel_join_workenv_users', 'cat_workenvs.idWorkEnv', '=', 'rel_join_workenv_users.idWorkEnv')
        ->leftJoin('cat_boards', 'cat_workenvs.idWorkEnv', '=', 'cat_boards.idWorkEnv')
        ->leftJoin('cat_lists', 'cat_boards.idBoard', '=', 'cat_lists.idBoard')
        ->leftJoin('cat_cards', 'cat_lists.idList', '=', 'cat_cards.idList')
        ->leftJoin('users', 'rel_join_workenv_users.idUser', '=', 'users.idUser')
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


    // Contar actividades por fecha dentro del rango
    $activitiesByDate = DB::table('cat_cards')
        ->select(DB::raw('DATE(end_date) as delivery_date'), DB::raw('COUNT(*) as task_count'))
        ->whereBetween('end_date', [$request->input('date1'), $request->input('date2')])
        ->groupBy(DB::raw('DATE(end_date)'))
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


}
