<footer class="sticky-bottom bg-light border-top py-3 mt-5">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Intégration progressive Laravel/Blade • Page: <code>{{ $currentPage ?? 'dashboard' }}</code>
                </small>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">
                    CATMIN {{ config('app.version', 'v0.1') }} • {{ date('Y') }}
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- ========== PAGE INITIALIZATION SCRIPTS ========== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set active page in navigation
    const currentPage = '{{ $currentPage ?? "dashboard" }}';
    
    // Highlight current page in sidebar
    document.querySelectorAll('.side-menu a').forEach(link => {
        link.classList.remove('active');
    });
    
    const activeLink = document.querySelector(`.side-menu a[href*="${currentPage}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
    
    // Initialize any components that need setup
    console.log('Page loaded:', currentPage);
});

// Toggle sidebar on small screens
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu_toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('nav-sm');
        });
    }
});
</script>
