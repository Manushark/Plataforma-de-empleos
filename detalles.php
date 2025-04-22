<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si se proporcionó un ID de oferta
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ../ofertas.php');
    exit();
}

$oferta_id = (int)$_GET['id'];
$conn = conectarDB();

// Obtener los detalles de la oferta
$oferta = getOfertaById($conn, $oferta_id);

// Si la oferta no existe, redirigir
if (!$oferta) {
    header('Location: ../ofertas.php');
    exit();
}

// Verificar si el usuario ha aplicado a esta oferta (si está logueado como candidato)
$ya_aplico = false;
if (estaAutenticado() && esCandidato()) {
    $user_id = $_SESSION['usuario_id'];
    $ya_aplico = checkAplicacion($conn, $user_id, $oferta_id);
}

// Obtener ofertas similares
$ofertas_similares = getOfertasSimilares($conn, $oferta['categoria_id'], $oferta_id, 4);

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <!-- Detalles de la oferta -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><?php echo $oferta['titulo']; ?></h3>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h4 class="mb-3"><?php echo $oferta['empresa_nombre']; ?></h4>
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <?php echo $oferta['ubicacion']; ?>
                            </span>
                            <span class="d-flex align-items-center">
                                <i class="fas fa-briefcase text-primary me-2"></i>
                                <?php echo $oferta['tipo_contrato']; ?>
                            </span>
                            <?php if (!empty($oferta['salario'])): ?>
                                <span class="d-flex align-items-center">
                                    <i class="fas fa-money-bill-wave text-primary me-2"></i>
                                    <?php echo $oferta['salario']; ?>
                                </span>
                            <?php endif; ?>
                            <span class="d-flex align-items-center">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                Publicada: <?php echo formatearFecha($oferta['fecha_publicacion'], 'd/m/Y'); ?>
                            </span>
                            <span class="badge bg-info"><?php echo $oferta['categoria_nombre']; ?></span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Descripción del puesto</h5>
                        <div class="mb-3">
                            <?php echo nl2br($oferta['descripcion']); ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Requisitos</h5>
                        <div class="mb-3">
                            <?php echo nl2br($oferta['requisitos']); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($oferta['beneficios'])): ?>
                    <div class="mb-4">
                        <h5>Beneficios</h5>
                        <div class="mb-3">
                            <?php echo nl2br($oferta['beneficios']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 col-md-6 mx-auto">
                        <?php if (estaAutenticado() && esCandidato()): ?>
                            <?php if ($ya_aplico): ?>
                                <button class="btn btn-secondary btn-lg" disabled>Ya has aplicado</button>
                            <?php else: ?>
                                <a href="../candidatos/aplicar.php?oferta_id=<?php echo $oferta_id; ?>" class="btn btn-primary btn-lg">Aplicar a esta oferta</a>
                            <?php endif; ?>
                        <?php elseif (estaAutenticado() && esEmpresa()): ?>
                            <p class="text-center text-muted">Debes iniciar sesión como candidato para aplicar a ofertas.</p>
                        <?php else: ?>
                            <p class="text-center text-muted">Debes iniciar sesión para aplicar a esta oferta.</p>
                            <a href="../candidatos/login.php?redirect=ofertas/detalle.php?id=<?php echo $oferta_id; ?>" class="btn btn-primary">Iniciar Sesión</a>
                            <a href="../candidatos/registro.php" class="btn btn-outline-primary">Registrarse</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Información de la empresa -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Acerca de la empresa</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <?php if (!empty($oferta['empresa_logo'])): ?>
                            <img src="../uploads/logos/<?php echo $oferta['empresa_logo']; ?>" alt="<?php echo $oferta['empresa_nombre']; ?>" class="me-3" style="width: 100px; height: auto;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center me-3" style="width: 100px; height: 100px;">
                                <i class="fas fa-building fa-3x text-secondary"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h5 class="mb-1"><?php echo $oferta['empresa_nombre']; ?></h5>
                            <?php if (!empty($oferta['empresa_sitio_web'])): ?>
                                <a href="<?php echo $oferta['empresa_sitio_web']; ?>" target="_blank" class="text-decoration-none">
                                    <i class="fas fa-globe me-1"></i> Sitio web
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($oferta['empresa_descripcion'])): ?>
                        <p><?php echo nl2br($oferta['empresa_descripcion']); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No hay información disponible sobre esta empresa.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Compartir oferta -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Compartir oferta</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode('Oferta de trabajo: ' . $oferta['titulo']); ?>" target="_blank" class="btn btn-outline-info">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-secondary">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="mailto:?subject=<?php echo urlencode('Oferta de trabajo: ' . $oferta['titulo']); ?>&body=<?php echo urlencode('He encontrado esta oferta de trabajo que podría interesarte: ' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-success">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Ofertas relacionadas -->
            <?php if (!empty($ofertas_similares)): ?>
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Ofertas similares</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($ofertas_similares as $similar): ?>
                            <a href="detalle.php?id=<?php echo $similar['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo $similar['titulo']; ?></h6>
                                </div>
                                <p class="mb-1"><?php echo $similar['empresa_nombre']; ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo $similar['ubicacion']; ?>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Volver a todas las ofertas -->
            <div class="d-grid gap-2">
                <a href="../ofertas.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i> Ver todas las ofertas
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>