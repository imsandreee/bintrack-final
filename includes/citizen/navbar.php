<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container-fluid container-xl">
        <a class="navbar-brand navbar-logo" href="dashboard.php" data-page="dashboard">
            🗑️ BinTrack
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="binlist.php">Smart Bins</a></li>
                <li class="nav-item"><a class="nav-link" href="reportForm.php">Report Issue</a></li>
            </ul>

            <!-- Profile Dropdown Menu -->
            <div class="d-flex align-items-center ms-lg-3">
                <div class="dropdown">
                    <a class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center" href="#" 
                       role="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 text-green me-2"></i>
                        <span id="navbarUserName" class="d-none d-lg-block">
                            <?= htmlspecialchars($user['full_name']) ?>
                        </span>
                    </a>
                    
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="profileDropdown">
                        <li><h6 class="dropdown-header">Citizen Actions</h6></li>
                        <li>
                            <a class="dropdown-item" href="profile.php" data-page="profile">
                                <i class="bi bi-person-gear me-2 text-muted"></i> Profile Settings
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="myreports.php" data-page="myReports">
                                <i class="bi bi-list-check me-2 text-muted"></i> My Reports
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger d-flex align-items-center" href="../auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    // Populate navbar with the user's name
    async function loadNavbarUser() {
        const user = supabase.auth.getUser(); // Get current user session
        const { data, error } = await supabase
            .from('profiles')
            .select('full_name')
            .eq('id', (await user).data.user?.id)
            .single();
        
        if (!error && data) {
            document.getElementById('navbarUserName').textContent = data.full_name;
        }
    }

    // Attach logout event
    document.getElementById('logoutBtn').addEventListener('click', async () => {
        await handleLogout();
    });

    // Call this on page load
    loadNavbarUser();
</script>
