<?php

session_start();

require_once "config/conexion.php";

// Verificar sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

// Verificar rol
if ($_SESSION["rol"] != "proveedor") {
    header("Location: dashboard.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];

// Obtener proveedor
$sql_proveedor = "SELECT id_proveedor
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


// Verificar que exista el ID de publicación
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: mis_publicaciones.php");
    exit();
}

$id_publicacion = $_GET["id"];


// Buscar la publicación
$sql = "SELECT
            id_publicacion,
            titulo,
            descripcion,
            tipo,
            precio,
            estado
        FROM publicaciones
        WHERE id_publicacion = ?
        AND id_proveedor = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_publicacion, $id_proveedor);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    die("La publicación no existe o no pertenece a este proveedor.");
}

$publicacion = $resultado->fetch_assoc();


// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $titulo = trim($_POST["titulo"]);
    $descripcion = trim($_POST["descripcion"]);
    $tipo = $_POST["tipo"];
    $precio = $_POST["precio"];
    $estado = $_POST["estado"];

    if (empty($titulo) || empty($descripcion) || empty($tipo)) {

        $mensaje = "Completa todos los campos obligatorios.";

    } elseif (!is_numeric($precio) || $precio < 0) {

        $mensaje = "El precio debe ser un número válido.";

    } else {

        $sql_update = "UPDATE publicaciones
                       SET titulo = ?,
                           descripcion = ?,
                           tipo = ?,
                           precio = ?,
                           estado = ?
                       WHERE id_publicacion = ?
                       AND id_proveedor = ?";

        $stmt_update = $conexion->prepare($sql_update);

        $stmt_update->bind_param(
            "sssdsii",
            $titulo,
            $descripcion,
            $tipo,
            $precio,
            $estado,
            $id_publicacion,
            $id_proveedor
        );


        if ($stmt_update->execute()) {

            header("Location: mis_publicaciones.php");
            exit();

        } else {

            $mensaje = "Ocurrió un error al actualizar la publicación.";
        }

        $stmt_update->close();
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar publicación - Universo Deportivo</title>

    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>

<header class="encabezado">

    <div class="contenedor-header">

        <a href="index.php" class="logo">
            Universo Deportivo
        </a>

        <nav>

            <a href="index.php">
                Inicio
            </a>

            <a href="mis_publicaciones.php">
                Mis publicaciones
            </a>

            <a href="logout.php">
                Cerrar sesión
            </a>

        </nav>

    </div>

</header>


<main class="contenedor-solicitudes">

    <div class="titulo-solicitudes">

        <h1>
            Editar publicación
        </h1>

        <p>
            Modifica la información de tu publicación.
        </p>

    </div>


    <?php if (isset($mensaje)) { ?>

        <div class="mensaje-error">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>

    <?php } ?>


    <section class="solicitud-card">

        <form method="POST">

            <div class="campo-formulario">

                <label for="titulo">
                    Título
                </label>

                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    maxlength="150"
                    value="<?php echo htmlspecialchars($publicacion["titulo"]); ?>"
                    required
                >

            </div>


            <div class="campo-formulario">

                <label for="descripcion">
                    Descripción
                </label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="5"
                    required
                ><?php echo htmlspecialchars($publicacion["descripcion"]); ?></textarea>

            </div>


            <div class="campo-formulario">

                <label for="tipo">
                    Tipo
                </label>

                <select
                    id="tipo"
                    name="tipo"
                    required
                >

                    <option value="servicio"
                        <?php if ($publicacion["tipo"] == "servicio") echo "selected"; ?>>
                        Servicio
                    </option>

                    <option value="actividad"
                        <?php if ($publicacion["tipo"] == "actividad") echo "selected"; ?>>
                        Actividad
                    </option>

                    <option value="producto"
                        <?php if ($publicacion["tipo"] == "producto") echo "selected"; ?>>
                        Producto
                    </option>

                </select>

            </div>


            <div class="campo-formulario">

                <label for="precio">
                    Precio
                </label>

                <input
                    type="number"
                    id="precio"
                    name="precio"
                    min="0"
                    step="0.01"
                    value="<?php echo htmlspecialchars($publicacion["precio"]); ?>"
                    required
                >

            </div>


            <div class="campo-formulario">

                <label for="estado">
                    Estado
                </label>

                <select
                    id="estado"
                    name="estado"
                    required
                >

                    <option value="activo"
                        <?php if ($publicacion["estado"] == "activo") echo "selected"; ?>>
                        Activo
                    </option>

                    <option value="inactivo"
                        <?php if ($publicacion["estado"] == "inactivo") echo "selected"; ?>>
                        Inactivo
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="btn-principal"
            >
                Guardar cambios
            </button>

            <a
                href="mis_publicaciones.php"
                class="btn-principal"
            >
                Cancelar
            </a>

        </form>

    </section>

</main>

</body>

</html>