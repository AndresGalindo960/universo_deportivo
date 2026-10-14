<?php

session_start();

require_once "config/conexion.php";

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

$logueado = isset($_SESSION["id_usuario"]);
$rol = $_SESSION["rol"] ?? "";

// Enlace al panel según el rol
$panel = "dashboard.php";
if ($rol === "admin") {
    $panel = "admin_dashboard.php";
} elseif ($rol === "proveedor") {
    $panel = "proveedor_dashboard.php";
}

// Estadísticas generales
$total_publicaciones = 0;
$total_proveedores = 0;

$res = $conexion->query("SELECT COUNT(*) AS total FROM publicaciones WHERE estado = 'activo'");
if ($res) {
    $total_publicaciones = (int) $res->fetch_assoc()["total"];
}

$res = $conexion->query("SELECT COUNT(*) AS total FROM proveedores");
if ($res) {
    $total_proveedores = (int) $res->fetch_assoc()["total"];
}

// Últimas publicaciones activas
$sql = "SELECT
            p.id_publicacion,
            p.titulo,
            p.descripcion,
            p.tipo,
            p.precio,
            pr.nombre_negocio
        FROM publicaciones p
        INNER JOIN proveedores pr
            ON p.id_proveedor = pr.id_proveedor
        WHERE p.estado = 'activo'
        ORDER BY p.fecha_publicacion DESC
        LIMIT 6";

$ultimas = $conexion->query($sql);

$iconos = [
    "servicio" => "",
    "actividad" => "",
    "producto" => ""
];

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Universo Deportivo</title>

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
            <a href="publicaciones.php?tipo=actividad">Actividades</a>
            <a href="publicaciones.php?tipo=servicio">Servicios</a>
            <a href="publicaciones.php?tipo=producto">Productos</a>

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


<main>

    <!-- Portada -->
    <section>

        <h2>Encuentra tu próxima experiencia deportiva</h2>

        <p>
            Descubre actividades, servicios y productos deportivos
            ofrecidos por entrenadores, clubes y tiendas de tu comunidad.
        </p>

        <div class="inicio-botones">

            <a href="publicaciones.php" class="btn-principal">
                Explorar publicaciones
            </a>

            <?php if ($logueado): ?>

                <a href="<?php echo e($panel); ?>" class="btn-secundario">
                    Ir a mi panel
                </a>

            <?php else: ?>

                <a href="registro.php" class="btn-secundario">
                    Crear cuenta
                </a>

            <?php endif; ?>

        </div>

        <div class="inicio-estadisticas">

            <div>
                <strong><?php echo $total_publicaciones; ?></strong>
                <span>publicaciones activas</span>
            </div>

            <div>
                <strong><?php echo $total_proveedores; ?></strong>
                <span>proveedores</span>
            </div>

        </div>

    </section>


    <!-- Categorías -->
    <section>

        <h2>¿Qué puedes encontrar?</h2>

        <article>
            <h3>Actividades</h3>
            <p>Encuentra actividades deportivas para practicar y disfrutar.</p>
            <a href="publicaciones.php?tipo=actividad" class="boton-publicacion">
                Ver actividades
            </a>
        </article>

        <article>
            <h3>Servicios</h3>
            <p>Encuentra entrenadores, academias y profesionales deportivos.</p>
            <a href="publicaciones.php?tipo=servicio" class="boton-publicacion">
                Ver servicios
            </a>
        </article>

        <article>
            <h3>Productos</h3>
            <p>Descubre productos y artículos relacionados con el deporte.</p>
            <a href="publicaciones.php?tipo=producto" class="boton-publicacion">
                Ver productos
            </a>
        </article>

    </section>


    <!-- Últimas publicaciones -->
    <section class="seccion-inicio">

        <h2>Últimas publicaciones</h2>

        <?php if ($ultimas && $ultimas->num_rows > 0): ?>

            <div class="contenedor-publicaciones">

                <?php while ($pub = $ultimas->fetch_assoc()): ?>

                    <div class="tarjeta-publicacion">

                        <span class="detalle-etiqueta">
                            <?php echo $iconos[$pub["tipo"]] ?? ""; ?>
                            <?php echo e(ucfirst($pub["tipo"])); ?>
                        </span>

                        <h3><?php echo e($pub["titulo"]); ?></h3>

                        <p>Por <strong><?php echo e($pub["nombre_negocio"]); ?></strong></p>

                        <p>
                            <?php
                            $texto = (string) $pub["descripcion"];
                            echo e(mb_strlen($texto) > 90 ? mb_substr($texto, 0, 90) . "..." : $texto);
                            ?>
                        </p>

                        <p>
                            <strong>
                                <?php
                                if ($pub["precio"] !== null && (float) $pub["precio"] > 0) {
                                    echo "$ " . number_format((float) $pub["precio"], 0, ",", ".");
                                } else {
                                    echo "Consultar precio";
                                }
                                ?>
                            </strong>
                        </p>

                        <a href="publicacion.php?id=<?php echo (int) $pub["id_publicacion"]; ?>"
                           class="boton-publicacion">
                            Ver detalle
                        </a>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <p style="text-align:center;">Aún no hay publicaciones activas. ¡Vuelve pronto!</p>

        <?php endif; ?>

    </section>


    <!-- Cómo funciona -->
    <section class="seccion-inicio">

        <h2>¿Cómo funciona?</h2>

        <div class="contenedor-publicaciones">

            <div class="tarjeta-publicacion">
                <h3>1. Regístrate</h3>
                <p>Crea tu cuenta como usuario, o como proveedor si quieres publicar.</p>
            </div>

            <div class="tarjeta-publicacion">
                <h3>2. Explora</h3>
                <p>Busca actividades, servicios y productos y consulta su detalle.</p>
            </div>

            <div class="tarjeta-publicacion">
                <h3>3. Solicita</h3>
                <p>Envía tu solicitud y haz seguimiento desde tu panel.</p>
            </div>

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