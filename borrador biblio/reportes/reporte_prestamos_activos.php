<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] == 'alumno') {
    header('Location: ../dashboard.php');
    exit();
}

// Obtener préstamos activos
$stmt = $pdo->query("
    SELECT p.*, l.titulo, l.autor, u.nombre as usuario_nombre, u.email,
           DATEDIFF(CURDATE(), p.fecha_limite) as dias_retraso
    FROM prestamos p
    JOIN libros l ON p.libro_id = l.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.estado = 'activo'
    ORDER BY p.fecha_limite ASC
");
$prestamos_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préstamos Activos - Biblioteca Escolar</title>
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
                    <h1 class="h2"><i class="fas fa-clock me-2"></i>Préstamos Activos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button onclick="window.print()" class="btn btn-outline-primary me-2">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>

                <?php if (!empty($prestamos_activos)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Total de Préstamos Activos: <?php echo count($prestamos_activos); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Email</th>
                                            <th>Libro</th>
                                            <th>Autor</th>
                                            <th>Fecha Préstamo</th>
                                            <th>Fecha Límite</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prestamos_activos as $prestamo): ?>
                                            <tr class="<?php echo $prestamo['dias_retraso'] > 0 ? 'table-danger' : ''; ?>">
                                                <td><?php echo htmlspecialchars($prestamo['usuario_nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($prestamo['email']); ?></td>
                                                <td><?php echo htmlspecialchars($prestamo['titulo']); ?></td>
                                                <td><?php echo htmlspecialchars($prestamo['autor']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_limite'])); ?></td>
                                                <td>
                                                    <?php if ($prestamo['dias_retraso'] > 0): ?>
                                                        <span class="badge bg-danger">
                                                            Vencido (<?php echo $prestamo['dias_retraso']; ?> días)
                                                        </span>
                                                    <?php elseif ($prestamo['dias_retraso'] == 0): ?>
                                                        <span class="badge bg-warning">Vence Hoy</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Vigente</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay préstamos activos en este momento.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
