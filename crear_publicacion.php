<?php

session_start();

require_once "config/conexion.php";

// Verificar que haya una sesión iniciada
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

// Verificar que el usuario sea proveedor
if ($_SESSION["rol"] != "proveedor") {
    header("Location: dashboard.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];

// Buscar el proveedor relacionado con el usuario
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

$mensaje = "";
$tipo_mensaje = "";

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $titulo = trim($_POST["titulo"]);
    $descripcion = trim($_POST["descripcion"]);
    $tipo = $_POST["tipo"];
    $precio = $_POST["precio"];

    // Validaciones
    if (empty($titulo) || empty($descripcion) || empty($tipo)) {

        $mensaje = "Por favor completa todos los campos obligatorios.";
        $tipo_mensaje = "error";

    } elseif (!is_numeric($precio) || $precio < 0) {

        $mensaje = "El precio debe ser un número válido mayor o igual a 0.";
        $tipo_mensaje = "error";

    } else {

        // Insertar publicación
        $sql = "INSERT INTO publicaciones
                (id_proveedor, titulo, descripcion, tipo, precio)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "isssd",
            $id_proveedor,
            $titulo,
            $descripcion,
            $tipo,
            $precio
        );

        if ($stmt->execute()) {

            header("Location: proveedor_dashboard.php");
            exit();

        } else {

            $mensaje = "Ocurrió un error al crear la publicación.";
            $tipo_mensaje = "error";
        }

        $stmt->close();
    }
}

$stmt_proveedor->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crear publicación - Universo Deportivo</title>

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

            <a href="publicaciones.php">
                Publicaciones
            </a>

            <span>
                Hola,
                <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
            </span>

            <a href="logout.php">
                Cerrar sesión
            </a>

        </nav>

    </div>

</header>


<main class="contenedor-solicitudes">

    <div class="titulo-solicitudes">

        <h1>
            Crear publicación
        </h1>

        <p>
            Publica un nuevo servicio, actividad o producto deportivo.
        </p>

    </div>


    <?php if (!empty($mensaje)) { ?>

        <div class="mensaje-error">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>

    <?php } ?>


    <section class="solicitud-card">

        <form method="POST" action="">

            <div class="campo-formulario">

                <label for="titulo">
                    Título de la publicación
                </label>

                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    maxlength="150"
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
                ></textarea>

            </div>


            <div class="campo-formulario">

                <label for="tipo">
                    Tipo de publicación
                </label>

                <select
                    id="tipo"
                    name="tipo"
                    required
                >

                    <option value="">
                        Selecciona una opción
                    </option>

                    <option value="servicio">
                        Servicio
                    </option>

                    <option value="actividad">
                        Actividad
                    </option>

                    <option value="producto">
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
                    value="0"
                    required
                >

            </div>


            <div>

                <button
                    type="submit"
                    class="btn-principal"
                >
                    Crear publicación
                </button>

                <a
                    href="proveedor_dashboard.php"
                    class="btn-principal"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</main>

</body>

</html>