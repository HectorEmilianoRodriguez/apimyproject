<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>Invitación a un espacio de trabajo</h1>
    <p>Hola {{$name}}, te ha invitado a formar parte del equipo del espacio de trabajo {{$workenv}}. </p>
    <p>Para unirte haz clic en el enlace adjunto</p>
    <a href="{{ url('api/acceptInvitationMember/' . $token . '/' . $idwork) }}">Unirse ahora</a>

</body>
</html>