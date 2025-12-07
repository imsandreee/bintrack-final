<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack Admin Dashboard | IoT Waste Management</title>
    <!-- Load Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Load Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Load Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom Tailwind Configuration and Styles -->
    <style>
        /* Custom Variables for Theming */
        :root {
            --brand-green: #027902;
            --brand-green-light: #4ade80; /* Light mode accent */
            --bg-primary: #f9fafb; /* Light Gray Background */
            --bg-secondary: #ffffff; /* Card Background */
            --text-color: #1f2937; /* Dark Text */
            --border-color: #e5e7eb; /* Light Border */
            --sidebar-width: 280px;
        }

        /* Dark Mode Overrides */
        .dark {
            --brand-green: #4ade80; /* Brighter green for contrast in dark mode */
            --brand-green-light: #027902;
            --bg-primary: #111827; /* Dark Blue Background */
            --bg-secondary: #1f2937; /* Darker Card Background */
            --text-color: #f3f4f6; /* Light Text */
            --border-color: #374151; /* Dark Border */
        }

        /* Base Body and Font */
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-color);
            transition: background-color 0.3s, color 0.3s;
        }

        /* App Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            padding: 1.5rem 0;
            position: fixed;
            top: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 50;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            padding: 1.5rem 2rem;
        }

        /* Sidebar Nav Links */
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            color: var(--text-color);
            transition: background-color 0.2s, color 0.2s;
        }
        .nav-link:hover {
            background-color: var(--border-color);
        }
        .nav-link.active {
            background-color: var(--brand-green);
            color: white;
            font-weight: 600;
        }
        .dark .nav-link.active {
            background-color: var(--brand-green);
            color: #111827;
        }
        .nav-link.active i {
            color: white !important;
        }
        
        /* Utility Classes (Custom for Theming) */
        .card-bg { background-color: var(--bg-secondary); border: 1px solid var(--border-color); }
        .text-brand { color: var(--brand-green) !important; }
        .bg-brand { background-color: var(--brand-green) !important; }

        /* Status Colors */
        .status-ok { background-color: #10b981; } /* Green */
        .status-nearly-full { background-color: #f59e0b; } /* Yellow */
        .status-full { background-color: #ef4444; } /* Red */
        .status-overload { background-color: #8b5cf6; } /* Purple */
        .status-disabled { background-color: #6b7280; } /* Gray */

        /* Real-time pulse indicator */
        .real-time-pulse {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--brand-green);
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(4, 120, 4, 0.7);
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            70% { transform: scale(2.5); opacity: 0; }
            100% { transform: scale(1); opacity: 0; }
        }

        /* Map Placeholder */
        .map-placeholder {
            min-height: 350px;
            background-color: #eef2f6;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
        .dark .map-placeholder {
            background-color: #374151;
            color: #9ca3af;
            border-color: #4b5563;
        }

        /* Hide pages initially */
        .page-content {
            display: none;
        }
        .active-page {
            display: block !important;
        }

        /* Mobile Adjustments */
        @media (max-width: 1024px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="antialiased">

    <!-- Firestore Initialization (MANDATORY Boilerplate) -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
        import { getAuth, signInAnonymously, signInWithCustomToken, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";
        import { getFirestore, setLogLevel } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";

        // Global Firebase variables provided by the environment
        const appId = typeof __app_id !== 'undefined' ? __app_id : 'default-app-id';
        const firebaseConfig = typeof __firebase_config !== 'undefined' ? JSON.parse(__firebase_config) : null;
        const initialAuthToken = typeof __initial_auth_token !== 'undefined' ? __initial_auth_token : null;

        let app;
        let db;
        let auth;
        window.userId = null; // Exposed for mock functions if needed

        if (firebaseConfig) {
            app = initializeApp(firebaseConfig);
            db = getFirestore(app);
            auth = getAuth(app);
            setLogLevel('debug'); // Enable debug logging

            onAuthStateChanged(auth, async (user) => {
                if (user) {
                    window.userId = user.uid;
                    console.log("Firebase Auth Ready. User ID:", window.userId);
                } else {
                    try {
                        if (initialAuthToken) {
                            await signInWithCustomToken(auth, initialAuthToken);
                        } else {
                            await signInAnonymously(auth);
                        }
                    } catch (error) {
                        console.error("Firebase Sign-in Failed:", error);
                    }
                }
            });
        } else {
            console.warn("Firebase configuration not found. Running in mock data mode.");
        }
    </script>
    
    <!-- --------------------------------- -->
    <!-- Mobile Offcanvas Menu -->
    <!-- --------------------------------- -->
    <div id="offcanvasNav" class="fixed inset-y-0 left-0 transform -translate-x-full lg:hidden transition-transform duration-300 ease-in-out bg-white dark:bg-gray-800 w-64 shadow-xl z-50">
        <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center">
            <h1 class="text-2xl font-extrabold text-brand">BinTrack</h1>
            <button id="closeOffcanvas" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="bi bi-x-lg text-2xl"></i>
            </button>
        </div>
        <div class="py-4 px-3 space-y-2">
            <!-- Nav links mirrored below -->
            <a href="#" class="nav-link active" data-page="dashboard"><i class="bi bi-speedometer2 w-6 text-xl"></i> Dashboard</a>
            <a href="#" class="nav-link" data-page="bins"><i class="bi bi-trash3 w-6 text-xl"></i> Bin Management</a>
            <a href="#" class="nav-link" data-page="sensors"><i class="bi bi-broadcast w-6 text-xl"></i> Sensor Management</a>
            <a href="#" class="nav-link" data-page="alerts"><i class="bi bi-bell w-6 text-xl"></i> Alerts Management</a>
            <a href="#" class="nav-link" data-page="users"><i class="bi bi-people w-6 text-xl"></i> User Management</a>
            <a href="#" class="nav-link" data-page="routes"><i class="bi bi-geo-alt w-6 text-xl"></i> Route Management</a>
            <a href="#" class="nav-link" data-page="reports"><i class="bi bi-graph-up-arrow w-6 text-xl"></i> Reports & Analytics</a>
            <a href="#" class="nav-link" data-page="logs"><i class="bi bi-journal-text w-6 text-xl"></i> System Logs</a>
            
            <hr class="my-4 border-gray-200 dark:border-gray-700">

            <!-- Profile and Logout in Offcanvas -->
            <a href="#" class="nav-link" data-page="profileSettings">
                <i class="bi bi-gear w-6 text-xl mr-2"></i> Profile Settings
            </a>
            <a href="#" class="nav-link text-red-500 hover:text-white hover:bg-red-500 dark:text-red-400 dark:hover:bg-red-600" onclick="handleLogout()">
                <i class="bi bi-box-arrow-right w-6 text-xl mr-2"></i> Logout
            </a>

            <hr class="my-4 border-gray-200 dark:border-gray-700">
            <button id="mobileThemeToggle" class="nav-link w-full justify-center">
                <i class="bi bi-moon w-6 text-xl mr-2"></i> <span>Toggle Dark Mode</span>
            </button>
        </div>
    </div>
    <div id="offcanvasOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="toggleOffcanvas()"></div>

    <!-- --------------------------------- -->
    <!-- Main App Container -->
    <!-- --------------------------------- -->
    <div class="app-container">

        <!-- --------------------------------- -->
        <!-- Fixed Sidebar (Desktop) -->
        <!-- --------------------------------- -->
        <aside class="sidebar hidden lg:block">
            <div class="px-6 pb-6 mb-8 border-b dark:border-gray-700">
                <h1 class="text-3xl font-extrabold text-brand">BinTrack</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Admin Control Panel</p>
            </div>
            
            <nav class="space-y-2 px-3">
                <a href="#" class="nav-link active" data-page="dashboard"><i class="bi bi-speedometer2 w-6 text-xl"></i> Dashboard</a>
                <a href="#" class="nav-link" data-page="bins"><i class="bi bi-trash3 w-6 text-xl"></i> Bin Management</a>
                <a href="#" class="nav-link" data-page="sensors"><i class="bi bi-broadcast w-6 text-xl"></i> Sensor Management</a>
                <a href="#" class="nav-link" data-page="alerts"><i class="bi bi-bell w-6 text-xl"></i> Alerts Management</a>
                <a href="#" class="nav-link" data-page="users"><i class="bi bi-people w-6 text-xl"></i> User Management</a>
                <a href="#" class="nav-link" data-page="routes"><i class="bi bi-geo-alt w-6 text-xl"></i> Route Management</a>
                <a href="#" class="nav-link" data-page="reports"><i class="bi bi-graph-up-arrow w-6 text-xl"></i> Reports & Analytics</a>
                <a href="#" class="nav-link" data-page="logs"><i class="bi bi-journal-text w-6 text-xl"></i> System Logs</a>
                
                <!-- Bottom Profile and Settings (UPDATED) -->
                <div class="pt-4 absolute bottom-0 w-full pr-6 pb-4">
                    <hr class="mb-2 border-gray-200 dark:border-gray-700">
                    
                    <div class="space-y-1 px-3">
                        <a href="#" class="nav-link !p-2" data-page="profileSettings">
                            <i class="bi bi-gear w-6 text-xl mr-2"></i> Profile Settings
                        </a>
                        <!-- Logout link uses red branding and calls handleLogout() -->
                        <a href="#" class="nav-link !p-2 text-red-500 hover:text-white hover:bg-red-500 dark:text-red-400 dark:hover:bg-red-600" onclick="handleLogout()">
                            <i class="bi bi-box-arrow-right w-6 text-xl mr-2"></i> Logout
                        </a>
                    </div>
                    
                    <hr class="mt-2 mb-2 border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3 px-3">
                        <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-gray-700 dark:text-gray-200 font-semibold">AD</div>
                        <div>
                            <p class="text-sm font-semibold">Admin User</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">admin@bintrack.io</p>
                        </div>
                        <button id="desktopThemeToggle" class="p-2 ml-auto rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400" title="Toggle Theme">
                            <i class="bi bi-moon text-lg"></i>
                        </button>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- --------------------------------- -->
        <!-- Main Content Area -->
        <!-- --------------------------------- -->
        <main class="main-content">
            <!-- Top Bar for Mobile Menu and Status -->
            <header class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-6 sticky top-0 z-40 card-bg">
                <div class="flex justify-between items-center">
                    <button id="openOffcanvas" class="lg:hidden p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="bi bi-list text-2xl"></i>
                    </button>
                    <h2 class="text-xl font-bold lg:hidden text-brand">BinTrack</h2>
                    
                    <div class="flex items-center space-x-4 ml-auto">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center">
                            <span class="real-time-pulse mr-2"></span> System Status: <strong class="ml-1 text-brand">Live</strong>
                        </span>
                        <button class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400" title="Notifications">
                            <i class="bi bi-bell text-xl"></i>
                        </button>
                    </div>
                </div>
            </header>

            <div class="container mx-auto">
                <!-- ======================================================= -->
                <!-- 1. Dashboard Overview Page -->
                <!-- ======================================================= -->
                <section id="dashboardPage" class="page-content active-page">
                    <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">Dashboard Overview</h1>

                    <!-- Status Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="p-5 rounded-xl shadow-lg card-bg">
                            <i class="bi bi-trash3 text-3xl text-brand mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400 uppercase">Total Bins</p>
                            <h3 class="text-2xl font-extrabold mt-1">1,245</h3>
                        </div>
                        <div class="p-5 rounded-xl shadow-lg card-bg">
                            <i class="bi bi-broadcast text-3xl text-blue-500 mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400 uppercase">Active Sensors</p>
                            <h3 class="text-2xl font-extrabold mt-1">1,210 <span class="text-sm text-gray-500">/ 1,245</span></h3>
                        </div>
                        <div class="p-5 rounded-xl shadow-lg card-bg">
                            <i class="bi bi-exclamation-triangle text-3xl text-yellow-500 mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400 uppercase">Nearly Full (<15cm)</p>
                            <h3 class="text-2xl font-extrabold mt-1 text-yellow-500">84</h3>
                        </div>
                        <div class="p-5 rounded-xl shadow-lg card-bg">
                            <i class="bi bi-x-octagon text-3xl text-red-500 mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400 uppercase">Critical (Full/Overload)</p>
                            <h3 class="text-2xl font-extrabold mt-1 text-red-500">12</h3>
                        </div>
                    </div>

                    <!-- Map and Trends -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                        <div class="lg:col-span-2 p-6 rounded-xl shadow-lg card-bg">
                            <h2 class="text-xl font-semibold mb-3">Map Overview (Real-time GPS)</h2>
                            <div class="map-placeholder h-96">
                                <p class="text-lg">Interactive Map Placeholder: Color-coded Bin Locations</p>
                            </div>
                        </div>
                        <div class="p-6 rounded-xl shadow-lg card-bg">
                            <h2 class="text-xl font-semibold mb-3">Fill-Level Trends (System Avg)</h2>
                            <div class="map-placeholder h-48 mb-4">
                                [Chart Placeholder: Avg Fill % Line Chart]
                            </div>
                            <h2 class="text-xl font-semibold mb-3">Weight Trends (System Avg)</h2>
                            <div class="map-placeholder h-48">
                                [Chart Placeholder: Avg Weight Line Chart]
                            </div>
                        </div>
                    </div>

                    <!-- Quick Alerts Panel -->
                    <div class="p-6 rounded-xl shadow-lg card-bg">
                        <h2 class="text-xl font-semibold mb-4 flex justify-between items-center">
                            Latest Sensor Readings & Alerts
                            <button class="text-sm font-medium text-brand hover:text-green-700 dark:hover:text-green-400" onclick="switchPage('alerts')">
                                View All <i class="bi bi-arrow-right"></i>
                            </button>
                        </h2>
                        <ul class="divide-y dark:divide-gray-700">
                            <li class="py-3 flex justify-between items-center">
                                <span class="status-full text-white px-2 py-0.5 rounded-full text-xs font-semibold">FULL</span>
                                <span class="font-medium">Bin B-0012</span>
                                <span class="text-sm text-gray-500">Location: 45 Oak St.</span>
                                <span class="text-sm text-red-500 font-semibold">5 cm / 95% Weight</span>
                                <span class="text-xs text-gray-400">2 min ago</span>
                            </li>
                            <li class="py-3 flex justify-between items-center">
                                <span class="status-overload text-white px-2 py-0.5 rounded-full text-xs font-semibold">OVERLOAD</span>
                                <span class="font-medium">Bin B-0050</span>
                                <span class="text-sm text-gray-500">Location: Central Market</span>
                                <span class="text-sm text-purple-500 font-semibold">20 cm / 9.5 kg</span>
                                <span class="text-xs text-gray-400">15 min ago</span>
                            </li>
                            <li class="py-3 flex justify-between items-center">
                                <span class="status-nearly-full text-white px-2 py-0.5 rounded-full text-xs font-semibold">NEARLY FULL</span>
                                <span class="font-medium">Bin B-0122</span>
                                <span class="text-sm text-gray-500">Location: City Hall Park</span>
                                <span class="text-sm text-yellow-500 font-semibold">12 cm / 80% Weight</span>
                                <span class="text-xs text-gray-400">35 min ago</span>
                            </li>
                        </ul>
                    </div>
                </section>
                
                <!-- ======================================================= -->
                <!-- 2. Bin Management Page -->
                <!-- ======================================================= -->
                <section id="binsPage" class="page-content">
                    <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">Bin Management</h1>
                    
                    <div class="p-6 rounded-xl shadow-lg card-bg">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold">Active Bins (1,245)</h2>
                            <button class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700">
                                <i class="bi bi-plus-lg mr-1"></i> Add New Bin
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bin ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Location</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ultrasonic Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Weight Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Reading</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Health</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">B-0012</td>
                                        <td class="px-4 py-3 whitespace-nowrap">45 Oak St (34.0, -118.0)</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="status-full text-white px-2 py-0.5 rounded text-xs">5 cm</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="status-nearly-full text-white px-2 py-0.5 rounded text-xs">95% (9.5 kg)</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">2025-12-05 15:55:01</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="status-overload text-white px-2 py-0.5 rounded text-xs">Battery: 10%</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <button class="text-brand hover:text-green-700" onclick="showBinProfile('B-0012')">View</button>
                                            <button class="text-gray-500 hover:text-gray-700">Disable</button>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">B-0240</td>
                                        <td class="px-4 py-3 whitespace-nowrap">Main Park Entrance</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="status-ok text-white px-2 py-0.5 rounded text-xs">40 cm</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="status-ok text-white px-2 py-0.5 rounded text-xs">30% (3.0 kg)</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">2025-12-05 15:58:30</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="status-ok text-white px-2 py-0.5 rounded text-xs">Online</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <button class="text-brand hover:text-green-700" onclick="showBinProfile('B-0240')">View</button>
                                            <button class="text-gray-500 hover:text-gray-700">Disable</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ======================================================= -->
                <!-- 3. Sensor Management Page -->
                <!-- ======================================================= -->
                <section id="sensorsPage" class="page-content">
                    <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">Sensor Management</h1>
                    
                    <div class="p-6 rounded-xl shadow-lg card-bg">
                        <h2 class="text-xl font-semibold mb-4">Sensor Health Status</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                             <div class="p-4 rounded-lg bg-green-100 dark:bg-green-900 border border-green-300 dark:border-green-700">
                                <p class="font-semibold text-green-700 dark:text-green-300">Ultrasonic Sensors</p>
                                <p class="text-2xl font-bold text-green-800 dark:text-green-200">98% Active</p>
                            </div>
                            <div class="p-4 rounded-lg bg-blue-100 dark:bg-blue-900 border border-blue-300 dark:border-blue-700">
                                <p class="font-semibold text-blue-700 dark:text-blue-300">Load Cells (Weight)</p>
                                <p class="text-2xl font-bold text-blue-800 dark:text-blue-200">95% Active</p>
                            </div>
                            <div class="p-4 rounded-lg bg-yellow-100 dark:bg-yellow-900 border border-yellow-300 dark:border-yellow-700">
                                <p class="font-semibold text-yellow-700 dark:text-yellow-300">GPS Modules</p>
                                <p class="text-2xl font-bold text-yellow-800 dark:text-yellow-200">96% Active</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bin ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Microcontroller Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Data Received</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">B-0012</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">ESP32-V2</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">2025-12-05 15:59:01</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="status-ok text-white px-2 py-0.5 rounded text-xs">Online</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <button class="text-brand hover:text-green-700" onclick="showSensorConfig('B-0012')">Configure</button>
                                            <button class="text-red-500 hover:text-red-700">Repair/Replace</button>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">B-0351</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">ESP8266-V1</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-red-500">2025-12-04 08:10:00</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="status-disabled text-white px-2 py-0.5 rounded text-xs">Offline (48h)</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <button class="text-brand hover:text-green-700" onclick="showSensorConfig('B-0351')">Configure</button>
                                            <button class="text-red-500 hover:text-red-700">Repair/Replace</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <!-- ======================================================= -->
                <!-- 4. Alerts Management Page -->
                <!-- ======================================================= -->
                <section id="alertsPage" class="page-content">
                    <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">Alerts Management</h1>

                    <div class="p-6 rounded-xl shadow-lg card-bg">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold">Critical Alerts Log</h2>
                            <button class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700" onclick="mockAlertRefresh()">
                                <i class="bi bi-arrow-clockwise mr-1"></i> Real-time Refresh
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bin ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Timestamp</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Resolution Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="status-overload text-white px-2 py-0.5 rounded text-xs">OVERLOAD</span></td>
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">B-0050</td>
                                        <td class="px-4 py-3">Weight > 90% (9.5kg). Critical.</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">1 min ago</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 px-2 py-0.5 rounded text-xs">Pending Assignment</span></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <button class="text-brand hover:text-green-700" onclick="assignCollector('B-0050')">Assign</button>
                                            <button class="text-gray-500 hover:text-gray-700" onclick="markResolved('B-0050')">Resolve</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="status-nearly-full text-white px-2 py-0.5 rounded text-xs">NEARLY FULL</span></td>
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">B-0122</td>
                                        <td class="px-4 py-3">Fill < 15 cm. Needs collection soon.</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">35 min ago</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 px-2 py-0.5 rounded text-xs">Assigned to C901</span></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <button class="text-brand hover:text-green-700" onclick="assignCollector('B-0122')">Re-Assign</button>
                                            <button class="text-gray-500 hover:text-gray-700" onclick="markResolved('B-0122')">Resolve</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ======================================================= -->
                <!-- 5. User Management Page -->
                <!-- ======================================================= -->
                <section id="usersPage" class="page-content">
                    <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">User Management</h1>

                    <div class="p-6 rounded-xl shadow-lg card-bg">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold">All User Accounts (2,500+)</h2>
                            <div class="flex space-x-2">
                                <input type="text" placeholder="Search users..." class="p-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <select class="p-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                    <option>Filter by Role</option>
                                    <option>Admin</option>
                                    <option>Collector</option>
                                    <option>Citizen</option>
                                </select>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Full Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created Date</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">Jane Smith</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 px-2 py-0.5 rounded text-xs">Admin</span></td>
                                        <td class="px-4 py-3 text-sm">jane.admin@bintrack.io</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">2024-01-15</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <button class="text-red-500 hover:text-red-700">Disable Account</button>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">David Lee</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 px-2 py-0.5 rounded text-xs">Collector</span></td>
                                        <td class="px-4 py-3 text-sm">david.c901@bintrack.io</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">2024-03-20</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <button class="text-brand hover:text-green-700">Promote to Admin</button>
                                            <button class="text-red-500 hover:text-red-700">Disable Account</button>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold">Citizen User</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 px-2 py-0.5 rounded text-xs">Citizen</span></td>
                                        <td class="px-4 py-3 text-sm">citizen@email.com</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">2025-11-01</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <button class="text-brand hover:text-green-700" onclick="promoteToCollector('Citizen User')">Promote to Collector</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <!-- ======================================================= -->
                <!-- 6. Collection Route Management Page -->
                <!-- ======================================================= -->
                <section id="routesPage" class="page-content">
                    <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">Collection Route Management</h1>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                        <div class="lg:col-span-2 p-6 rounded-xl shadow-lg card-bg">
                            <h2 class="text-xl font-semibold mb-3">Live Route Monitor: Route 001 (David Lee)</h2>
                            <div class="map-placeholder h-96">
                                <p class="text-lg">Interactive Route Map: Collector GPS vs. Optimal Path</p>
                            </div>
                            <div class="flex justify-between mt-4">
                                <p class="text-sm font-medium">Status: <span class="text-brand">3 / 15 Bins Collected</span></p>
                                <button class="bg-brand text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-green-700" onclick="sendRouteNotification('Route 001')">
                                    <i class="bi bi-send-fill mr-1"></i> Send Alert
                                </button>
                            </div>
                        </div>
                        <div class="p-6 rounded-xl shadow-lg card-bg">
                            <h2 class="text-xl font-semibold mb-4">Route Actions</h2>
                            <button class="w-full bg-brand text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 mb-3" onclick="showRouteOptimizer()">
                                <i class="bi bi-tools mr-2"></i> Create Optimized Route
                            </button>
                            <div class="space-y-3">
                                <div class="p-3 border rounded-lg dark:border-gray-700">
                                    <p class="font-semibold">R-002: West Sector</p>
                                    <p class="text-sm text-gray-500">Assigned: Unassigned</p>
                                    <button class="text-brand hover:text-green-700 text-sm mt-1" onclick="assignRouteBins('R-002')">Assign Bins & Collector</button>
                                </div>
                                <div class="p-3 border rounded-lg dark:border-gray-700">
                                    <p class="font-semibold">R-003: Industrial Zone</p>
                                    <p class="text-sm text-gray-500">Assigned: Collector Jane</p>
                                    <button class="text-red-500 hover:text-red-700 text-sm mt-1">Stop Monitoring</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ======================================================= -->
                <!-- 7. Reports & Analytics Page -->
                <!-- ======================================================= -->
                <section id="reportsPage" class="page-content">
                    <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">Reports & Analytics</h1>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                        <div class="col-span-1 lg:col-span-3 p-6 rounded-xl shadow-lg card-bg">
                            <h2 class="text-xl font-semibold mb-4">Report Generation</h2>
                            <div class="flex space-x-4 mb-4 items-end">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Time Filter</label>
                                    <select class="p-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                        <option>Daily</option>
                                        <option>Weekly</option>
                                        <option>Monthly</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Report Type</label>
                                    <select class="p-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                        <option>Collection Statistics</option>
                                        <option>Bin Usage Patterns</option>
                                        <option>Peak Waste Times</option>
                                    </select>
                                </div>
                                <button class="bg-brand text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700">
                                    <i class="bi bi-file-earmark-bar-graph mr-1"></i> Generate Report
                                </button>
                            </div>
                            
                            <div class="map-placeholder h-80">
                                [Chart Placeholder: Generated Report Visualization]
                            </div>
                        </div>

                        <div class="col-span-1 p-6 rounded-xl shadow-lg card-bg">
                            <h2 class="text-xl font-semibold mb-3">Garbage Collection Stats</h2>
                            <ul class="space-y-2 text-sm">
                                <li class="flex justify-between"><span>Total Collections (Month):</span> <span class="font-bold text-brand">350</span></li>
                                <li class="flex justify-between"><span>Average Route Time:</span> <span class="font-bold">2.5 hours</span></li>
                                <li class="flex justify-between"><span>Total Collected Weight:</span> <span class="font-bold">15,000 kg</span></li>
                            </ul>
                        </div>
                        
                        <div class="col-span-1 p-6 rounded-xl shadow-lg card-bg">
                            <h2 class="text-xl font-semibold mb-3">Bin Usage Heatmap</h2>
                            <div class="map-placeholder h-40">
                                [Chart Placeholder: Bin Usage Heatmap]
                            </div>
                        </div>
                        
                        <div class="col-span-1 p-6 rounded-xl shadow-lg card-bg">
                            <h2 class="text-xl font-semibold mb-3">Download Reports</h2>
                            <button class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-600 mb-2">
                                <i class="bi bi-file-earmark-excel mr-1"></i> Export to Excel
                            </button>
                            <button class="w-full bg-red-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-600">
                                <i class="bi bi-file-pdf mr-1"></i> Export to PDF
                            </button>
                        </div>
                    </div>
                </section>
                
                <!-- ======================================================= -->
                <!-- 8. System Logs Page -->
                <!-- ======================================================= -->
                <section id="logsPage" class="page-content">
                    <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">System Logs</h1>

                    <div class="p-6 rounded-xl shadow-lg card-bg">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold">Activity Timeline</h2>
                            <div class="flex space-x-2">
                                <input type="text" placeholder="Search logs..." class="p-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <select class="p-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                    <option>Filter by Category</option>
                                    <option>Alert</option>
                                    <option>Sensor Data</option>
                                    <option>User Action</option>
                                    <option>Role Update</option>
                                </select>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Timestamp</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">2025-12-05 16:00:10</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 px-2 py-0.5 rounded text-xs">Alert</span></td>
                                        <td class="px-4 py-3">Alert `overload` triggered for Bin B-0050.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">2025-12-05 15:58:30</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 px-2 py-0.5 rounded text-xs">Sensor Data</span></td>
                                        <td class="px-4 py-3">New reading from B-0240: 40cm, 3.0kg.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">2025-12-05 14:30:00</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 px-2 py-0.5 rounded text-xs">Role Update</span></td>
                                        <td class="px-4 py-3">Admin (Jane Smith) promoted User U-099 to `Collector` role.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">2025-12-05 14:00:00</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 px-2 py-0.5 rounded text-xs">User Creation</span></td>
                                        <td class="px-4 py-3">New user profile automatically created (Role: Citizen). Trigger: `handle_new_user`.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">2025-12-05 13:05:15</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><span class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 px-2 py-0.5 rounded text-xs">Admin Action</span></td>
                                        <td class="px-4 py-3">Admin (Jane Smith) updated threshold for B-0012.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ======================================================= -->
                <!-- 9. Profile Settings Page (NEW) -->
                <!-- ======================================================= -->
                <section id="profileSettingsPage" class="page-content">
                    <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-white">Profile Settings</h1>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2 p-6 rounded-xl shadow-lg card-bg">
                            <h2 class="text-xl font-semibold mb-4 border-b pb-3 dark:border-gray-700">Account Information</h2>
                            <form class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Full Name</label>
                                    <input type="text" value="Admin User" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Email (Admin)</label>
                                    <input type="email" value="admin@bintrack.io" disabled class="w-full p-2 border border-gray-300 rounded-lg bg-gray-100 dark:bg-gray-600 dark:border-gray-500 cursor-not-allowed text-gray-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">New Password</label>
                                    <input type="password" placeholder="Enter new password" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                </div>
                                <button type="submit" class="bg-brand text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700">Update Profile</button>
                            </form>
                        </div>

                        <div class="p-6 rounded-xl shadow-lg card-bg">
                            <h2 class="text-xl font-semibold mb-4 border-b pb-3 dark:border-gray-700">Preferences</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">UI Theme</label>
                                    <div class="flex items-center space-x-4">
                                        <button class="flex items-center p-2 rounded-lg border dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700" onclick="toggleTheme()">
                                            <i id="prefThemeIcon" class="bi bi-sun mr-2"></i> Toggle Dark/Light Mode
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Real-Time Alerts</label>
                                    <div class="flex items-center">
                                        <input type="checkbox" checked class="h-4 w-4 text-brand rounded focus:ring-brand">
                                        <span class="ml-2 text-sm">Enable desktop notifications</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </main>
    </div>

    <!-- Reusable Confirmation/Action Modal -->
    <div id="bintrackModal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg card-bg transform scale-95 transition-transform duration-300" id="modalContent">
            <div class="p-6">
                <h3 id="modalTitle" class="text-xl font-bold mb-3 text-brand">Modal Title</h3>
                <div id="modalBody" class="text-gray-700 dark:text-gray-300 mb-6">Modal body content.</div>
                <div class="flex justify-end space-x-3">
                    <button id="modalCancel" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600" onclick="closeModal()">Cancel</button>
                    <button id="modalPrimary" class="px-4 py-2 bg-brand text-white rounded-lg font-medium hover:bg-green-700">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // --- Core UI Logic ---

        // Theme Toggle Logic
        const body = document.body;
        const themeToggles = document.querySelectorAll('#desktopThemeToggle, #mobileThemeToggle');
        
        function updateThemeIcon(isDark) {
            themeToggles.forEach(toggle => {
                toggle.querySelector('i').className = isDark ? 'bi bi-sun text-lg' : 'bi bi-moon text-lg';
            });
             const prefIcon = document.getElementById('prefThemeIcon');
             if (prefIcon) {
                prefIcon.className = isDark ? 'bi bi-sun mr-2' : 'bi bi-moon mr-2';
            }
        }

        window.toggleTheme = function() { // Expose to window for the Profile Settings button
            const isDark = body.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon(isDark);
        }

        // Initialize theme
        const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        if (savedTheme === 'dark') {
            body.classList.add('dark');
        }
        updateThemeIcon(savedTheme === 'dark');

        themeToggles.forEach(toggle => toggle.addEventListener('click', window.toggleTheme));

        // Navigation Logic
        window.switchPage = function(pageId) {
            const pageElementId = pageId + 'Page';
            
            // Hide all content sections and remove active class from links
            document.querySelectorAll('.page-content').forEach(page => page.classList.remove('active-page'));
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));

            // Show the requested page
            const requestedPage = document.getElementById(pageElementId);
            if (requestedPage) {
                requestedPage.classList.add('active-page');

                // Update active state in both sidebars/offcanvas
                document.querySelectorAll(`[data-page="${pageId}"]`).forEach(link => link.classList.add('active'));

                // Close offcanvas on mobile
                const offcanvas = document.getElementById('offcanvasNav');
                if (!offcanvas.classList.contains('-translate-x-full')) {
                    toggleOffcanvas();
                }

                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Setup navigation click handlers
            document.querySelectorAll('[data-page]').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    switchPage(link.getAttribute('data-page'));
                });
            });

            // Start on the Dashboard page
            switchPage('dashboard');
        });
        
        // Mobile Offcanvas Toggle
        const offcanvas = document.getElementById('offcanvasNav');
        const overlay = document.getElementById('offcanvasOverlay');
        const openBtn = document.getElementById('openOffcanvas');
        const closeBtn = document.getElementById('closeOffcanvas');

        function toggleOffcanvas() {
            offcanvas.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        openBtn.addEventListener('click', toggleOffcanvas);
        closeBtn.addEventListener('click', toggleOffcanvas);
        
        
        // --- Modal Logic ---
        const modal = document.getElementById('bintrackModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const modalPrimary = document.getElementById('modalPrimary');

        function openModal(title, bodyHtml, primaryBtnText, primaryBtnAction, isDangerous = false) {
            modalTitle.innerHTML = title;
            modalBody.innerHTML = bodyHtml;
            
            modalPrimary.textContent = primaryBtnText;
            modalPrimary.className = isDangerous ? 'px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700' : 'px-4 py-2 bg-brand text-white rounded-lg font-medium hover:bg-green-700';
            
            modalPrimary.onclick = function() {
                if (typeof primaryBtnAction === 'function') {
                    primaryBtnAction();
                }
                closeModal();
            };
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
        
        // --- Mock Admin Functions ---
        window.handleLogout = function() {
            openModal(
                "Confirm Logout",
                "<p class='text-lg'>Are you sure you want to end your administrative session?</p>",
                "Logout",
                () => {
                    console.log("Admin user logged out successfully.");
                    // In a real application, you would call firebase.auth().signOut() here.
                    // For this mock UI, we just refresh the page or redirect.
                    // window.location.reload(); 
                },
                true // isDangerous=true for prominent action
            );
        }

        window.showBinProfile = function(binId) {
             openModal(
                `Bin Profile: ${binId}`,
                `<p class='text-sm mb-4'>Bin Configuration and Sensor Data History.</p>
                <p><strong>Location:</strong> 45 Oak St. (34.0, -118.0)</p>
                <p><strong>Current Status:</strong> <span class="status-full text-white px-2 py-0.5 rounded text-xs">FULL</span></p>
                <div class="map-placeholder h-40 my-3">Bin Fill-level History Chart</div>
                <div class="map-placeholder h-40">Bin Weight Usage Chart</div>
                `,
                "Close",
                null
            );
        }

        window.showSensorConfig = function(binId) {
             openModal(
                `Configure Sensor: ${binId}`,
                `<form>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Ultrasonic Threshold (cm for Nearly Full)</label>
                        <input type="number" class="w-full p-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" value="15">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Overload Weight Threshold (kg)</label>
                        <input type="number" class="w-full p-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" value="9.0">
                    </div>
                </form>`,
                "Save Configuration",
                () => console.log(`Configuration for ${binId} saved.`)
            );
        }
        
        window.mockAlertRefresh = function() {
            openModal(
                "Real-time Data Refresh",
                `<p class='text-lg text-brand'>Data synchronization successful.</p><p class='text-sm text-gray-500 dark:text-gray-400'>Alerts log updated with 2 new entries.</p>`,
                "OK",
                null
            );
        }
        
        window.assignCollector = function(binId) {
            openModal(
                `Assign Collector to ${binId}`,
                `<p class='mb-3'>Select a collector to handle the alert for <strong>${binId}</strong>:</p>
                <select class="w-full p-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                    <option>C901 - David Lee</option>
                    <option>C902 - Maria Chen</option>
                    <option>C903 - Unassigned</option>
                </select>`,
                "Assign & Notify",
                () => console.log(`Collector assigned to ${binId}.`)
            );
        }

        window.markResolved = function(binId) {
            openModal(
                `Mark Alert Resolved: ${binId}`,
                `<p class='text-lg'>Confirm that the issue for <strong>${binId}</strong> has been fully resolved (e.g., collected/repaired)?</p>`,
                "Confirm Resolution",
                () => console.log(`Alert for ${binId} marked resolved.`),
                true // isDangerous=true for permanent change
            );
        }

        window.promoteToCollector = function(user) {
            openModal(
                `Promote User: ${user}`,
                `<p class='text-lg'>Are you sure you want to promote <strong>${user}</strong> to the <strong>Collector</strong> role?</p>`,
                "Confirm Promotion",
                () => console.log(`${user} promoted to Collector.`)
            );
        }

        window.showRouteOptimizer = function() {
             openModal(
                `Route Optimization`,
                `<p class='text-sm mb-4'>The optimizer will analyze all Full/Nearly Full bins and create the most efficient route.</p>
                <p><strong>Bins to Include:</strong> 96 Critical Bins</p>
                <p><strong>Collectors Available:</strong> 12</p>
                <button class="bg-blue-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-600 mt-3">Run Optimization Algorithm</button>
                <div class="map-placeholder h-40 mt-3">Optimization Results</div>`,
                "Close",
                null
            );
        }
        
        window.sendRouteNotification = function(routeId) {
            openModal(
                `Send Alert to Collector on ${routeId}`,
                `<p class='text-lg'>Send a real-time notification to the collector on <strong>${routeId}</strong>?</p>
                 <textarea placeholder="Enter message (e.g., New critical stop added)" class="w-full p-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 mt-2"></textarea>`,
                "Send Message",
                () => console.log(`Notification sent to collector on ${routeId}.`)
            );
        }
    </script>
</body>
</html>