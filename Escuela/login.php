<?php
require_once __DIR__ . "/config/conexion.php";

if (isset($_SESSION['id_user'])) {
    header("Location: " . $base . "/principal.php");
    exit;
}

$error = "";
$aviso = isset($_GET['cerrada']) ? "Sesión cerrada correctamente." : "";
$usuarioIngresado = "";

if (isset($_POST['ingresar'])) {
    $usuarioIngresado = trim($_POST['nombreuser'] ?? '');
    $passwordIngresado = $_POST['pwuser'] ?? '';

    if ($usuarioIngresado === '' || $passwordIngresado === '') {
        $error = "Ingresa tu usuario y contraseña.";
    } else {
        $sql = "SELECT u.ID_USER, u.NOMBREUSER, u.PWUSER, u.PUESTO, u.ID_PROFESORES, u.ID_ALUMNOS, u.STATUS_USER,
                       p.DESCRIPCION_PUESTO, p.STATUS_PUESTO,
                       CONCAT(pr.NOMBRE, ' ', pr.APELLIDO_PATERNO, ' ', pr.APELLIDO_MATERNO) AS NOMBRE_PROFESOR,
                       pr.ESTATUS AS PROF_ESTATUS,
                       CONCAT(al.NOMBRE, ' ', al.APELLIDO_PATERNO, ' ', al.APELLIDO_MATERNO) AS NOMBRE_ALUMNO,
                       al.MATRICULA AS ALUM_MATRICULA,
                       al.ESTATUS AS ALUM_ESTATUS
                FROM usuarios u
                INNER JOIN puesto p ON p.ID_PUESTO = u.PUESTO
                LEFT JOIN profesores pr ON pr.ID_PROFESORES = u.ID_PROFESORES
                LEFT JOIN alumnos al ON al.ID_ALUMNOS = u.ID_ALUMNOS
                WHERE u.NOMBREUSER = ?
                LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $usuarioIngresado);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $hash = $row['PWUSER'] ?? '';
        $passValida = false;
        if ($row !== null) {
            if (strlen($hash) === 40 && hash_equals(strtolower($hash), sha1($passwordIngresado))) {
                $passValida = true;
            } elseif (strlen($hash) === 32 && hash_equals(strtolower($hash), md5($passwordIngresado))) {
                $passValida = true;
            }
        }

        if ($row === null || !$passValida) {
            $error = "Usuario o contraseña incorrectos.";
        } elseif ($row['STATUS_USER'] !== 'ALTA' || $row['STATUS_PUESTO'] !== 'ALTA') {
            $error = "Tu usuario o perfil se encuentra dado de baja. Contacta al administrador.";
        } else {
            $descPuesto = mb_strtoupper(trim($row['DESCRIPCION_PUESTO']), 'UTF-8');
            $rol = '';
            if ((int)$row['PUESTO'] === 1 || str_contains($descPuesto, 'ADMIN')) {
                $rol = 'ADMINISTRADOR';
            } elseif ((int)$row['PUESTO'] === 2 || str_contains($descPuesto, 'MAESTRO') || str_contains($descPuesto, 'PROFESOR')) {
                $rol = 'MAESTRO';
            } elseif ((int)$row['PUESTO'] === 3 || str_contains($descPuesto, 'ALUMNO')) {
                $rol = 'ALUMNO';
            }

            if ($rol === '') {
                $error = "El puesto asignado a este usuario no tiene un rol válido.";
            } elseif ($rol === 'MAESTRO' && (empty($row['ID_PROFESORES']) || $row['PROF_ESTATUS'] !== 'ALTA')) {
                $error = "Esta cuenta de maestro no está vinculada a un profesor activo.";
            } elseif ($rol === 'ALUMNO' && (empty($row['ID_ALUMNOS']) || $row['ALUM_ESTATUS'] !== 'ALTA')) {
                $error = "Esta cuenta de alumno no está vinculada a un alumno activo.";
            } else {
                session_regenerate_id(true);
                $_SESSION['id_user']     = (int)$row['ID_USER'];
                $_SESSION['nombre_user'] = $row['NOMBREUSER'];
                $_SESSION['id_puesto']   = (int)$row['PUESTO'];
                $_SESSION['puesto_desc'] = $row['DESCRIPCION_PUESTO'];
                $_SESSION['rol']         = $rol;
                $_SESSION['id_profesor'] = $row['ID_PROFESORES'] ? (int)$row['ID_PROFESORES'] : null;
                $_SESSION['id_alumno']   = $row['ID_ALUMNOS'] ? (int)$row['ID_ALUMNOS'] : null;

                if ($rol === 'MAESTRO') {
                    $_SESSION['nombre_mostrar'] = trim($row['NOMBRE_PROFESOR']);
                } elseif ($rol === 'ALUMNO') {
                    $_SESSION['nombre_mostrar'] = trim($row['NOMBRE_ALUMNO']) . " (" . $row['ALUM_MATRICULA'] . ")";
                } else {
                    $_SESSION['nombre_mostrar'] = $row['NOMBREUSER'];
                }

                header("Location: " . $base . "/principal.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="<?= $base ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-main: #090512;
            --bg-panel: #130924;
            --bg-card: #1a0d30;
            --border-purple: #6f2dbd;
            --border-subtle: #3b1c6e;
            --accent-purple: #7c3aed;
            --accent-purple-hover: #6d28d9;
            --text-main: #f3e8ff;
            --text-muted: #c4b5fd;
        }

        body.dark-login-theme {
            background-color: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-card {
            background-color: var(--bg-panel);
            border: 1.5px solid var(--border-purple);
            border-radius: 14px;
            box-shadow: 0 0 28px rgba(111, 45, 189, 0.22);
            overflow: hidden;
        }

        .login-header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-subtle);
            color: #ffffff;
            text-align: center;
            padding: 1.15rem 1rem;
        }

        .login-header small {
            color: var(--text-muted);
        }

        .login-body {
            padding: 1.5rem;
        }

        .login-body h5 {
            color: #ffffff;
            font-weight: 600;
        }

        .form-label {
            color: var(--text-main);
            font-size: 0.92rem;
        }

        .form-control {
            background-color: var(--bg-main);
            border: 1px solid var(--border-subtle);
            color: var(--text-main);
        }

        .form-control:focus {
            background-color: var(--bg-main);
            border-color: var(--accent-purple);
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(124, 58, 237, 0.25);
        }

        .btn-purple {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
            color: #ffffff;
            font-weight: 500;
        }

        .btn-purple:hover,
        .btn-purple:focus {
            background-color: var(--accent-purple-hover);
            border-color: var(--accent-purple-hover);
            color: #ffffff;
        }
    </style>
</head>
<body class="dark-login-theme">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card login-card">
                <div class="login-header">
                    <h4 class="mb-0">Escuela</h4>
                    <small>Administración</small>
                </div>

                <div class="login-body">
                    <h5 class="text-center mb-3">Inicio de sesión</h5>

                    <?php if ($aviso !== "") { ?>
                        <div class="alert alert-info py-2"><?= htmlspecialchars($aviso) ?></div>
                    <?php } ?>

                    <?php if ($error !== "") { ?>
                        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
                    <?php } ?>

                    <form method="POST" autocomplete="off">
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="form-label">Usuario</label>
                                <input type="text" name="nombreuser" class="form-control" maxlength="50"
                                       value="<?= htmlspecialchars($usuarioIngresado) ?>" required autofocus>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="pwuser" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" name="ingresar" class="btn btn-purple w-100">
                            Entrar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>