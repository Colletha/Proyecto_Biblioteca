<?php
// Configuración de sesiones
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para verificar rol del usuario
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'] ?? '';
    
    // Administrador tiene acceso a todo
    if ($user_role === 'administrador') {
        return true;
    }
    
    // Verificar rol específico
    return $user_role === $required_role;
}

// Función para redirigir si no está autorizado
function requireAuth($required_role = null) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    if ($required_role && !hasRole($required_role)) {
        header('Location: dashboard.php?error=no_permission');
        exit();
    }
}

// Función para cerrar sesión
function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
