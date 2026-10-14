<?php

session_start();

require_once "config/conexion.php";

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

// Muestra un dato o un texto por defecto si está vacío
function dato($valor, $vacio = "No especificado")
{
    return ($valor !== null && trim((string) $valor) !== "") ? e($valor) : $vacio;
}

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

// Cada rol tiene su propio panel
$rol = $_SESSION["rol"] ?? "";

if ($rol === "admin") {
    header("Location: admin_dashboard.php");
    exit();
}

if ($rol === "proveedor") {
    header("Location: proveedor_dashboard.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];

// Datos actualizados del usuario
$stmt = $conexion->prepare(
    "SELECT nombre, apellido, correo, telefono, fecha_registro
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

// Resumen de solicitudes del usuario
$stmt = $conexion->prepare(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(estado = 'pendiente'), 0) AS pendientes,
        COALESCE(SUM(estado = 'aceptada'), 0) AS aceptadas,
        COALESCE(SUM(estado = 'finalizada'), 0) AS finalizadas
     FROM solicitudes
     WHERE id_usuario = ?"
);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Últimas solicitudes
$stmt = $conexion->prepare(
    "SELECT
        s.estado,
        s.fecha_solicitud,
        p.id_publicacion,
        p.titulo
     FROM solicitudes s
     INNER JOIN publicaciones p
        ON s.id_publicacion = p.id_publicacion
     WHERE s.id_usuario = ?
     ORDER BY s.fecha_solicitud DESC
     LIMIT 3"
);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$ultimas = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mi cuenta - Universo Deportivo</title>

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
                <a href="mis_solicitudes.php">Mis solicitudes</a>
                <a href="perfil.php">Mi perfil</a>

                <span>
                    Hola, <?php echo e($usuario["nombre"]); ?>
                </span>

                <a href="logout.php">Cerrar sesión</a>

            </nav>

        </div>

    </header>


    <main class="contenedor-solicitudes">

        <div class="titulo-solicitudes">

            <h1>
                Bienvenido, <?php echo e($usuario["nombre"]); ?>
            </h1>

            <p>
                Este es tu espacio personal en Universo Deportivo.
            </p>

        </div>


        <div class="lista-solicitudes">

            <section class="solicitud-card">

                <h2>Mi cuenta</h2>

                <div class="solicitud-info">

                    <p>
                        <strong>Nombre:</strong>
                        <?php echo e($usuario["nombre"] . " " . $usuario["apellido"]); ?>
                    </p>

                    <p>
                        <strong>Correo:</strong>
                        <?php echo e($usuario["correo"]); ?>
                    </p>

                    <p>
                        <strong>Teléfono:</strong>
                        <?php echo dato($usuario["telefono"]); ?>
                    </p>

                    <p>
                        <strong>Miembro desde:</strong>
                        <?php echo date("d/m/Y", strtotime($usuario["fecha_registro"])); ?>
                    </p>

                </div>

                <div class="acciones-solicitud">

                    <a href="perfil.php" class="btn-secundario">
                        Editar perfil
                    </a>

                </div>

            </section>


            <section class="solicitud-card">

                <h2>Mis solicitudes</h2>

                <div class="inicio-estadisticas">

                    <div>
                        <strong><?php echo (int) $resumen["total"]; ?></strong>
                        <span>en total</span>
                    </div>

                    <div>
                        <strong><?php echo (int) $resumen["pendientes"]; ?></strong>
                        <span>pendientes</span>
                    </div>

                    <div>
                        <strong><?php echo (int) $resumen["aceptadas"]; ?></strong>
                        <span>aceptadas</span>
                    </div>

                </div>

                <div class="solicitud-info">

                    <?php if ($ultimas->num_rows > 0): ?>

                        <?php while ($solicitud = $ultimas->fetch_assoc()): ?>

                            <p>
                                <a href="publicacion.php?id=<?php echo (int) $solicitud["id_publicacion"]; ?>">
                                    <?php echo e($solicitud["titulo"]); ?>
                                </a>
                                <span class="estado-solicitud estado-<?php echo e($solicitud["estado"]); ?>">
                                    <?php echo e($solicitud["estado"]); ?>
                                </span>
                            </p>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <p>Aún no has enviado solicitudes.</p>

                    <?php endif; ?>

                </div>

                <div class="acciones-solicitud">

                    <a href="mis_solicitudes.php" class="btn-secundario">
                        Ver todas
                    </a>

                </div>

            </section>


            <section class="solicitud-card">

                <h2>¿Qué quieres hacer?</h2>

                <p class="solicitud-descripcion">
                    Encuentra actividades, servicios y productos deportivos
                    y envía tu solicitud al proveedor.
                </p>

                <div class="acciones-solicitud">

                    <a href="publicaciones.php" class="btn-principal">
                        Explorar publicaciones
                    </a>

                </div>

            </section>

        </div>

    </main>


    <footer>

        <p>
            © 2026 Universo Deportivo - Proyecto académico
        </p>

    </footer>

</body>

</html>

<?php

$stmt->close();

?>