<?php
// Incluimos la conexión (ajusta la ruta '../conexion.php' si está en la carpeta raíz)
include '../conexion.php'; 

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $id_mat = $_POST['id_mat'];
  $id_alumno = $_POST['id_alumno'];
  $id_profesor = $_POST['id_profesor'];
  try {
    $sql = "INSERT INTO asigmat (id_mat, id_alumno, id_profesor) VALUES (:id_mat, :id_alumno, :id_profesor)";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([
      ':id_mat' => $id_mat,
      ':id_alumno' => $id_alumno,
      ':id_profesor' => $id_profesor
      ]);
      $mensaje = "¡Asignación de materia registrada con éxito!";
      } 
      catch (PDOException $e) {
        $mensaje = "Error al registrar la asignación: " . $e->getMessage();
        }
      }
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
<a class="nav-link active" aria-current="false" href="../index.php">Inicio</a>
</li>
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Baja</a>
<ul class="dropdown-menu" style="">
<li><a class="dropdown-item" href='../Baja/ba_grupos.php'>Grupos</a></li>
<li><a class="dropdown-item" href='../Baja/ba_materias.php'>Materia</a></li>
<li><a class="dropdown-item" href='../Baja/ba_profesores.php'>Profesor</a></li>
<li><a class="dropdown-item" href='../Baja/ba_alumnos.php'>Alumnos</a></li>
</ul>
</li>
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Procesos</a>
<ul class="dropdown-menu" style="">
<li><a class="dropdown-item" href='../procesos/pro_alumnos.php'>Alumnos</a></li>
<li><a class="dropdown-item" href='../procesos/pro_grupos.php'>Grupos</a></li>
<li><a class="dropdown-item" href='../procesos/pro_materias.php'>Materias</a></li>
<li><a class="dropdown-item" href='../procesos/pro_profesores.php'>Profesores</a></li>
<li><a class="dropdown-item" href='../procesos/pro_capcal.php'>Capcal</a></li>
<li><a class="dropdown-item" href='../procesos/pro_insgrup.php'>Insgrup</a></li>
</ul>
</li>
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Registro</a>
<ul class="dropdown-menu" style="">
<li><a class="dropdown-item" href='../registro/reg_materias.php'>Materia</a><li>
<li><a class="dropdown-item" href='../registro/reg_profesores.php'>Profesore</a></li>
<li><a class="dropdown-item" href='../registro/reg_grupos.php'>Grupo</a><li>
<li><a class="dropdown-item" href='../registro/reg_alumno.php'>Alumno</a><li>
<li><a class="dropdown-item" href='../registro/reg_unidad.php'>Unidad</a><li>
</ul>
</li>
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Reporte</a>
<ul class="dropdown-menu" style="">
<li><a class="dropdown-item" href='../reporter/rep_alumnos.php'>Alumnos</a><li>
<li><a class="dropdown-item" href='../reportes/rep_profesor.php'>Profesores</a></li>
<li><a class="dropdown-item" href='../reportes/rep_materias.php'>Materia</a></li>
<li><a class="dropdown-item" href='../reportes/rep_grupos.php'>Grupos</a></li>
</ul>
</li>
</ul>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<meta charset="UTF-8">
<title>Asignación de Materias</title>
<body>
<figure class="blockquote text-center">
<blockquote>
<style>
body{font-family:Arial;margin:30px;background: #fff5f7;color: #333}
form{max-width:500px;background:#fff;padding:25px;border-radius:8px;box-shadow:0 0 10px #f8d7da;border-top:4px solid #d3690d}
input,select{width:100%;padding:8px;margin:5px 0 15px;box-sizing:border-box;border:1px solid #f1b0b7;border-radius:4px}
button{padding:10px 20px;background: #d3690d;color:#fff;border:none;border-radius:5px;cursor:pointer;font-weight:bold}
button:hover{background: #d3690d}
a{color: #bcd30d;text-decoration:none;display:inline-block;margin-top:10px;font-weight:bold}
a:hover{text-decoration: underline;color: #a61e4d}
p{padding:10px;border-radius:4px;font-weight:bold}
.ok{background: #d4edda;color: #155724}
.err{background: #f8d7da;color: #721c24}
</style>
</head>
<body>

<div class="container">
<h2>Proceso: Asignación de Materias</h2>
<?php if($mensaje != "") echo "<p class='msg'>$mensaje</p>"; ?> 
<form action="pro_asignat.php" method="POST">
<label for="id_mat">ID de Materia (`id-mat`):</label>
<input type="text" name="id_mat" required placeholder="Ej. MAT01">
<label for="id_alumno">ID de Alumno (`id-alumno`):</label>
<input type="text" name="id_alumno" required placeholder="Ej. ALUM01">
<label for="id_profesor">ID de Profesor (`id-profesor`):</label>
<input type="text" name="id_profesor" required placeholder="Ej. PROF01">
<button type="submit">Guardar Asignación</button>
</form>
</div>
</body>
</html>


