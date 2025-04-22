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
$sql = "SELECT * FROM ofertas WHERE id = ? AND empresa_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $oferta_id, $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: index.php');
    exit();
}

$oferta = $result->fetch_assoc();

// Obtener categorías para el formulario
$categorias = getCategorias($conn);

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = sanitizeInput($_POST['titulo']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $requisitos = sanitizeInput($_POST['requisitos']);
    $beneficios = isset($_POST['beneficios']) ? sanitizeInput($_POST['beneficios']) : '';
    $ubicacion = sanitizeInput($_POST['ubicacion']);
    $tipo_contrato = sanitizeInput($_POST['tipo_contrato']);
    $salario = sanitizeInput($_POST['salario']);
    $categoria_id = (int)$_POST['categoria_id'];
    $estado = sanitizeInput($_POST['estado']);
    
    // Validar campos obligatorios
    if (empty($titulo) || empty($descripcion) || empty($requisitos) || empty($ubicacion) || empty($tipo_contrato) || $categoria_id <= 0) {
        $error = 'Por favor, completa todos los campos obligatorios.';
    } else {
        // Actualizar la oferta
        $sql = "UPDATE ofertas SET 
                titulo = ?, 
                descripcion = ?, 
                requisitos = ?, 
                beneficios = ?, 
                ubicacion = ?, 
                tipo_contrato = ?, 
                salario = ?, 
                categoria_id = ?,
                estado = ?
                WHERE id = ? AND empresa_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssiii", $titulo, $descripcion, $requisitos, $beneficios, $ubicacion, $tipo_contrato, $salario, $categoria_id, $estado, $oferta_id, $empresa_id);
        
        if ($stmt->execute()) {
            $mensaje = 'Oferta actualizada correctamente.';
            
            // Actualizar los datos locales de la oferta
            $oferta['titulo'] = $titulo;
            $oferta['descripcion'] = $descripcion;
            $oferta['requisitos'] = $requisitos;
            $oferta['beneficios'] = $beneficios;
            $oferta['ubicacion'] = $ubicacion;
            $oferta['tipo_contrato'] = $tipo_contrato;
            $oferta['salario'] = $salario;
            $oferta['categoria_id'] = $categoria_id;
            $oferta['estado'] = $estado;
        } else {
            $error = 'Error al actualizar la oferta: ' . $conn->error;
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
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Editar Oferta de Empleo</h4>
                </div>
                <div class="card-body">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título de la oferta *</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required value="<?php echo htmlspecialchars($oferta['titulo']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="categoria_id" class="form-label">Categoría *</label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">-- Seleccionar categoría --</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" <?php echo ($oferta['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación *</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" required value="<?php echo htmlspecialchars($oferta['ubicacion']); ?>">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tipo_contrato" class="form-label">Tipo de contrato *</label>
                                <select class="form-select" id="tipo_contrato" name="tipo_contrato" required>
                                    <option value="">-- Seleccionar tipo --</option>
                                    <option value="Tiempo completo" <?php echo ($oferta['tipo_contrato'] == 'Tiempo completo') ? 'selected' : ''; ?>>Tiempo completo</option>
                                    <option value="Medio tiempo" <?php echo ($oferta['tipo_contrato'] == 'Medio tiempo') ? 'selected' : ''; ?>>Medio tiempo</option>
                                    <option value="Temporal" <?php echo ($oferta['tipo_contrato'] == 'Temporal') ? 'selected' : ''; ?>>Temporal</option>
                                    <option value="Freelance" <?php echo ($oferta['tipo_contrato'] == 'Freelance') ? 'selected' : ''; ?>>Freelance</option>
                                    <option value="Prácticas" <?php echo ($oferta['tipo_contrato'] == 'Prácticas') ? 'selected' : ''; ?>>Prácticas</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="salario" class="form-label">Salario</label>
                                <input type="text" class="form-control" id="salario" name="salario" placeholder="Ej: $1000-$1500 mensual" value="<?php echo htmlspecialchars($oferta['salario']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado de la oferta *</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="activa" <?php echo ($oferta['estado'] == 'activa') ? 'selected' : ''; ?>>Activa</option>
                                <option value="pausada" <?php echo ($oferta['estado'] == 'pausada') ? 'selected' : ''; ?>>Pausada</option>
                                <option value="cerrada" <?php echo ($oferta['estado'] == 'cerrada') ? 'selected' : ''; ?>>Cerrada</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción del puesto *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required><?php echo htmlspecialchars($oferta['descripcion']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="requisitos" class="form-label">Requisitos *</label>
                            <textarea class="form-control" id="requisitos" name="requisitos" rows="4" required><?php echo htmlspecialchars($oferta['requisitos']); ?></textarea>
                            <small class="text-muted">Especifica formación, experiencia, habilidades y otros requisitos necesarios.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="beneficios" class="form-label">Beneficios</label>
                            <textarea class="form-control" id="beneficios" name="beneficios" rows="3"><?php echo htmlspecialchars($oferta['beneficios']); ?></textarea>
                            <small class="text-muted">Incluye los beneficios que ofrece la empresa (seguro médico, horario flexible, etc.)</small>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-outline-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted">
                    <div class="d-flex justify-content-between align-items-center">
                        <small>Última modificación: <?php echo date('d/m/Y H:i', strtotime($oferta['fecha_publicacion'])); ?></small>
                        <a href="eliminar-oferta.php?id=<?php echo $oferta_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar esta oferta? Esta acción no se puede deshacer.')">
                            <i class="fas fa-trash-alt me-1"></i> Eliminar oferta
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
$conn->close();
?>