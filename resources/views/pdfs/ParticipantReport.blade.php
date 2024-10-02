<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de participación de {{$user}}</title>
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
            <h1>Reporte de participación de {{$user}}</h1>
        </div>
        <h2>Actividades asignadas ({{$totalActivities->totalActivities}}) periodo {{$date1}} - {{$date2}}:</h2>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Actividad</th>
                <th>Descripción</th>
                <th>¿Urgente?</th>
                <th>Fecha de Expiración</th>
                <th>¿Está completada?</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cardDetails as $card)
            <tr>
                <td>{{ $card->nameC }}</td>
                <td>{{ $card->descriptionC }}</td>
                <td>{{ $card->important == 1 ? 'Sí' : 'No' }}</td>
                <td>{{ \Carbon\Carbon::parse($card->end_date)->format('d/m/Y') }}</td>
                <td>{{ $card->done == 1 ? 'Sí' : 'No' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
 <!-- Espacio para la gráfica -->
    <div class="chart-wrapper">
        <h2>Actividades Etiquetadas: Frecuencia por Etiqueta</h2>
        <img src="data:image/png;base64,{{$chartBase64}}" alt="Gráfica de Barras">
    </div>

    <div class="chart-wrapper">
        <h2>Actividades de urgencia vs no urgencia</h2>
        <img src="data:image/png;base64,{{$pieChartBase64}}" alt="Gráfica de pastel">
    </div>

</body>
</html>
