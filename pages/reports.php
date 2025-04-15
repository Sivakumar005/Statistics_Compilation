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
        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
        }

        /* Navbar and Sidebar positioning matching data_upload.php */
        nav#navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 40;
        }

        aside#sidebar {
            position: fixed;
            top: 4rem;
            left: 0;
            width: 16rem; /* Matches w-64 in Tailwind (256px) */
            height: calc(100% - 4rem);
            z-index: 50;
            transition: transform 0.3s ease-in-out; /* Smooth transition for sidebar */
        }

        .main-content {
            margin-left: 16rem;
            padding-top: 6rem;
            transition: margin-left 0.3s ease-in-out; /* Smooth transition for main content */
            min-height: 100vh;
        }

        /* Sidebar hidden state using transform */
        aside#sidebar.hidden {
            transform: translateX(-100%);
        }

        /* Main content expanded state */
        .main-content.content-expanded {
            margin-left: 0;
        }

        /* Original styles below */
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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

        /* Header buttons */
        .header-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .header-buttons a,
        .header-buttons button {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
        }

        .header-buttons a {
            background-color: #2563eb;
            color: white;
            text-decoration: none;
        }

        .header-buttons a:hover {
            background-color: #1d4ed8;
        }

        .header-buttons button {
            background-color: #059669;
            color: white;
            border: none;
        }

        .header-buttons button:hover {
            background-color: #047857;
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
            aside#sidebar {
                transform: translateX(-100%);
            }

            aside#sidebar:not(.hidden) {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .header-buttons {
                flex-direction: column;
                width: 100%;
            }

            .header-buttons a,
            .header-buttons button {
                width: 100%;
                justify-content: center;
            }
        }

        /* Custom styles for notes section */
        #noteTitle::placeholder,
        #noteDescription::placeholder {
            color: #9CA3AF;
        }

        #noteTitle:hover,
        #noteDescription:hover {
            border-color: #93C5FD;
        }

        #noteTitle:focus,
        #noteDescription:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        /* Saved notes card styling */
        .note-card {
            background-color: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            padding: 1rem;
            transition: all 0.2s ease-in-out;
        }

        .note-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Save button loading state */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 1rem;
            height: 1rem;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
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
        <?php include '../includes/sidebar.php';  ?>

        <!-- Main Content -->
        <main class="flex-1 main-content p-8" id="mainContent">
            <h1 class="text-2xl font-semibold text-gray-800 mb-8">Advanced Reports</h1>
            <div class="header-buttons mb-8">
                <a href="data_upload.php">
                    <i class='bx bx-upload mr-2'></i> New Upload
                </a>
                <button onclick="exportReport()">
                    <i class='bx bx-download mr-2'></i> Export Report
                </button>
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

                        <!-- Charts Section -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-semibold">Visualizations</h2>
                                <div class="flex gap-2">
                                    <select id="chartType" class="rounded-lg border-gray-300">
                                        <option value="bar">Bar Chart</option>
                                        <option value="line">Line Chart</option>
                                        <option value="pie">Pie Chart</option>
                                        <option value="scatter">Scatter Plot</option>
                                    </select>
                                    <button onclick="updateChart()" class="px-4 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                        <i class='bx bx-refresh mr-1'></i> Update
                                    </button>
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
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                    <div class="date-inputs-container flex gap-4">
                                        <input type="date" id="dateStart" class="rounded-lg border-gray-300">
                                        <input type="date" id="dateEnd" class="rounded-lg border-gray-300">
                                    </div>
                                </div>
                                <div class="filters-button-container">
                                    <button onclick="applyFilters()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 flex-1">
                                        Apply Filters
                                    </button>
                                    <button onclick="clearFilters()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 flex-1">
                                        Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Notes Section -->
                        <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Notes</h2>
                            <div class="space-y-4">
                                <div>
                                    <label for="noteTitle" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                    <input type="text"
                                        id="noteTitle"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-150 ease-in-out"
                                        placeholder="Enter note title..."
                                        autocomplete="off">
                                </div>
                                <div>
                                    <label for="noteDescription" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea id="noteDescription"
                                        class="w-full h-32 rounded-lg border border-gray-300 px-4 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-150 ease-in-out resize-none"
                                        placeholder="Enter note description..."
                                        rows="4"></textarea>
                                </div>
                                <button onclick="saveNotes()"
                                    class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                                    <i class="fas fa-save mr-2"></i> Save Notes
                                </button>
                            </div>

                            <!-- Saved Notes List -->
                            <div class="mt-8 border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Saved Notes</h3>
                                <div id="savedNotes" class="space-y-4">
                                    <!-- Notes will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Sidebar toggle functionality - Updated to use 'hidden' class
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            // Toggle the 'hidden' class for sidebar
            sidebar.classList.toggle('hidden');

            // Toggle the 'content-expanded' class for main content
            mainContent.classList.toggle('content-expanded');
        });

        let currentDataset = null;
        let mainChart = null;

        // Initialize Chart.js with better default options
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
                        legend: {
                            display: false,
                            labels: {
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                color: '#1f2937' // Dark gray for legend text
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: false, // Initially hidden
                            title: {
                                display: true,
                                text: 'Date',
                                color: '#1f2937',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                color: '#4b5563', // Gray for tick labels
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false // Hide grid lines for cleaner look
                            }
                        },
                        y: {
                            display: false, // Initially hidden
                            title: {
                                display: true,
                                text: 'Value',
                                color: '#1f2937',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                color: '#4b5563',
                                font: {
                                    size: 12
                                },
                                beginAtZero: true
                            },
                            grid: {
                                color: '#e5e7eb' // Light gray grid lines
                            }
                        }
                    }
                }
            });
        }

        // Load dataset and its statistics
        function loadDataset(datasetId) {
            currentDataset = {
                id: datasetId
            };

            // Show chart scales and legend when loading dataset
            mainChart.options.scales.x.display = true;
            mainChart.options.scales.y.display = true;
            mainChart.options.plugins.legend.display = true;

            // Fetch dataset statistics
            fetch(`get_dataset_stats.php?id=${datasetId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received dataset data:', data); // Debug log

                    if (data.error) {
                        throw new Error(data.error);
                    }

                    if (!data.success) {
                        throw new Error('Failed to load dataset statistics');
                    }

                    // Update currentDataset with the full data
                    currentDataset = {
                        id: datasetId,
                        dataset_name: data.dataset_name,
                        stats: data.stats,
                        columns: data.columns
                    };
                })
                .catch(error => {
                    console.error('Error fetching dataset stats:', error);
                    // Hide chart scales on error
                    mainChart.options.scales.x.display = false;
                    mainChart.options.scales.y.display = false;
                    mainChart.options.plugins.legend.display = false;
                    mainChart.update();
                });

            // Fetch chart data
            fetch(`get_chart_data.php?id=${datasetId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const chart = data;
                        // Update chart type selector
                        document.getElementById('chartType').value = chart.type;

                        // Update chart display
                        mainChart.config.type = chart.type;
                        mainChart.config.data = {
                            labels: chart.labels,
                            datasets: [{
                                label: chart.label,
                                data: chart.values,
                                backgroundColor: chart.backgroundColor,
                                borderColor: chart.borderColor,
                                borderWidth: 1
                            }]
                        };

                        // Ensure scales are visible and properly labeled
                        mainChart.options.scales.x.display = true;
                        mainChart.options.scales.y.display = true;
                        mainChart.options.plugins.legend.display = true;

                        // Update axis titles based on chart type
                        mainChart.options.scales.x.title.text = chart.type === 'pie' ? '' : 'Date';
                        mainChart.options.scales.y.title.text = chart.type === 'pie' ? '' : 'Value';

                        mainChart.update();
                    } else {
                        throw new Error(data.error || 'Failed to load chart data');
                    }
                })
                .catch(error => {
                    console.error('Error fetching chart data:', error);
                    // Hide chart scales on error
                    mainChart.options.scales.x.display = false;
                    mainChart.options.scales.y.display = false;
                    mainChart.options.plugins.legend.display = false;
                    mainChart.update();
                });

            // Load saved notes for the selected dataset
            loadSavedNotes();
        }

        // Update chart type
        function updateChart() {
            if (!currentDataset) {
                return;
            }
            const chartType = document.getElementById('chartType').value;

            // Get the current data
            const currentData = mainChart.config.data.datasets[0].data;
            const currentLabels = mainChart.config.data.labels;

            // Special handling for histogram
            if (chartType === 'histogram') {
                // Convert data to histogram bins
                const values = currentData;
                const binCount = Math.ceil(Math.sqrt(values.length)); // Square root rule for bin count
                const min = Math.min(...values);
                const max = Math.max(...values);
                const binWidth = (max - min) / binCount;

                // Create bins
                const bins = Array(binCount).fill(0);
                const binLabels = [];

                // Fill bins
                values.forEach(value => {
                    const binIndex = Math.min(Math.floor((value - min) / binWidth), binCount - 1);
                    bins[binIndex]++;
                });

                // Create labels for bins
                for (let i = 0; i < binCount; i++) {
                    const start = (min + (i * binWidth)).toFixed(1);
                    const end = (min + ((i + 1) * binWidth)).toFixed(1);
                    binLabels.push(`${start}-${end}`);
                }

                // Update chart with histogram data
                mainChart.config.type = 'bar';
                mainChart.config.data = {
                    labels: binLabels,
                    datasets: [{
                        label: 'Frequency',
                        data: bins,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                };
                mainChart.config.options = {
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Value Ranges',
                                color: '#1f2937',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                color: '#4b5563',
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Frequency',
                                color: '#1f2937',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                color: '#4b5563',
                                font: {
                                    size: 12
                                },
                                beginAtZero: true
                            },
                            grid: {
                                color: '#e5e7eb'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                };
            } else {
                // Regular chart types
                mainChart.config.type = chartType;
                mainChart.config.data = {
                    labels: currentLabels,
                    datasets: [{
                        label: currentDataset.dataset_name,
                        data: currentData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                };
                // Ensure scales are visible and properly labeled
                mainChart.options.scales.x.display = true;
                mainChart.options.scales.y.display = true;
                mainChart.options.plugins.legend.display = true;
                mainChart.options.scales.x.title.text = chartType === 'pie' ? '' : 'Date';
                mainChart.options.scales.y.title.text = chartType === 'pie' ? '' : 'Value';
            }

            mainChart.update();

            // First check if a chart already exists for this dataset
            fetch(`get_chart_data.php?id=${currentDataset.id}`)
                .then(response => response.json())
                .then(data => {
                    // Prepare chart data
                    const chartData = {
                        dataset_id: currentDataset.id,
                        chart_type: chartType,
                        title: currentDataset.dataset_name + ' - ' + chartType.charAt(0).toUpperCase() + chartType.slice(1) + ' Chart',
                        config: JSON.stringify(mainChart.config),
                        chart_data: JSON.stringify({
                            labels: mainChart.config.data.labels,
                            data: mainChart.config.data.datasets[0].data
                        })
                    };

                    // If chart exists, update it; otherwise create new
                    const url = data.success ? 'update_chart.php' : 'save_chart.php';
                    const method = data.success ? 'PUT' : 'POST';

                    // Log the data being sent
                    console.log('Sending chart data:', chartData);

                    // Save/Update chart in database
                    fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(chartData)
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Server response:', data);
                            if (data.success) {
                                // Show success message
                                const button = document.querySelector('button[onclick="updateChart()"]');
                                const originalText = button.innerHTML;
                                button.innerHTML = '<i class="bx bx-check mr-1"></i> Updated!';
                                button.classList.add('bg-green-600', 'hover:bg-green-700');

                                // Reset button after 2 seconds
                                setTimeout(() => {
                                    button.innerHTML = originalText;
                                    button.classList.remove('bg-green-600', 'hover:bg-green-700');
                                }, 2000);

                                // Refresh the chart count
                                loadDataset(currentDataset.id);
                            } else {
                                console.error('Error saving chart:', data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                })
                .catch(error => {
                    console.error('Error checking existing chart:', error);
                });
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
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Add title
            doc.setFontSize(20);
            doc.text('Statistical Report', 20, 20);

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

        // Apply date range filters
        function applyFilters() {
            const dateStart = document.getElementById('dateStart').value;
            const dateEnd = document.getElementById('dateEnd').value;

            // Validate dates
            if (!dateStart || !dateEnd) {
                alert('Please select both start and end dates');
                return;
            }

            // Show loading state on button
            const button = document.querySelector('button[onclick="applyFilters()"]');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
            button.disabled = true;

            // Reset current dataset and chart
            currentDataset = null;
            mainChart.data.labels = [];
            mainChart.data.datasets = [];
            mainChart.options.scales.x.display = false;
            mainChart.options.scales.y.display = false;
            mainChart.options.plugins.legend.display = false;
            mainChart.update();

            // Fetch datasets within date range
            fetch(`get_filtered_datasets.php?start_date=${dateStart}&end_date=${dateEnd}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update dataset cards
                        const datasetContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.gap-4');
                        if (data.datasets.length === 0) {
                            datasetContainer.innerHTML = `
                            <div class="col-span-full text-center py-8">
                                <p class="text-gray-500">No datasets found in the selected date range</p>
                            </div>
                        `;
                        } else {
                            datasetContainer.innerHTML = data.datasets.map(dataset => `
                            <div class="report-card bg-white rounded-lg p-4 cursor-pointer border border-gray-200 hover:border-blue-500 shadow-sm hover:shadow-md" 
                                 onclick="loadDataset(${dataset.id})">
                                <h5 class="font-medium text-gray-900">${dataset.dataset_name}</h5>
                                <p class="text-sm text-gray-500 mt-1">
                                    Uploaded: ${new Date(dataset.upload_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                </p>
                                <div class="mt-2 flex items-center text-xs text-gray-500">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    <span>${dataset.chart_count} charts</span>
                                </div>
                            </div>
                        `).join('');
                        }

                        // Show success state
                        button.innerHTML = '<i class="fas fa-check"></i> Applied';
                        button.classList.add('bg-green-600', 'hover:bg-green-700');
                    } else {
                        throw new Error(data.error || 'Failed to apply filters');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show error state
                    button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                    button.classList.add('bg-red-600', 'hover:bg-red-700');
                })
                .finally(() => {
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.classList.remove('bg-green-600', 'hover:bg-green-700', 'bg-red-600', 'hover:bg-red-700');
                        button.disabled = false;
                    }, 2000);
                });
        }

        // Clear filters and reset to initial state
        function clearFilters() {
            // Clear date inputs
            document.getElementById('dateStart').value = '';
            document.getElementById('dateEnd').value = '';

            // Reset current dataset and chart
            currentDataset = null;
            mainChart.data.labels = [];
            mainChart.data.datasets = [];
            mainChart.options.scales.x.display = false;
            mainChart.options.scales.y.display = false;
            mainChart.options.plugins.legend.display = false;
            mainChart.update();

            // Show loading state on button
            const button = document.querySelector('button[onclick="clearFilters()"]');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
            button.disabled = true;

            // Fetch all datasets
            fetch('get_filtered_datasets.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Update dataset cards
                        const datasetContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.gap-4');
                        if (data.datasets.length === 0) {
                            datasetContainer.innerHTML = `
                            <div class="col-span-full text-center py-8">
                                <p class="text-gray-500">No datasets found</p>
                            </div>
                        `;
                        } else {
                            datasetContainer.innerHTML = data.datasets.map(dataset => `
                            <div class="report-card bg-white rounded-lg p-4 cursor-pointer border border-gray-200 hover:border-blue-500 shadow-sm hover:shadow-md" 
                                 onclick="loadDataset(${dataset.id})">
                                <h5 class="font-medium text-gray-900">${dataset.dataset_name}</h5>
                                <p class="text-sm text-gray-500 mt-1">
                                    Uploaded: ${new Date(dataset.upload_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                </p>
                                <div class="mt-2 flex items-center text-xs text-gray-500">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    <span>${dataset.chart_count} charts</span>
                                </div>
                            </div>
                        `).join('');
                        }

                        // Show success state
                        button.innerHTML = '<i class="fas fa-check"></i> Cleared';
                        button.classList.add('bg-green-600', 'hover:bg-green-700', 'text-white');
                    } else {
                        throw new Error(data.error || 'Failed to clear filters');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show error state
                    button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                    button.classList.add('bg-red-600', 'hover:bg-red-700', 'text-white');
                })
                .finally(() => {
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.classList.remove('bg-green-600', 'hover:bg-green-700', 'bg-red-600', 'hover:bg-red-700', 'text-white');
                        button.disabled = false;
                    }, 2000);
                });
        }

        // Function to load and display saved notes
        function loadSavedNotes() {
            if (!currentDataset) return;

            fetch(`get_notes.php?dataset_id=${currentDataset.id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const notesContainer = document.getElementById('savedNotes');
                        if (data.notes.length === 0) {
                            notesContainer.innerHTML = `
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-sticky-note text-4xl mb-2"></i>
                                <p class="text-sm">No notes saved yet</p>
                            </div>
                        `;
                        } else {
                            notesContainer.innerHTML = data.notes.map(note => `
                            <div class="note-card group">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900">${note.title}</h4>
                                        <p class="text-sm text-gray-600 mt-1">${note.description}</p>
                                        <p class="text-xs text-gray-400 mt-2">${new Date(note.created_at).toLocaleString()}</p>
                                    </div>
                                    <button onclick="deleteNote(${note.id})" 
                                            class="ml-4 text-gray-400 hover:text-red-500 transition-colors duration-150 opacity-0 group-hover:opacity-100">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading notes:', error);
                    document.getElementById('savedNotes').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-circle text-4xl mb-2"></i>
                        <p class="text-sm">Error loading notes</p>
                    </div>
                `;
                });
        }

        // Update the saveNotes function to handle loading state
        function saveNotes() {
            const title = document.getElementById('noteTitle').value.trim();
            const description = document.getElementById('noteDescription').value.trim();
            const button = document.querySelector('button[onclick="saveNotes()"]');
            const originalContent = button.innerHTML;

            if (!title) {
                showError('Please enter a title for your note');
                return;
            }

            if (!description) {
                showError('Please enter a description for your note');
                return;
            }

            if (!currentDataset || !currentDataset.id) {
                showError('Please select a dataset first');
                return;
            }

            // Show loading state
            button.disabled = true;
            button.classList.add('btn-loading');
            button.innerHTML = '<span class="opacity-0">Saving...</span>';

            // Save note to database
            fetch('save_note.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: title,
                        description: description,
                        dataset_id: currentDataset.id
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.error || 'Failed to save note');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Clear form
                        document.getElementById('noteTitle').value = '';
                        document.getElementById('noteDescription').value = '';

                        // Show success state
                        button.classList.remove('btn-loading');
                        button.classList.add('bg-green-600');
                        button.innerHTML = '<i class="fas fa-check mr-2"></i> Saved!';

                        // Reload saved notes
                        loadSavedNotes();
                    } else {
                        throw new Error(data.error || 'Failed to save note');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError(error.message || 'Failed to save note. Please try again.');
                    button.classList.remove('btn-loading');
                    button.classList.add('bg-red-600');
                    button.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Error';
                })
                .finally(() => {
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        button.disabled = false;
                        button.classList.remove('bg-green-600', 'bg-red-600');
                        button.innerHTML = originalContent;
                    }, 2000);
                });
        }

        // Function to show error messages
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-md transition-opacity duration-500';
            errorDiv.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>${message}</span>
            </div>
        `;
            document.body.appendChild(errorDiv);

            // Remove the error message after 3 seconds
            setTimeout(() => {
                errorDiv.style.opacity = '0';
                setTimeout(() => errorDiv.remove(), 500);
            }, 3000);
        }

        // Function to delete a note
        function deleteNote(noteId) {
            if (!confirm('Are you sure you want to delete this note?')) {
                return;
            }

            fetch('delete_note.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        note_id: noteId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadSavedNotes();
                    } else {
                        throw new Error(data.error || 'Failed to delete note');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete note');
                });
        }
    </script>
</body>

</html>