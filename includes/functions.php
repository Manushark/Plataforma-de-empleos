<?php
require_once __DIR__ . '/../config/db.php';

// Iniciar sesión si no está iniciada
function iniciarSesion() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Verificar si el usuario está autenticado
function estaAutenticado() {
    iniciarSesion();
    return isset($_SESSION['usuario_id']);
}

// Verificar si el usuario es un candidato
function esCandidato() {
    iniciarSesion();
    return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'candidato';
}

// Verificar si el usuario es una empresa
function esEmpresa() {
    iniciarSesion();
    return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'empresa';
}

// Redirigir si no está autenticado
function requiereAutenticacion() {
    if (!estaAutenticado()) {
        header('Location: /Plataforma-de-empleos/login.php');
        exit;
    }
}

// Redirigir si no es candidato
function requiereCandidato() {
    requiereAutenticacion();
    if (!esCandidato()) {
        header('Location: /Plataforma-de-empleos/index.php');
        exit;
    }
}

// Redirigir si no es empresa
function requiereEmpresa() {
    requiereAutenticacion();
    if (!esEmpresa()) {
        header('Location: /Plataforma-de-empleos/index.php');
        exit;
    }
}

// Mostrar mensajes de notificación
function mostrarNotificacion($mensaje, $tipo = 'success') {
    iniciarSesion();
    $_SESSION['notificacion'] = [
        'mensaje' => $mensaje,
        'tipo' => $tipo
    ];
}

// Verificar si hay notificaciones
function hayNotificaciones() {
    iniciarSesion();
    return isset($_SESSION['notificacion']);
}

// Obtener notificaciones
function obtenerNotificaciones() {
    iniciarSesion();
    if (isset($_SESSION['notificacion'])) {
        $notificacion = $_SESSION['notificacion'];
        unset($_SESSION['notificacion']);
        return $notificacion;
    }
    return null;
}

// Subir archivo
function subirArchivo($archivo, $directorio = '../uploads/cv/', $tipos_permitidos = ['application/pdf']) {
    // Verificar si hay errores
    if ($archivo['error'] > 0) {
        return ['error' => 'Error al subir el archivo: ' . $archivo['error']];
    }

    // Verificar el tipo de archivo
    if (!in_array($archivo['type'], $tipos_permitidos)) {
        return ['error' => 'Tipo de archivo no permitido'];
    }

    // Crear un nombre único para el archivo
    $nombre_unico = md5(uniqid(rand(), true)) . '_' . $archivo['name'];
    $ruta_destino = $directorio . $nombre_unico;

    // Crear directorio si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }

    // Mover el archivo
    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        return ['nombre' => $nombre_unico, 'ruta' => $ruta_destino];
    } else {
        return ['error' => 'Error al guardar el archivo'];
    }
}

// Subir imagen
function subirImagen($imagen, $directorio = '../uploads/fotos/') {
    return subirArchivo($imagen, $directorio, ['image/jpeg', 'image/png', 'image/gif']);
}

// Sanitizar HTML para prevenir XSS
function sanitizarHTML($html) {
    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}

// Formatear fecha
function formatearFecha($fecha, $formato = 'd-m-Y H:i:s') {
    $timestamp = strtotime($fecha);
    return date($formato, $timestamp);
}

// Obtener todas las ofertas activas
function getActiveOfertas($conn, $limit = null) {
    $sql = "SELECT o.*, e.nombre AS empresa_nombre, c.nombre AS categoria_nombre 
            FROM ofertas o 
            INNER JOIN empresas e ON o.empresa_id = e.id 
            LEFT JOIN categorias c ON o.categoria_id = c.id 
            WHERE o.estado = 'activa' 
            ORDER BY o.fecha_publicacion DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $result = $conn->query($sql);
    $ofertas = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ofertas[] = $row;
        }
    }
    
    return $ofertas;
}

// Obtener una oferta por ID
function getOfertaById($conn, $id) {
    $sql = "SELECT o.*, e.nombre AS empresa_nombre, e.logo AS empresa_logo, e.sitio_web AS empresa_sitio_web, 
            e.descripcion AS empresa_descripcion, c.nombre AS categoria_nombre, c.id AS categoria_id 
            FROM ofertas o 
            INNER JOIN empresas e ON o.empresa_id = e.id 
            LEFT JOIN categorias c ON o.categoria_id = c.id 
            WHERE o.id = ? AND o.estado = 'activa'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Obtener ofertas similares (misma categoría, diferente ID)
function getOfertasSimilares($conn, $categoria_id, $oferta_id, $limit = 4) {
    $sql = "SELECT o.id, o.titulo, o.ubicacion, e.nombre AS empresa_nombre 
            FROM ofertas o 
            INNER JOIN empresas e ON o.empresa_id = e.id 
            WHERE o.categoria_id = ? AND o.id != ? AND o.estado = 'activa' 
            ORDER BY o.fecha_publicacion DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $categoria_id, $oferta_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ofertas = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ofertas[] = $row;
        }
    }
    
    return $ofertas;
}

// Verificar si un candidato ya aplicó a una oferta
function checkAplicacion($conn, $candidato_id, $oferta_id) {
    // Verificar si la tabla aplicaciones existe
    $checkTable = $conn->query("SHOW TABLES LIKE 'aplicaciones'");
    if ($checkTable->num_rows == 0) {
        // Si no existe, crearla
        $conn->query("CREATE TABLE aplicaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            candidato_id INT NOT NULL,
            oferta_id INT NOT NULL,
            fecha_aplicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            estado VARCHAR(50) DEFAULT 'pendiente',
            UNIQUE KEY unique_aplicacion (candidato_id, oferta_id)
        )");
        return false;
    }
    
    $sql = "SELECT * FROM aplicaciones WHERE candidato_id = ? AND oferta_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $candidato_id, $oferta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return ($result && $result->num_rows > 0);
}

// Obtener ofertas filtradas y paginadas
function getOfertasFiltradas($conn, $busqueda = '', $ubicacion = '', $categoria = '', $tipo_contrato = '', $pagina = 1, $por_pagina = 10) {
    $inicio = ($pagina - 1) * $por_pagina;
    
    // Verificar si la tabla categorias existe
    $checkTable = $conn->query("SHOW TABLES LIKE 'categorias'");
    if ($checkTable->num_rows == 0) {
        // Si no existe, devolver un array vacío o manejar el error de otra forma
        return [];
    }
    
    $sql = "SELECT o.*, e.nombre AS empresa_nombre, c.nombre AS categoria_nombre 
            FROM ofertas o 
            INNER JOIN empresas e ON o.empresa_id = e.id 
            LEFT JOIN categorias c ON o.categoria_id = c.id 
            WHERE 1=1";
    
    // Cambiar la condición de estado solo si la columna existe
    $checkColumn = $conn->query("SHOW COLUMNS FROM ofertas LIKE 'estado'");
    if ($checkColumn->num_rows > 0) {
        $sql .= " AND o.estado = 'activa'";
    }
    
    $params = [];
    $types = "";
    
    if (!empty($busqueda)) {
        $sql .= " AND (o.titulo LIKE ? OR o.descripcion LIKE ? OR e.nombre LIKE ?)";
        $busqueda_param = "%" . $busqueda . "%";
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
        $types .= "sss";
    }
    
    // Verificar si la columna ubicacion existe
    $checkColumn = $conn->query("SHOW COLUMNS FROM ofertas LIKE 'ubicacion'");
    if ($checkColumn->num_rows > 0 && !empty($ubicacion)) {
        $sql .= " AND o.ubicacion LIKE ?";
        $ubicacion_param = "%" . $ubicacion . "%";
        $params[] = $ubicacion_param;
        $types .= "s";
    }
    
    if (!empty($categoria)) {
        $sql .= " AND o.categoria_id = ?";
        $params[] = $categoria;
        $types .= "i";
    }
    
    // Verificar si la columna tipo_contrato existe
    $checkColumn = $conn->query("SHOW COLUMNS FROM ofertas LIKE 'tipo_contrato'");
    if ($checkColumn->num_rows > 0 && !empty($tipo_contrato)) {
        $sql .= " AND o.tipo_contrato = ?";
        $params[] = $tipo_contrato;
        $types .= "s";
    }
    
    $sql .= " ORDER BY o.fecha_publicacion DESC LIMIT ?, ?";
    $params[] = $inicio;
    $params[] = $por_pagina;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ofertas = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ofertas[] = $row;
        }
    }
    
    return $ofertas;
}

// Obtener el total de ofertas filtradas (para paginación)
function getTotalOfertasFiltradas($conn, $busqueda = '', $ubicacion = '', $categoria = '', $tipo_contrato = '') {
    // Verificar si la tabla categorias existe
    $checkTable = $conn->query("SHOW TABLES LIKE 'categorias'");
    if ($checkTable->num_rows == 0) {
        // Si no existe, devolver 0 o manejar el error de otra forma
        return 0;
    }
    
    $sql = "SELECT COUNT(*) as total 
            FROM ofertas o 
            INNER JOIN empresas e ON o.empresa_id = e.id 
            WHERE 1=1";
    
    // Cambiar la condición de estado solo si la columna existe
    $checkColumn = $conn->query("SHOW COLUMNS FROM ofertas LIKE 'estado'");
    if ($checkColumn->num_rows > 0) {
        $sql .= " AND o.estado = 'activa'";
    }
    
    $params = [];
    $types = "";
    
    if (!empty($busqueda)) {
        $sql .= " AND (o.titulo LIKE ? OR o.descripcion LIKE ? OR e.nombre LIKE ?)";
        $busqueda_param = "%" . $busqueda . "%";
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
        $types .= "sss";
    }
    
    // Verificar si la columna ubicacion existe
    $checkColumn = $conn->query("SHOW COLUMNS FROM ofertas LIKE 'ubicacion'");
    if ($checkColumn->num_rows > 0 && !empty($ubicacion)) {
        $sql .= " AND o.ubicacion LIKE ?";
        $ubicacion_param = "%" . $ubicacion . "%";
        $params[] = $ubicacion_param;
        $types .= "s";
    }
    
    if (!empty($categoria)) {
        $sql .= " AND o.categoria_id = ?";
        $params[] = $categoria;
        $types .= "i";
    }
    
    // Verificar si la columna tipo_contrato existe
    $checkColumn = $conn->query("SHOW COLUMNS FROM ofertas LIKE 'tipo_contrato'");
    if ($checkColumn->num_rows > 0 && !empty($tipo_contrato)) {
        $sql .= " AND o.tipo_contrato = ?";
        $params[] = $tipo_contrato;
        $types .= "s";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

// Obtener todas las categorías
function getCategorias($conn) {
    // Verificar si la tabla categorias existe
    $checkTable = $conn->query("SHOW TABLES LIKE 'categorias'");
    if ($checkTable->num_rows == 0) {
        // Si no existe, crear la tabla
        $conn->query("CREATE TABLE categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT NULL
        )");
        
        // Insertar algunas categorías predeterminadas
        $conn->query("INSERT INTO categorias (nombre) VALUES 
            ('Informática/IT'),
            ('Administración'),
            ('Marketing'),
            ('Ventas'),
            ('Recursos Humanos')
        ");
    }
    
    $sql = "SELECT * FROM categorias ORDER BY nombre";
    $result = $conn->query($sql);
    $categorias = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
    }
    
    return $categorias;
}

// Sanitizar input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>