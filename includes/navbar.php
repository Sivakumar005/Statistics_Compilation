<!-- navbar.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="bg-gradient-to-r from-gray-900 to-gray-800 text-white shadow-md p-4" id="navbar" style="position: fixed; top: 0; left: 0; right: 0; width: 100%; z-index: 40;">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <!-- Toggle Button -->
            <button class="toggle-btn" id="toggleSidebar">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-xl font-bold text-white ml-4">StatSync</h1>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-white">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
            <!-- Profile Dropdown -->
            <div class="relative">
                <button id="profileButton" class="flex items-center space-x-2 focus:outline-none">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white">
                        <i class="fas fa-user text-lg"></i>
                    </div>
                </button>
                <!-- Dropdown Menu -->
                <div id="profileDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 hidden">
                    <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user-circle mr-2"></i> Profile
                    </a>
                    <a href="./auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileButton = document.getElementById('profileButton');
    const profileDropdown = document.getElementById('profileDropdown');
    let isOpen = false;

    // Toggle dropdown
    profileButton.addEventListener('click', function(e) {
        e.stopPropagation();
        isOpen = !isOpen;
        profileDropdown.classList.toggle('hidden', !isOpen);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (isOpen && !profileButton.contains(e.target) && !profileDropdown.contains(e.target)) {
            isOpen = false;
            profileDropdown.classList.add('hidden');
        }
    });

    // Close dropdown when clicking a dropdown item
    profileDropdown.querySelectorAll('a').forEach(item => {
        item.addEventListener('click', function() {
            isOpen = false;
            profileDropdown.classList.add('hidden');
        });
    });
});
</script>

<!-- Add Alpine.js for dropdown functionality -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    [x-cloak] { display: none !important; }
</style>