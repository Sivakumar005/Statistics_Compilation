// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    let currentDatasetId = null;
    let currentChart = null;
    let notes = [];

    // Load dataset function
    window.loadDataset = function(datasetId) {
        currentDatasetId = datasetId;
        
        // Show loading state
        const chartContainer = document.querySelector('.chart-container');
        chartContainer.innerHTML = '<div class="flex items-center justify-center h-full"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div></div>';
        
        // Fetch dataset data
        fetch(`get_dataset.php?id=${datasetId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to load dataset');
                }
                
                // Reset chart container
                chartContainer.innerHTML = '<canvas id="mainChart"></canvas>';
                
                // Update chart with new data
                updateChart(data);
                
                // Load notes for this dataset
                loadNotes(datasetId);
            })
            .catch(error => {
                console.error('Error loading dataset:', error);
                alert('Error loading dataset: ' + error.message);
                // Reset chart container
                chartContainer.innerHTML = '<canvas id="mainChart"></canvas>';
            });
    };

    // Delete dataset function
    window.deleteDataset = function(event, datasetId) {
        event.stopPropagation(); // Prevent triggering the parent click event
        
        if (confirm('Are you sure you want to delete this dataset? This action cannot be undone.')) {
            fetch(`delete_dataset.php?id=${datasetId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the dataset card from the DOM
                    const datasetCard = event.target.closest('.report-card');
                    datasetCard.remove();
                    
                    // If this was the current dataset, clear the chart
                    if (currentDatasetId === datasetId) {
                        currentDatasetId = null;
                        const chartContainer = document.querySelector('.chart-container');
                        chartContainer.innerHTML = '<canvas id="mainChart"></canvas>';
                    }
                } else {
                    alert('Error deleting dataset: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting dataset:', error);
                alert('Error deleting dataset. Please try again.');
            });
        }
    };

    // Update chart function
    window.updateChart = function() {
        if (!currentDatasetId) {
            alert('Please select a dataset first.');
            return;
        }

        const chartType = document.getElementById('chartType').value;
        
        // Fetch updated data with current filters
        const dateStart = document.getElementById('dateStart').value;
        const dateEnd = document.getElementById('dateEnd').value;
        
        fetch(`get_dataset.php?id=${currentDatasetId}&type=${chartType}&start=${dateStart}&end=${dateEnd}`)
            .then(response => response.json())
            .then(data => {
                updateChart(data);
            })
            .catch(error => {
                console.error('Error updating chart:', error);
                alert('Error updating chart. Please try again.');
            });
    };

    // Helper function to update the chart
    function updateChart(data) {
        const ctx = document.getElementById('mainChart').getContext('2d');
        
        // Destroy existing chart if it exists
        if (currentChart) {
            currentChart.destroy();
        }
        
        // Create new chart
        currentChart = new Chart(ctx, {
            type: data.type || 'bar',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: data.label || 'Data',
                    data: data.values || [],
                    backgroundColor: data.backgroundColor || 'rgba(54, 162, 235, 0.2)',
                    borderColor: data.borderColor || 'rgba(54, 162, 235, 1)',
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
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }

    // Download chart function
    window.downloadChart = function() {
        if (!currentChart) {
            alert('No chart to download.');
            return;
        }

        const link = document.createElement('a');
        link.download = `chart-${currentDatasetId}-${new Date().toISOString().slice(0,10)}.png`;
        link.href = currentChart.toBase64Image();
        link.click();
    };

    // Apply filters function
    window.applyFilters = function() {
        if (!currentDatasetId) {
            alert('Please select a dataset first.');
            return;
        }

        updateChart();
    };

    // Clear filters function
    window.clearFilters = function() {
        document.getElementById('dateStart').value = '';
        document.getElementById('dateEnd').value = '';
        
        if (currentDatasetId) {
            updateChart();
        }
    };

    // Load notes function
    function loadNotes(datasetId) {
        fetch(`get_notes.php?dataset_id=${datasetId}`)
            .then(response => response.json())
            .then(data => {
                notes = data;
                displayNotes();
            })
            .catch(error => {
                console.error('Error loading notes:', error);
                alert('Error loading notes. Please try again.');
            });
    }

    // Save note function
    window.saveNote = function() {
        if (!currentDatasetId) {
            alert('Please select a dataset first.');
            return;
        }

        const title = document.getElementById('noteTitle').value;
        const content = document.getElementById('noteContent').value;

        if (!title || !content) {
            alert('Please fill in both title and content.');
            return;
        }

        fetch('save_note.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                dataset_id: currentDatasetId,
                title: title,
                content: content
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear input fields
                document.getElementById('noteTitle').value = '';
                document.getElementById('noteContent').value = '';
                
                // Reload notes
                loadNotes(currentDatasetId);
            } else {
                alert('Error saving note: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error saving note:', error);
            alert('Error saving note. Please try again.');
        });
    };

    // Display notes function
    function displayNotes() {
        const notesList = document.getElementById('notesList');
        notesList.innerHTML = '';

        notes.forEach(note => {
            const noteElement = document.createElement('div');
            noteElement.className = 'bg-gray-50 p-4 rounded-lg';
            noteElement.innerHTML = `
                <h3 class="font-medium text-gray-900">${note.title}</h3>
                <p class="text-sm text-gray-600 mt-1">${note.content}</p>
                <div class="mt-2 text-xs text-gray-500">
                    ${new Date(note.created_at).toLocaleString()}
                </div>
                <button onclick="deleteNote(${note.id})" 
                        class="mt-2 text-red-500 hover:text-red-700 text-sm">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            `;
            notesList.appendChild(noteElement);
        });
    }

    // Delete note function
    window.deleteNote = function(noteId) {
        if (confirm('Are you sure you want to delete this note?')) {
            fetch(`delete_note.php?id=${noteId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload notes
                    loadNotes(currentDatasetId);
                } else {
                    alert('Error deleting note: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting note:', error);
                alert('Error deleting note. Please try again.');
            });
        }
    };
}); 