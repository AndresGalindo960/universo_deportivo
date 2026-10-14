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


<!-- Portada -->
<section class="hero">

    <div class="hero-contenido">

        <span class="hero-etiqueta">
            Plataforma deportiva de tu comunidad
        </span>

        <h1>
            Encuentra tu próxima <span>experiencia deportiva</span>
        </h1>

        <p>
            Descubre actividades, servicios y productos deportivos
            ofrecidos por entrenadores, clubes y tiendas cerca de ti.
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

    </div>

</section>


<main>

    <!-- Categorías -->
    <div class="bloque">

        <h2 class="bloque-titulo">¿Qué puedes encontrar?</h2>

        <p class="bloque-sub">
            Tres formas de acercarte al deporte, todas en un solo lugar.
        </p>

        <div class="categorias">

            <a href="publicaciones.php?tipo=actividad" class="categoria-card tipo-actividad">
                <h3>Actividades</h3>
                <p>Encuentra actividades deportivas para practicar y disfrutar.</p>
                <span>Ver actividades</span>
            </a>

            <a href="publicaciones.php?tipo=servicio" class="categoria-card tipo-servicio">
                <h3>Servicios</h3>
                <p>Encuentra entrenadores, academias y profesionales deportivos.</p>
                <span>Ver servicios</span>
            </a>

            <a href="publicaciones.php?tipo=producto" class="categoria-card tipo-producto">
                <h3>Productos</h3>
                <p>Descubre productos y artículos relacionados con el deporte.</p>
                <span>Ver productos</span>
            </a>

        </div>

    </div>


    <!-- Últimas publicaciones -->
    <div class="bloque">

        <h2 class="bloque-titulo">Últimas publicaciones</h2>

        <p class="bloque-sub">
            Lo más reciente publicado por nuestros proveedores.
        </p>

        <?php if ($ultimas && $ultimas->num_rows > 0): ?>

            <div class="contenedor-publicaciones">

                <?php while ($pub = $ultimas->fetch_assoc()): ?>

                    <div class="tarjeta-publicacion">

                        <div class="tarjeta-imagen tipo-<?php echo e($pub["tipo"]); ?>">
                            <span><?php echo e(ucfirst($pub["tipo"])); ?></span>
                        </div>

                        <h3><?php echo e($pub["titulo"]); ?></h3>

                        <p>Por <strong><?php echo e($pub["nombre_negocio"]); ?></strong></p>

                        <p>
                            <?php
                            $texto = (string) $pub["descripcion"];
                            echo e(mb_strlen($texto) > 90 ? mb_substr($texto, 0, 90) . "..." : $texto);
                            ?>
                        </p>

                        <p class="precio-tarjeta">
                            <?php
                            if ($pub["precio"] !== null && (float) $pub["precio"] > 0) {
                                echo "$ " . number_format((float) $pub["precio"], 0, ",", ".");
                            } else {
                                echo "Consultar precio";
                            }
                            ?>
                        </p>

                        <a href="publicacion.php?id=<?php echo (int) $pub["id_publicacion"]; ?>"
                           class="boton-publicacion">
                            Ver detalle
                        </a>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <p class="bloque-sub">Aún no hay publicaciones activas. ¡Vuelve pronto!</p>

        <?php endif; ?>

    </div>


    <!-- Cómo funciona -->
    <div class="bloque">

        <h2 class="bloque-titulo">¿Cómo funciona?</h2>

        <p class="bloque-sub">
            Tres pasos para encontrar lo que buscas.
        </p>

        <div class="pasos">

            <div class="paso">
                <div class="paso-numero">1</div>
                <h3>Regístrate</h3>
                <p>Crea tu cuenta como usuario, o como proveedor si quieres publicar.</p>
            </div>

            <div class="paso">
                <div class="paso-numero">2</div>
                <h3>Explora</h3>
                <p>Busca actividades, servicios y productos y consulta su detalle.</p>
            </div>

            <div class="paso">
                <div class="paso-numero">3</div>
                <h3>Solicita</h3>
                <p>Envía tu solicitud y haz seguimiento desde tu panel.</p>
            </div>

        </div>

    </div>

</main>


<?php if (!$logueado): ?>

    <section class="cta-final">

        <h2>¿Ofreces servicios o productos deportivos?</h2>

        <p>
            Regístrate como proveedor, publica lo que ofreces y recibe
            solicitudes de personas interesadas.
        </p>

        <a href="registro.php" class="btn-principal">
            Registrarme como proveedor
        </a>

    </section>

<?php endif; ?>


<footer>

    <p>
        © 2026 Universo Deportivo - Proyecto académico
    </p>

</footer>

</body>

</html>
