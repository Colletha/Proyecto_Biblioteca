<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

// Solo administradores y bibliotecarios pueden ver reportes
if ($_SESSION['rol'] == 'alumno') {
    header('Location: ../dashboard.php');
    exit();
}

$titulo = "Reportes del Sistema";

// Obtener estadísticas generales
$stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM libros) as total_libros,
        (SELECT COUNT(*) FROM usuarios WHERE rol != 'administrador') as total_usuarios,
        (SELECT COUNT(*) FROM prestamos WHERE estado = 'activo') as prestamos_activos,
        (SELECT COUNT(*) FROM prestamos WHERE estado = 'devuelto' AND fecha_devolucion > fecha_limite) as prestamos_con_multa
");
$estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?> - Biblioteca Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-bar me-2"></i><?php echo $titulo; ?></h1>
                </div>

                <!-- Estadísticas Generales -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $estadisticas['total_libros']; ?></h4>
                                        <p class="mb-0">Total Libros</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-book fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $estadisticas['total_usuarios']; ?></h4>
                                        <p class="mb-0">Total Usuarios</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $estadisticas['prestamos_activos']; ?></h4>
                                        <p class="mb-0">Préstamos Activos</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-hand-holding fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $estadisticas['prestamos_con_multa']; ?></h4>
                                        <p class="mb-0">Con Multas</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tipos de Reportes -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-user-graduate me-2"></i>Reportes por Usuario</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Generar reportes de préstamos por usuario específico.</p>
                                <a href="reporte_usuarios.php" class="btn btn-primary">
                                    <i class="fas fa-file-alt me-2"></i>Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-book me-2"></i>Reportes por Libro</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Historial de préstamos por libro específico.</p>
                                <a href="reporte_libros.php" class="btn btn-success">
                                    <i class="fas fa-file-alt me-2"></i>Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-clock me-2"></i>Préstamos Activos</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Lista de todos los préstamos actualmente vigentes.</p>
                                <a href="reporte_prestamos_activos.php" class="btn btn-warning">
                                    <i class="fas fa-file-alt me-2"></i>Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-check-circle me-2"></i>Libros Disponibles</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Inventario de libros disponibles para préstamo.</p>
                                <a href="reporte_libros_disponibles.php" class="btn btn-info">
                                    <i class="fas fa-file-alt me-2"></i>Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
