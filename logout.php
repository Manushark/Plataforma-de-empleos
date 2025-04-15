<?php
require_once 'includes/functions.php';

iniciarSesion();

// Cerrar sesión
session_unset();
session_destroy();

// Redireccionar a login
header('Location: login.php');
exit;
?>