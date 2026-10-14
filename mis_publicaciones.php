<?php

session_start();

require_once "config/conexion.php";

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

// Verificar que haya una sesión iniciada
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

// Verificar que el usuario sea proveedor
if (($_SESSION["rol"] ?? "") != "proveedor") {
    header("Location: dashboard.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];

// Buscar el proveedor relacionado con el usuario
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
$mensaje = $_SESSION["mensaje_publicacion"] ?? null;
unset($_SESSION["mensaje_publicacion"]);

// Publicaciones del proveedor, con sus solicitudes pendientes
$sql = "SELECT
            p.id_publicacion,
            p.titulo,
            p.descripcion,
            p.tipo,
            p.precio,
            p.fecha_publicacion,
            p.estado,
            (SELECT COUNT(*)
             FROM solicitudes s
             WHERE s.id_publicacion = p.id_publicacion
             AND s.estado = 'pendiente') AS pendientes
        FROM publicaciones p
        WHERE p.id_proveedor = ?
        ORDER BY p.fecha_publicacion DESC";

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

    <title>Mis publicaciones - Universo Deportivo</title>

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

            <span>
                Hola, <?php echo e($_SESSION["nombre"]); ?>
            </span>

            <a href="logout.php">Cerrar sesión</a>

        </nav>

    </div>

</header>


<main class="contenedor-solicitudes">

    <div class="titulo-solicitudes">

        <h1>
            Mis publicaciones
        </h1>

        <p>
            Publicaciones de <?php echo e($nombre_negocio); ?>
        </p>

        <div class="acciones-solicitud">

            <a href="crear_publicacion.php" class="btn-principal">
                + Crear publicación
            </a>

        </div>

    </div>


    <?php if ($mensaje): ?>

        <div class="<?php echo $mensaje["tipo"] === "exito" ? "mensaje-exito" : "mensaje-error"; ?>">
            <?php echo e($mensaje["texto"]); ?>
        </div>

    <?php endif; ?>


    <?php if ($resultado->num_rows > 0): ?>

        <div class="lista-solicitudes">

            <?php while ($publicacion = $resultado->fetch_assoc()): ?>

                <?php $activa = ($publicacion["estado"] === "activo"); ?>

                <section class="solicitud-card">

                    <h2>
                        <?php echo e($publicacion["titulo"]); ?>
                    </h2>

                    <span class="<?php echo $activa ? "estado-activo" : "estado-inactivo"; ?>">
                        <?php echo $activa ? "Activa" : "Inactiva"; ?>
                    </span>

                    <?php if ((int) $publicacion["pendientes"] > 0): ?>

                        <span class="estado-solicitud estado-pendiente">
                            <?php echo (int) $publicacion["pendientes"]; ?> pendiente(s)
                        </span>

                    <?php endif; ?>

                    <div class="solicitud-info">

                        <p>
                            <strong>Tipo:</strong>
                            <?php echo e(ucfirst($publicacion["tipo"])); ?>
                        </p>

                        <p class="solicitud-descripcion">
                            <?php
                            $texto = (string) $publicacion["descripcion"];
                            echo e(mb_strlen($texto) > 120 ? mb_substr($texto, 0, 120) . "..." : $texto);
                            ?>
                        </p>

                        <p>
                            <strong>Precio:</strong>
                            <?php
                            if ($publicacion["precio"] !== null && (float) $publicacion["precio"] > 0) {
                                echo "$ " . number_format((float) $publicacion["precio"], 0, ",", ".");
                            } else {
                                echo "Consultar precio";
                            }
                            ?>
                        </p>

                        <p>
                            <strong>Fecha:</strong>
                            <?php echo date("d/m/Y H:i", strtotime($publicacion["fecha_publicacion"])); ?>
                        </p>

                    </div>

                    <div class="acciones-solicitud">

                        <a href="publicacion.php?id=<?php echo (int) $publicacion["id_publicacion"]; ?>"
                           class="btn-secundario">
                            Ver
                        </a>

                        <a href="editar_publicacion.php?id=<?php echo (int) $publicacion["id_publicacion"]; ?>"
                           class="btn-principal">
                            Editar
                        </a>

                        <?php if ($activa): ?>

                            <form method="POST" action="cambiar_estado_publicacion.php"
                                  data-confirmar="¿Seguro que deseas inactivar esta publicación? Dejará de mostrarse al público.">
                                <input type="hidden" name="id_publicacion"
                                       value="<?php echo (int) $publicacion["id_publicacion"]; ?>">
                                <input type="hidden" name="nuevo_estado" value="inactivo">
                                <button type="submit" class="btn-principal btn-peligro">Inactivar</button>
                            </form>

                        <?php else: ?>

                            <form method="POST" action="cambiar_estado_publicacion.php">
                                <input type="hidden" name="id_publicacion"
                                       value="<?php echo (int) $publicacion["id_publicacion"]; ?>">
                                <input type="hidden" name="nuevo_estado" value="activo">
                                <button type="submit" class="btn-principal">Activar</button>
                            </form>

                        <?php endif; ?>

                    </div>

                </section>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="sin-solicitudes">

            <h2>
                No tienes publicaciones todavía.
            </h2>

            <p>
                Crea tu primera publicación para comenzar a ofrecer tus servicios,
                actividades o productos deportivos.
            </p>

            <a href="crear_publicacion.php" class="btn-principal">
                Crear publicación
            </a>

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