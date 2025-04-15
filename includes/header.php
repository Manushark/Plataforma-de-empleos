<?php
require_once __DIR__ . '/functions.php';
iniciarSesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EmpleosDirect - Tu portal de empleo</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="/plataforma-de-empleos/assets/css/styles.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="/plataforma-de-empleos/index.php">
                    <i class="fas fa-briefcase me-2"></i>
                    EmpleosDirect
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/plataforma-de-empleos/index.php">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/plataforma-de-empleos/ofertas.php">Ofertas de Empleo</a>
                        </li>
                        <?php if (esCandidato()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/plataforma-de-empleos/candidatos/formulario_CV.php">Mi CV</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/plataforma-de-empleos/candidatos/mis_aplicaciones.php">Mis Aplicaciones</a>
                            </li>
                        <?php elseif (esEmpresa()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/plataforma-de-empleos/empresas/mis_ofertas.php">Mis Ofertas</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/plataforma-de-empleos/empresas/nueva_oferta.php">Publicar Oferta</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <?php if (estaAutenticado()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-1"></i>
                                    <?= sanitizarHTML($_SESSION['email']) ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <?php if (esCandidato()): ?>
                                        <li><a class="dropdown-item" href="/plataforma-de-empleos/candidatos/perfil.php">Mi Perfil</a></li>
                                    <?php elseif (esEmpresa()): ?>
                                        <li><a class="dropdown-item" href="/plataforma-de-empleos/empresas/perfil.php">Perfil de Empresa</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/plataforma-de-empleos/logout.php">Cerrar Sesión</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/plataforma-de-empleos/login.php">Iniciar Sesión</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/plataforma-de-empleos/registro.php">Registrarse</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mt-4">
        <?php if (hayNotificaciones()): 
            $notificacion = obtenerNotificaciones(); ?>
            <div class="alert alert-<?= $notificacion['tipo'] ?> alert-dismissible fade show" role="alert">
                <?= $notificacion['mensaje'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>