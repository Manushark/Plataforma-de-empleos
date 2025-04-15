<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      
define('DB_PASS', '');           
define('DB_NAME', 'empleos_db');

// Establecer conexión a la base de datos
function conectarDB() {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Verificar conexión
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Configurar caracteres UTF-8
    $conexion->set_charset("utf8");
    
    return $conexion;
}

// Función para escapar y limpiar datos de entrada
function limpiarDatos($datos) {
    $conexion = conectarDB();
    if (is_array($datos)) {
        $datos = array_map(function($item) use ($conexion) {
            return $conexion->real_escape_string(trim($item));
        }, $datos);
        return $datos;
    }
    return $conexion->real_escape_string(trim($datos));
}
?>