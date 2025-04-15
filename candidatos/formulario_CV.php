<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado y es candidato
if (!isLoggedIn() || getUserType() !== 'candidato') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$candidato = getCandidatoInfo($conn, $user_id);
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $telefono = sanitizeInput($_POST['telefono']);
    $direccion = sanitizeInput($_POST['direccion']);
    $ciudad = sanitizeInput($_POST['ciudad']);
    $formacion_academica = sanitizeInput($_POST['formacion_academica']);
    $experiencia_laboral = sanitizeInput($_POST['experiencia_laboral']);
    $habilidades = sanitizeInput($_POST['habilidades']);
    $idiomas = sanitizeInput($_POST['idiomas']);
    $objetivo_profesional = sanitizeInput($_POST['objetivo_profesional']);
    $logros = sanitizeInput($_POST['logros']);
    $disponibilidad = sanitizeInput($_POST['disponibilidad']);
    $redes_profesionales = sanitizeInput($_POST['redes_profesionales']);
    $referencias = sanitizeInput($_POST['referencias']);
    
    // Procesar foto si se ha subido
    $foto = $candidato['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $foto_upload = uploadFile($_FILES['foto'], '../uploads/fotos/');
        if ($foto_upload) {
            $foto = $foto_upload;
        } else {
            $errors[] = "Error al subir la foto";
        }
    }
    
    // Procesar CV PDF si se ha subido
    $cv_pdf = $candidato['cv_pdf'];
    if (isset($_FILES['cv_pdf']) && $_FILES['cv_pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
        $cv_pdf_upload = uploadFile($_FILES['cv_pdf'], '../uploads/cv/');
        if ($cv_pdf_upload) {
            $cv_pdf = $cv_pdf_upload;
        } else {
            $errors[] = "Error al subir el CV en PDF";
        }
    }
    
    // Si no hay errores, actualizar el CV
    if (empty($errors)) {
        $sql = "UPDATE candidatos SET 
                telefono = ?, 
                direccion = ?, 
                ciudad = ?, 
                formacion_academica = ?, 
                experiencia_laboral = ?, 
                habilidades = ?, 
                idiomas = ?, 
                objetivo_profesional = ?, 
                logros = ?, 
                disponibilidad = ?, 
                redes_profesionales = ?, 
                referencias = ?, 
                foto = ?, 
                cv_pdf = ? 
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssssssssssi", 
            $telefono, 
            $direccion, 
            $ciudad, 
            $formacion_academica, 
            $experiencia_laboral, 
            $habilidades, 
            $idiomas, 
            $objetivo_profesional, 
            $logros, 
            $disponibilidad, 
            $redes_profesionales, 
            $referencias, 
            $foto, 
            $cv_pdf, 
            $user_id
        );
        
        if ($stmt->execute()) {
            $success = true;
            
            // Actualizar la información del candidato
            $candidato = getCandidatoInfo($conn, $user_id);
        } else {
            $errors[] = "Error al actualizar el CV: " . $conn->error;
        }
    }
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <?php if (!empty($candidato['foto'])): ?>
                        <img src="../uploads/fotos/<?php echo $candidato['foto']; ?>" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <img src="../assets/img/default-user.png" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php endif; ?>
                    
                    <h4><?php echo $candidato['nombre'] . ' ' . $candidato['apellidos']; ?></h4>
                    <p class="text-muted"><?php echo $candidato['email']; ?></p>
                </div>
            </div>
            
            <div class="list-group mb-4">
                <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                <a href="perfil.php" class="list-group-item list-group-item-action">Mi Perfil</a>
                <a href="cv.php" class="list-group-item list-group-item-action active">Mi CV</a>
                <a href="mis_aplicaciones.php" class="list-group-item list-group-item-action">Mis Aplicaciones</a>
                <a href="buscar_ofertas.php" class="list-group-item list-group-item-action">Buscar Ofertas</a>
                <a href="../logout.php" class="list-group-item list-group-item-action text-danger">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Mi Currículum</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Tu currículum ha sido actualizado correctamente.
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Información de Contacto</h5>
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo $candidato['telefono']; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección</label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="2"><?php echo $candidato['direccion']; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="ciudad" class="form-label">Ciudad / Provincia</label>
                                    <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?php echo $candidato['ciudad']; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Archivos</h5>
                                <div class="mb-3">
                                    <label for="foto" class="form-label">Foto de Perfil</label>
                                    <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                                    <?php if (!empty($candidato['foto'])): ?>
                                        <small class="text-muted">Ya tienes una foto subida. Si subes una nueva, reemplazará la actual.</small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="cv_pdf" class="form-label">CV en PDF</label>
                                    <input type="file" class="form-control" id="cv_pdf" name="cv_pdf" accept="application/pdf">
                                    <?php if (!empty($candidato['cv_pdf'])): ?>
                                        <div class="mt-2">
                                            <a href="../uploads/cv/<?php echo $candidato['cv_pdf']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-pdf"></i> Ver PDF actual
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <h5>Formación y Experiencia</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="formacion_academica" class="form-label">Formación Académica</label>
                                    <textarea class="form-control" id="formacion_academica" name="formacion_academica" rows="4"><?php echo $candidato['formacion_academica']; ?></textarea>
                                    <small class="text-muted">Incluye institución, título y fechas</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="experiencia_laboral" class="form-label">Experiencia Laboral</label>
                                    <textarea class="form-control" id="experiencia_laboral" name="experiencia_laboral" rows="4"><?php echo $candidato['experiencia_laboral']; ?></textarea>
                                    <small class="text-muted">Incluye empresa, puesto y fechas</small>
                                </div>
                            </div>
                        </div>
                        
                        <h5>Habilidades y Conocimientos</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="habilidades" class="form-label">Habilidades Clave</label>
                                    <textarea class="form-control" id="habilidades" name="habilidades" rows="3"><?php echo $candidato['habilidades']; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="idiomas" class="form-label">Idiomas</label>
                                    <textarea class="form-control" id="idiomas" name="idiomas" rows="3"><?php echo $candidato['idiomas']; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="objetivo_profesional" class="form-label">Objetivo Profesional / Resumen</label>
                                    <textarea class="form-control" id="objetivo_profesional" name="objetivo_profesional" rows="3"><?php echo $candidato['objetivo_profesional']; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="logros" class="form-label">Logros o Proyectos Destacados</label>
                                    <textarea class="form-control" id="logros" name="logros" rows="3"><?php echo $candidato['logros']; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <h5>Información Adicional</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="disponibilidad" class="form-label">Disponibilidad</label>
                                    <select class="form-select" id="disponibilidad" name="disponibilidad">
                                        <option value="">-- Seleccionar --</option>
                                        <option value="Inmediata" <?php echo $candidato['disponibilidad'] === 'Inmediata' ? 'selected' : ''; ?>>Inmediata</option>
                                        <option value="En 15 días" <?php echo $candidato['disponibilidad'] === 'En 15 días' ? 'selected' : ''; ?>>En 15 días</option>
                                        <option value="En 1 mes" <?php echo $candidato['disponibilidad'] === 'En 1 mes' ? 'selected' : ''; ?>>En 1 mes</option>
                                        <option value="A convenir" <?php echo $candidato['disponibilidad'] === 'A convenir' ? 'selected' : ''; ?>>A convenir</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="redes_profesionales" class="form-label">Redes Profesionales</label>
                                    <textarea class="form-control" id="redes_profesionales" name="redes_profesionales" rows="3"><?php echo $candidato['redes_profesionales']; ?></textarea>
                                    <small class="text-muted">LinkedIn, GitHub, Portfolio, etc.</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="referencias" class="form-label">Referencias</label>
                                    <textarea class="form-control" id="referencias" name="referencias" rows="3"><?php echo $candidato['referencias']; ?></textarea>
                                    <small class="text-muted">Contacto o breve descripción</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Guardar Currículum</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>