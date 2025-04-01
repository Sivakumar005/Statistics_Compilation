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

// Include header
include '../includes/header.php';
?>

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


<?php include '../includes/footer.php'; ?>

</html>

