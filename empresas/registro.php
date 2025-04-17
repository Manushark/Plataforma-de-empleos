<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si ya está logueado
if (isLoggedIn()) {
    redirectByUserType();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $nombre = sanitizeInput($_POST['nombre']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $direccion = sanitizeInput($_POST['direccion']);
    $telefono = sanitizeInput($_POST['telefono']);
    $sitio_web = sanitizeInput($_POST['sitio_web']);
    
    // Validar campos requeridos
    if (empty($nombre)) {
        $errors[] = "El nombre de la empresa es obligatorio";
    }
    
    if (empty($email)) {
        $errors[] = "El correo electrónico es obligatorio";
    } elseif (!isValidEmail($email)) {
        $errors[] = "El formato del correo electrónico no es válido";
    }
    
    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria";
    } elseif (strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden";
    }
    
    // Verificar si el correo ya está registrado
    if (empty($errors) && emailExists($conn, $email, 'empresas')) {
        $errors[] = "Este correo electrónico ya está registrado";
    }
    
    // Si no hay errores, registrar a la empresa
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Procesar logo si se ha subido
        $logo = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Verificar el tipo de archivo
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['logo']['type'], $allowed_types)) {
                $upload_result = uploadFile($_FILES['logo'], '../uploads/logos/');
                if ($upload_result) {
                    $logo = $upload_result;
                } else {
                    $errors[] = "Error al subir el logo. Inténtalo de nuevo.";
                }
            } else {
                $errors[] = "Formato de imagen no válido. Use JPG, PNG o GIF.";
            }
        }
        
        if (empty($errors)) {
            $sql = "INSERT INTO empresas (nombre, email, password, direccion, telefono, sitio_web, logo) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $nombre, $email, $hashed_password, $direccion, $telefono, $sitio_web, $logo);
            
            if ($stmt->execute()) {
                $success = true;
                
                // Iniciar sesión automáticamente
                $user_id = $stmt->insert_id;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_type'] = 'empresa';
                $_SESSION['nombre'] = $nombre;
                
                // Redireccionar al dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = "Error al registrar: " . $conn->error;
            }
        }
    }
}

// Incluir el encabezado
include_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">Registro de Empresa</h3>
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
                            ¡Registro exitoso! Serás redirigido a tu dashboard...
                        </div>
                    <?php else: ?>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre de la Empresa</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($_POST['nombre']) ? $_POST['nombre'] : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo isset($_POST['direccion']) ? $_POST['direccion'] : ''; ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo isset($_POST['telefono']) ? $_POST['telefono'] : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sitio_web" class="form-label">Sitio Web</label>
                                    <input type="url" class="form-control" id="sitio_web" name="sitio_web" value="<?php echo isset($_POST['sitio_web']) ? $_POST['sitio_web'] : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="logo" class="form-label">Logo de la Empresa (opcional)</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            </div>
                            
                            <button type="submit" class="btn btn-success">Registrarse</button>
                            
                            <div class="mt-3">
                                <p>¿Ya tienes una cuenta? <a href="login.php">Iniciar sesión</a></p>
                                <p>¿Buscas empleo? <a href="../candidatos/registro.php">Regístrate como candidato</a></p>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>