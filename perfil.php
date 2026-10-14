<?php

session_start();

require_once "config/conexion.php";

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

/* =========================
   VERIFICAR SESIÓN
========================= */

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$rol = $_SESSION["rol"] ?? "";

// Enlace al panel según el rol
$panel = "dashboard.php";
if ($rol === "admin") {
    $panel = "admin_dashboard.php";
} elseif ($rol === "proveedor") {
    $panel = "proveedor_dashboard.php";
}

$mensaje = "";
$error = "";

/* =========================
   ACTUALIZAR PERFIL
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");

    if ($nombre === "" || $apellido === "" || $correo === "") {

        $error = "Nombre, apellido y correo son obligatorios.";

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $error = "Ingresa un correo electrónico válido.";

    } else {

        /* Verificar que el correo no pertenezca a otro usuario */

        $stmt_correo = $conexion->prepare(
            "SELECT id_usuario
             FROM usuarios
             WHERE correo = ?
             AND id_usuario != ?"
        );

        $stmt_correo->bind_param(
            "si",
            $correo,
            $id_usuario
        );

        $stmt_correo->execute();

        $resultado_correo = $stmt_correo->get_result();

        if ($resultado_correo->num_rows > 0) {

            $error = "Ese correo ya está registrado por otro usuario.";

        } else {

            /* Actualizar información */

            $stmt = $conexion->prepare(
                "UPDATE usuarios
                 SET nombre = ?,
                     apellido = ?,
                     correo = ?,
                     telefono = ?
                 WHERE id_usuario = ?"
            );

            $stmt->bind_param(
                "ssssi",
                $nombre,
                $apellido,
                $correo,
                $telefono,
                $id_usuario
            );

            if ($stmt->execute()) {

                $mensaje = "Perfil actualizado correctamente.";

                /* Actualizar los datos guardados en la sesión */

                $_SESSION["nombre"] = $nombre;
                $_SESSION["apellido"] = $apellido;
                $_SESSION["correo"] = $correo;

            } else {

                $error = "No fue posible actualizar el perfil.";

            }

            $stmt->close();
        }

        $stmt_correo->close();
    }
}

/* =========================
   CONSULTAR DATOS DEL USUARIO
========================= */

$stmt = $conexion->prepare(
    "SELECT
        nombre,
        apellido,
        correo,
        telefono,
        rol,
        fecha_registro
     FROM usuarios
     WHERE id_usuario = ?"
);

$stmt->bind_param("i", $id_usuario);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("No se encontró el usuario.");
}

$usuario = $resultado->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mi perfil - Universo Deportivo</title>

    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>

<header class="encabezado">

    <div class="contenedor-header">

        <a href="index.php" class="logo">
            Universo Deportivo
        </a>

        <nav>

            <a href="index.php">Inicio</a>

            <a href="publicaciones.php">Publicaciones</a>

            <a href="<?php echo e($panel); ?>">Mi panel</a>

            <a href="logout.php">Cerrar sesión</a>

        </nav>

    </div>

</header>

<main>

    <section class="contenedor-solicitudes">

        <h2 class="titulo-solicitudes">
            Mi perfil
        </h2>

        <p>
            Consulta y actualiza tu información personal.
        </p>

        <?php if ($mensaje != ""): ?>

            <div class="mensaje-exito">
                <?php echo e($mensaje); ?>
            </div>

        <?php endif; ?>

        <?php if ($error != ""): ?>

            <div class="mensaje-error">
                <?php echo e($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST" class="formulario-perfil">

            <div class="campo-formulario">

                <label for="nombre">
                    Nombre
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="50"
                    value="<?php echo e($usuario["nombre"]); ?>"
                    required
                >

            </div>

            <div class="campo-formulario">

                <label for="apellido">
                    Apellido
                </label>

                <input
                    type="text"
                    id="apellido"
                    name="apellido"
                    maxlength="50"
                    value="<?php echo e($usuario["apellido"]); ?>"
                    required
                >

            </div>

            <div class="campo-formulario">

                <label for="correo">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    id="correo"
                    name="correo"
                    maxlength="100"
                    value="<?php echo e($usuario["correo"]); ?>"
                    required
                >

            </div>

            <div class="campo-formulario">

                <label for="telefono">
                    Teléfono
                </label>

                <input
                    type="text"
                    id="telefono"
                    name="telefono"
                    maxlength="20"
                    value="<?php echo e($usuario["telefono"] ?? ""); ?>"
                >

            </div>

            <div class="campo-formulario">

                <label>
                    Tipo de usuario
                </label>

                <input
                    type="text"
                    value="<?php echo e(ucfirst($usuario["rol"])); ?>"
                    readonly
                >

            </div>

            <div class="campo-formulario">

                <label>
                    Fecha de registro
                </label>

                <input
                    type="text"
                    value="<?php echo e(date("d/m/Y H:i", strtotime($usuario["fecha_registro"]))); ?>"
                    readonly
                >

            </div>

            <button type="submit" class="btn-principal">
                Guardar cambios
            </button>

        </form>

    </section>

</main>

<footer>

    <p>
        © 2026 Universo Deportivo - Proyecto académico
    </p>

</footer>

</body>

</html>