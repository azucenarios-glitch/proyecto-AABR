<?php
$servidor = "localhost"; 
$usuario = "root"; 
$clave = ""; 
$base = "Escuela"; 

// Naikkaten ti puerto 3307 tapno agtrabaho iti default a puerto ti XAMPP (3306)
$conexion = mysqli_connect($servidor, $usuario, $clave, $base);
if (!$conexion) {
    die("❌ Conexión fallida: " . mysqli_connect_error());
}

$alumnos = mysqli_query($conexion, "SELECT * FROM alumnos ORDER BY apaterno, amaterno");
$total = mysqli_num_rows($alumnos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet"/>
    <title>Bootstrap Example</title>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </head>
  <body class="p-3 m-0 border-0 bd-example m-0 border-0">
    <!-- Example Code Start-->
    <ul class="nav nav-pills">
      <li class="nav-item">
        <a class="nav-link active" aria-current="false" href="../index.php">← Inicio</a>
      </li>
     <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"
          >Baja</a>
        <ul class="dropdown-menu" style="">
          <li><a class="dropdown-item" href='../Baja/ba_grupos.php'>Grupos</a></li>
          <li><a class="dropdown-item" href='../Baja/ba_materias.php'>Materia</a></li>
          <li><a class="dropdown-item" href='../Baja/ba_profesores.php'>Profesor</a></li>
          <li><a class="dropdown-item" href='../Baja/ba_alumnos.php'>Alumnos</a></li>
        </ul>
      </li>
      <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"
          >Procesos</a>
          <ul class="dropdown-menu" style="">
            <li><a class="dropdown-item" href='../procesos/pro_alumnos.php'>Alumnos</a></li>
            <li><a class="dropdown-item" href='../procesos/pro_grupos.php'>Grupos</a></li>
            <li><a class="dropdown-item" href='../procesos/pro_materias.php'>Lista</a></li>
            <li><a class="dropdown-item" href='../procesos/pro_profesores.php'>Profesores</a></li>
        </ul>
      </li>
        <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"
          >Registro</a>
        <ul class="dropdown-menu" style="">
          <li><a class="dropdown-item" href='../registro/reg_materias.php'>Materia</a><li>
          <li><a class="dropdown-item" href='../registro/reg_profesores.php'>Profesores</a></li>
          <li><a class="dropdown-item" href='../registro/reg_grupos.php'>Grupo</a><li>
        </ul>
      </li>
      <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"
          >Reporte</a>
        <ul class="dropdown-menu" style="">
          <li><a class="dropdown-item" href='../reporter/rep_alumnos.php'>Alumnos</a><li>
          <li><a class="dropdown-item" href='../reportes/rep_profesor.php'>Profesores</a></li>
          <li><a class="dropdown-item" href='../reportes/rep_materias.php'>Materia</a></li>
          <li><a class="dropdown-item" href='../reportes/rep_grupos.php'>Grupos</a></li>
        </ul>
      </li>
    </ul>
    <!-- Example Code End -->
  </body>
</html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Reporte Alumnos</title>
    <style>
        body{font-family:Arial;margin:30px;background:#fff}
        h1{text-align:center;color:#FF69B4;border-bottom:2px solid #FF69B4;padding-bottom:10px}
        table{border-collapse:collapse;width:100%;margin-top:20px}
        th{background:#FF69B4;color:#fff;padding:12px;text-align:left;border:1px solid #ccc}
        td{border:1px solid #ccc;padding:10px}
        tr:nth-child(even){background:#f9f9f9}
        .total{margin-top:15px;font-weight:bold;font-size:16px}
        @media print{button,a{display:none}}
    </style>
</head>
<body>

<h1>📄 REPORTE DE ALUMNOS</h1>
<table>
<tr>
    <th>ID</th>
    <th>Nombre Completo</th>
    <th>Domicilio</th>
    <th>Correo</th>
    <th>Teléfono</th>
    <th>Grupo</th>
</tr>
<?php while($a = mysqli_fetch_assoc($alumnos)): ?>
<tr>
    <td><?=$a['id']?></td>
    <td><?=$a['apaterno']?> <?=$a['amaterno']?>, <?=$a['nombre']?></td>
    <td><?=$a['dom']?></td>
    <td><?=$a['mail']?></td>
    <td><?=$a['tel']?></td>
    <td><?=$a['id_grupo']?></td>
</tr>
<?php endwhile; ?>
</table>

<p class='total'>Total de alumnos registrados: <?=$total?></p>
<button onclick='window.print()'>🖨️ Imprimir</button>
<a href='../procesos/pro_alumnos.php'>← Volver</a>

</body>
</html>