<?php
// Start the session to access messages set by login.php or signup.php
session_start();

// Check for a pending message from a previous action
$message = $_SESSION['message'] ?? null;
$message_type = $_SESSION['message_type'] ?? 'danger'; // Default to danger (error)
// Clear the session message so it doesn't reappear on refresh
unset($_SESSION['message']);
unset($_SESSION['message_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack - User Auth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Color Palette */
        :root {
            --bintrack-green: #28a745;
            --bintrack-green-dark: #218838;
            --bintrack-green-light: #e6f4ea;
            --text-color-primary: #333333;
            --text-color-secondary: #6c757d;
            --white: #ffffff;
        }

        body {
            background-color: var(--bintrack-green-light);
            font-family: 'Segoe UI', sans-serif;
        }

        .header {
            text-align: center;
            margin-top: 60px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: var(--bintrack-green);
            font-weight: 700;
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header h1 i {
            margin-right: 10px;
            font-size: 2.2rem;
        }

        .header p {
            color: var(--text-color-primary);
            font-size: 1.1rem;
            margin-top: 5px;
        }

        .auth-container {
            max-width: 420px;
            margin: 0 auto 60px auto;
        }

        .auth-card {
            background-color: var(--white);
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .nav-tabs {
            border-bottom: none;
        }

        .nav-tabs .nav-link {
            color: var(--text-color-secondary);
            font-weight: 500;
            border: none;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--bintrack-green);
        }

        .nav-tabs .nav-link.active {
            color: var(--white) !important;
            background-color: var(--bintrack-green) !important;
            border-color: var(--bintrack-green) !important;
            border-radius: 8px;
            border-bottom-color: var(--bintrack-green) !important; 
        }

        label {
            font-weight: 500;
            color: var(--text-color-primary);
        }

        .form-control, .form-select {
            border-radius: 5px; 
        }

        .form-control:focus {
            border-color: var(--bintrack-green);
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        /* Buttons */
        .btn {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 8px;
            padding: 10px 20px;
        }
        
        .btn-primary {
            background-color: var(--bintrack-green);
            border-color: var(--bintrack-green);
        }

        .btn-primary:hover {
            background-color: var(--bintrack-green-dark);
            border-color: var(--bintrack-green-dark);
        }

        .btn-success {
            background-color: var(--bintrack-green-dark);
            border-color: var(--bintrack-green-dark);
        }

        .btn-success:hover {
            background-color: #1e7e34; 
            border-color: #1e7e34;
        }

        .tab-content {
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1><i class="fas fa-leaf"></i>BinTrack</h1>
        <p>Welcome Back!</p>
    </div>

    <div class="auth-container">
        <div class="auth-card">
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <ul class="nav nav-tabs mb-3" id="authTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">Login</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="signup-tab" data-bs-toggle="tab" data-bs-target="#signup" type="button">Signup</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="login">
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="signup">
                    <form action="signup.php" method="POST">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" class="form-control" name="full_name" placeholder="Your full name" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                        </div>
                        <div class="mb-3">
                            <label>Role</label>
                            <select class="form-control" name="role">
                                <option value="citizen">Citizen</option>
                                <option value="collector">Collector</option> 
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Signup</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>