<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar que sea un candidato
requiereCandidato();

// Obtener datos del candidato
function obtenerDatosCandidato($usuario_id) {
    $conexion = conectarDB();
    $query = "SELECT * FROM candidatos WHERE usuario_id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $candidato = $resultado->fetch_assoc();
    $stmt->close();
    $conexion->close();
    return $candidato;
}

// Obtener aplicaciones recientes
function obtenerAplicacionesRecientes($candidato_id, $limit = 5) {
    $conexion = conectarDB();
    $query = "SELECT a.*, o.titulo, e.nombre as empresa_nombre 
              FROM aplicaciones a
              JOIN ofertas o ON a.oferta_id = o.id
              JOIN empresas e ON o.empresa_id = e.id
              WHERE a.candidato_id = ?
              ORDER BY a.fecha_aplicacion DESC
              LIMIT ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $candidato_id, $limit);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $aplicaciones = [];
    while ($aplicacion = $resultado->fetch_assoc()) {
        $aplicaciones[] = $aplicacion;
    }
    
    $stmt->close();
    $conexion->close();
    
    return $aplicaciones;
}

// Obtener ofertas recomendadas
function obtenerOfertasRecomendadas($limit = 5) {
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

$candidato = obtenerDatosCandidato($_SESSION['usuario_id']);
$aplicaciones = obtenerAplicacionesRecientes($candidato['id']);
$ofertas_recomendadas = obtenerOfertasRecomendadas();

// Incluir la cabecera
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card profile-header mb-4">
                <div class="card-body text-center">
                    <?php if (!empty($candidato['foto'])): ?>
                        <img src="/plataforma-de-empleos/uploads/fotos/<?= $candidato['foto'] ?>" alt="Foto de perfil" class="profile-photo mb-3">
                    <?php else: ?>
                        <div class="profile-photo mb-3 d-flex align-items-center justify-content-center bg-light">
                            <i class="fas fa-user fa-4x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                    <h4><?= sanitizarHTML($candidato['nombre'] . ' ' . $candidato['apellidos']) ?></h4>
                    <p class="text-muted"><?= !empty($candidato['titulo_profesional']) ? sanitizarHTML($candidato['titulo_profesional']) : 'Completa tu perfil' ?></p>
                    
                    <div class="mt-3">
                        <a href="formulario_CV.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-1"></i> Editar Perfil
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Resumen del Perfil</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Estado del CV
                            <?php if (!empty($candidato['cv_pdf']) || !empty($candidato['experiencia_laboral'])): ?>
                                <span class="badge bg-success">Completo</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Incompleto</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Aplicaciones enviadas
                            <span class="badge bg-primary rounded-pill"><?= count($aplicaciones) ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Aplicaciones Recientes</h5>
                    <a href="mis_aplicaciones.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                </div>
                <div class="card-body">
                    <?php if (empty($aplicaciones)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                            <p class="lead">Aún no has aplicado a ninguna oferta</p>
                            <a href="../ofertas.php" class="btn btn-primary">Explorar ofertas</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($aplicaciones as $aplicacion): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?= sanitizarHTML($aplicacion['titulo']) ?></h5>
                                        <small class="text-muted">
                                            <?= formatearFecha($aplicacion['fecha_aplicacion'], 'd/m/Y') ?>
                                        </small>
                                    </div>
                                    <p class="mb-1"><?= sanitizarHTML($aplicacion['empresa_nombre']) ?></p>
                                    <small class="text-muted">
                                        Estado: 
                                        <?php if($aplicacion['estado'] == 'pendiente'): ?>
                                            <span class="badge bg-warning">Pendiente</span>
                                        <?php elseif($aplicacion['estado'] == 'revisado'): ?>
                                            <span class="badge bg-info">Revisado</span>
                                        <?php elseif($aplicacion['estado'] == 'entrevista'): ?>
                                            <span class="badge bg-primary">Entrevista</span>
                                        <?php elseif($aplicacion['estado'] == 'rechazado'): ?>
                                            <span class="badge bg-danger">No seleccionado</span>
                                        <?php elseif($aplicacion['estado'] == 'seleccionado'): ?>
                                            <span class="badge bg-success">Seleccionado</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ofertas Recomendadas</h5>
                    <a href="../ofertas.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                </div>
                <div class="card-body">
                    <?php if (empty($ofertas_recomendadas)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <p>No hay ofertas disponibles actualmente</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach($ofertas_recomendadas as $oferta): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card job-card h-100">
                                        <div class="card-header d-flex align-items-center">
                                            <?php if (!empty($oferta['empresa_logo'])): ?>
                                                <img src="/plataforma-de-empleos/uploads/logos/<?= $oferta['empresa_logo'] ?>" alt="Logo" class="company-logo me-3">
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
                                                <?= substr(sanitizarHTML($oferta['descripcion']), 0, 100) . '...' ?>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <a href="/plataforma-de-empleos/ofertas/detalle.php?id=<?= $oferta['id'] ?>" class="btn btn-outline-primary btn-sm w-100">Ver detalles</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>