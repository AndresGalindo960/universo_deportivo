<?php

session_start();

require_once "config/conexion.php";

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

$logueado = isset($_SESSION["id_usuario"]);
$id_sesion = $_SESSION["id_usuario"] ?? 0;
$rol = $_SESSION["rol"] ?? "";

// Enlace al panel según el rol
$panel = "dashboard.php";
if ($rol === "admin") {
    $panel = "admin_dashboard.php";
} elseif ($rol === "proveedor") {
    $panel = "proveedor_dashboard.php";
}

$id_publicacion = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

$sql = "SELECT
            p.id_publicacion,
            p.titulo,
            p.descripcion,
            p.tipo,
            p.precio,
            p.estado,
            pr.nombre_negocio AS nombre_proveedor,
            pr.direccion,
            pr.id_usuario AS id_usuario_proveedor
        FROM publicaciones p
        INNER JOIN proveedores pr
            ON p.id_proveedor = pr.id_proveedor
        WHERE p.id_publicacion = ?";

$stmt_pub = $conexion->prepare($sql);
$stmt_pub->bind_param("i", $id_publicacion);
$stmt_pub->execute();
$resultado = $stmt_pub->get_result();

if ($resultado->num_rows == 0) {
    die("La publicación no existe.");
}

$publicacion = $resultado->fetch_assoc();
$stmt_pub->close();

$es_admin = ($rol === "admin");
$es_dueno = $logueado && ((int) $publicacion["id_usuario_proveedor"] === (int) $id_sesion);
$disponible = ($publicacion["estado"] === "activo");

// Una publicación inactiva solo la ven su dueño y el administrador
if (!$disponible && !$es_admin && !$es_dueno) {
    die("Esta publicación no está disponible.");
}

$puede_solicitar = $logueado && $disponible && !$es_dueno && !$es_admin;

// ¿Ya tiene una solicitud en curso para esta publicación?
$solicitud_activa = false;

if ($puede_solicitar) {

    $sql_activa = "SELECT id_solicitud
                   FROM solicitudes
                   WHERE id_usuario = ?
                   AND id_publicacion = ?
                   AND estado IN ('pendiente', 'aceptada')
                   LIMIT 1";

    $stmt_activa = $conexion->prepare($sql_activa);
    $stmt_activa->bind_param("ii", $id_sesion, $id_publicacion);
    $stmt_activa->execute();
    $solicitud_activa = $stmt_activa->get_result()->num_rows > 0;
    $stmt_activa->close();
}

$error = "";
$mensaje_enviado = "";

// Procesar solicitud
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!$logueado) {
        header("Location: login.php");
        exit();
    }

    $mensaje_enviado = trim($_POST["mensaje"] ?? "");

    if (!$puede_solicitar) {

        $error = "No puedes enviar una solicitud para esta publicación.";

    } elseif ($solicitud_activa) {

        $error = "Ya tienes una solicitud en curso para esta publicación.";

    } elseif ($mensaje_enviado === "") {

        $error = "Por favor, escribe un mensaje.";

    } elseif (mb_strlen($mensaje_enviado) > 500) {

        $error = "El mensaje no puede superar los 500 caracteres.";

    } else {

        $sql_solicitud = "INSERT INTO solicitudes
                          (id_usuario, id_publicacion, estado, mensaje)
                          VALUES (?, ?, 'pendiente', ?)";

        $stmt = $conexion->prepare($sql_solicitud);
        $stmt->bind_param("iis", $id_sesion, $id_publicacion, $mensaje_enviado);

        if ($stmt->execute()) {

            header("Location: mis_solicitudes.php");
            exit();

        } else {

            $error = "No fue posible realizar la solicitud.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo e($publicacion["titulo"]); ?> - Universo Deportivo</title>

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

                <?php if ($logueado): ?>

                    <a href="<?php echo e($panel); ?>">Mi panel</a>

                    <span>
                        Hola, <?php echo e($_SESSION["nombre"]); ?>
                    </span>

                    <a href="logout.php">Cerrar sesión</a>

                <?php else: ?>

                    <a href="registro.php">Registrarse</a>
                    <a href="login.php">Iniciar sesión</a>

                <?php endif; ?>

            </nav>

        </div>

    </header>


    <main class="pagina-detalle">

        <div class="detalle-card">

            <div class="detalle-etiqueta">
                <?php echo e(ucfirst($publicacion["tipo"])); ?>
            </div>


            <h1 class="detalle-titulo">
                <?php echo e($publicacion["titulo"]); ?>
            </h1>


            <p class="detalle-descripcion">
                <?php echo e($publicacion["descripcion"]); ?>
            </p>


            <div class="informacion-grid">

                <div class="informacion-box">
                    <span class="informacion-label">Precio</span>

                    <strong class="informacion-valor precio">
                        <?php
                        if ($publicacion["precio"] !== null && (float) $publicacion["precio"] > 0) {
                            echo "$ " . number_format((float) $publicacion["precio"], 0, ",", ".");
                        } else {
                            echo "Consultar precio";
                        }
                        ?>
                    </strong>
                </div>

                <div class="informacion-box">
                    <span class="informacion-label">Proveedor</span>

                    <strong class="informacion-valor">
                        <?php echo e($publicacion["nombre_proveedor"]); ?>
                    </strong>
                </div>

                <div class="informacion-box">
                    <span class="informacion-label">Dirección</span>

                    <strong class="informacion-valor">
                        
                        <?php
                        echo ($publicacion["direccion"] !== null && $publicacion["direccion"] !== "")
                            ? e($publicacion["direccion"])
                            : "No especificada";
                        ?>
                    </strong>
                </div>

            </div>


            <?php if (!empty($error)): ?>

                <div class="mensaje error">
                    <?php echo e($error); ?>
                </div>

            <?php endif; ?>


            <?php if (!$disponible): ?>

                <div class="mensaje error">
                    Esta publicación está inactiva y no recibe solicitudes.
                </div>

            <?php elseif (!$logueado): ?>

                <p>
                    Debes iniciar sesión para realizar una solicitud.
                </p>

            <?php elseif ($es_dueno): ?>

                <div class="mensaje exito">
                    Esta es tu publicación. Las solicitudes llegan a tu panel de proveedor.
                </div>

            <?php elseif ($es_admin): ?>

                <div class="mensaje exito">
                    Estás viendo esta publicación como administrador.
                </div>

            <?php elseif ($solicitud_activa): ?>

                <div class="mensaje exito">
                    Ya tienes una solicitud en curso para esta publicación.
                    Puedes seguirla en <a href="mis_solicitudes.php">Mis solicitudes</a>.
                </div>

            <?php else: ?>

                <form method="POST" class="form-solicitud">

                    <label for="mensaje">
                        Mensaje para el proveedor
                    </label>

                    <textarea
                        name="mensaje"
                        id="mensaje"
                        rows="4"
                        maxlength="500"
                        placeholder="Escribe aquí tu solicitud..."
                        required
                    ><?php echo e($mensaje_enviado); ?></textarea>

                    <small id="contador-mensaje">0 / 500</small>

                    <button type="submit" class="btn-principal">
                        Realizar solicitud
                    </button>

                </form>

            <?php endif; ?>


            <div class="acciones-detalle">

                <?php if (!$logueado && $disponible): ?>

                    <a href="login.php" class="btn-principal">
                        Iniciar sesión
                    </a>

                <?php endif; ?>

                <a href="publicaciones.php" class="btn-secundario">
                    Volver a publicaciones
                </a>

            </div>

        </div>

    </main>


    <script>
    document.addEventListener("DOMContentLoaded", function () {

        var campo = document.getElementById("mensaje");
        var contador = document.getElementById("contador-mensaje");

        // Contador de caracteres del mensaje
        if (campo && contador) {

            function actualizar() {
                contador.textContent = campo.value.length + " / " + campo.maxLength;
            }

            campo.addEventListener("input", actualizar);
            actualizar();
        }
    });
    </script>

</body>

</html>