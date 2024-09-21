<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de actividades completadas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            text-align: center; /* Centra el texto en el cuerpo del documento */
        }

        .header {
            margin-bottom: 20px;
        }

        .logo-wrapper {
            display: inline-block;
            margin-bottom: 20px;
        }

        .logo-wrapper img {
            width: 100px;
            height: auto;
            vertical-align: middle;
        }

        .logo-wrapper h1 {
            font-size: 24px;
            margin: 0;
            display: inline;
            vertical-align: middle;
        }

        h2 {
            font-size: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .table {
            width: 80%;
            margin: 0 auto; /* Centra la tabla horizontalmente */
            border-collapse: collapse;
        }

        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center; /* Centra el texto dentro de las celdas */
        }

        .table th {
            background-color: #f4f4f4;
        }

        .chart-wrapper {
            margin-top: 40px;
        }

        .chart-wrapper img {
            width: 500px;
            height: auto;
        }

    </style>
</head>
<body>

    <div class="header">
        <div class="logo-wrapper">
            <img src="{{ public_path('images/logo.png') }}" alt="LogoUpemor">
            <h1>Reporte de tareas completadas</h1>
        </div>
        <h2>Actividades completadas ({{$totalActivities}}) periodo {{$date1}} - {{$date2}} :</h2>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Actividad</th>
                <th>Descripción</th>
                <th>Fecha de Entrega</th>
                <th>Días de retraso</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cardDetails as $card)
            <tr>
                <td>{{ $card->nameC }}</td>
                <td>{{ $card->descriptionC }}</td>
                <td>{{ \Carbon\Carbon::parse($card->end_date)->format('d/m/Y') }}</td>
                <td>{{ $card->days_late <= 0 ? 0 : $card->days_late }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
 <!-- Espacio para la gráfica $lineChartBase64 -->
  
    <div class="chart-wrapper">
        <img src="data:image/png;base64,{{$barChartBase64}}" alt="Gráfica de pastel">
    </div>
    
    <div class="chart-wrapper">
        <img src="data:image/png;base64,{{$lineChartBase64}}" alt="Gráfica de lineas">
    </div>

</body>
</html>
