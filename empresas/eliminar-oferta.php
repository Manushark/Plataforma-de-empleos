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
$sql = "SELECT * FROM ofertas WHERE id = ? AND empresa_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $oferta_id, $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: index.php');
    exit();
}

// Si está confirmando la eliminación
if (isset($_GET['confirmar']) && $_GET['confirmar'] == 1) {
    // Verificar si hay aplicaciones asociadas y eliminarlas primero
    $sql = "DELETE FROM aplicaciones WHERE oferta_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $oferta_id);
    $stmt->execute();
    
    // Eliminar la oferta
    $sql = "DELETE FROM ofertas WHERE id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $oferta_id, $empresa_id);
    
    if ($stmt->execute()) {
        // Redirigir a la página de ofertas con mensaje de éxito
        header("Location: index.php?eliminado=1");
        exit();
    } else {
        // Redirigir con mensaje de error
        header("Location: index.php?error_eliminar=1");
        exit();
    }
} else {
    // Incluir el encabezado
    include_once '../includes/header.php';
    $oferta = $result->fetch_assoc();
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Confirmar eliminación</h4>
                </div>
                <div class="card-body">
                    <p class="lead">¿Estás seguro de que deseas eliminar la oferta <strong>"<?php echo htmlspecialchars($oferta['titulo']); ?>"</strong>?</p>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> Esta acción no se puede deshacer. Se eliminarán también todas las aplicaciones asociadas.
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Detalles de la oferta</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Título:</strong> <?php echo htmlspecialchars($oferta['titulo']); ?></p>
                            <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($oferta['ubicacion']); ?></p>
                            <p><strong>Tipo de contrato:</strong> <?php echo htmlspecialchars($oferta['tipo_contrato']); ?></p>
                            <p><strong>Estado:</strong> 
                                <span class="badge <?php echo $oferta['estado'] == 'activa' ? 'bg-success' : ($oferta['estado'] == 'pausada' ? 'bg-warning' : 'bg-secondary'); ?>">
                                    <?php echo ucfirst($oferta['estado']); ?>
                                </span>
                            </p>
                            <p><strong>Fecha de publicación:</strong> <?php echo date('d/m/Y', strtotime($oferta['fecha_publicacion'])); ?></p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Cancelar
                        </a>
                        <a href="eliminar-oferta.php?id=<?php echo $oferta_id; ?>&confirmar=1" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Sí, eliminar oferta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    // Incluir el pie de página
    include_once '../includes/footer.php';
}

$conn->close();
?>