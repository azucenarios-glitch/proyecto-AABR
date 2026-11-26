<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

function obtener_esquema_usr(PDO $pdo, string $tabla): array {
    $cols = [];
    $pk = null;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$tabla}`");
        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $cols[] = $row["Field"];
                if ($row["Key"] === "PRI" && $pk === null) {
                    $pk = $row["Field"];
                }
            }
        }
    } catch (PDOException $e) {
        return ["cols" => [], "pk" => null];
    }
    return ["cols" => $cols, "pk" => $pk];
}

function detectar_col_usr(array $cols, array $candidatos, $default = null) {
    foreach ($candidatos as $c) {
        if (in_array($c, $cols, true)) {
            return $c;
        }
    }
    return $default;
}

$esq_usr    = obtener_esquema_usr($conexion, "usuarios");
$cols_usr   = $esq_usr["cols"];
$pk_usr     = detectar_col_usr($cols_usr, ["id_usuario", "id"], $esq_usr["pk"] ?? "id_usuario");
$col_nom    = detectar_col_usr($cols_usr, ["nombre", "nombre_completo"]);
$col_user   = detectar_col_usr($cols_usr, ["usuario", "user", "login", "correo"], "usuario");
$col_pass   = detectar_col_usr($cols_usr, ["password", "password_hash", "clave"], "password");
$col_puesto = detectar_col_usr($cols_usr, ["id_puesto", "puesto", "rol"]);
$col_est    = detectar_col_usr($cols_usr, ["estatus", "estado"]);

// Cargar puestos si existe la tabla puestos
$puestos = [];
$pk_puesto = "id_puesto";
$col_nom_puesto = null;
try {
    $esq_p = obtener_esquema_usr($conexion, "puestos");
    if (!empty($esq_p["cols"])) {
        $pk_puesto = detectar_col_usr($esq_p["cols"], ["id_puesto", "id"], $esq_p["pk"] ?? $esq_p["cols"][0]);
        $col_nom_puesto = detectar_col_usr($esq_p["cols"], ["nombre", "puesto", "descripcion", "rol"]);
        $puestos = $conexion->query("SELECT * FROM `puestos` ORDER BY `{$pk_puesto}` ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $puestos = [];
}

$puesto_por_defecto = !empty($puestos) ? (int)$puestos[0][$pk_puesto] : 1;

$mensaje = "";
$tipo_msg = "info";
$mostrar_nuevo = isset($_GET["nuevo"]) && $_GET["nuevo"] === "1";

// Crear nuevo usuario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["crear_usuario"])) {
    $nombre_n  = trim((string)($_POST["nombre"] ?? ""));
    $usuario_n = trim((string)($_POST["usuario"] ?? ""));
    $pass_n    = trim((string)($_POST["password"] ?? ""));
    $puesto_n  = (int)($_POST["id_puesto"] ?? $puesto_por_defecto);
    $estatus_n = ($_POST["estatus"] ?? "Activo") === "Inactivo" ? "Inactivo" : "Activo";

    if ($usuario_n === "" || $pass_n === "") {
        $mensaje = "Ingresa usuario y contraseña para crear el registro.";
        $tipo_msg = "danger";
        $mostrar_nuevo = true;
    } else {
        $stmtChk = $conexion->prepare("SELECT COUNT(*) FROM `usuarios` WHERE `{$col_user}` = ?");
        $stmtChk->execute([$usuario_n]);

        if ((int)$stmtChk->fetchColumn() > 0) {
            $mensaje = "El usuario '{$usuario_n}' ya existe en el sistema.";
            $tipo_msg = "danger";
            $mostrar_nuevo = true;
        } else {
            $campos = ["`{$col_user}`", "`{$col_pass}`"];
            $vals   = ["?", "?"];
            $params = [$usuario_n, password_hash($pass_n, PASSWORD_DEFAULT)];

            if ($col_nom) {
                $campos[] = "`{$col_nom}`";
                $vals[] = "?";
                $params[] = $nombre_n;
            }
            if ($col_puesto) {
                $campos[] = "`{$col_puesto}`";
                $vals[] = "?";
                $params[] = $puesto_n > 0 ? $puesto_n : $puesto_por_defecto;
            }
            if ($col_est) {
                $campos[] = "`{$col_est}`";
                $vals[] = "?";
                $params[] = $estatus_n;
            }

            try {
                $sql_ins = "INSERT INTO `usuarios` (" . implode(", ", $campos) . ") VALUES (" . implode(", ", $vals) . ")";
                $stmt_ins = $conexion->prepare($sql_ins);
                $stmt_ins->execute($params);

                header("Location: /proyecto-AABR/Escuela/registro/reg_usuarios.php?msg=creado");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === "23000") {
                    $mensaje = "No se pudo crear el usuario '{$usuario_n}' (duplicado o puesto no válido).";
                } else {
                    $mensaje = "Error al guardar en la base de datos: " . $e->getMessage();
                }
                $tipo_msg = "danger";
                $mostrar_nuevo = true;
            }
        }
    }
}

// Eliminar usuario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar_usuario"])) {
    $id_eliminar = (int)($_POST["id_usuario"] ?? 0);
    if ($id_eliminar > 0) {
        try {
            $stmt_del = $conexion->prepare("DELETE FROM `usuarios` WHERE `{$pk_usr}` = ?");
            $stmt_del->execute([$id_eliminar]);
            header("Location: /proyecto-AABR/Escuela/registro/reg_usuarios.php?msg=eliminado");
            exit;
        } catch (PDOException $e) {
            $mensaje = "No se pudo eliminar el usuario: " . $e->getMessage();
            $tipo_msg = "danger";
        }
    }
}

// Guardar cambios de fila
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guardar_usuario"])) {
    $id_usuario     = (int)($_POST["id_usuario"] ?? 0);
    $nombre_u       = trim((string)($_POST["nombre"] ?? ""));
    $puesto_u       = (int)($_POST["id_puesto"] ?? $puesto_por_defecto);
    $estatus_u      = ($_POST["estatus"] ?? "Activo") === "Inactivo" ? "Inactivo" : "Activo";
    $password_plain = trim((string)($_POST["password"] ?? ""));

    if ($id_usuario > 0) {
        $sets = [];
        $params = [];

        if ($col_nom) {
            $sets[] = "`{$col_nom}` = ?";
            $params[] = $nombre_u;
        }
        if ($col_puesto) {
            $sets[] = "`{$col_puesto}` = ?";
            $params[] = $puesto_u > 0 ? $puesto_u : $puesto_por_defecto;
        }
        if ($col_pass && $password_plain !== "") {
            $sets[] = "`{$col_pass}` = ?";
            $params[] = password_hash($password_plain, PASSWORD_DEFAULT);
        }
        if ($col_est) {
            $sets[] = "`{$col_est}` = ?";
            $params[] = $estatus_u;
        }

        if (count($sets) > 0) {
            try {
                $params[] = $id_usuario;
                $sql_upd = "UPDATE `usuarios` SET " . implode(", ", $sets) . " WHERE `{$pk_usr}` = ?";
                $stmt_upd = $conexion->prepare($sql_upd);
                $stmt_upd->execute($params);
                header("Location: /proyecto-AABR/Escuela/registro/reg_usuarios.php?msg=guardado");
                exit;
            } catch (PDOException $e) {
                $mensaje = "Error al actualizar el usuario: " . $e->getMessage();
                $tipo_msg = "danger";
            }
        }
    }
}

if ($mensaje === "" && isset($_GET["msg"])) {
    if ($_GET["msg"] === "guardado") {
        $mensaje = "Cambios guardados correctamente.";
        $tipo_msg = "ok";
    } elseif ($_GET["msg"] === "eliminado") {
        $mensaje = "Usuario eliminado correctamente.";
        $tipo_msg = "ok";
    } elseif ($_GET["msg"] === "creado") {
        $mensaje = "Usuario creado correctamente.";
        $tipo_msg = "ok";
    }
}

$usuarios = [];
try {
    $usuarios = $conexion->query("SELECT * FROM `usuarios` ORDER BY `{$pk_usr}` ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al consultar usuarios: " . $e->getMessage();
    $tipo_msg = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Lista de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h2, h3 { color: var(--morado-claro); }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
        .btn-morado { background: var(--morado-principal); color: var(--blanco) !important; border: 1px solid var(--blanco); padding: 7px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-morado:hover { background: var(--morado-oscuro); }
        .btn-accion { background: var(--morado-oscuro); color: var(--blanco) !important; border: 1px solid var(--morado-claro); padding: 6px 10px; border-radius: 4px; font-size: 13px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-accion:hover { background: var(--morado-principal); }
        .msg-ok { background: var(--morado-oscuro); color: var(--blanco); border: 1px solid var(--morado-claro); padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-weight: bold; }
        .msg-danger { background: #5c1628; color: var(--blanco); border: 1px solid #ff8fa3; padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-weight: bold; }
        .panel-nuevo { background: var(--negro-panel); border: 2px solid var(--morado-principal); border-radius: 6px; padding: 16px; margin-bottom: 18px; max-width: 780px; }
        table { border-collapse: collapse; width: 100%; background: var(--negro-panel); color: var(--blanco); margin: 15px 0; }
        th { background: var(--morado-oscuro); color: var(--blanco); padding: 10px; text-align: left; border: 1px solid var(--borde-morado); }
        td { border: 1px solid var(--borde-morado); padding: 10px; vertical-align: middle; color: var(--blanco); }
        tr:nth-child(even) { background: var(--negro-fila-alt); }
        select, input[type="password"], input[type="text"] { padding: 6px 8px; background: var(--blanco); color: #111111; border: 1px solid var(--morado-principal); border-radius: 4px; }
        .pass-grupo { display: inline-flex; align-items: center; gap: 6px; }
        .acciones-grupo { display: flex; gap: 6px; flex-wrap: wrap; }
    </style>
</head>
<body class="p-3 m-0 border-0">
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link" href="/proyecto-AABR/Escuela/index.php">Inicio</a></li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">Baja</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_profesores.php">Profesor</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_alumnos.php">Alumnos</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">Procesos</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_materias.php">Materias</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_profesores.php">Profesores</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_capcal.php">Capcal</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_asignat.php">Asignat</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_insgrup.php">Insgrup</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_vista.php">Vista Alumno</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle active" data-bs-toggle="dropdown" href="#" role="button">Registro</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_profesores.php">Profesores</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_grupos.php">Grupo</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_alumno.php">Alumno</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_unidad.php">Unidad</a></li>
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/registro/reg_usuarios.php">Usuarios</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">Reporte</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_profesor.php">Profesores</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_general.php">General</a></li>
            </ul>
        </li>
    </ul>

    <div class="top-bar">
        <h2>Lista de Usuarios</h2>
        <a href="/proyecto-AABR/Escuela/registro/reg_usuarios.php?nuevo=1" class="btn-morado"><i class="fa fa-user-plus"></i> Nuevo Usuario</a>
    </div>

    <?php if ($mensaje !== ""): ?>
        <div class="<?= $tipo_msg === 'danger' ? 'msg-danger' : 'msg-ok' ?>">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($mostrar_nuevo): ?>
        <div class="panel-nuevo">
            <h3 class="mb-3">Registrar Nuevo Usuario</h3>
            <form method="post" action="/proyecto-AABR/Escuela/registro/reg_usuarios.php" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Nombre completo" value="<?= htmlspecialchars((string)($_POST["nombre"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="usuario" class="form-control" required placeholder="Usuario de acceso" value="<?= htmlspecialchars((string)($_POST["usuario"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Puesto</label>
                    <select name="id_puesto" class="form-select">
                        <?php if (!empty($puestos)): ?>
                            <?php foreach ($puestos as $p): ?>
                                <?php
                                $pid = (int)$p[$pk_puesto];
                                $ptext = $col_nom_puesto && isset($p[$col_nom_puesto]) ? (string)$p[$col_nom_puesto] : ("Puesto #" . $pid);
                                $sel = ((int)($_POST["id_puesto"] ?? $puesto_por_defecto)) === $pid ? "selected" : "";
                                ?>
                                <option value="<?= $pid ?>" <?= $sel ?>><?= htmlspecialchars($ptext, ENT_QUOTES, "UTF-8") ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="1">1</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required placeholder="Contraseña">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estatus</label>
                    <select name="estatus" class="form-select">
                        <option value="Activo" <?= (($_POST["estatus"] ?? "Activo") === "Activo") ? "selected" : "" ?>>Activo</option>
                        <option value="Inactivo" <?= (($_POST["estatus"] ?? "") === "Inactivo") ? "selected" : "" ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" name="crear_usuario" value="1" class="btn-morado">Crear Usuario</button>
                    <a href="/proyecto-AABR/Escuela/registro/reg_usuarios.php" class="btn-accion">Cancelar</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Usuario</th>
                <th>Puesto</th>
                <th>Contraseña</th>
                <th>Estatus</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <?php
                $id_u = (int)($u[$pk_usr] ?? 0);
                $form_id = "form_usr_" . $id_u;
                $nombre_u = (string)($col_nom && isset($u[$col_nom]) ? $u[$col_nom] : "");
                $user_login = (string)($col_user && isset($u[$col_user]) ? $u[$col_user] : "");
                $puesto_u = (int)($col_puesto && isset($u[$col_puesto]) ? $u[$col_puesto] : $puesto_por_defecto);
                $est_raw = (string)($col_est && isset($u[$col_est]) ? $u[$col_est] : "Activo");
                $estatus_u = ($est_raw === "Inactivo" || $est_raw === "0") ? "Inactivo" : "Activo";
                ?>
                <tr>
                    <td>
                        <?= htmlspecialchars((string)$id_u, ENT_QUOTES, "UTF-8") ?>
                        <form id="<?= $form_id ?>" method="post" action="/proyecto-AABR/Escuela/registro/reg_usuarios.php">
                            <input type="hidden" name="id_usuario" value="<?= $id_u ?>">
                        </form>
                    </td>
                    <td>
                        <input type="text" name="nombre" form="<?= $form_id ?>" value="<?= htmlspecialchars($nombre_u, ENT_QUOTES, "UTF-8") ?>">
                    </td>
                    <td><strong><?= htmlspecialchars($user_login, ENT_QUOTES, "UTF-8") ?></strong></td>
                    <td>
                        <select name="id_puesto" form="<?= $form_id ?>">
                            <?php if (!empty($puestos)): ?>
                                <?php foreach ($puestos as $p): ?>
                                    <?php
                                    $pid = (int)$p[$pk_puesto];
                                    $ptext = $col_nom_puesto && isset($p[$col_nom_puesto]) ? (string)$p[$col_nom_puesto] : ("Puesto #" . $pid);
                                    ?>
                                    <option value="<?= $pid ?>" <?= $puesto_u === $pid ? "selected" : "" ?>><?= htmlspecialchars($ptext, ENT_QUOTES, "UTF-8") ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="<?= $puesto_u ?>"><?= $puesto_u ?></option>
                            <?php endif; ?>
                        </select>
                    </td>
                    <td>
                        <div class="pass-grupo">
                            <input type="password" name="password" form="<?= $form_id ?>" placeholder="Nueva contraseña" autocomplete="new-password">
                            <button type="button" class="btn-accion toggle-pass" title="Mostrar/ocultar contraseña"><i class="fa fa-eye"></i></button>
                        </div>
                    </td>
                    <td>
                        <select name="estatus" form="<?= $form_id ?>">
                            <option value="Activo" <?= $estatus_u === "Activo" ? "selected" : "" ?>>Activo</option>
                            <option value="Inactivo" <?= $estatus_u === "Inactivo" ? "selected" : "" ?>>Inactivo</option>
                        </select>
                    </td>
                    <td>
                        <div class="acciones-grupo">
                            <button type="submit" name="guardar_usuario" value="1" form="<?= $form_id ?>" class="btn-accion">Guardar</button>
                            <button type="submit" name="eliminar_usuario" value="1" form="<?= $form_id ?>" class="btn-accion" onclick="return confirm('Eliminar este usuario?');">Eliminar</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        document.querySelectorAll('.toggle-pass').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
                if (input) {
                    input.type = input.type === 'password' ? 'text' : 'password';
                }
            });
        });
    </script>
</body>
</html>