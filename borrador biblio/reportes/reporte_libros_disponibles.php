<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] == 'alumno') {
    header('Location: ../dashboard.php');
    exit();
}

// Obtener libros disponibles
$stmt = $pdo->query("
    SELECT l.*, c.nombre as categoria_nombre,
           (l.cantidad - COALESCE(prestados.total, 0)) as disponibles
    FROM libros l
    LEFT JOIN categorias c ON l.categoria_id = c.id
    LEFT JOIN (
        SELECT libro_id, COUNT(*) as total
        FROM prestamos
        WHERE estado = 'activo'
        GROUP BY libro_id
    ) prestados ON l.id = prestados.libro_id
    WHERE l.estado = 'disponible'
    ORDER BY l.titulo
");
$libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libros Disponibles - Biblioteca Escolar</title>
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
                    <h1 class="h2"><i class="fas fa-check-circle me-2"></i>Libros Disponibles</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button onclick="window.print()" class="btn btn-outline-primary me-2">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>

                <?php if (!empty($libros)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Inventario de Libros Disponibles: <?php echo count($libros); ?> títulos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Autor</th>
                                            <th>Editorial</th>
                                            <th>Categoría</th>
                                            <th>ISBN</th>
                                            <th>Total</th>
                                            <th>Disponibles</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($libros as $libro): ?>
                                            <tr class="<?php echo $libro['disponibles'] == 0 ? 'table-warning' : ''; ?>">
                                                <td><?php echo htmlspecialchars($libro['titulo']); ?></td>
                                                <td><?php echo htmlspecialchars($libro['autor']); ?></td>
                                                <td><?php echo htmlspecialchars($libro['editorial']); ?></td>
                                                <td><?php echo htmlspecialchars($libro['categoria_nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($libro['isbn']); ?></td>
                                                <td><?php echo $libro['cantidad']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $libro['disponibles'] > 0 ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo $libro['disponibles']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($libro['disponibles'] > 0): ?>
                                                        <span class="badge bg-success">Disponible</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Agotado</span>
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
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No hay libros disponibles en el inventario.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
