<!-- navbar.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="bg-white shadow-md p-4 fixed-navbar" id="navbar">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <!-- Toggle Button -->
            <button class="toggle-btn" id="toggleSidebar">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-xl font-bold text-gray-800 ml-4">Statistics Compilation</h1>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
            <a href="./auth/logout.php" class="ml-4 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600">Logout</a>
        </div>
    </div>
</nav>