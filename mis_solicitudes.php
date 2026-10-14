<?php

session_start();

require_once "config/conexion.php";

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

// Verificar que el usuario haya iniciado sesión
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

// Mensaje de la última acción
$mensaje = $_SESSION["mensaje_mis_solicitudes"] ?? null;
unset($_SESSION["mensaje_mis_solicitudes"]);

// Consultar las solicitudes del usuario
$sql = "SELECT
            s.id_solicitud,
            s.fecha_solicitud,
            s.estado,
            s.mensaje,
            p.id_publicacion,
            p.titulo,
            p.descripcion,
            p.estado AS estado_publicacion
        FROM solicitudes s
        INNER JOIN publicaciones p
            ON s.id_publicacion = p.id_publicacion
        WHERE s.id_usuario = ?
        ORDER BY s.fecha_solicitud DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();

$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mis solicitudes - Universo Deportivo</title>

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
                <a href="perfil.php">Mi perfil</a>

                <span>
                    Hola, <?php echo e($_SESSION["nombre"]); ?>
                </span>

                <a href="logout.php">Cerrar sesión</a>

            </nav>

        </div>

    </header>


    <main class="contenedor-solicitudes">

        <div class="titulo-solicitudes">

            <h1>Mis solicitudes</h1>

            <p>
                Aquí puedes consultar las solicitudes que has realizado.
            </p>

        </div>


        <?php if ($mensaje): ?>

            <div class="<?php echo $mensaje["tipo"] === "exito" ? "mensaje-exito" : "mensaje-error"; ?>">
                <?php echo e($mensaje["texto"]); ?>
            </div>

        <?php endif; ?>


        <?php if ($resultado->num_rows > 0): ?>

            <div class="filtros-estado">

                <button type="button" class="filtro-estado activo" data-estado="todas">Todas</button>
                <button type="button" class="filtro-estado" data-estado="pendiente">Pendientes</button>
                <button type="button" class="filtro-estado" data-estado="aceptada">Aceptadas</button>
                <button type="button" class="filtro-estado" data-estado="rechazada">Rechazadas</button>
                <button type="button" class="filtro-estado" data-estado="finalizada">Finalizadas</button>

            </div>

            <div class="lista-solicitudes">

                <?php while ($solicitud = $resultado->fetch_assoc()): ?>

                    <section class="solicitud-card"
                             data-estado-solicitud="<?php echo e($solicitud["estado"]); ?>">

                        <h2>
                            <?php echo e($solicitud["titulo"]); ?>
                        </h2>

                        <p class="solicitud-descripcion">
                            <?php
                            $texto = (string) $solicitud["descripcion"];
                            echo e(mb_strlen($texto) > 100 ? mb_substr($texto, 0, 100) . "..." : $texto);
                            ?>
                        </p>

                        <div class="solicitud-info">

                            <p>
                                <strong>Fecha:</strong>
                                <?php echo date("d/m/Y H:i", strtotime($solicitud["fecha_solicitud"])); ?>
                            </p>

                            <p>
                                <strong>Estado:</strong>

                                <span class="estado-solicitud estado-<?php echo e($solicitud["estado"]); ?>">
                                    <?php echo e($solicitud["estado"]); ?>
                                </span>

                            </p>

                        </div>


                        <?php if (!empty($solicitud["mensaje"])): ?>

                            <div class="mensaje-solicitud">

                                <strong>Mensaje enviado:</strong>

                                <p>
                                    <?php echo e($solicitud["mensaje"]); ?>
                                </p>

                            </div>

                        <?php endif; ?>


                        <div class="acciones-solicitud">

                            <?php if ($solicitud["estado_publicacion"] === "activo"): ?>

                                <a href="publicacion.php?id=<?php echo (int) $solicitud["id_publicacion"]; ?>"
                                   class="btn-secundario">
                                    Ver publicación
                                </a>

                            <?php endif; ?>

                            <?php if ($solicitud["estado"] === "pendiente"): ?>

                                <form method="POST"
                                      action="cancelar_solicitud.php"
                                      data-confirmar="¿Seguro que deseas cancelar esta solicitud?">

                                    <input type="hidden" name="id_solicitud"
                                           value="<?php echo (int) $solicitud["id_solicitud"]; ?>">

                                    <button type="submit" class="btn-principal btn-peligro">
                                        Cancelar solicitud
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>

                    </section>

                <?php endwhile; ?>

            </div>

            <div class="sin-solicitudes" id="filtro-vacio" style="display: none;">

                <h2>No hay solicitudes con ese estado</h2>

            </div>

        <?php else: ?>

            <div class="sin-solicitudes">

                <h2>Aún no tienes solicitudes</h2>

                <p>
                    Explora las publicaciones disponibles y realiza tu primera solicitud.
                </p>

                <a href="publicaciones.php" class="btn-principal">
                    Explorar publicaciones
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
    document.addEventListener("DOMContentLoaded", function () {

        // Confirmación antes de cancelar una solicitud
        document.querySelectorAll("form[data-confirmar]").forEach(function (formulario) {
            formulario.addEventListener("submit", function (evento) {
                if (!confirm(formulario.dataset.confirmar)) {
                    evento.preventDefault();
                }
            });
        });

        // Filtro por estado, sin recargar la página
        var botones = document.querySelectorAll(".filtro-estado");
        var tarjetas = document.querySelectorAll("[data-estado-solicitud]");
        var vacio = document.getElementById("filtro-vacio");

        botones.forEach(function (boton) {

            boton.addEventListener("click", function () {

                botones.forEach(function (otro) {
                    otro.classList.remove("activo");
                });

                boton.classList.add("activo");

                var estado = boton.dataset.estado;
                var visibles = 0;

                tarjetas.forEach(function (tarjeta) {

                    var coincide = (estado === "todas") ||
                                   (tarjeta.dataset.estadoSolicitud === estado);

                    tarjeta.style.display = coincide ? "" : "none";

                    if (coincide) {
                        visibles++;
                    }
                });

                if (vacio) {
                    vacio.style.display = (visibles === 0) ? "" : "none";
                }
            });
        });
    });
    </script>

</body>

</html>

<?php

$stmt->close();

?>