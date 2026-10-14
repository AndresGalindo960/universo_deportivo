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

/* =========================
   FILTROS DE BÚSQUEDA
========================= */

$busqueda = isset($_GET["busqueda"]) ? trim($_GET["busqueda"]) : "";
$tipo = isset($_GET["tipo"]) ? $_GET["tipo"] : "";

$sql = "SELECT
            p.id_publicacion,
            p.titulo,
            p.descripcion,
            p.tipo,
            p.precio,
            pr.nombre_negocio,
            pr.direccion
        FROM publicaciones p
        INNER JOIN proveedores pr
            ON p.id_proveedor = pr.id_proveedor
        WHERE p.estado = 'activo'";

$params = [];
$types = "";

/* Filtro de búsqueda */
if ($busqueda != "") {

    $sql .= " AND (
                p.titulo LIKE ?
                OR p.descripcion LIKE ?
                OR pr.nombre_negocio LIKE ?
              )";

    $texto_busqueda = "%" . $busqueda . "%";

    $params[] = $texto_busqueda;
    $params[] = $texto_busqueda;
    $params[] = $texto_busqueda;

    $types .= "sss";
}

/* Filtro por tipo */
if ($tipo != "" && in_array($tipo, ["servicio", "actividad", "producto"], true)) {

    $sql .= " AND p.tipo = ?";

    $params[] = $tipo;

    $types .= "s";
}

$sql .= " ORDER BY p.fecha_publicacion DESC";

$stmt = $conexion->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$resultado = $stmt->get_result();

$total = $resultado->num_rows;

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Publicaciones - Universo Deportivo</title>

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

    <main>

        <section>

            <h2>Publicaciones deportivas</h2>

            <p>
                Encuentra actividades, servicios y productos deportivos.
            </p>

            <form method="GET" action="publicaciones.php" class="formulario-filtros" id="formulario-filtros">

                <input
                    type="text"
                    name="busqueda"
                    id="busqueda"
                    placeholder="Buscar actividad, servicio o producto..."
                    value="<?php echo e($busqueda); ?>"
                    autocomplete="off"
                >

                <select name="tipo" id="tipo">

                    <option value="">Todos los tipos</option>

                    <option value="servicio"
                        <?php echo ($tipo == "servicio") ? "selected" : ""; ?>>
                        Servicios
                    </option>

                    <option value="actividad"
                        <?php echo ($tipo == "actividad") ? "selected" : ""; ?>>
                        Actividades
                    </option>

                    <option value="producto"
                        <?php echo ($tipo == "producto") ? "selected" : ""; ?>>
                        Productos
                    </option>

                </select>

                <button type="submit" class="btn-principal">
                    Buscar
                </button>

                <a href="publicaciones.php" class="btn-principal">
                    Limpiar
                </a>

            </form>

        </section>

        <section class="contenedor-publicaciones" id="resultados" aria-live="polite">

            <p class="resultados-info">
                <?php
                echo $total . " " . ($total === 1 ? "publicación encontrada" : "publicaciones encontradas");
                ?>
            </p>

            <?php if ($total > 0): ?>

                <?php while ($publicacion = $resultado->fetch_assoc()): ?>

                    <article class="tarjeta-publicacion">

                        <h3>
                            <?php echo e($publicacion["titulo"]); ?>
                        </h3>

                        <p>
                            <?php
                            $texto = (string) $publicacion["descripcion"];
                            echo e(mb_strlen($texto) > 120 ? mb_substr($texto, 0, 120) . "..." : $texto);
                            ?>
                        </p>

                        <p>
                            <strong>Tipo:</strong>
                            <?php echo e(ucfirst($publicacion["tipo"])); ?>
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
                            <strong>Proveedor:</strong>
                            <?php echo e($publicacion["nombre_negocio"]); ?>
                        </p>

                        <p>
                            <strong>Dirección:</strong>
                            <?php
                            echo ($publicacion["direccion"] !== null && $publicacion["direccion"] !== "")
                                ? e($publicacion["direccion"])
                                : "No especificada";
                            ?>
                        </p>

                        <a href="publicacion.php?id=<?php echo (int) $publicacion["id_publicacion"]; ?>"
                           class="boton-publicacion">
                            Ver publicación
                        </a>

                    </article>

                <?php endwhile; ?>

            <?php else: ?>

                <p class="resultados-info">
                    No hay publicaciones que coincidan con tu búsqueda.
                </p>

            <?php endif; ?>

        </section>

    </main>

    <footer>

        <p>
            © 2026 Universo Deportivo - Proyecto académico
        </p>

    </footer>


    <script>
    document.addEventListener("DOMContentLoaded", function () {

        var formulario = document.getElementById("formulario-filtros");
        var campoBusqueda = document.getElementById("busqueda");
        var campoTipo = document.getElementById("tipo");
        var resultados = document.getElementById("resultados");
        var temporizador = null;

        // Pide los resultados al servidor y reemplaza solo esa zona
        function buscar() {

            var parametros = new URLSearchParams(new FormData(formulario));
            var url = "publicaciones.php?" + parametros.toString();

            fetch(url)
                .then(function (respuesta) {
                    return respuesta.text();
                })
                .then(function (html) {
                    var documento = new DOMParser().parseFromString(html, "text/html");
                    var nuevos = documento.getElementById("resultados");

                    if (nuevos) {
                        resultados.innerHTML = nuevos.innerHTML;
                        history.replaceState(null, "", url);
                    }
                })
                .catch(function () {
                    // Si algo falla, se envía el formulario de forma normal
                    formulario.submit();
                });
        }

        // Búsqueda dinámica mientras se escribe (con una pequeña espera)
        campoBusqueda.addEventListener("input", function () {
            clearTimeout(temporizador);
            temporizador = setTimeout(buscar, 300);
        });

        // Filtro por tipo al instante
        campoTipo.addEventListener("change", buscar);

        // El botón Buscar y la tecla Enter también usan la búsqueda dinámica
        formulario.addEventListener("submit", function (evento) {
            evento.preventDefault();
            buscar();
        });
    });
    </script>

</body>

</html>