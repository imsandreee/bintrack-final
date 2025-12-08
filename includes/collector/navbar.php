<!-- Offcanvas for Mobile Navigation -->
<div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasNav" aria-labelledby="offcanvasNavLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title sidebar-header" id="offcanvasNavLabel">BinTrack</h5>
        <button type="button" class="btn-close text-reset bg-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <!-- Mobile Nav Links -->
        <ul class="nav flex-column" data-bs-dismiss="offcanvas">
            <li><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
            <li><a class="nav-link" href="routes.php"><i class="bi bi-geo-alt-fill me-2"></i> Assigned Routes</a></li>
            <li><a class="nav-link" href="logs.php"><i class="bi bi-journal-text me-2"></i> Collection Logs</a></li>
            <li><a class="nav-link" href="alerts.php"><i class="bi bi-bell-fill me-2"></i> Alerts & Notifications</a></li>

            <li class="mt-4"><a class="nav-link" href="profile.php"><i class="bi bi-person-gear me-2"></i> Profile</a></li>
            <li><a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
        </ul>
    </div>
</div>

<div class="app-container">

    <!-- Desktop Sidebar -->
    <nav class="sidebar d-none d-lg-block">
        <h1 class="sidebar-header">BinTrack</h1>

        <!-- Dynamic User Info -->
        <div class="mb-5 sidebar-profile-info">
            <p class="fw-bold mb-1">
                UsernameL            </p>
            <p class="small text-muted mb-0">
                <?= htmlspecialchars($user['full_name'] ?? 'Collector') ?>
            </p>
        </div>

        <ul class="nav flex-column">
            <li><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
            <li><a class="nav-link" href="routes.php"><i class="bi bi-geo-alt-fill me-2"></i> Assigned Routes</a></li>
            <li><a class="nav-link" href="logs.php"><i class="bi bi-journal-text me-2"></i> Collection Logs</a></li>
            <li><a class="nav-link" href="alerts.php"><i class="bi bi-bell-fill me-2"></i> Alerts & Notifications</a></li>

            <li class="mt-4"><a class="nav-link" href="profile.php"><i class="bi bi-person-gear me-2"></i> Profile</a></li>
            <li><a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
            <button class="btn btn-outline-primary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav">
                <i class="bi bi-list"></i> Menu
            </button>

            <h2 class="h4 mb-0 d-lg-none text-dark-green fw-bold">BinTrack</h2>

            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block me-3 text-muted">
                    Welcome, <?= htmlspecialchars($user['full_name'] ?? 'Collector') ?>!
                </span>
                <button class="btn btn-sm btn-outline-secondary rounded-circle" data-page="profile" title="Profile">
                    <i class="bi bi-person"></i>
                </button>
            </div>
        </div>
        <!-- Continue page content... -->
