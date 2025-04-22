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

// Verificar si se han proporcionado los parámetros necesarios
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['estado']) || empty($_GET['estado'])) {
    header('Location: index.php');
    exit();
}

$oferta_id = (int)$_GET['id'];
$nuevo_estado = sanitizeInput($_GET['estado']);

// Validar que el estado sea válido
$estados_validos = ['activa', 'pausada', 'cerrada'];
if (!in_array($nuevo_estado, $estados_validos)) {
    header('Location: index.php');
    exit();
}

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

// Actualizar el estado de la oferta
$sql = "UPDATE ofertas SET estado = ? WHERE id = ? AND empresa_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $nuevo_estado, $oferta_id, $empresa_id);

if ($stmt->execute()) {
    // Redirigir a la página de ofertas con mensaje de éxito
    header("Location: index.php?actualizado=1&oferta_id=$oferta_id");
    exit();
} else {
    // Redirigir con mensaje de error
    header("Location: index.php?error=1&oferta_id=$oferta_id");
    exit();
}

$conn->close();
?>