<?php

session_start();

require_once "config/conexion.php";

/* =========================
   VERIFICAR SESIÓN
========================= */

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

/* =========================
   VERIFICAR ROL ADMIN
========================= */

if ($_SESSION["rol"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

/* =========================
   CONSULTAR PUBLICACIONES
========================= */

$sql = "SELECT
            p.id_publicacion,
            p.titulo,
            p.descripcion,
            p.tipo,
            p.precio,
            p.fecha_publicacion,
            p.estado,
            pr.nombre_negocio AS nombre_proveedor
        FROM publicaciones p
        INNER JOIN proveedores pr
            ON p.id_proveedor = pr.id_proveedor
        ORDER BY p.fecha_publicacion DESC";

$resultado = $conexion->query($sql);

$mensaje = $_SESSION["mensaje_admin"] ?? null;
unset($_SESSION["mensaje_admin"]);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestión de publicaciones - Universo Deportivo</title>

    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>

<header class="encabezado">

    <div class="contenedor-header">

        <div class="logo">
            Universo Deportivo
        </div>

        <nav>

            <a href="admin_dashboard.php">
                Panel administrador
            </a>

            <a href="index.php">
                Inicio
            </a>

            <a href="perfil.php">
                Mi perfil
            </a>

            <a href="logout.php">
                Cerrar sesión
            </a>

        </nav>

    </div>

</header>

<main>

    <section class="contenedor-solicitudes">

        <h2 class="titulo-solicitudes">
            Gestión de publicaciones
        </h2>

        <p>
            Desde aquí el administrador puede consultar todas las publicaciones
            registradas en Universo Deportivo, incluyendo las activas e inactivas,
            así como el proveedor responsable de cada una.
        </p>

        <?php if ($mensaje): ?>
    <div class="mensaje-<?php echo $mensaje["tipo"] === "exito" ? "exito" : "error"; ?>">
        <?php echo htmlspecialchars($mensaje["texto"], ENT_QUOTES, "UTF-8"); ?>
    </div>
<?php endif; ?>

        <div class="tabla-admin">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Precio</th>
                        <th>Proveedor</th>
                        <th>Fecha de publicación</th>
                        <th>Estado</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if ($resultado && $resultado->num_rows > 0): ?>

                        <?php while ($publicacion = $resultado->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo (int) $publicacion["id_publicacion"]; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $publicacion["titulo"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        ucfirst($publicacion["tipo"]),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    if ($publicacion["precio"] !== null) {
                                        echo "$ " . number_format(
                                            (float) $publicacion["precio"],
                                            0,
                                            ",",
                                            "."
                                        );
                                    } else {
                                        echo "No definido";
                                    }
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $publicacion["nombre_proveedor"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $publicacion["fecha_publicacion"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                                                <td>
                                    <?php if ($publicacion["estado"] === "activo"): ?>

                                        <span class="estado-activo">
                                            Activa
                                        </span>

                                    <?php else: ?>

                                        <span class="estado-inactivo">
                                            Inactiva
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <!-- ===== CELDA NUEVA: Acciones ===== -->
                                <td>
                                    <?php $esta_activa = ($publicacion["estado"] === "activo"); ?>
                                    <form method="POST"
                                          action="admin_cambiar_estado_publicacion.php"
                                          class="form-estado"
                                          data-accion="<?php echo $esta_activa ? "inactivar" : "activar"; ?>">

                                        <input type="hidden" name="id_publicacion"
                                               value="<?php echo (int) $publicacion["id_publicacion"]; ?>">
                                        <input type="hidden" name="nuevo_estado"
                                               value="<?php echo $esta_activa ? "inactivo" : "activo"; ?>">

                                        <button type="submit"
                                                class="btn-estado <?php echo $esta_activa ? "btn-inactivar" : "btn-activar"; ?>">
                                            <?php echo $esta_activa ? "Inactivar" : "Activar"; ?>
                                        </button>
                                    </form>
                                </td>
                                <!-- ===== FIN CELDA NUEVA ===== -->

                            </tr>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7">
                                No hay publicaciones registradas.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="acciones-admin">

            <a href="admin_dashboard.php" class="btn-principal">
                ← Volver al panel
            </a>

        </div>

    </section>

</main>

<footer>

    <p>
        © 2026 Universo Deportivo - Proyecto académico
    </p>

</footer>

</body>

</html>