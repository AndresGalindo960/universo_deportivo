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

if (($_SESSION["rol"] ?? "") !== "admin") {
    header("Location: dashboard.php");
    exit();
}

/* =========================
   ESTADÍSTICAS
========================= */

$total_usuarios = $conexion->query(
    "SELECT COUNT(*) AS total FROM usuarios"
)->fetch_assoc()["total"];

$total_proveedores = $conexion->query(
    "SELECT COUNT(*) AS total FROM proveedores"
)->fetch_assoc()["total"];

$total_publicaciones = $conexion->query(
    "SELECT COUNT(*) AS total
     FROM publicaciones
     WHERE estado = 'activo'"
)->fetch_assoc()["total"];

$total_solicitudes = $conexion->query(
    "SELECT COUNT(*) AS total FROM solicitudes"
)->fetch_assoc()["total"];

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de administrador - Universo Deportivo</title>

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
            <a href="admin_usuarios.php">Usuarios</a>
            <a href="admin_publicaciones.php">Gestión de publicaciones</a>
            <a href="perfil.php">Mi perfil</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>

    </div>

</header>

<main>

    <section class="contenedor-solicitudes">

        <h2 class="titulo-solicitudes">
            Panel de administrador
        </h2>

        <p>
            Bienvenido,
            <?php
            echo htmlspecialchars(
                $_SESSION["nombre"] ?? "Administrador",
                ENT_QUOTES,
                "UTF-8"
            );
            ?>.
            Desde este panel puedes consultar el estado general de la plataforma.
        </p>

        <div class="estadisticas-admin">

            <div class="tarjeta-estadistica">
                <h3>Usuarios</h3>
                <p><?php echo (int) $total_usuarios; ?></p>
            </div>

            <div class="tarjeta-estadistica">
                <h3>Proveedores</h3>
                <p><?php echo (int) $total_proveedores; ?></p>
            </div>

            <div class="tarjeta-estadistica">
                <h3>Publicaciones activas</h3>
                <p><?php echo (int) $total_publicaciones; ?></p>
            </div>

            <div class="tarjeta-estadistica">
                <h3>Solicitudes</h3>
                <p><?php echo (int) $total_solicitudes; ?></p>
            </div>

        </div>

        <div class="acciones-admin">

            <h3>Gestión administrativa</h3>

            <a href="admin_usuarios.php" class="btn-principal">
                Gestionar usuarios
            </a>

            <a href="admin_publicaciones.php" class="btn-principal">
                Gestionar publicaciones
            </a>

        </div>

    </section>

</main>

<footer>
    <p>© 2026 Universo Deportivo - Proyecto académico</p>
</footer>

</body>
</html>