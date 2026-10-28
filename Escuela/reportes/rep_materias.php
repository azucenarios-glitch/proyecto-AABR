<?php
$servidor = "localhost"; 
$usuario = "root"; 
$clave = ""; 
$base = "Escuela"; 

// 1. Conexión inicial al servidor
$conexion = mysqli_connect($servidor, $usuario,$clave);
if (!$conexion) {
    die("❌ Conexión fallida: " . mysqli_connect_error());
}

// 2. Crear la base de datos si no existe
mysqli_query($conexion, "CREATE DATABASE IF NOT EXISTS `$base` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
mysqli_select_db($conexion,$base);

// 3. Crear las tablas necesarias automáticamente para evitar el error
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS profesores (
    id_prof INT AUTO_INCREMENT PRIMARY KEY,
    nom_prof VARCHAR(100) NOT NULL,
    apaterno_prof VARCHAR(100) NOT NULL,
    amaterno_prof VARCHAR(100) NOT NULL
)");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS materias (
    id_mat INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(150) NOT NULL,
    id_prof INT NOT NULL,
    FOREIGN KEY (id_prof) REFERENCES profesores(id_prof)
)");

// 4. Consultar los datos de materias uniendo la tabla de profesores
$materias = mysqli_query($conexion, "SELECT m.*, CONCAT(p.apaterno_prof,' ',p.amaterno_prof,', ',p.nom_prof) AS profesor FROM materias m JOIN profesores p ON m.id_prof=p.id_prof ORDER BY descripcion");
$total = $materias ? mysqli_num_rows($materias) : 0;
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
    <title>Reporte Materias</title>
    <style>
        body{font-family:Arial;margin:30px;background:#fff}
        h1{text-align:center;color: #00FF00;border-bottom:2px solid #00FF00;padding-bottom:10px}
        table{border-collapse:collapse;width:100%;margin-top:20px}
        th{background: #00FF00;color:#fff;padding:12px;text-align:left;border:1px solid #ccc}
        td{border:1px solid #ccc;padding:10px}
        tr:nth-child(even){background: #f9f9f9}
        .total{margin-top:15px;font-weight:bold}
        @media print{button,a{display:none}}
    </style>
</head>
<body>

<h1>📄 REPORTE DE MATERIAS</h1>
<table>
    <tr>
        <th>ID</th>
        <th>Materia</th>
        <th>Profesor Responsable</th>
    </tr>
    <?php if($materias && mysqli_num_rows($materias) > 0): ?>
        <?php while($m = mysqli_fetch_assoc($materias)): ?>
        <tr>
            <td><?=$m['id_mat']?></td>
            <td><?=$m['descripcion']?></td>
            <td><?=$m['profesor']?></td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan='3' style='text-align:center;'>No hay materias registradas todavía.</td>
        </tr>
    <?php endif; ?>
</table>

<p class='total'>Total de materias: <?=$total?></p>
<button onclick='window.print()'>🖨️ Imprimir</button>
<a href='../procesos/materias.php'>← Volver</a>

</body>
</html>