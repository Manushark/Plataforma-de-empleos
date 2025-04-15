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
        header('Location: /empleos_web/login.php');
        exit;
    }
}

// Redirigir si no es candidato
function requiereCandidato() {
    requiereAutenticacion();
    if (!esCandidato()) {
        header('Location: /empleos_web/index.php');
        exit;
    }
}

// Redirigir si no es empresa
function requiereEmpresa() {
    requiereAutenticacion();
    if (!esEmpresa()) {
        header('Location: /empleos_web/index.php');
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
?>