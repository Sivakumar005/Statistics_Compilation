<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's datasets
$datasets_query = "SELECT d.*, COUNT(c.id) as chart_count 
                  FROM datasets d 
                  LEFT JOIN charts c ON d.id = c.dataset_id 
                  WHERE d.user_id = ? 
                  GROUP BY d.id 
                  ORDER BY d.upload_date DESC";

$stmt = $mysqli->prepare($datasets_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$datasets = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Reports - Statistics Project</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        /* Reset default margins and ensure full viewport width */
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
        }
        .report-card {
            transition: all 0.2s ease-in-out;
            background-color: white;
        }
        .report-card:hover {
            transform: translateY(-5px);
            background-color: #f8fafc;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .stat-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2563eb;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
        }
        /* Sidebar styles */
        .fixed-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            transition: transform 0.3s ease-in-out;
            z-index: 50;
        }
        .fixed-sidebar.sidebar-hidden {
            transform: translateX(-100%);
        }
        /* Navbar adjustment to sit beside the sidebar */
        .fixed-navbar {
            position: fixed;
            top: 0;
            left: 16rem;
            right: 0;
            width: auto;
            transition: left 0.3s ease-in-out;
            z-index: 40;
        }
        .fixed-navbar.navbar-expanded {
            left: 0;
            right: 0;
        }
        /* Main content adjustment to avoid overlap with sidebar and navbar */
        .main-content {
            margin-left: 16rem;
            padding-top: 5rem;
            transition: margin-left 0.3s ease-in-out;
        }
        .main-content.content-expanded {
            margin-left: 0;
        }
        /* Active menu item style */
        .menu-item.active {
            background-color: #e0f2fe;
            color: #1e40af;
            font-weight: 600;
        }
        .menu-item.active i {
            color: #1e40af;
        }
        /* Toggle button styles */
        .toggle-btn {
            cursor: pointer;
            padding: 0.5rem;
            color: #1f2937;
            transition: color 0.3s ease-in-out;
        }
        .toggle-btn:hover {
            color: #4f46e5;
        }
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .fixed-sidebar {
                transform: translateX(-100%);
            }
            .fixed-sidebar.sidebar-hidden {
                transform: translateX(-100%);
            }
            .fixed-sidebar:not(.sidebar-hidden) {
                transform: translateX(0);
            }
            .fixed-navbar {
                left: 0;
                right: 0;
                width: auto;
            }
            .fixed-navbar.navbar-expanded {
                left: 0;
                right: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.content-expanded {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>

    <!-- Sidebar and Main Content -->
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 main-content p-8" id="mainContent">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-semibold text-gray-800">Advanced Reports</h1>
                <div class="flex gap-2">
                    <a href="data_upload.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class='bx bx-upload mr-2'></i> New Upload
                    </a>
                    <button onclick="exportReport()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class='bx bx-download mr-2'></i> Export Report
                    </button>
                </div>
            </div>

            <?php if (empty($datasets)): ?>
                <div class="text-center py-12">
                    <i class='bx bx-file text-gray-400 text-6xl'></i>
                    <h3 class="mt-4 text-xl font-medium text-gray-900">No Reports Yet</h3>
                    <p class="mt-2 text-gray-500">Upload your first dataset to get started!</p>
                    <a href="data_upload.php" class="inline-flex items-center mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class='bx bx-upload mr-2'></i> Upload Data
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content Area -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Dataset Selection -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">Select Dataset</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($datasets as $dataset): ?>
                                    <div class="report-card bg-white rounded-lg p-4 cursor-pointer border border-gray-200 hover:border-blue-500 shadow-sm hover:shadow-md" 
                                         onclick="loadDataset(<?php echo $dataset['id']; ?>)">
                                        <h5 class="font-medium text-gray-900"><?php echo htmlspecialchars($dataset['dataset_name']); ?></h5>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Uploaded: <?php echo date('M d, Y', strtotime($dataset['upload_date'])); ?>
                                        </p>
                                        <div class="mt-2 flex items-center text-xs text-gray-500">
                                            <i class="fas fa-chart-bar mr-1"></i>
                                            <span><?php echo $dataset['chart_count']; ?> charts</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Summary Statistics -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">Summary Statistics</h2>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="summaryStats">
                                <!-- Stats will be loaded dynamically -->
                            </div>
                        </div>

                        <!-- Charts Section -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-semibold">Visualizations</h2>
                                <div class="flex gap-2">
                                    <select id="chartType" class="rounded-lg border-gray-300" onchange="updateChart()">
                                        <option value="bar">Bar Chart</option>
                                        <option value="line">Line Chart</option>
                                        <option value="pie">Pie Chart</option>
                                        <option value="scatter">Scatter Plot</option>
                                        <option value="histogram">Histogram</option>
                                    </select>
                                    <button onclick="downloadChart()" class="px-3 py-1 bg-gray-100 rounded-lg hover:bg-gray-200">
                                        <i class='bx bx-download'></i>
                                    </button>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="mainChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Filters -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">Filters</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Columns</label>
                                    <select id="columnSelect" class="w-full rounded-lg border-gray-300" multiple>
                                        <!-- Columns will be loaded dynamically -->
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                                    <div class="flex gap-2">
                                        <input type="date" id="dateStart" class="rounded-lg border-gray-300">
                                        <input type="date" id="dateEnd" class="rounded-lg border-gray-300">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Custom Filter</label>
                                    <input type="text" id="customFilter" placeholder="e.g., value > 100" 
                                           class="w-full rounded-lg border-gray-300">
                                </div>
                                <button onclick="applyFilters()" 
                                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Apply Filters
                                </button>
                            </div>
                        </div>

                        <!-- Correlation Analysis -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">Correlation Analysis</h2>
                            <div class="space-y-4">
                                <select id="correlationVar1" class="w-full rounded-lg border-gray-300">
                                    <option value="">Select Variable 1</option>
                                </select>
                                <select id="correlationVar2" class="w-full rounded-lg border-gray-300">
                                    <option value="">Select Variable 2</option>
                                </select>
                                <button onclick="calculateCorrelation()" 
                                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Calculate Correlation
                                </button>
                                <div id="correlationResult" class="text-center font-medium"></div>
                            </div>
                        </div>

                        <!-- Notes Section -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">Notes</h2>
                            <textarea id="reportNotes" 
                                      class="w-full rounded-lg border-gray-300" 
                                      rows="4" 
                                      placeholder="Add your notes here..."></textarea>
                            <button onclick="saveNotes()" 
                                    class="mt-2 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Save Notes
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    // Sidebar toggle functionality
    document.getElementById('toggleSidebar').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const navbar = document.getElementById('navbar');
        const mainContent = document.getElementById('mainContent');

        sidebar.classList.toggle('sidebar-hidden');
        navbar.classList.toggle('navbar-expanded');
        mainContent.classList.toggle('content-expanded');
    });

    let currentDataset = null;
    let mainChart = null;

    // Initialize Chart.js
    function initChart() {
        const ctx = document.getElementById('mainChart').getContext('2d');
        mainChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    zoom: {
                        zoom: {
                            wheel: { enabled: true },
                            pinch: { enabled: true }
                        }
                    }
                }
            }
        });
    }

    // Load dataset and its statistics
    function loadDataset(datasetId) {
        fetch(`get_dataset_stats.php?id=${datasetId}`)
            .then(response => response.json())
            .then(data => {
                currentDataset = data;
                updateSummaryStats(data.stats);
                updateColumnSelect(data.columns);
                updateChart(data.chartData);
            })
            .catch(error => console.error('Error:', error));
    }

    // Update summary statistics
    function updateSummaryStats(stats) {
        const container = document.getElementById('summaryStats');
        container.innerHTML = `
            <div class="stat-card">
                <div class="stat-value">${stats.mean.toFixed(2)}</div>
                <div class="stat-label">Mean</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">${stats.median.toFixed(2)}</div>
                <div class="stat-label">Median</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">${stats.stdDev.toFixed(2)}</div>
                <div class="stat-label">Std Dev</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">${stats.count}</div>
                <div class="stat-label">Count</div>
            </div>
        `;
    }

    // Update chart type
    function updateChart() {
        if (!currentDataset) return;
        const chartType = document.getElementById('chartType').value;
        mainChart.config.type = chartType;
        mainChart.update();

        // Save chart to database
        const chartData = {
            dataset_id: currentDataset.id,
            chart_type: chartType,
            title: currentDataset.dataset_name + ' - ' + chartType.charAt(0).toUpperCase() + chartType.slice(1) + ' Chart',
            config: JSON.stringify(mainChart.config)
        };

        fetch('save_chart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(chartData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh the chart count
                loadDataset(currentDataset.id);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Download chart as image
    function downloadChart() {
        const link = document.createElement('a');
        link.download = 'chart.png';
        link.href = mainChart.toBase64Image();
        link.click();
    }

    // Export report
    function exportReport() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Add title
        doc.setFontSize(20);
        doc.text('Statistical Report', 20, 20);
        
        // Add summary statistics
        doc.setFontSize(12);
        doc.text('Summary Statistics:', 20, 40);
        const stats = document.getElementById('summaryStats').innerText;
        doc.text(stats, 20, 50);
        
        // Add chart
        const chartImage = mainChart.toBase64Image();
        doc.addImage(chartImage, 'PNG', 20, 70, 170, 100);
        
        // Save PDF
        doc.save('report.pdf');
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        initChart();
    });
    </script>
</body>
</html>
