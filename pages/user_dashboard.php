<?php
session_start();
include '../includes/db.php'; // Updated path for db.php

// Check if the user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: ../pages/auth/login.php"); // Redirect to login page if not logged in
//     exit;
// }

// Fetch user-specific data from the database
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch total datasets uploaded by the user
$sql = "SELECT COUNT(*) AS `total_datasets` FROM `datasets` WHERE `user_id` = 1";
$stmt = $mysqli->prepare($sql);
// $stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_datasets = $row['total_datasets'];
} else {
    $total_datasets = 0; // Default value if no datasets are found
}

// Fetch recent activities (e.g., last 5 datasets uploaded)
$sql = "SELECT `dataset_name`, `upload_date` FROM `datasets` WHERE `user_id` = 1 ORDER BY `upload_date` DESC LIMIT 5";
$stmt = $mysqli->prepare($sql);
// $stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_activities = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Statistics Compilation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/CSS/userdashboard.css"> <!-- Updated path -->
</head>

<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md p-4 fixed-navbar">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Statistics Compilation</h1>
            <div>
                <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($username); ?></span>
                <a href="../pages/auth/login.html" class="ml-4 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600">Logout</a> <!-- Updated path -->
            </div>
        </div>
    </nav>

    <!-- Sidebar and Main Content -->
    <div class="flex">
        <!-- Sidebar -->
        <aside class="bg-white w-64 min-h-screen p-6 shadow-lg fixed-sidebar">
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800">Menu</h2>
            </div>
            <ul class="space-y-2">
                <!-- Dashboard -->
                <li>
                    <a href="user_dashboard.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-lg transition duration-200">
                        <i class="fas fa-tachometer-alt mr-3 text-blue-500"></i>
                        <span class="font-medium">Dashboard</span>
                    </a>
                </li>
                <!-- Upload Data -->
                <li>
                    <a href="data_upload.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-lg transition duration-200">
                        <i class="fas fa-upload mr-3 text-blue-500"></i>
                        <span class="font-medium">Upload Data</span>
                    </a>
                </li>
                <!-- Reports -->
                <li>
                    <a href="reports.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-lg transition duration-200">
                        <i class="fas fa-file-alt mr-3 text-blue-500"></i>
                        <span class="font-medium">Reports</span>
                    </a>
                </li>
                <!-- Profile -->
                <li>
                    <a href="profile.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-lg transition duration-200">
                        <i class="fas fa-user-circle mr-3 text-blue-500"></i>
                        <span class="font-medium">Profile</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 scrollable-content">
            <h1 class="text-2xl font-bold mb-6">Dashboard Overview</h1>

            <!-- Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Datasets Uploaded -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold">Total Datasets Uploaded</h2>
                    <p class="text-3xl font-bold mt-2"><?php echo $total_datasets; ?></p>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold">Recent Activities</h2>
                    <ul class="mt-2">
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                            <li><?php echo htmlspecialchars($activity['dataset_name']); ?> - <?php echo $activity['upload_date']; ?></li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <!-- Quick Links -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold mb-4">Quick Links</h2>
                    <div class="space-y-3">
                        <!-- Upload Data Link -->
                        <a href="data_upload.php" class="block w-full bg-blue-50 text-blue-600 hover:bg-blue-100 px-4 py-2 rounded-lg transition duration-200 text-center">
                            <i class="fas fa-upload mr-2"></i>Upload Data
                        </a>
                        <!-- Generate Report Link -->
                        <a href="reports.php" class="block w-full bg-blue-50 text-blue-600 hover:bg-blue-100 px-4 py-2 rounded-lg transition duration-200 text-center">
                            <i class="fas fa-file-alt mr-2"></i>Generate Report
                        </a>
                    </div>
                </div>

                <!-- Data Visualization Section -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold mb-4">Data Visualization</h2>
                    <canvas id="dataChart" class="w-full h-64"></canvas>
                </div>
        </main>
    </div>

    <!-- Chart.js for Data Visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
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