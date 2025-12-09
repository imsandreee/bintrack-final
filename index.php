<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinTrack — Smart IoT Waste Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

 <?php
include 'includes/home/navbar.php';
?>

    <header class="masthead text-white d-flex align-items-center">
        <div class="container px-4 px-lg-5 text-center">
            <h1 class="display-3 fw-bold mb-3">BinTrack — Smart IoT Waste Monitoring System</h1>
            <p class="lead mb-5 mx-auto">An <b>IoT-powered solution</b> for <b>real-time waste monitoring</b>, <b>optimized collection routes</b>, and <b>cleaner communities</b>.</p>
            <a class="btn btn-primary btn-xl me-2" href="#features">Learn More</a>
            <a class="btn btn-outline-light btn-xl" href="#">Get Started</a>
        </div>
    </header>

    <section id="about" class="py-5">
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="section-heading mb-4">The Challenge: Outdated Waste Management</h2>
                    <p class="text-muted lead mb-4">Current waste collection methods are often reactive, leading to unnecessary costs, missed pickups, and environmental strain. BinTrack addresses these critical pain points:</p>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <i class="bi bi-truck-flatbed icon-large text-primary"></i>
                            <h5 class="mt-3">Inefficient Collection</h5>
                            <p class="text-secondary">Fixed routes result in wasted fuel and time collecting half-empty bins.</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <i class="bi bi-trash icon-large text-primary"></i>
                            <h5 class="mt-3">Overflowing Bins</h5>
                            <p class="text-secondary">Lack of real-time monitoring leads to unsightly, unsanitary, and polluting overflowing bins.</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <i class="bi bi-clock-history icon-large text-primary"></i>
                            <h5 class="mt-3">Delayed Response</h5>
                            <p class="text-secondary">Manual reporting and poor visibility mean slow responses to critical situations.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="bg-light py-5">
        <div class="container px-4 px-lg-5">
            <h2 class="section-heading text-center mb-5">Key Features of BinTrack</h2>
            <div class="row gx-4 gx-lg-5">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100 shadow-sm border-0 text-center p-3">
                        <div class="card-body">
                            <i class="bi bi-bar-chart-line-fill text-primary icon-medium mb-3"></i>
                            <h5 class="card-title fw-bold">Real-time Fill-Level</h5>
                            <p class="card-text text-secondary">Continuously monitor the waste volume in every bin across the municipality.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100 shadow-sm border-0 text-center p-3">
                        <div class="card-body">
                            <i class="bi bi-geo-alt-fill text-primary icon-medium mb-3"></i>
                            <h5 class="card-title fw-bold">Optimized Route Mapping</h5>
                            <p class="card-text text-secondary">Dynamic, map-based route generation based on bin capacity and location data.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100 shadow-sm border-0 text-center p-3">
                        <div class="card-body">
                            <i class="bi bi-bell-fill text-primary icon-medium mb-3"></i>
                            <h5 class="card-title fw-bold">Automated Alerts & Dashboards</h5>
                            <p class="card-text text-secondary">Instant notifications for near-full bins sent to municipal staff and quick action dashboards.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100 shadow-sm border-0 text-center p-3">
                        <div class="card-body">
                            <i class="bi bi-phone-fill text-primary icon-medium mb-3"></i>
                            <h5 class="card-title fw-bold">Citizen Web App</h5>
                            <p class="card-text text-secondary">Allows citizens to view collection schedules and report issues with specific bins.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100 shadow-sm border-0 text-center p-3">
                        <div class="card-body">
                            <i class="bi bi-cpu-fill text-primary icon-medium mb-3"></i>
                            <h5 class="card-title fw-bold">IoT Sensor Data</h5>
                            <p class="card-text text-secondary">Utilizes Ultrasonic, Weight (Load Cell), and GPS sensors for comprehensive data collection.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100 shadow-sm border-0 text-center p-3">
                        <div class="card-body">
                            <i class="bi bi-speedometer text-primary icon-medium mb-3"></i>
                            <h5 class="card-title fw-bold">Performance Tracking</h5>
                            <p class="card-text text-secondary">Monitor key performance indicators (KPIs) like collection efficiency and response time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="components" class="py-5 bg-white">
    <div class="container px-4 px-lg-5">
        <h2 class="section-heading text-center mb-5">The Technology Under the Hood</h2>
        <div class="row gx-4 gx-lg-5 text-center">
            <div class="col-md-3 mb-4">
                <div class="component-box py-4 px-3 h-100">
                    <i class="bi bi-gear-fill icon-large text-primary mb-3"></i>
                    <h5 class="fw-bold">IoT Microcontroller</h5>
                    <p class="text-primary">(ESP32)</p>
                    <p class="text-secondary">The **brains** of the bin, processing sensor data and connecting to the cloud.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="component-box py-4 px-3 h-100">
                    <i class="bi bi-arrows-collapse icon-large text-primary mb-3"></i>
                    <h5 class="fw-bold">Ultrasonic Sensor</h5>
                    <p class="text-primary">Fill-Level Measurement</p>
                    <p class="text-secondary">Measures the distance from the sensor to the waste, determining the fill percentage.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="component-box py-4 px-3 h-100">
                    <i class="bi bi-clipboard-data-fill icon-large text-primary mb-3"></i>
                    <h5 class="fw-bold">Load Cell (Weight)</h5>
                    <p class="text-primary">Weight Measurement</p>
                    <p class="text-secondary">Tracks the actual weight of the waste for better density analysis and theft detection.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="component-box py-4 px-3 h-100">
                    <i class="bi bi-globe-americas icon-large text-primary mb-3"></i>
                    <h5 class="fw-bold">GPS Module</h5>
                    <p class="text-primary">Location Tracking</p>
                    <p class="text-secondary">Provides accurate geographical coordinates for map-based visualization and route planning.</p>
                </div>
            </div>
        </div>
    </div>
</section>

    <section id="howitworks" class="bg-primary py-5 text-white">
        <div class="container px-4 px-lg-5">
            <h2 class="section-heading text-center mb-5 text-white">How BinTrack Works</h2>
            <div class="row gx-4 gx-lg-5 text-center process-flow">
                <div class="col-md-2 process-step">
                    <i class="bi bi-hdd-fill icon-xl text-white mb-2"></i>
                    <p class="fw-bold">1. Sensors Detect</p>
                </div>
                <div class="col-md-2 process-arrow d-flex align-items-center justify-content-center">
                    <i class="bi bi-arrow-right icon-large text-white-50"></i>
                </div>
                <div class="col-md-2 process-step">
                    <i class="bi bi-cloud-arrow-up-fill icon-xl text-white mb-2"></i>
                    <p class="fw-bold">2. Data to Cloud</p>
                </div>
                <div class="col-md-2 process-arrow d-flex align-items-center justify-content-center">
                    <i class="bi bi-arrow-right icon-large text-white-50"></i>
                </div>
                <div class="col-md-2 process-step">
                    <i class="bi bi-display-fill icon-xl text-white mb-2"></i>
                    <p class="fw-bold">3. Dashboard Alerts</p>
                </div>
                <div class="col-md-2 process-arrow d-flex align-items-center justify-content-center">
                    <i class="bi bi-arrow-right icon-large text-white-50"></i>
                </div>
                <div class="col-md-2 process-step">
                    <i class="bi bi-truck-flatbed icon-xl text-white mb-2"></i>
                    <p class="fw-bold">4. Municipal Action</p>
                </div>
                <div class="col-md-2 process-arrow d-flex align-items-center justify-content-center">
                    <i class="bi bi-arrow-right icon-large text-white-50"></i>
                </div>
                <div class="col-md-2 process-step">
                    <i class="bi bi-house-door-fill icon-xl text-white mb-2"></i>
                    <p class="fw-bold">5. Cleaner Cities</p>
                </div>
            </div>
        </div>
    </section>

    <section id="objectives" class="py-5 bg-light">
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <div class="p-4 h-100 card-light">
                        <h3 class="fw-bold mb-3"><i class="bi bi-check2-circle me-2 text-primary"></i>Objectives</h3>
                        <ul class="list-unstyled text-secondary">
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>**General:** To develop a real-time, cost-effective waste monitoring system.</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>**Specific 1:** Design and implement a multi-sensor IoT device prototype.</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>**Specific 2:** Create a cloud-based dashboard for data visualization and routing.</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>**Specific 3:** Validate system efficiency in reducing overflow and optimizing collection.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <div class="p-4 h-100 card-light">
                        <h3 class="fw-bold mb-3"><i class="bi bi-bounding-box-circles me-2 text-primary"></i>Scope</h3>
                        <ul class="list-unstyled text-secondary">
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>Real-time **Fill-Level** and **Weight** monitoring.</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>Map-based visualization and **optimized route maps**.</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>**Automated alerts** and **quick actions** panel for staff.</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>**Notification system** for critical bin status.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="p-4 h-100 card-light">
                        <h3 class="fw-bold mb-3"><i class="bi bi-exclamation-triangle-fill me-2 text-primary"></i>Limitations</h3>
                        <ul class="list-unstyled text-secondary">
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>**Weather Dependency:** Extreme weather (e.g., heavy rain) may affect sensor accuracy.</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>**Internet/Power:** Requires consistent Wi-Fi/cellular connectivity and stable power source.</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>**Area Constraint:** Initial prototype testing is limited to a small, defined area.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="team" class="py-5 bg-white">
        <div class="container px-4 px-lg-5">
            <h2 class="section-heading text-center mb-5">Meet **ISsential TechHive**</h2>
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card team-card h-100 shadow-sm border-0 text-center">
                        <div class="card-body p-4">
                            <i class="bi bi-person-circle icon-xl text-secondary mb-3"></i>
                            <h5 class="card-title fw-bold">Roxas, Renalyn</h5>
                            <p class="card-text text-primary">Project Manager</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card team-card h-100 shadow-sm border-0 text-center">
                        <div class="card-body p-4">
                            <i class="bi bi-person-circle icon-xl text-secondary mb-3"></i>
                            <h5 class="card-title fw-bold">Antang, Sandree</h5>
                            <p class="card-text text-primary">Researcher / Back-End Developer</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card team-card h-100 shadow-sm border-0 text-center">
                        <div class="card-body p-4">
                            <i class="bi bi-person-circle icon-xl text-secondary mb-3"></i>
                            <h5 class="card-title fw-bold">Aquino, John Benedict</h5>
                            <p class="card-text text-primary">Back-End & Front-End Developer</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card team-card h-100 shadow-sm border-0 text-center">
                        <div class="card-body p-4">
                            <i class="bi bi-person-circle icon-xl text-secondary mb-3"></i>
                            <h5 class="card-title fw-bold">Dueñas, Jan Mishael</h5>
                            <p class="card-text text-primary">Researcher / System Analyst / Back-End Developer</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card team-card h-100 shadow-sm border-0 text-center">
                        <div class="card-body p-4">
                            <i class="bi bi-person-circle icon-xl text-secondary mb-3"></i>
                            <h5 class="card-title fw-bold">Mirafuente, Mary May</h5>
                            <p class="card-text text-primary">Researcher / UI Designer</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-4 bg-primary">
        <div class="container px-4 px-lg-5">
            <div class="small text-center text-white-50">
                &copy; 2024 BinTrack — ISsential TechHive. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>