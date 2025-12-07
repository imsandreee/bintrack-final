document.addEventListener('DOMContentLoaded', () => {
    // Get current file name from URL
    const currentPage = window.location.pathname.split("/").pop().replace('.php', '');

    // Remove 'active' from all links
    document.querySelectorAll('.sidebar .nav-link, .offcanvas-body .nav-link').forEach(link => {
        link.classList.remove('active');
    });

    // Add 'active' to the link matching current page
    document.querySelectorAll('.sidebar .nav-link, .offcanvas-body .nav-link').forEach(link => {
        if (link.dataset.page === currentPage) {
            link.classList.add('active');
        }
    });
});