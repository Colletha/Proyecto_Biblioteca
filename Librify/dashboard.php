<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireAuth();

// Obtener estadísticas del dashboard
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Contar libros totales
    $stmt = $db->query("SELECT COUNT(*) as total FROM libros WHERE activo = 1");
    $total_libros = $stmt->fetch()['total'];
    
    // Contar usuarios activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
    $total_usuarios = $stmt->fetch()['total'];
    
    // Contar préstamos activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'activo'");
    $prestamos_activos = $stmt->fetch()['total'];
    
    // Contar préstamos vencidos
    $stmt = $db->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'vencido' OR (estado = 'activo' AND fecha_devolucion_esperada < CURDATE())");
    $prestamos_vencidos = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $error = "Error al cargar estadísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Biblioteca Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tarjetas de estadísticas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Libros
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $total_libros ?? 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Usuarios Activos
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $total_usuarios ?? 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Préstamos Activos
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $prestamos_activos ?? 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-hand-holding fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Préstamos Vencidos
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $prestamos_vencidos ?? 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Accesos rápidos -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-plus-circle"></i> Acciones Rápidas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if (hasRole('administrador') || hasRole('bibliotecario')): ?>
                                    <div class="col-md-6 mb-3">
                                        <a href="libros/crear.php" class="btn btn-primary btn-block">
                                            <i class="fas fa-book-medical"></i><br>
                                            Agregar Libro
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="prestamos/crear.php" class="btn btn-success btn-block">
                                            <i class="fas fa-hand-holding"></i><br>
                                            Nuevo Préstamo
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (hasRole('administrador')): ?>
                                    <div class="col-md-6 mb-3">
                                        <a href="usuarios/crear.php" class="btn btn-info btn-block">
                                            <i class="fas fa-user-plus"></i><br>
                                            Agregar Usuario
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="col-md-6 mb-3">
                                        <a href="reportes/" class="btn btn-warning btn-block">
                                            <i class="fas fa-chart-bar"></i><br>
                                            Ver Reportes
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-clock"></i> Actividad Reciente
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item">
                                        <i class="fas fa-book text-primary"></i>
                                        <strong>Nuevo libro agregado:</strong> "Introducción a la Programación"
                                        <small class="text-muted d-block">Hace 2 horas</small>
                                    </div>
                                    <div class="list-group-item">
                                        <i class="fas fa-hand-holding text-success"></i>
                                        <strong>Préstamo realizado:</strong> "El Principito" - Juan Pérez
                                        <small class="text-muted d-block">Hace 4 horas</small>
                                    </div>
                                    <div class="list-group-item">
                                        <i class="fas fa-undo text-info"></i>
                                        <strong>Devolución:</strong> "Don Quijote de la Mancha"
                                        <small class="text-muted d-block">Ayer</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
