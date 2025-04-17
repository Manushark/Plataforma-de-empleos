<?php
function requireCandidato() {
    // Verificar si hay una sesión activa
    session_start();
    
    // Verificar si el usuario está logueado como candidato
    if (!isset($_SESSION['candidato_id'])) {
        // Si no hay sesión de candidato, redirigir al login
        header('Location: login.php');
        exit;
    }
    
    // Si hay sesión, podemos continuar y devolver el ID del candidato
    return $_SESSION['candidato_id'];
}
?>