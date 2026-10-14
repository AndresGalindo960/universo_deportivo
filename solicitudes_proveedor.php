<?php

session_start();

require_once "config/conexion.php";

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

// Verificar sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

// Verificar que sea proveedor
if (($_SESSION["rol"] ?? "") != "proveedor") {
    header("Location: dashboard.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];

// Obtener proveedor
$sql_proveedor = "SELECT id_proveedor, nombre_negocio
                  FROM proveedores
                  WHERE id_usuario = ?";

$stmt_proveedor = $conexion->prepare($sql_proveedor);
$stmt_proveedor->bind_param("i", $id_usuario);
$stmt_proveedor->execute();

$resultado_proveedor = $stmt_proveedor->get_result();

if ($resultado_proveedor->num_rows == 0) {
    die("No se encontró información del proveedor.");
}

$proveedor = $resultado_proveedor->fetch_assoc();

$id_proveedor = $proveedor["id_proveedor"];
$nombre_negocio = $proveedor["nombre_negocio"];
$stmt_proveedor->close();

// Mensaje de la última acción
$mensaje = $_SESSION["mensaje_solicitud"] ?? null;
unset($_SESSION["mensaje_solicitud"]);

// Obtener las solicitudes recibidas
$sql = "SELECT
            s.id_solicitud,
            s.fecha_solicitud,
            s.estado,
            s.mensaje,
            u.nombre AS nombre_usuario,
            p.titulo AS titulo_publicacion
        FROM solicitudes s

        INNER JOIN usuarios u
            ON s.id_usuario = u.id_usuario

        INNER JOIN publicaciones p
            ON s.id_publicacion = p.id_publicacion

        WHERE p.id_proveedor = ?

        ORDER BY s.fecha_solicitud DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_proveedor);
$stmt->execute();

$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Solicitudes recibidas - Universo Deportivo</title>

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
            <a href="proveedor_dashboard.php">Mi panel</a>
            <a href="logout.php">Cerrar sesión</a>

        </nav>

    </div>

</header>


<main class="contenedor-solicitudes">

    <div class="titulo-solicitudes">

        <h1>
            Solicitudes recibidas
        </h1>

        <p>
            Solicitudes relacionadas con las publicaciones de
            <?php echo e($nombre_negocio); ?>.
        </p>

    </div>


    <?php if ($mensaje): ?>

        <div class="<?php echo $mensaje["tipo"] === "exito" ? "mensaje-exito" : "mensaje-error"; ?>">
            <?php echo e($mensaje["texto"]); ?>
        </div>

    <?php endif; ?>


    <?php if ($resultado->num_rows > 0): ?>

        <div class="lista-solicitudes">

            <?php while ($solicitud = $resultado->fetch_assoc()): ?>

                <section class="solicitud-card">

                    <h2>
                        <?php echo e($solicitud["titulo_publicacion"]); ?>
                    </h2>

                    <span class="estado-solicitud estado-<?php echo e($solicitud["estado"]); ?>">
                        <?php echo e($solicitud["estado"]); ?>
                    </span>

                    <div class="solicitud-info">

                        <p>
                            <strong>Usuario:</strong>
                            <?php echo e($solicitud["nombre_usuario"]); ?>
                        </p>

                        <p>
                            <strong>Fecha:</strong>
                            <?php echo date("d/m/Y H:i", strtotime($solicitud["fecha_solicitud"])); ?>
                        </p>

                    </div>

                    <div class="mensaje-solicitud">
                        <p>
                            <?php
                            echo !empty($solicitud["mensaje"])
                                ? e($solicitud["mensaje"])
                                : "Sin mensaje";
                            ?>
                        </p>
                    </div>


                    <div class="acciones-solicitud">

                        <?php if ($solicitud["estado"] == "pendiente"): ?>

                            <form method="POST" action="actualizar_solicitud.php">
                                <input type="hidden" name="id_solicitud" value="<?php echo (int) $solicitud["id_solicitud"]; ?>">
                                <input type="hidden" name="estado" value="aceptada">
                                <button type="submit" class="btn-principal">Aceptar</button>
                            </form>

                            <form method="POST" action="actualizar_solicitud.php"
                                  data-confirmar="¿Seguro que deseas rechazar esta solicitud?">
                                <input type="hidden" name="id_solicitud" value="<?php echo (int) $solicitud["id_solicitud"]; ?>">
                                <input type="hidden" name="estado" value="rechazada">
                                <button type="submit" class="btn-principal btn-peligro">Rechazar</button>
                            </form>

                        <?php elseif ($solicitud["estado"] == "aceptada"): ?>

                            <form method="POST" action="actualizar_solicitud.php"
                                  data-confirmar="¿Deseas marcar esta solicitud como finalizada?">
                                <input type="hidden" name="id_solicitud" value="<?php echo (int) $solicitud["id_solicitud"]; ?>">
                                <input type="hidden" name="estado" value="finalizada">
                                <button type="submit" class="btn-principal">Finalizar</button>
                            </form>

                        <?php endif; ?>

                    </div>

                </section>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="sin-solicitudes">

            <h2>
                No tienes solicitudes recibidas.
            </h2>

            <p>
                Cuando un usuario solicite alguno de tus servicios,
                actividades o productos, aparecerá aquí.
            </p>

        </div>

    <?php endif; ?>

</main>


<footer>

    <p>
        © 2026 Universo Deportivo - Proyecto académico
    </p>

</footer>


<script>
// Confirmación antes de enviar los formularios que la piden
document.querySelectorAll("form[data-confirmar]").forEach(function (formulario) {
    formulario.addEventListener("submit", function (evento) {
        if (!confirm(formulario.dataset.confirmar)) {
            evento.preventDefault();
        }
    });
});
</script>

</body>

</html>

<?php

$stmt->close();

?>