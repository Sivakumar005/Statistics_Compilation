<?php
session_start();
require_once '../includes/config.php';
require_once '../../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Check if report ID is provided
if (!isset($_GET['id'])) {
    header('Location: reports.php');
    exit();
}

$report_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch report details
$report_query = "SELECT * FROM uploads WHERE id = ? AND user_id = ?";
$stmt = $pdo->prepare($report_query);
$stmt->execute([$report_id, $user_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    header('Location: reports.php');
    exit();
}

// Fetch charts associated with this report
$charts_query = "SELECT * FROM charts WHERE upload_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($charts_query);
$stmt->execute([$report_id]);
$charts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Report - Statistics Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .chart-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .chart-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><?php echo htmlspecialchars($report['filename']); ?></h1>
                <p class="text-muted mb-0">
                    Uploaded on <?php echo date('M d, Y', strtotime($report['created_at'])); ?>
                </p>
            </div>
            <div>
                <a href="reports.php" class="btn btn-outline-secondary me-2">
                    <i class='bx bx-arrow-back'></i> Back to Reports
                </a>
                <a href="create_chart.php?upload_id=<?php echo $report_id; ?>" class="btn btn-primary">
                    <i class='bx bx-plus'></i> Create New Chart
                </a>
            </div>
        </div>

        <?php if (empty($charts)): ?>
            <div class="text-center py-5">
                <i class='bx bx-bar-chart-alt-2' style="font-size: 4rem; color: #6c757d;"></i>
                <h3 class="mt-3">No Charts Yet</h3>
                <p class="text-muted">Create your first chart to visualize your data!</p>
                <a href="create_chart.php?upload_id=<?php echo $report_id; ?>" class="btn btn-primary mt-3">
                    Create Chart
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($charts as $chart): ?>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-header">
                                <h5 class="mb-0"><?php echo htmlspecialchars($chart['title']); ?></h5>
                                <div class="chart-actions">
                                    <a href="edit_chart.php?id=<?php echo $chart['id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class='bx bx-edit'></i>
                                    </a>
                                    <a href="delete_chart.php?id=<?php echo $chart['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Are you sure you want to delete this chart?')">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </div>
                            </div>
                            <div class="chart-preview">
                                <img src="../assets/charts/<?php echo $chart['id']; ?>.png" 
                                     alt="<?php echo htmlspecialchars($chart['title']); ?>" 
                                     class="img-fluid">
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">
                                    Created on <?php echo date('M d, Y', strtotime($chart['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 