<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si está autenticado y es empresa
if (!estaAutenticado() || !esEmpresa()) {
    header('Location: ../login.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$conn = conectarDB();
$mensaje = '';
$error = '';

// Verificar si la empresa existe
$sql = "SELECT * FROM empresas WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Redirigir a completar perfil
    header('Location: perfil.php?nuevo=1');
    exit();
}

$empresa = $result->fetch_assoc();
$empresa_id = $empresa['id'];

// Verificar si se ha proporcionado un ID de oferta válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$oferta_id = (int)$_GET['id'];

// Verificar que la oferta pertenezca a esta empresa
$sql = "SELECT o.*, c.nombre as categoria_nombre 
        FROM ofertas o 
        LEFT JOIN categorias c ON o.categoria_id = c.id 
        WHERE o.id = ? AND o.empresa_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $oferta_id, $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: index.php');
    exit();
}

$oferta = $result->fetch_assoc();

// Obtener el número de aplicaciones para esta oferta
$sql = "SELECT COUNT(*) as total FROM aplicaciones WHERE oferta_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $oferta_id);
$stmt->execute();
$result = $stmt->get_result();
$aplicaciones = $result->fetch_assoc();
$num_aplicaciones = $aplicaciones['total'];

// Si viene de crear, mostrar mensaje
if (isset($_GET['creado']) && $_GET['creado'] == 1) {
    $mensaje = 'La oferta ha sido creada exitosamente.';
}

// Si viene de actualizar, mostrar mensaje
if (isset($_GET['actualizado']) && $_GET['actualizado'] == 1) {
    $mensaje = 'La oferta ha sido actualizada exitosamente.';
}

// Procesar cambio de estado si se solicita
if (isset($_GET['accion']) && in_array($_GET['accion'], ['activar', 'pausar', 'finalizar'])) {
    $nuevo_estado = '';
    
    switch ($_GET['accion']) {
        case 'activar':
            $nuevo_estado = 'activa';
            break;
        case 'pausar':
            $nuevo_estado = 'pausada';
            break;
        case 'finalizar':
            $nuevo_estado = 'finalizada';
            break;
    }
    
    if (!empty($nuevo_estado)) {
        $sql = "UPDATE ofertas SET estado = ? WHERE id = ? AND empresa_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $nuevo_estado, $oferta_id, $empresa_id);
        
        if ($stmt->execute()) {
            $mensaje = 'El estado de la oferta ha sido actualizado a ' . $nuevo_estado . '.';
            
            // Actualizar el objeto oferta
            $oferta['estado'] = $nuevo_estado;
        } else {
            $error = 'Error al actualizar el estado de la oferta.';
        }
    }
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Menú lateral -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Panel de Empresa</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="perfil.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Mi Perfil
                    </a>
                    <a href="crear-oferta.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2"></i> Nueva Oferta
                    </a>
                    <a href="aplicaciones.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Aplicaciones
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="col-lg-9">
            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h4 class="mb-0">Detalles de la Oferta</h4>
                    <div>
                        <a href="editar-oferta.php?id=<?php echo $oferta_id; ?>" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                        <a href="eliminar-oferta.php?id=<?php echo $oferta_id; ?>" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash-alt me-1"></i> Eliminar
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0"><?php echo htmlspecialchars($oferta['titulo']); ?></h2>
                        <span class="badge <?php echo $oferta['estado'] == 'activa' ? 'bg-success' : ($oferta['estado'] == 'pausada' ? 'bg-warning' : 'bg-secondary'); ?> fs-6">
                            <?php echo ucfirst($oferta['estado']); ?>
                        </span>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><i class="fas fa-map-marker-alt me-2 text-muted"></i> <strong>Ubicación:</strong> <?php echo htmlspecialchars($oferta['ubicacion']); ?></p>
                            <p class="mb-1"><i class="fas fa-briefcase me-2 text-muted"></i> <strong>Tipo:</strong> <?php echo htmlspecialchars($oferta['tipo_contrato']); ?></p>
                            <p class="mb-1"><i class="fas fa-tag me-2 text-muted"></i> <strong>Categoría:</strong> <?php echo htmlspecialchars($oferta['categoria_nombre']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><i class="fas fa-calendar-alt me-2 text-muted"></i> <strong>Publicada:</strong> <?php echo date('d/m/Y', strtotime($oferta['fecha_publicacion'])); ?></p>
                            <p class="mb-1"><i class="fas fa-money-bill-wave me-2 text-muted"></i> <strong>Salario:</strong> <?php echo !empty($oferta['salario']) ? htmlspecialchars($oferta['salario']) : 'No especificado'; ?></p>
                            <p class="mb-1"><i class="fas fa-users me-2 text-muted"></i> <strong>Aplicaciones:</strong> <?php echo $num_aplicaciones; ?></p>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Descripción</h5>
                        </div>
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($oferta['descripcion'])); ?>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Requisitos</h5>
                        </div>
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($oferta['requisitos'])); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($oferta['beneficios'])): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Beneficios</h5>
                        </div>
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($oferta['beneficios'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                            <?php if ($num_aplicaciones > 0): ?>
                            <a href="aplicaciones.php?oferta_id=<?php echo $oferta_id; ?>" class="btn btn-outline-primary ms-2">
                                <i class="fas fa-users me-1"></i> Ver <?php echo $num_aplicaciones; ?> aplicaciones
                            </a>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($oferta['estado'] != 'activa'): ?>
                            <a href="detalle-oferta.php?id=<?php echo $oferta_id; ?>&accion=activar" class="btn btn-success">
                                <i class="fas fa-check me-1"></i> Activar
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($oferta['estado'] != 'pausada'): ?>
                            <a href="detalle-oferta.php?id=<?php echo $oferta_id; ?>&accion=pausar" class="btn btn-warning text-dark">
                                <i class="fas fa-pause me-1"></i> Pausar
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($oferta['estado'] != 'finalizada'): ?>
                            <a href="detalle-oferta.php?id=<?php echo $oferta_id; ?>&accion=finalizar" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Finalizar
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir el pie de página
include_once '../includes/footer.php';
$conn->close();
?>