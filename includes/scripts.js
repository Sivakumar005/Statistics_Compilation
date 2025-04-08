// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Profile dropdown functionality
    const profileButton = document.getElementById('profileButton');
    const profileDropdown = document.getElementById('profileDropdown');
    let isOpen = false;

    if (profileButton && profileDropdown) {
        // Toggle dropdown
        profileButton.addEventListener('click', function(e) {
            e.stopPropagation();
            isOpen = !isOpen;
            profileDropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileButton.contains(e.target) && !profileDropdown.contains(e.target)) {
                isOpen = false;
                profileDropdown.classList.add('hidden');
            }
        });
    }

    // Sidebar toggle functionality
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-hidden');
            mainContent.classList.toggle('content-expanded');
        });

        // Handle responsive behavior
        function handleResize() {
            if (window.innerWidth <= 768) {
                sidebar.classList.add('sidebar-hidden');
                mainContent.classList.add('content-expanded');
            } else {
                sidebar.classList.remove('sidebar-hidden');
                mainContent.classList.remove('content-expanded');
            }
        }

        // Initial check and listen for window resize
        handleResize();
        window.addEventListener('resize', handleResize);
    }
}); 