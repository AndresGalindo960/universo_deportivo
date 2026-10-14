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

// Buscar los datos del proveedor
$sql = "SELECT
            id_proveedor,
            nombre_negocio,
            tipo_proveedor,
            descripcion,
            telefono,
            direccion
        FROM proveedores
        WHERE id_usuario = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    die("No se encontró información del proveedor.");
}

$proveedor = $resultado->fetch_assoc();
$id_proveedor = $proveedor["id_proveedor"];
$stmt->close();

// Resumen de publicaciones
$stmt = $conexion->prepare(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(estado = 'activo'), 0) AS activas
     FROM publicaciones
     WHERE id_proveedor = ?"
);
$stmt->bind_param("i", $id_proveedor);
$stmt->execute();
$resumen_pub = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Resumen de solicitudes recibidas
$stmt = $conexion->prepare(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(s.estado = 'pendiente'), 0) AS pendientes
     FROM solicitudes s
     INNER JOIN publicaciones p
        ON s.id_publicacion = p.id_publicacion
     WHERE p.id_proveedor = ?"
);
$stmt->bind_param("i", $id_proveedor);
$stmt->execute();
$resumen_sol = $stmt->get_result()->fetch_assoc();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel del proveedor - Universo Deportivo</title>

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
                <a href="mis_publicaciones.php">Mis publicaciones</a>
                <a href="solicitudes_proveedor.php">Solicitudes</a>

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
                Panel del proveedor
            </h1>

            <p>
                Administra la información y publicaciones de tu negocio deportivo.
            </p>

        </div>


        <div class="lista-solicitudes">

            <section class="solicitud-card">

                <h2>
                    <?php echo e($proveedor["nombre_negocio"]); ?>
                </h2>

                <div class="solicitud-info">

                    <p>
                        <strong>Tipo:</strong>
                        <?php echo dato($proveedor["tipo_proveedor"]); ?>
                    </p>

                    <p>
                        <strong>Descripción:</strong>
                        <?php echo dato($proveedor["descripcion"], "Sin descripción"); ?>
                    </p>

                    <p>
                        <strong>Teléfono:</strong>
                        <?php echo dato($proveedor["telefono"]); ?>
                    </p>

                    <p>
                        <strong>Dirección:</strong>
                        <?php echo dato($proveedor["direccion"]); ?>
                    </p>

                </div>

            </section>


            <section class="solicitud-card">

                <h2>
                    Resumen
                </h2>

                <div class="inicio-estadisticas">

                    <div>
                        <strong><?php echo (int) $resumen_pub["total"]; ?></strong>
                        <span>publicaciones</span>
                    </div>

                    <div>
                        <strong><?php echo (int) $resumen_pub["activas"]; ?></strong>
                        <span>activas</span>
                    </div>

                </div>

                <div class="inicio-estadisticas" style="margin-top: 20px;">

                    <div>
                        <strong><?php echo (int) $resumen_sol["total"]; ?></strong>
                        <span>solicitudes</span>
                    </div>

                    <div>
                        <strong><?php echo (int) $resumen_sol["pendientes"]; ?></strong>
                        <span>pendientes</span>
                    </div>

                </div>

            </section>


            <section class="solicitud-card">

                <h2>
                    Gestión de publicaciones
                </h2>

                <p class="solicitud-descripcion">
                    Crea y administra los servicios, actividades y productos
                    deportivos de tu negocio.
                </p>

                <div class="acciones-solicitud">

                    <a href="crear_publicacion.php" class="btn-principal">
                        Crear publicación
                    </a>

                    <a href="mis_publicaciones.php" class="btn-secundario">
                        Mis publicaciones
                    </a>

                    <a href="solicitudes_proveedor.php" class="btn-secundario">
                        Solicitudes recibidas
                        <?php if ((int) $resumen_sol["pendientes"] > 0): ?>
                            (<?php echo (int) $resumen_sol["pendientes"]; ?>)
                        <?php endif; ?>
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