<aside class="bg-white w-64 min-h-screen p-6 shadow-lg fixed-sidebar" id="sidebar">
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-800">Menu</h2>
    </div>
    <ul class="space-y-2">
        <!-- Dashboard -->
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'user_dashboard.php' ? 'active' : ''; ?>">
            <a href="user_dashboard.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-lg transition duration-200">
                <i class="fas fa-tachometer-alt mr-3 text-blue-500"></i>
                <span class="font-medium">Dashboard</span>
            </a>
        </li>
        <!-- Upload Data -->
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'data_upload.php' ? 'active' : ''; ?>">
            <a href="data_upload.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-lg transition duration-200">
                <i class="fas fa-upload mr-3 text-blue-500"></i>
                <span class="font-medium">Upload Data</span>
            </a>
        </li>
        <!-- Reports -->
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
            <a href="reports.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-lg transition duration-200">
                <i class="fas fa-file-alt mr-3 text-blue-500"></i>
                <span class="font-medium">Reports</span>
            </a>
        </li>
        <!-- Profile -->
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
            <a href="profile.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-lg transition duration-200">
                <i class="fas fa-user-circle mr-3 text-blue-500"></i>
                <span class="font-medium">Profile</span>
            </a>
        </li>
    </ul>
</aside>