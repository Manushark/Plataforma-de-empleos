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

// Obtener información de la empresa
$sql = "SELECT * FROM empresas WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Si no existe la empresa, redirigir a completar perfil
    header('Location: perfil.php?nuevo=1');
    exit();
}

$empresa = $result->fetch_assoc();
$empresa_id = $empresa['id'];

// Obtener ofertas de la empresa
$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM aplicaciones WHERE oferta_id = o.id) as total_aplicaciones 
        FROM ofertas o 
        WHERE o.empresa_id = ? 
        ORDER BY o.fecha_publicacion DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

$ofertas = [];
while ($row = $result->fetch_assoc()) {
    $ofertas[] = $row;
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
                    <a href="index.php" class="list-group-item list-group-item-action active">
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
            <!-- Tarjetas de estadísticas -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Ofertas Activas</h6>
                                <h2 class="mb-0">
                                    <?php 
                                    $activas = 0;
                                    foreach ($ofertas as $oferta) {
                                        if (isset($oferta['estado']) && $oferta['estado'] == 'activa') {
                                            $activas++;
                                        }
                                    }
                                    echo $activas;
                                    ?>
                                </h2>
                            </div>
                            <i class="fas fa-briefcase fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Aplicaciones</h6>
                                <h2 class="mb-0">
                                    <?php 
                                    $total_aplicaciones = 0;
                                    foreach ($ofertas as $oferta) {
                                        $total_aplicaciones += $oferta['total_aplicaciones'];
                                    }
                                    echo $total_aplicaciones;
                                    ?>
                                </h2>
                            </div>
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Días Activos</h6>
                                <h2 class="mb-0">
                                    <?php 
                                    $fecha_registro = isset($empresa['fecha_registro']) ? new DateTime($empresa['fecha_registro']) : new DateTime();
                                    $hoy = new DateTime();
                                    $intervalo = $fecha_registro->diff($hoy);
                                    echo $intervalo->days;
                                    ?>
                                </h2>
                            </div>
                            <i class="fas fa-calendar-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ofertas recientes -->
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mis Ofertas de Empleo</h5>
                    <a href="crear-oferta.php" class="btn btn-light btn-sm">
                        <i class="fas fa-plus-circle me-1"></i> Nueva Oferta
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($ofertas) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Aplicaciones</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ofertas as $oferta): ?>
                                        <tr>
                                            <td>
                                                <a href="../empresas/detalle-oferta.php?id=<?php echo $oferta['id']; ?>" class="text-decoration-none">
                                                    <?php echo $oferta['titulo']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo formatearFecha($oferta['fecha_publicacion'], 'd/m/Y'); ?></td>
                                            <td>
                                                <?php if (isset($oferta['estado'])): ?>
                                                    <?php if ($oferta['estado'] == 'activa'): ?>
                                                        <span class="badge bg-success">Activa</span>
                                                    <?php elseif ($oferta['estado'] == 'inactiva'): ?>
                                                        <span class="badge bg-secondary">Inactiva</span>
                                                    <?php elseif ($oferta['estado'] == 'cerrada'): ?>
                                                        <span class="badge bg-danger">Cerrada</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Activa</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="aplicaciones.php?oferta_id=<?php echo $oferta['id']; ?>" class="text-decoration-none">
                                                    <?php echo $oferta['total_aplicaciones']; ?> aplicaciones
                                                </a>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="editar-oferta.php?id=<?php echo $oferta['id']; ?>" class="btn btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if (isset($oferta['estado']) && $oferta['estado'] == 'activa'): ?>
                                                        <a href="cambiar-estado.php?id=<?php echo $oferta['id']; ?>&estado=inactiva" class="btn btn-warning">
                                                            <i class="fas fa-pause"></i>
                                                        </a>
                                                    <?php elseif (isset($oferta['estado']) && $oferta['estado'] == 'inactiva'): ?>
                                                        <a href="cambiar-estado.php?id=<?php echo $oferta['id']; ?>&estado=activa" class="btn btn-success">
                                                            <i class="fas fa-play"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="eliminar-oferta.php?id=<?php echo $oferta['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar esta oferta?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h5 class="alert-heading">¡Aún no tienes ofertas publicadas!</h5>
                            <p>Comienza a publicar ofertas de empleo para encontrar a los candidatos ideales para tu empresa.</p>
                            <hr>
                            <div class="d-grid gap-2 col-6 mx-auto">
                                <a href="crear-oferta.php" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i> Crear mi primera oferta
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>