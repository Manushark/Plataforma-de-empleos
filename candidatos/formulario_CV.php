<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar que sea un candidato
requiereCandidato();

// Función para obtener datos del candidato
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

// Función para obtener experiencia laboral


// Función para obtener formación académica
function obtenerFormacionAcademica($candidato_id) {
    $conexion = conectarDB();
    $query = "SELECT * FROM formacion_academica WHERE candidato_id = ? ORDER BY fecha_fin DESC";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $candidato_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $formaciones = [];
    while ($formacion = $resultado->fetch_assoc()) {
        $formaciones[] = $formacion;
    }
    
    $stmt->close();
    $conexion->close();
    
    return $formaciones;
}

// Procesar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion = conectarDB();
    $usuario_id = $_SESSION['usuario_id'];
    
    // Obtener el candidato actual o crear uno nuevo
    $query = "SELECT id FROM candidatos WHERE usuario_id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $candidato = $resultado->fetch_assoc();
        $candidato_id = $candidato['id'];
    } else {
        // Este caso no debería ocurrir normalmente si el registro fue correcto
        $query = "INSERT INTO candidatos (usuario_id) VALUES (?)";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $candidato_id = $stmt->insert_id;
    }
    
    // Datos básicos
    $nombre = limpiarDatos($_POST['nombre']);
    $apellidos = limpiarDatos($_POST['apellidos']);
    $telefono = limpiarDatos($_POST['telefono']);
    $direccion = limpiarDatos($_POST['direccion']);
    $ciudad = limpiarDatos($_POST['ciudad']);
    $codigo_postal = limpiarDatos($_POST['codigo_postal']);
    $fecha_nacimiento = limpiarDatos($_POST['fecha_nacimiento']);
    $titulo_profesional = limpiarDatos($_POST['titulo_profesional']);
    $objetivo_profesional = limpiarDatos($_POST['objetivo_profesional']);
    $habilidades = limpiarDatos($_POST['habilidades']);
    $idiomas = limpiarDatos($_POST['idiomas']);
    $disponibilidad = limpiarDatos($_POST['disponibilidad']);
    $linkedin = limpiarDatos($_POST['linkedin']);
    
    // Subir foto si existe
    $foto = '';
    if (!empty($_FILES['foto']['name'])) {
        $resultado_subida = subirImagen($_FILES['foto']);
        if (!isset($resultado_subida['error'])) {
            $foto = $resultado_subida['nombre'];
        } else {
            mostrarNotificacion($resultado_subida['error'], 'danger');
        }
    }
    
    // Subir CV PDF si existe
    $cv_pdf = '';
    if (!empty($_FILES['cv_pdf']['name'])) {
        $resultado_subida = subirArchivo($_FILES['cv_pdf'], '../uploads/cv/');
        if (!isset($resultado_subida['error'])) {
            $cv_pdf = $resultado_subida['nombre'];
        } else {
            mostrarNotificacion($resultado_subida['error'], 'danger');
        }
    }
    
    // Actualizar datos del candidato
        $query = "UPDATE candidatos SET 
                nombre = ?, 
                apellidos = ?, 
                telefono = ?, 
                direccion = ?, 
                ciudad = ?, 
                codigo_postal = ?, 
                fecha_nacimiento = ?, 
                titulo_profesional = ?, 
                objetivo_profesional = ?, 
                habilidades = ?, 
                idiomas = ?, 
                disponibilidad = ?, 
                linkedin = ?";

    $params = [$nombre, $apellidos, $telefono, $direccion, $ciudad, $codigo_postal, $fecha_nacimiento, 
            $titulo_profesional, $objetivo_profesional, $habilidades, $idiomas, $disponibilidad, 
            $linkedin];
    $tipos = "sssssssssssss"; // 13 's' for 13 parameters
        
    if (!empty($foto)) {
        $query .= ", foto = ?";
        $params[] = $foto;
        $tipos .= "s";
    }
    
    if (!empty($cv_pdf)) {
        $query .= ", cv_pdf = ?";
        $params[] = $cv_pdf;
        $tipos .= "s";
    }
    
    $query .= " WHERE id = ?";
    $params[] = $candidato_id;
    $tipos .= "i";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
    
    // Procesar experiencia laboral si existe
    if (isset($_POST['experiencia']) && is_array($_POST['experiencia'])) {
        // Primero eliminamos las experiencias anteriores
        $query = "DELETE FROM experiencia_laboral WHERE candidato_id = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("i", $candidato_id);
        $stmt->execute();
        
        // Luego insertamos las nuevas
        foreach ($_POST['experiencia'] as $exp) {
            if (!empty($exp['empresa']) && !empty($exp['puesto'])) {
                $empresa = limpiarDatos($exp['empresa']);
                $puesto = limpiarDatos($exp['puesto']);
                $fecha_inicio = limpiarDatos($exp['fecha_inicio']);
                $fecha_fin = isset($exp['actual']) ? NULL : limpiarDatos($exp['fecha_fin']);
                $descripcion = limpiarDatos($exp['descripcion']);
                $actual = isset($exp['actual']) ? 1 : 0;
                
                $query = "INSERT INTO experiencia_laboral (candidato_id, empresa, puesto, fecha_inicio, fecha_fin, descripcion, actual) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("isssssi", $candidato_id, $empresa, $puesto, $fecha_inicio, $fecha_fin, $descripcion, $actual);
                $stmt->execute();
            }
        }
    }
    
    // Procesar formación académica si existe
    if (isset($_POST['formacion']) && is_array($_POST['formacion'])) {
        // Primero eliminamos las formaciones anteriores
        $query = "DELETE FROM formacion_academica WHERE candidato_id = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("i", $candidato_id);
        $stmt->execute();
        
        // Luego insertamos las nuevas
        foreach ($_POST['formacion'] as $form) {
            if (!empty($form['institucion']) && !empty($form['titulo'])) {
                $institucion = limpiarDatos($form['institucion']);
                $titulo = limpiarDatos($form['titulo']);
                $fecha_inicio = limpiarDatos($form['fecha_inicio']);
                $fecha_fin = isset($form['actual']) ? NULL : limpiarDatos($form['fecha_fin']);
                $descripcion = limpiarDatos($form['descripcion']);
                $actual = isset($form['actual']) ? 1 : 0;
                
                $query = "INSERT INTO formacion_academica (candidato_id, institucion, titulo, fecha_inicio, fecha_fin, descripcion, actual) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("isssssi", $candidato_id, $institucion, $titulo, $fecha_inicio, $fecha_fin, $descripcion, $actual);
                $stmt->execute();
            }
        }
    }
    
    $conexion->close();
    
    mostrarNotificacion("CV actualizado correctamente", "success");
    header('Location: index.php');
    exit;
}

// Obtener datos del candidato para prellenar el formulario
$candidato = obtenerDatosCandidato($_SESSION['usuario_id']);
$experiencias = [];
$formaciones = [];



include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Currículum Vitae</h4>
        </div>
        <div class="card-body">
            <form action="formulario_CV.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <!-- Datos Personales -->
                <h5 class="border-bottom pb-2 mb-4">Datos Personales</h5>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="nombre" class="form-label required-field">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= $candidato ? sanitizarHTML($candidato['nombre']) : '' ?>" required>
                        <div class="invalid-feedback">Por favor ingresa tu nombre</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="apellidos" class="form-label required-field">Apellidos</label>
                        <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= $candidato ? sanitizarHTML($candidato['apellidos']) : '' ?>" required>
                        <div class="invalid-feedback">Por favor ingresa tus apellidos</div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label required-field">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= $candidato && isset($candidato['telefono']) ? sanitizarHTML($candidato['telefono']) : '' ?>" required>
                        <div class="invalid-feedback">Por favor ingresa tu teléfono</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= $candidato && isset($candidato['fecha_nacimiento']) ? $candidato['fecha_nacimiento'] : '' ?>">
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" value="<?= $candidato && isset($candidato['direccion']) ? sanitizarHTML($candidato['direccion']) : '' ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="ciudad" class="form-label">Ciudad</label>
                        <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?= $candidato && isset($candidato['ciudad']) ? sanitizarHTML($candidato['ciudad']) : '' ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="codigo_postal" class="form-label">Código Postal</label>
                        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" value="<?= $candidato && isset($candidato['codigo_postal']) ? sanitizarHTML($candidato['codigo_postal']) : '' ?>">
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="foto" class="form-label">Foto de Perfil</label>
                        <input type="file" class="form-control img-preview-input" id="foto" name="foto" data-preview="foto-preview" accept="image/*">
                        <div class="form-text">Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 2MB</div>
                    </div>
                    <div class="col-md-6 mb-3 text-center">
                        <?php if ($candidato && !empty($candidato['foto'])): ?>
                            <img src="/plataforma-de-empleos/uploads/fotos/<?= $candidato['foto'] ?>" id="foto-preview" class="img-thumbnail" style="max-height: 150px;" alt="Vista previa">
                        <?php else: ?>
                            <img src="" id="foto-preview" class="img-thumbnail" style="max-height: 150px; display: none;" alt="Vista previa">
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Información Profesional -->
                <h5 class="border-bottom pb-2 mb-4 mt-5">Información Profesional</h5>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="titulo_profesional" class="form-label required-field">Título Profesional</label>
                        <input type="text" class="form-control" id="titulo_profesional" name="titulo_profesional" value="<?= $candidato && isset($candidato['titulo_profesional']) ? sanitizarHTML($candidato['titulo_profesional']) : '' ?>" required>
                        <div class="invalid-feedback">Por favor ingresa tu título profesional</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="disponibilidad" class="form-label">Disponibilidad</label>
                        <select class="form-select" id="disponibilidad" name="disponibilidad">
                            <option value="inmediata" <?= $candidato && isset($candidato['disponibilidad']) && $candidato['disponibilidad'] == 'inmediata' ? 'selected' : '' ?>>Inmediata</option>
                            <option value="15 días" <?= $candidato && isset($candidato['disponibilidad']) && $candidato['disponibilidad'] == '15 días' ? 'selected' : '' ?>>15 días</option>
                            <option value="1 mes" <?= $candidato && isset($candidato['disponibilidad']) && $candidato['disponibilidad'] == '1 mes' ? 'selected' : '' ?>>1 mes</option>
                            <option value="más de 1 mes" <?= $candidato && isset($candidato['disponibilidad']) && $candidato['disponibilidad'] == 'más de 1 mes' ? 'selected' : '' ?>>Más de 1 mes</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="objetivo_profesional" class="form-label required-field">Objetivo Profesional / Resumen</label>
                    <textarea class="form-control" id="objetivo_profesional" name="objetivo_profesional" rows="3" required><?= $candidato && isset($candidato['objetivo_profesional']) ? sanitizarHTML($candidato['objetivo_profesional']) : '' ?></textarea>
                    <div class="invalid-feedback">Por favor ingresa tu objetivo profesional</div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="habilidades" class="form-label required-field">Habilidades Clave</label>
                        <textarea class="form-control" id="habilidades" name="habilidades" rows="3" required><?= $candidato && isset($candidato['habilidades']) ? sanitizarHTML($candidato['habilidades']) : '' ?></textarea>
                        <div class="form-text">Separa las habilidades con comas (por ejemplo: HTML, CSS, JavaScript)</div>
                        <div class="invalid-feedback">Por favor ingresa tus habilidades</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="idiomas" class="form-label">Idiomas</label>
                        <textarea class="form-control" id="idiomas" name="idiomas" rows="3"><?= $candidato && isset($candidato['idiomas']) ? sanitizarHTML($candidato['idiomas']) : '' ?></textarea>
                        <div class="form-text">Indica idiomas y nivel (por ejemplo: Inglés - Avanzado, Francés - Básico)</div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="linkedin" class="form-label">LinkedIn</label>
                        <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?= $candidato && isset($candidato['linkedin']) ? sanitizarHTML($candidato['linkedin']) : '' ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cv_pdf" class="form-label">CV en PDF</label>
                        <input type="file" class="form-control" id="cv_pdf" name="cv_pdf" accept="application/pdf">
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