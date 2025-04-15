<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

iniciarSesion();

// Redireccionar si ya está autenticado
if (estaAutenticado()) {
    header('Location: index.php');
    exit;
}

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
if (!in_array($tipo, ['candidato', 'empresa'])) {
    $tipo = 'candidato'; // Valor por defecto
}

$errores = [];
$datos = [];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion = conectarDB();
    
    // Datos comunes
    $email = limpiarDatos($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $tipo = limpiarDatos($_POST['tipo']);
    
    // Validaciones básicas
    if (empty($email)) {
        $errores[] = "El correo electrónico es obligatorio";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del correo electrónico no es válido";
    }
    
    if (empty($password)) {
        $errores[] = "La contraseña es obligatoria";
    } elseif (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    if ($password !== $confirm_password) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    // Verificar si el correo ya existe
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE email = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        $errores[] = "Este correo electrónico ya está registrado";
    }
    
    // Si no hay errores, registrar al usuario
    if (empty($errores)) {
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar usuario
        $query = "INSERT INTO usuarios (email, password, tipo) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("sss", $email, $password_hash, $tipo);
        
        if ($stmt->execute()) {
            $usuario_id = $stmt->insert_id;
            
            // Si es candidato, insertar datos adicionales
            if ($tipo === 'candidato') {
                $nombre = limpiarDatos($_POST['nombre']);
                $apellidos = limpiarDatos($_POST['apellidos']);
                
                $query = "INSERT INTO candidatos (usuario_id, nombre, apellidos) VALUES (?, ?, ?)";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("iss", $usuario_id, $nombre, $apellidos);
                $stmt->execute();
            }
            // Si es empresa, insertar datos adicionales
            elseif ($tipo === 'empresa') {
                $nombre_empresa = limpiarDatos($_POST['nombre_empresa']);
                
                $query = "INSERT INTO empresas (usuario_id, nombre) VALUES (?, ?)";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("is", $usuario_id, $nombre_empresa);
                $stmt->execute();
            }
            
            // Iniciar sesión del usuario
            $_SESSION['usuario_id'] = $usuario_id;
            $_SESSION['email'] = $email;
            $_SESSION['tipo_usuario'] = $tipo;
            
            // Mensaje de éxito y redirección
            if ($tipo === 'candidato') {
                mostrarNotificacion("¡Registro exitoso! Ahora puedes completar tu CV", "success");
                header('Location: candidatos/formulario_CV.php');
            } else {
                mostrarNotificacion("¡Registro exitoso! Ahora puedes completar el perfil de tu empresa", "success");
                header('Location: empresas/perfil.php');
            }
            exit;
        } else {
            $errores[] = "Error al registrar: " . $conexion->error;
        }
    }
    
    $conexion->close();
    
    // Guardar datos para repoblar el formulario
    $datos = $_POST;
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">Registro como <?= $tipo === 'candidato' ? 'Candidato' : 'Empresa' ?></h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errores as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <ul class="nav nav-pills mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?= $tipo === 'candidato' ? 'active' : '' ?>" href="?tipo=candidato">Soy Candidato</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $tipo === 'empresa' ? 'active' : '' ?>" href="?tipo=empresa">Soy Empresa</a>
                    </li>
                </ul>
                
                <form method="POST" action="registro.php" class="needs-validation" novalidate>
                    <input type="hidden" name="tipo" value="<?= $tipo ?>">
                    
                    <?php if ($tipo === 'candidato'): ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label required-field">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= isset($datos['nombre']) ? sanitizarHTML($datos['nombre']) : '' ?>" required>
                                <div class="invalid-feedback">Por favor ingresa tu nombre</div>
                            </div>
                            <div class="col-md-6">
                                <label for="apellidos" class="form-label required-field">Apellidos</label>
                                <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= isset($datos['apellidos']) ? sanitizarHTML($datos['apellidos']) : '' ?>" required>
                                <div class="invalid-feedback">Por favor ingresa tus apellidos</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="nombre_empresa" class="form-label required-field">Nombre de la Empresa</label>
                            <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" value="<?= isset($datos['nombre_empresa']) ? sanitizarHTML($datos['nombre_empresa']) : '' ?>" required>
                            <div class="invalid-feedback">Por favor ingresa el nombre de la empresa</div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label required-field">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= isset($datos['email']) ? sanitizarHTML($datos['email']) : '' ?>" required>
                        <div class="invalid-feedback">Por favor ingresa un correo electrónico válido</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label required-field">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">La contraseña debe tener al menos 6 caracteres</div>
                        <div class="invalid-feedback">Por favor ingresa una contraseña</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label required-field">Confirmar Contraseña</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">Por favor confirma tu contraseña</div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="terminos" name="terminos" required>
                        <label class="form-check-label" for="terminos">
                            Acepto los <a href="#">términos y condiciones</a>
                        </label>
                        <div class="invalid-feedback">Debes aceptar los términos y condiciones</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Registrarme</button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    ¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>