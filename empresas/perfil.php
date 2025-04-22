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
$es_nuevo = isset($_GET['nuevo']) && $_GET['nuevo'] == 1;

// Obtener información de la empresa
$sql = "SELECT * FROM empresas WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $empresa = $result->fetch_assoc();
} else {
    // Si no existe la empresa, crear un registro en blanco
    $sql = "INSERT INTO empresas (usuario_id, nombre) VALUES (?, 'Nueva Empresa')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    
    // Obtener el nuevo registro
    $sql = "SELECT * FROM empresas WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $empresa = $result->fetch_assoc();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitizeInput($_POST['nombre']);
    $direccion = isset($_POST['direccion']) ? sanitizeInput($_POST['direccion']) : null;
    $telefono = isset($_POST['telefono']) ? sanitizeInput($_POST['telefono']) : null;
    $descripcion = isset($_POST['descripcion']) ? sanitizeInput($_POST['descripcion']) : null;
    $sitio_web = isset($_POST['sitio_web']) ? sanitizeInput($_POST['sitio_web']) : null;
    
    // Validar campos obligatorios
    if (empty($nombre)) {
        $error = 'El nombre de la empresa es obligatorio.';
    } else {
        // Procesar el logo si se subió uno nuevo
        $logo = $empresa['logo']; // Mantener el logo actual por defecto
        
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/logos/';
            
            // Crear directorio si no existe
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . $_FILES['logo']['name'];
            $upload_file = $upload_dir . $file_name;
            
            // Verificar si es una imagen válida
            $file_type = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));
            if ($file_type == 'jpg' || $file_type == 'jpeg' || $file_type == 'png' || $file_type == 'gif') {
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_file)) {
                    // Si había un logo anterior, eliminarlo
                    if (!empty($empresa['logo']) && file_exists($upload_dir . $empresa['logo'])) {
                        unlink($upload_dir . $empresa['logo']);
                    }
                    $logo = $file_name;
                } else {
                    $error = 'Hubo un error al subir la imagen.';
                }
            } else {
                $error = 'Solo se permiten archivos JPG, JPEG, PNG y GIF.';
            }
        }
        
        if (empty($error)) {
            // Actualizar datos de la empresa
            $sql = "UPDATE empresas SET nombre = ?, direccion = ?, telefono = ?, descripcion = ?, logo = ?, sitio_web = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $nombre, $direccion, $telefono, $descripcion, $logo, $sitio_web, $empresa['id']);
            
            if ($stmt->execute()) {
                $mensaje = 'Perfil actualizado correctamente.';
                
                // Actualizar la información de empresa en la sesión
                if ($es_nuevo) {
                    header('Location: index.php');
                    exit();
                } else {
                    // Refrescar los datos de la empresa
                    $sql = "SELECT * FROM empresas WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $empresa['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $empresa = $result->fetch_assoc();
                }
            } else {
                $error = 'Error al actualizar el perfil: ' . $conn->error;
            }
        }
    }
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Menú lateral (solo si no es nuevo) -->
        <?php if (!$es_nuevo): ?>
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Panel de Empresa</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="perfil.php" class="list-group-item list-group-item-action active">
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
        <?php endif; ?>
        
        <!-- Contenido principal -->
        <div class="<?php echo $es_nuevo ? 'col-lg-8 offset-lg-2' : 'col-lg-9'; ?>">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo $es_nuevo ? 'Completa tu Perfil de Empresa' : 'Editar Perfil de Empresa'; ?></h4>
                </div>
                <div class="card-body">
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
                    
                    <form action="perfil.php<?php echo $es_nuevo ? '?nuevo=1' : ''; ?>" method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-12 mb-3">
                                <div class="text-center mb-3">
                                    <?php if (!empty($empresa['logo'])): ?>
                                        <img src="../uploads/logos/<?php echo $empresa['logo']; ?>" alt="Logo de <?php echo $empresa['nombre']; ?>" class="img-thumbnail" style="max-height: 150px;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center mb-3" style="height: 150px; width: 150px; margin: 0 auto;">
                                            <i class="fas fa-building fa-4x text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <label for="logo" class="form-label">Logo de la Empresa</label>
                                <input type="file" class="form-control" id="logo" name="logo">
                                <div class="form-text">Sube un logo en formato PNG, JPG o GIF (tamaño recomendado: 400x400px)</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre de la Empresa *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $empresa['nombre']; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo $empresa['telefono'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo $empresa['direccion'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="sitio_web" class="form-label">Sitio Web</label>
                                <input type="url" class="form-control" id="sitio_web" name="sitio_web" value="<?php echo $empresa['sitio_web'] ?? ''; ?>" placeholder="https://">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción de la Empresa</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="5"><?php echo $empresa['descripcion'] ?? ''; ?></textarea>
                            <div class="form-text">Describe tu empresa, actividad, cultura, valores, etc.</div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <?php if (!$es_nuevo): ?>
                                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $es_nuevo ? 'Completar Perfil' : 'Guardar Cambios'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>