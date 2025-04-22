<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si está autenticado y es candidato
if (!estaAutenticado() || !esCandidato()) {
    header('Location: ../login.php?redirect=candidatos/aplicar.php&msg=login_requerido');
    exit();
}

// Obtener ID de la oferta
if (!isset($_GET['oferta']) || !is_numeric($_GET['oferta'])) {
    header('Location: ../index.php');
    exit();
}

$oferta_id = (int)$_GET['oferta'];
$conn = conectarDB();
$usuario_id = $_SESSION['usuario_id'];
$mensaje = '';
$error = '';

// Obtener información del candidato
$sql = "SELECT * FROM candidatos WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Redirigir a completar perfil de candidato
    header('Location: ../candidatos/perfil.php?completar=1&redirect=aplicar.php?oferta=' . $oferta_id);
    exit();
}

$candidato = $result->fetch_assoc();
$candidato_id = $candidato['id'];

// Verificar si la oferta existe y está activa
$sql = "SELECT o.*, e.nombre as empresa_nombre 
        FROM ofertas o 
        INNER JOIN empresas e ON o.empresa_id = e.id 
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

// Verificar si ya ha aplicado a esta oferta
$sql = "SELECT id FROM aplicaciones WHERE candidato_id = ? AND oferta_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $candidato_id, $oferta_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Ya ha aplicado, redirigir a la página de la oferta
    header('Location: ../ofertas/detalle.php?id=' . $oferta_id . '&ya_aplicado=1');
    exit();
}

// Verificar si el candidato tiene CV
$tiene_cv = !empty($candidato['cv_pdf']);
if ($tiene_cv) {
    $candidato['cv'] = $candidato['cv_pdf'];
} else {
    $candidato['cv'] = null;
}

// Procesar el formulario de aplicación
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $carta_presentacion = sanitizeInput($_POST['carta_presentacion']);
    $usar_cv_actual = isset($_POST['usar_cv_actual']) ? true : false;
    $cv_actualizado = false;
    
    // Validar la carta de presentación
    if (empty($carta_presentacion)) {
        $error = 'La carta de presentación es obligatoria.';
    } else {
        // Manejar la subida del CV si se proporciona uno nuevo
        if (!$usar_cv_actual && isset($_FILES['cv']) && $_FILES['cv']['error'] == 0) {
            $cv_nombre = $_FILES['cv']['name'];
            $cv_tmp = $_FILES['cv']['tmp_name'];
            $cv_tipo = $_FILES['cv']['type'];
            $cv_tamano = $_FILES['cv']['size'];
            
            // Validar tipo de archivo (PDF)
            $cv_ext = pathinfo($cv_nombre, PATHINFO_EXTENSION);
            if (strtolower($cv_ext) != 'pdf') {
                $error = 'Solo se permiten archivos PDF.';
            } else if ($cv_tamano > 5000000) { // 5MB
                $error = 'El tamaño máximo permitido es 5MB.';
            } else {
                // Crear directorio si no existe
                $directorio = '../uploads/cvs/';
                if (!file_exists($directorio)) {
                    mkdir($directorio, 0777, true);
                }
                
                // Generar nombre único para el archivo
                $cv_nombre_unico = 'cv_' . $candidato_id . '_' . time() . '.pdf';
                $cv_ruta = $directorio . $cv_nombre_unico;
                
                // Subir el archivo
                if (move_uploaded_file($cv_tmp, $cv_ruta)) {
                    // Actualizar CV en la base de datos
                    $sql = "UPDATE candidatos SET cv_pdf = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $cv_nombre_unico, $candidato_id);
                    
                    if ($stmt->execute()) {
                        $cv_actualizado = true;
                        $candidato['cv'] = $cv_nombre_unico;
                    } else {
                        $error = 'Error al actualizar el CV en la base de datos.';
                    }
                } else {
                    $error = 'Error al subir el archivo.';
                }
            }
        } else if (!$usar_cv_actual && !$tiene_cv) {
            $error = 'Debes subir tu CV para aplicar a esta oferta.';
        }
        
        // Si no hay errores, registrar la aplicación
        if (empty($error)) {
            $fecha_aplicacion = date('Y-m-d H:i:s');
            $estado = 'pendiente';
            
            $sql = "INSERT INTO aplicaciones (candidato_id, oferta_id, fecha_aplicacion, estado, carta_presentacion) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisss", $candidato_id, $oferta_id, $fecha_aplicacion, $estado, $carta_presentacion);
            
            if ($stmt->execute()) {
                // Aplicación exitosa, redirigir a la página de la oferta
                header('Location: ../ofertas/detalle.php?id=' . $oferta_id . '&aplicado=1');
                exit();
            } else {
                $error = 'Error al registrar la aplicación: ' . $conn->error;
            }
        }
    }
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="../ofertas.php">Ofertas de empleo</a></li>
            <li class="breadcrumb-item"><a href="../ofertas/detalle.php?id=<?php echo $oferta_id; ?>"><?php echo htmlspecialchars($oferta['titulo']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Aplicar</li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Aplicar a: <?php echo htmlspecialchars($oferta['titulo']); ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info mb-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-info-circle fa-2x text-info"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading">Información importante</h5>
                                <p class="mb-0">Estás aplicando a la oferta <strong><?php echo htmlspecialchars($oferta['titulo']); ?></strong> de <strong><?php echo htmlspecialchars($oferta['empresa_nombre']); ?></strong>. Completa el formulario con atención para aumentar tus posibilidades de ser seleccionado.</p>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <!-- Carta de presentación -->
                        <div class="mb-4">
                            <label for="carta_presentacion" class="form-label fw-bold">Carta de presentación / Mensaje a la empresa *</label>
                            <textarea class="form-control" id="carta_presentacion" name="carta_presentacion" rows="6" required><?php echo isset($_POST['carta_presentacion']) ? htmlspecialchars($_POST['carta_presentacion']) : ''; ?></textarea>
                            <div class="form-text">
                                Explica brevemente por qué estás interesado en el puesto y por qué eres un buen candidato. Destaca tus habilidades y experiencia relevantes.
                            </div>
                        </div>
                        
                        <!-- Curriculum Vitae -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Curriculum Vitae (CV) *</label>
                            
                            <?php if ($tiene_cv): ?>
                            <div class="alert alert-success mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">Ya tienes un CV en tu perfil</h6>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="usar_cv_actual" name="usar_cv_actual" checked>
                                            <label class="form-check-label" for="usar_cv_actual">
                                                Usar mi CV actual
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="../uploads/cvs/<?php echo $candidato['cv']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> Ver
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="nuevo_cv" style="display: none;">
                                <label for="cv" class="form-label">Subir nuevo CV (reemplazará el actual)</label>
                                <input class="form-control" type="file" id="cv" name="cv" accept=".pdf">
                                <div class="form-text">Solo se permiten archivos PDF (máx. 5MB)</div>
                            </div>
                            <?php else: ?>
                            <div class="mb-3">
                                <input class="form-control" type="file" id="cv" name="cv" accept=".pdf" required>
                                <div class="form-text">Solo se permiten archivos PDF (máx. 5MB)</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Términos y condiciones -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terminos" required>
                                <label class="form-check-label" for="terminos">
                                    Acepto que mis datos sean compartidos con la empresa y procesados según la <a href="../politica-privacidad.php" target="_blank">política de privacidad</a>.
                                </label>
                            </div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="../ofertas/detalle.php?id=<?php echo $oferta_id; ?>" class="btn btn-outline-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Enviar aplicación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Consejos para una buena aplicación</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Personaliza tu carta de presentación</strong> para cada oferta, mencionando por qué te interesa específicamente este puesto.
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Destaca tus logros</strong> relevantes para el puesto y utiliza ejemplos concretos.
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Asegúrate de que tu CV esté actualizado</strong> y adaptado a la oferta a la que estás aplicando.
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Revisa tu ortografía y gramática</strong> antes de enviar tu aplicación.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script para mostrar/ocultar la sección de subir nuevo CV
document.addEventListener('DOMContentLoaded', function() {
    const usarCvActualCheckbox = document.getElementById('usar_cv_actual');
    const nuevoCvDiv = document.getElementById('nuevo_cv');
    
    if (usarCvActualCheckbox) {
        usarCvActualCheckbox.addEventListener('change', function() {
            if (this.checked) {
                nuevoCvDiv.style.display = 'none';
                document.getElementById('cv').required = false;
            } else {
                nuevoCvDiv.style.display = 'block';
                document.getElementById('cv').required = true;
            }
        });
    }
});
</script>

<?php
// Incluir el pie de página
include_once '../includes/footer.php';
$conn->close();
?>