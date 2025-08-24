<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] == 'alumno') {
    header('Location: ../dashboard.php');
    exit();
}

$usuario_seleccionado = $_GET['usuario_id'] ?? '';
$usuarios = [];
$prestamos = [];

// Obtener lista de usuarios
$stmt = $pdo->query("SELECT id, nombre, email, rol FROM usuarios WHERE rol != 'administrador' ORDER BY nombre");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si se seleccionó un usuario, obtener sus préstamos
if ($usuario_seleccionado) {
    $stmt = $pdo->prepare("
        SELECT p.*, l.titulo, l.autor, u.nombre as usuario_nombre
        FROM prestamos p
        JOIN libros l ON p.libro_id = l.id
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.usuario_id = ?
        ORDER BY p.fecha_prestamo DESC
    ");
    $stmt->execute([$usuario_seleccionado]);
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte por Usuario - Biblioteca Escolar</title>
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
                    <h1 class="h2"><i class="fas fa-user-graduate me-2"></i>Reporte por Usuario</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>

                <!-- Selector de Usuario -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <label for="usuario_id" class="form-label">Seleccionar Usuario</label>
                                <select class="form-select" id="usuario_id" name="usuario_id" required>
                                    <option value="">Seleccione un usuario...</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?php echo $usuario['id']; ?>" 
                                                <?php echo $usuario_seleccionado == $usuario['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($usuario['nombre'] . ' (' . $usuario['email'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Generar Reporte
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($usuario_seleccionado && !empty($prestamos)): ?>
                    <!-- Reporte de Préstamos -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Historial de Préstamos - <?php echo htmlspecialchars($prestamos[0]['usuario_nombre']); ?></h5>
                            <button onclick="window.print()" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-print me-2"></i>Imprimir
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Libro</th>
                                            <th>Autor</th>
                                            <th>Fecha Préstamo</th>
                                            <th>Fecha Límite</th>
                                            <th>Fecha Devolución</th>
                                            <th>Estado</th>
                                            <th>Multa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prestamos as $prestamo): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($prestamo['titulo']); ?></td>
                                                <td><?php echo htmlspecialchars($prestamo['autor']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_limite'])); ?></td>
                                                <td>
                                                    <?php echo $prestamo['fecha_devolucion'] ? 
                                                        date('d/m/Y', strtotime($prestamo['fecha_devolucion'])) : 
                                                        '<span class="text-warning">Pendiente</span>'; ?>
                                                </td>
                                                <td>
                                                    <?php if ($prestamo['estado'] == 'activo'): ?>
                                                        <span class="badge bg-warning">Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Devuelto</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($prestamo['multa'] > 0): ?>
                                                        <span class="text-danger">$<?php echo number_format($prestamo['multa'], 2); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-success">Sin multa</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($usuario_seleccionado): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        El usuario seleccionado no tiene préstamos registrados.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
