<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Obtener las últimas ofertas de empleo
function obtenerUltimasOfertas($limit = 6) {
    $conexion = conectarDB();
    $query = "SELECT o.*, e.nombre as empresa_nombre, e.logo as empresa_logo
              FROM ofertas o
              JOIN empresas e ON o.empresa_id = e.id
              WHERE o.estado = 'activa'
              ORDER BY o.fecha_publicacion DESC
              LIMIT ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $ofertas = [];
    while ($oferta = $resultado->fetch_assoc()) {
        $ofertas[] = $oferta;
    }
    
    $stmt->close();
    $conexion->close();
    
    return $ofertas;
}

// Obtener estadísticas
function obtenerEstadisticas() {
    $conexion = conectarDB();
    
    // Total de ofertas activas
    $query1 = "SELECT COUNT(*) as total FROM ofertas WHERE estado = 'activa'";
    $resultado1 = $conexion->query($query1);
    $totalOfertas = $resultado1->fetch_assoc()['total'];
    
    // Total de empresas
    $query2 = "SELECT COUNT(*) as total FROM empresas";
    $resultado2 = $conexion->query($query2);
    $totalEmpresas = $resultado2->fetch_assoc()['total'];
    
    // Total de candidatos
    $query3 = "SELECT COUNT(*) as total FROM candidatos";
    $resultado3 = $conexion->query($query3);
    $totalCandidatos = $resultado3->fetch_assoc()['total'];
    
    $conexion->close();
    
    return [
        'ofertas' => $totalOfertas,
        'empresas' => $totalEmpresas,
        'candidatos' => $totalCandidatos
    ];
}

$ultimasOfertas = obtenerUltimasOfertas();
$estadisticas = obtenerEstadisticas();

// Incluir la cabecera
include 'includes/header.php';
?>

<section class="hero py-5 bg-primary text-white text-center">
    <div class="container">
        <h1 class="display-4">Encuentra tu trabajo ideal</h1>
        <p class="lead">Conectamos empresas y profesionales para crear oportunidades laborales perfectas</p>
        <div class="mt-4">
            <?php if (estaAutenticado()): ?>
                <?php if (esCandidato()): ?>
                    <a href="ofertas.php" class="btn btn-light btn-lg me-2">Explorar Ofertas</a>
                <?php elseif (esEmpresa()): ?>
                    <a href="empresas/nueva_oferta.php" class="btn btn-light btn-lg me-2">Publicar Oferta</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="registro.php?tipo=candidato" class="btn btn-light btn-lg me-2">Soy Candidato</a>
                <a href="registro.php?tipo=empresa" class="btn btn-outline-light btn-lg">Soy Empresa</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Últimas Ofertas de Empleo</h2>
        
        <div class="row">
            <?php if (empty($ultimasOfertas)): ?>
                <div class="col-12 text-center">
                    <p>No hay ofertas disponibles actualmente.</p>
                </div>
            <?php else: ?>
                <?php foreach ($ultimasOfertas as $oferta): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card job-card h-100 fade-in">
                            <div class="card-header d-flex align-items-center">
                                <?php if (!empty($oferta['empresa_logo'])): ?>
                                    <img src="/empleos_web/uploads/logos/<?= $oferta['empresa_logo'] ?>" alt="Logo" class="company-logo me-3">
                                <?php else: ?>
                                    <div class="company-logo me-3 d-flex align-items-center justify-content-center bg-light">
                                        <i class="fas fa-building fa-2x text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                                <h5 class="card-title mb-0"><?= sanitizarHTML($oferta['titulo']) ?></h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted"><?= sanitizarHTML($oferta['empresa_nombre']) ?></h6>
                                <p class="card-text">
                                    <?= substr(sanitizarHTML($oferta['descripcion']), 0, 150) . '...' ?>
                                    <?= substr(sanitizarHTML($oferta['descripcion']), 0, 150) . '...' ?>
                                </p>
                                <div class="text-muted small mb-2">
                                    <i class="far fa-calendar-alt me-1"></i> 
                                    Publicado: <?= formatearFecha($oferta['fecha_publicacion'], 'd/m/Y') ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="oferta_detalle.php?id=<?= $oferta['id'] ?>" class="btn btn-outline-primary btn-sm w-100">Ver detalles</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="ofertas.php" class="btn btn-primary">Ver todas las ofertas</a>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="mb-4">Nuestra Plataforma en Números</h2>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <i class="fas fa-briefcase fa-3x text-primary mb-3"></i>
                        <h3 class="counter"><?= $estadisticas['ofertas'] ?></h3>
                        <p class="text-muted">Ofertas Activas</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <i class="fas fa-building fa-3x text-primary mb-3"></i>
                        <h3 class="counter"><?= $estadisticas['empresas'] ?></h3>
                        <p class="text-muted">Empresas Registradas</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h3 class="counter"><?= $estadisticas['candidatos'] ?></h3>
                        <p class="text-muted">Profesionales</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">¿Cómo Funciona?</h2>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-user-plus fa-2x"></i>
                        </div>
                        <h4>Regístrate</h4>
                        <p>Crea tu cuenta como candidato o empresa y completa tu perfil con información relevante.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                            <?php if (esCandidato()): ?>
                                <i class="fas fa-search fa-2x"></i>
                            <?php else: ?>
                                <i class="fas fa-file-alt fa-2x"></i>
                            <?php endif; ?>
                        </div>
                        <h4><?= esCandidato() ? 'Explora Ofertas' : 'Publica Ofertas' ?></h4>
                        <p>
                            <?php if (esCandidato()): ?>
                                Busca entre las ofertas disponibles aquellas que coincidan con tu perfil profesional.
                            <?php else: ?>
                                Crea anuncios de trabajo detallados para atraer a los mejores profesionales.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                            <?php if (esCandidato()): ?>
                                <i class="fas fa-paper-plane fa-2x"></i>
                            <?php else: ?>
                                <i class="fas fa-check-circle fa-2x"></i>
                            <?php endif; ?>
                        </div>
                        <h4><?= esCandidato() ? 'Postúlate' : 'Selecciona Candidatos' ?></h4>
                        <p>
                            <?php if (esCandidato()): ?>
                                Envía tu currículum a las empresas y sigue el estado de tus aplicaciones.
                            <?php else: ?>
                                Revisa los perfiles de los candidatos y selecciona a los mejores para tu empresa.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>