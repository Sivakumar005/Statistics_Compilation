<?php
require_once '../includes/session.php';
require_once '../includes/db.php';

// Check if user is logged in
requireLogin();

// Update last activity
updateLastActivity();

// Check for session timeout
if (isSessionTimeout()) {
    clearUserSession();
    header("Location: ../auth/login.php?error=Session expired. Please login again.");
    exit;
}

// Fetch user-specific data from the database
$user_id = getCurrentUserId();
$username = getCurrentUsername();

// Fetch total datasets uploaded by the user
$sql = "SELECT COUNT(*) AS `total_datasets` FROM `datasets` WHERE `user_id` = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_datasets = $row['total_datasets'];
} else {
    $total_datasets = 0;
}

// Fetch recent activities (e.g., last 5 datasets uploaded)
$sql = "SELECT `dataset_name`, `upload_date` FROM `datasets` WHERE `user_id` = ? ORDER BY `upload_date` DESC LIMIT 5";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_activities = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Statistics Compilation</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="../includes/styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Sidebar and Navbar Styles */
        .fixed-sidebar {
            position: fixed;
            top: 72px; /* Adjusted to account for navbar height */
            left: 0;
            height: calc(100% - 72px); /* Adjusted to account for navbar height */
            width: 16rem;
            z-index: 10;
            transition: transform 0.3s ease;
        }
        
        .fixed-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 20;
        }
        
        .main-content {
            margin-left: 16rem; /* Width of sidebar */
            transition: margin-left 0.3s ease;
        }
        
        .sidebar-hidden {
            transform: translateX(-16rem);
        }
        
        .content-expanded {
            margin-left: 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .fixed-sidebar {
                transform: translateX(-16rem);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Dashboard card styles */
        .dashboard-card {
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Chart container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>

    <!-- Sidebar and Main Content -->
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 main-content p-8" id="mainContent">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Dashboard Overview</h1>

            <!-- Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Datasets Uploaded -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-database text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-700">Total Datasets</h2>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $total_datasets; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="bg-white p-6 rounded-lg shadow-md col-span-2">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Recent Activity</h2>
                    <div class="mt-4">
                        <?php if ($recent_activities->num_rows > 0): ?>
                            <ul class="space-y-3">
                                <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                                    <li class="flex items-center text-gray-600 border-b border-gray-100 pb-2">
                                        <i class="fas fa-file-upload text-blue-500 mr-3"></i>
                                        <div>
                                            <span class="font-medium"><?php echo htmlspecialchars($activity['dataset_name']); ?></span>
                                            <span class="text-sm text-gray-500 ml-2">
                                                <?php echo date('M d, Y', strtotime($activity['upload_date'])); ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-4">No recent activity</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions and Chart Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Quick Actions -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Quick Actions</h2>
                    <div class="space-y-3">
                        <a href="data_upload.php" class="flex items-center justify-center w-full bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-upload mr-2"></i>Upload Data
                        </a>
                        <a href="reports.php" class="flex items-center justify-center w-full bg-green-600 text-white hover:bg-green-700 px-4 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-chart-bar mr-2"></i>View Reports
                        </a>
                    </div>
                </div>

                <!-- Data Visualization -->
                <div class="bg-white p-6 rounded-lg shadow-md col-span-2">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Data Overview</h2>
                    <div class="h-64">
                        <canvas id="dataChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../includes/scripts.js"></script>
    <script>
    // Example Chart.js configuration
    const ctx = document.getElementById('dataChart').getContext('2d');
    const dataChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Dataset 1', 'Dataset 2', 'Dataset 3'],
            datasets: [{
                label: 'Data Points',
                data: [12, 19, 3],
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>
</html>

