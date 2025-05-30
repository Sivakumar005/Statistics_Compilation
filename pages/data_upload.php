<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/config.php';

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

$user_id = getCurrentUserId();
$message = '';
$error = '';

// Clear chart data on initial page load or refresh (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['chart_data']);
}

// Handle file upload and store data in session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dataset_file'])) {
    $dataset_name = $_POST['dataset_name'];
    $chart_type = isset($_POST['chart_type']) ? $_POST['chart_type'] : 'bar';
    
    // Check if dataset name already exists for this user
    $check_query = "SELECT COUNT(*) as count FROM datasets WHERE user_id = ? AND dataset_name = ?";
    $check_stmt = $mysqli->prepare($check_query);
    $check_stmt->bind_param("is", $user_id, $dataset_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $error = "A dataset with this name already exists. Please choose a different name.";
    } else {
        $file_name = basename($_FILES['dataset_file']['name']);
        $unique_filename = time() . "_" . sanitizeFileName($file_name);
        $file_path = UPLOAD_DIR . $unique_filename;
        
        // Debug information
        error_log("Upload directory: " . UPLOAD_DIR);
        error_log("File path: " . $file_path);
        error_log("Upload directory exists: " . (file_exists(UPLOAD_DIR) ? 'Yes' : 'No'));
        error_log("Upload directory is writable: " . (is_writable(UPLOAD_DIR) ? 'Yes' : 'No'));
        
        $allowed_types = array('csv', 'xlsx', 'xls', 'json', 'txt');
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $error = "Only CSV, XLSX, XLS, JSON, and TXT files are allowed";
        } elseif (move_uploaded_file($_FILES['dataset_file']['tmp_name'], $file_path)) {
            // Insert into datasets table
            $query = "INSERT INTO datasets (user_id, dataset_name, file_path, upload_date) VALUES (?, ?, ?, NOW())";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("iss", $user_id, $dataset_name, $unique_filename);
            
            if ($stmt->execute()) {
                $dataset_id = $mysqli->insert_id;
                $success = "Dataset uploaded successfully! Your visualization is ready below.";

                $labels = [];
                $values = [];

                if ($file_ext === 'csv') {
                    // Read the CSV file
                    $file_handle = fopen($file_path, 'r');
                    
                    // Skip the header row but store the column names
                    $headers = fgetcsv($file_handle);
                    
                    // Read the data rows
                    while (($row = fgetcsv($file_handle)) !== false) {
                        if (isset($row[0], $row[1])) {
                            // First column is Label, second column is Value
                            $labels[] = trim($row[0]); // Trim to remove any whitespace
                            $values[] = is_numeric($row[1]) ? floatval($row[1]) : 0; // Convert to float or 0 if not numeric
                        }
                    }
                    fclose($file_handle);
                    
                    // Debug the parsed data
                    error_log("Parsed Labels: " . print_r($labels, true));
                    error_log("Parsed Values: " . print_r($values, true));
                } elseif ($file_ext === 'json') {
                    $json_data = json_decode(file_get_contents($file_path), true);
                    if (is_array($json_data)) {
                        $labels = array_column($json_data, 'label');
                        $values = array_column($json_data, 'value');
                    }
                } elseif ($file_ext === 'txt') {
                    $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $data = array_map('str_getcsv', $lines);
                    array_shift($data);
                    foreach ($data as $row) {
                        if (isset($row[0], $row[1])) {
                            $labels[] = trim($row[0]);
                            $values[] = is_numeric($row[1]) ? floatval($row[1]) : 0;
                        }
                    }
                }

                if (!empty($labels) && !empty($values) && is_array($labels) && is_array($values) && count($labels) === count($values)) {
                    $_SESSION['chart_data'] = [
                        'labels' => $labels,
                        'values' => $values,
                        'dataset_name' => $dataset_name,
                        'chart_type' => $chart_type
                    ];

                    // Save initial chart to database
                    $chart_config = json_encode([
                        'type' => $chart_type,
                        'data' => [
                            'labels' => $labels,
                            'datasets' => [[
                                'label' => $dataset_name,
                                'data' => $values,
                                'backgroundColor' => [
                                    'rgba(255, 99, 132, 0.2)',
                                    'rgba(54, 162, 235, 0.2)',
                                    'rgba(255, 206, 86, 0.2)',
                                    'rgba(75, 192, 192, 0.2)',
                                    'rgba(153, 102, 255, 0.2)',
                                    'rgba(255, 159, 64, 0.2)'
                                ],
                                'borderColor' => [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 206, 86, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(153, 102, 255, 1)',
                                    'rgba(255, 159, 64, 1)'
                                ],
                                'borderWidth' => 1
                            ]]
                        ],
                        'options' => [
                            'responsive' => true,
                            'plugins' => [
                                'legend' => [
                                    'position' => 'top',
                                ],
                                'title' => [
                                    'display' => true,
                                    'text' => $dataset_name
                                ]
                            ]
                        ]
                    ]);

                    $chart_query = "INSERT INTO charts (dataset_id, chart_type, title, config, chart_data, created_at) 
                                   VALUES (?, ?, ?, ?, ?, NOW())";
                    $chart_data = json_encode([
                        'labels' => $labels,
                        'data' => $values
                    ]);
                    $chart_stmt = $mysqli->prepare($chart_query);
                    $chart_title = $dataset_name . ' - Initial Chart';
                    $chart_stmt->bind_param("issss", 
                        $dataset_id,
                        $chart_type,
                        $chart_title,
                        $chart_config,
                        $chart_data
                    );
                    $chart_stmt->execute();

                    $success = "Dataset uploaded successfully! Your visualization is ready below.";
                } else {
                    $error = "Invalid or empty data in the uploaded file.";
                    // Delete the dataset record if data parsing failed
                    $delete_query = "DELETE FROM datasets WHERE id = ?";
                    $delete_stmt = $mysqli->prepare($delete_query);
                    $delete_stmt->bind_param("i", $dataset_id);
                    $delete_stmt->execute();
                    unset($_SESSION['chart_data']);
                }
            } else {
                $error = "Error saving to database: " . $mysqli->error;
                // Remove uploaded file if database insert failed
                unlink($file_path);
            }
        } else {
            $error = "Error uploading file. Please try again.";
        }
    }
}

// Handle chart type change without re-uploading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_chart_type'])) {
    if (isset($_SESSION['chart_data'])) {
        $_SESSION['chart_data']['chart_type'] = $_POST['new_chart_type'];
    }
}

// Handle clear chart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_chart'])) {
    unset($_SESSION['chart_data']);
    header("Location: data_upload.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Upload - Statistics Compilation</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="../includes/styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Form input styles */
        input[type="text"] {
            background-color: #ffffff;
            border: 2px solid #d1d5db;
            color: #1f2937;
            padding: 8px 12px;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        input[type="text"]:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            outline: none;
        }
        select {
            background-color: #ffffff;
            border: 2px solid #d1d5db;
            color: #1f2937;
            padding: 8px 12px;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>');
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.5em;
        }
        select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            outline: none;
        }
        select option {
            background-color: #ffffff;
            color: #1f2937;
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
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Upload Your Dataset</h1>

            <!-- Upload Form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label for="dataset_name" class="block text-sm font-medium text-gray-700">Dataset Name</label>
                        <input type="text" name="dataset_name" id="dataset_name" required class="mt-1 block w-full rounded-md">
                    </div>
                    <div>
                        <label for="dataset_file" class="block text-sm font-medium text-gray-700">Select File (CSV, XLSX, XLS, JSON, TXT)</label>
                        <input type="file" name="dataset_file" id="dataset_file" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <div>
                        <label for="chart_type" class="block text-sm font-medium text-gray-700">Initial Chart Type</label>
                        <select name="chart_type" id="chart_type" required class="mt-1 block w-full rounded-md">
                            <option value="bar">Bar</option>
                            <option value="line">Line</option>
                            <option value="pie">Pie</option>
                            <option value="histogram">Histogram</option>
                            <option value="scatter">Scatter</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                        Upload Dataset
                    </button>
                </form>

                <?php if (!empty($success)): ?>
                    <div class="mt-4 p-4 bg-green-100 text-green-700 rounded-md"><?php echo $success; ?></div>
                <?php elseif (!empty($error)): ?>
                    <div class="mt-4 p-4 bg-red-100 text-red-700 rounded-md"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>

            <!-- Chart Display -->
            <div class="mt-8 bg-white rounded-lg shadow-md <?php echo isset($_SESSION['chart_data']) ? 'p-6' : 'p-4'; ?>">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Data Visualization</h2>
                <?php if (!isset($_SESSION['chart_data'])): ?>
                    <div class="text-center text-gray-500">
    <i class="fas fa-chart-line fa-2x text-gray-400 mb-2 block"></i>
    <p>Upload a file to visualize your data.</p>
</div>
                <?php else: ?>
                    <div class="mb-4 flex justify-between items-center">
                        <form action="" method="POST" class="flex items-center space-x-4">
                            <input type="hidden" name="change_chart_type" value="1">
                            <label for="new_chart_type" class="text-sm font-medium text-gray-700">Change Chart Type:</label>
                            <select name="new_chart_type" id="new_chart_type" class="rounded-md">
                                <option value="bar" <?php echo $_SESSION['chart_data']['chart_type'] === 'bar' ? 'selected' : ''; ?>>Bar</option>
                                <option value="line" <?php echo $_SESSION['chart_data']['chart_type'] === 'line' ? 'selected' : ''; ?>>Line</option>
                                <option value="pie" <?php echo $_SESSION['chart_data']['chart_type'] === 'pie' ? 'selected' : ''; ?>>Pie</option>
                                <option value="histogram" <?php echo $_SESSION['chart_data']['chart_type'] === 'histogram' ? 'selected' : ''; ?>>Histogram</option>
                                <option value="scatter" <?php echo $_SESSION['chart_data']['chart_type'] === 'scatter' ? 'selected' : ''; ?>>Scatter</option>
                            </select>
                            <button type="submit" class="py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                                Update Chart
                            </button>
                        </form>
                        <form action="" method="POST">
                            <input type="hidden" name="clear_chart" value="1">
                            <button type="submit" onclick="return confirm('Are you sure you want to clear the chart?');" class="py-2 px-4 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                                Clear Chart
                            </button>
                        </form>
                    </div>
                    <div class="w-full max-w-2xl mx-auto">
                        <canvas id="dataChart" height="400"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../includes/scripts.js"></script>
    <script>
    // Chart rendering
    <?php
    if (isset($_SESSION['chart_data'])) {
        $chart_data = $_SESSION['chart_data'];
        $labels = $chart_data['labels'];
        $values = $chart_data['values'];

        $colorPalette = [
            'rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)',
            'rgba(75, 192, 192, 0.2)', 'rgba(153, 102, 255, 0.2)', 'rgba(255, 159, 64, 0.2)'
        ];
        $borderColorPalette = [
            'rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)'
        ];

        // Prepare data for non-scatter charts
        $backgroundColors = [];
        $borderColors = [];
        for ($i = 0; $i < count($values); $i++) {
            $backgroundColors[] = $colorPalette[$i % count($colorPalette)];
            $borderColors[] = $borderColorPalette[$i % count($borderColorPalette)];
        }

        // Prepare data for scatter chart (map labels to numerical x-coordinates)
        $scatterData = [];
        for ($i = 0; $i < count($values); $i++) {
            $scatterData[] = [
                'x' => $i + 1,  // Use numerical index for x-coordinate
                'y' => floatval($values[$i])
            ];
        }

        // Data for non-scatter charts (bar, line, pie, histogram)
        echo "const standardChartData = {
            labels: " . json_encode($labels) . ",
            datasets: [{
                label: '" . htmlspecialchars($chart_data['dataset_name']) . "',
                data: " . json_encode(array_map('floatval', $values)) . ",
                backgroundColor: " . json_encode($backgroundColors) . ",
                borderColor: " . json_encode($borderColors) . ",
                borderWidth: 1
            }]
        };";

        // Data for scatter chart
        echo "const scatterChartData = {
            datasets: [{
                label: '" . htmlspecialchars($chart_data['dataset_name']) . "',
                data: " . json_encode($scatterData) . ",
                backgroundColor: " . json_encode($backgroundColors) . ",
                borderColor: " . json_encode($borderColors) . ",
                borderWidth: 1,
                pointRadius: 5
            }]
        };";

        echo "const chartType = '" . htmlspecialchars($chart_data['chart_type']) . "';";
        echo "const chartLabels = " . json_encode($labels) . ";";  // Store labels separately
    } else {
        echo "const standardChartData = null;";
        echo "const scatterChartData = null;";
        echo "const chartType = null;";
    }
    ?>

    let chartInstance = null;

    function renderChart(standardData, scatterData, type) {
        const ctx = document.getElementById('dataChart').getContext('2d');
        
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        if (standardData && scatterData && type) {
            let chartJsType = type;
            let dataToUse = standardData;

            // Adjust chart type and data for specific charts
            if (type === 'histogram') {
                chartJsType = 'bar';
            } else if (type === 'scatter') {
                chartJsType = 'scatter';
                dataToUse = scatterData;
            }

            const options = {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: standardData.datasets[0].label
                    }
                }
            };

            // Add specific options for pie chart
            if (type === 'pie') {
                options.plugins.tooltip = {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                };
            } else {
                // Add scales for non-pie charts
                options.scales = {
                    y: {
                        beginAtZero: true,
                        display: type === 'bar' || type === 'line' || type === 'histogram' || type === 'scatter',
                        title: {
                            display: true,
                            text: 'Value'
                        }
                    },
                    x: {
                        display: type !== 'pie',
                        title: {
                            display: type === 'scatter',
                            text: 'Data Points'
                        },
                        ticks: type === 'scatter' ? {
                            callback: function(value) {
                                return chartLabels[value - 1] || '';
                            }
                        } : {
                            callback: function(value) {
                                return chartLabels[value] || '';
                            }
                        }
                    }
                };
            }

            chartInstance = new Chart(ctx, {
                type: chartJsType,
                data: dataToUse,
                options: options
            });
        }
    }

    window.onload = function() {
        renderChart(standardChartData, scatterChartData, chartType);
    };
    </script>
</body>
</html>