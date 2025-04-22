<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si está autenticado
if (!estaAutenticado()) {
    header('Location: ../login.php');
    exit();
}

// Obtener ID de la oferta
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit();
}

$oferta_id = (int)$_GET['id'];
$conn = conectarDB();
$usuario_id = $_SESSION['usuario_id'];
$es_candidato = esCandidato();

// Obtener detalles de la oferta
$sql = "SELECT o.*, e.nombre as empresa_nombre, e.logo as empresa_logo, e.descripcion as empresa_descripcion, 
        c.nombre as categoria_nombre 
        FROM ofertas o 
        INNER JOIN empresas e ON o.empresa_id = e.id 
        LEFT JOIN categorias c ON o.categoria_id = c.id 
        WHERE o.id = ? AND o.estado = 'activa'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $oferta_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Oferta no encontrada o no activa
    header('Location: ../index.php?error=oferta_no_disponible');
    exit();
}

$oferta = $result->fetch_assoc();

// Verificar si el usuario ya ha aplicado a esta oferta
$ha_aplicado = false;
if ($es_candidato) {
    // Obtener ID del candidato
    $sql = "SELECT id FROM candidatos WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $candidato = $result->fetch_assoc();
        $candidato_id = $candidato['id'];
        
        // Verificar si ya aplicó
        $sql = "SELECT id FROM aplicaciones WHERE candidato_id = ? AND oferta_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $candidato_id, $oferta_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $ha_aplicado = ($result->num_rows > 0);
    }
}

// Verificar si hay un mensaje de aplicación exitosa
$aplicacion_exitosa = isset($_GET['aplicado']) && $_GET['aplicado'] == '1';

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="../ofertas.php">Ofertas de empleo</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($oferta['titulo']); ?></li>
        </ol>
    </nav>
    
    <?php if ($aplicacion_exitosa): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> Has aplicado exitosamente a esta oferta. La empresa se pondrá en contacto contigo si tu perfil se ajusta a lo que buscan.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Información principal de la oferta -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h2 mb-2"><?php echo htmlspecialchars($oferta['titulo']); ?></h1>
                    <div class="d-flex align-items-center mb-4">
                        <?php if (!empty($oferta['empresa_logo'])): ?>
                            <img src="../uploads/logos/<?php echo htmlspecialchars($oferta['empresa_logo']); ?>" alt="<?php echo htmlspecialchars($oferta['empresa_nombre']); ?>" class="me-3" style="width: 60px; height: 60px; object-fit: contain;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-building text-secondary" style="font-size: 1.5rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="mb-0"><?php echo htmlspecialchars($oferta['empresa_nombre']); ?></h4>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt me-1"></i> 
                                <?php echo htmlspecialchars($oferta['ubicacion']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <div class="d-flex">
                                    <div class="me-2">
                                        <i class="fas fa-briefcase text-primary"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0">Tipo de contrato</p>
                                        <p class="mb-0"><strong><?php echo htmlspecialchars($oferta['tipo_contrato']); ?></strong></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-2">
                                <div class="d-flex">
                                    <div class="me-2">
                                        <i class="fas fa-coins text-primary"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0">Salario</p>
                                        <p class="mb-0"><strong><?php echo !empty($oferta['salario']) ? htmlspecialchars($oferta['salario']) : 'No especificado'; ?></strong></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-2">
                                <div class="d-flex">
                                    <div class="me-2">
                                        <i class="fas fa-tag text-primary"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0">Categoría</p>
                                        <p class="mb-0"><strong><?php echo htmlspecialchars($oferta['categoria_nombre']); ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Descripción del puesto</h5>
                        <div class="formatted-content">
                            <?php echo nl2br(htmlspecialchars($oferta['descripcion'])); ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Requisitos</h5>
                        <div class="formatted-content">
                            <?php echo nl2br(htmlspecialchars($oferta['requisitos'])); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($oferta['beneficios'])): ?>
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Beneficios</h5>
                        <div class="formatted-content">
                            <?php echo nl2br(htmlspecialchars($oferta['beneficios'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <p class="text-muted mt-4">
                        <small>
                            <i class="far fa-calendar-alt me-1"></i> 
                            Publicado el <?php echo date('d/m/Y', strtotime($oferta['fecha_publicacion'])); ?>
                        </small>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Panel lateral con información y acciones -->
        <div class="col-lg-4">
            <!-- Acciones para candidatos -->
            <?php if ($es_candidato): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">¿Te interesa este empleo?</h5>
                    
                    <?php if ($ha_aplicado): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i> Ya has aplicado a esta oferta.
                        </div>
                    <?php else: ?>
                        <p>Si cumples con los requisitos, ¡no dudes en aplicar!</p>
                        <a href="../candidatos/aplicar.php?oferta=<?php echo $oferta_id; ?>" class="btn btn-primary d-block">
                            <i class="fas fa-paper-plane me-2"></i> Aplicar ahora
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Información de la empresa -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Acerca de la empresa</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php if (!empty($oferta['empresa_logo'])): ?>
                            <img src="../uploads/logos/<?php echo htmlspecialchars($oferta['empresa_logo']); ?>" alt="<?php echo htmlspecialchars($oferta['empresa_nombre']); ?>" class="img-fluid mb-3" style="max-height: 100px;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px;">
                                <i class="fas fa-building text-secondary" style="font-size: 2.5rem;"></i>
                            </div>
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($oferta['empresa_nombre']); ?></h5>
                    </div>
                    
                    <?php if (!empty($oferta['empresa_descripcion'])): ?>
                    <div class="mb-3">
                        <p><?php echo nl2br(htmlspecialchars($oferta['empresa_descripcion'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <a href="../empresas/perfil-publico.php?id=<?php echo $oferta['empresa_id']; ?>" class="btn btn-outline-primary btn-sm d-block">
                        <i class="fas fa-building me-1"></i> Ver perfil de la empresa
                    </a>
                </div>
            </div>
            
            <!-- Compartir en redes sociales -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Compartir oferta</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">¿Conoces a alguien que podría estar interesado en esta oferta?</p>
                    <div class="d-flex justify-content-around">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode('¡Mira esta oferta de trabajo: ' . $oferta['titulo']); ?>" target="_blank" class="btn btn-outline-info btn-sm">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('¡Mira esta oferta de trabajo: ' . $oferta['titulo'] . ' ' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-success btn-sm">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ofertas similares -->
    <?php
    // Buscar ofertas similares (misma categoría, excluyendo la actual)
    $sql = "SELECT o.id, o.titulo, o.ubicacion, o.tipo_contrato, o.salario, o.fecha_publicacion, e.nombre as empresa_nombre 
            FROM ofertas o 
            INNER JOIN empresas e ON o.empresa_id = e.id 
            WHERE o.categoria_id = ? AND o.id != ? AND o.estado = 'activa' 
            ORDER BY o.fecha_publicacion DESC 
            LIMIT 3";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $oferta['categoria_id'], $oferta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0):
    ?>
    <div class="mt-5">
        <h4 class="mb-4">Ofertas similares</h4>
        <div class="row">
            <?php while($similar = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($similar['titulo']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($similar['empresa_nombre']); ?></h6>
                        <p class="card-text">
                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($similar['ubicacion']); ?><br>
                            <i class="fas fa-briefcase me-1"></i> <?php echo htmlspecialchars($similar['tipo_contrato']); ?><br>
                            <?php if (!empty($similar['salario'])): ?>
                            <i class="fas fa-coins me-1"></i> <?php echo htmlspecialchars($similar['salario']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="detalle.php?id=<?php echo $similar['id']; ?>" class="btn btn-sm btn-outline-primary d-block">Ver detalles</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Incluir el pie de página
include_once '../includes/footer.php';
$conn->close();
?>