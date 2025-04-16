<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Statistics Project</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Reset default margins and ensure full viewport width */
        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
        }

        /* Background gradient for the dashboard */
        body {
            background: linear-gradient(135deg, #e0f2fe 0%, #f9fafb 100%);
        }

        /* Header styles */
        header#navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 40;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        header .logo i {
            color: #2563eb;
            font-size: 1.5rem;
        }

        header .logo span {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }

        header .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        header .nav-links a {
            color: #4b5563;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease-in-out;
        }

        header .nav-links a:hover {
            color: #2563eb;
        }

        header .login-btn {
            background-color: #2563eb;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease-in-out;
        }

        header .login-btn:hover {
            background-color: #1d4ed8;
        }

        /* Main content styles */
        main {
            max-width: 64rem;
            margin: 8rem auto 4rem;
            padding: 2rem;
            text-align: center;
        }

        .hero {
            background: white;
            border-radius: 1rem;
            padding: 3rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .hero h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.125rem;
            color: #4b5563;
            line-height: 1.75;
            margin-bottom: 2rem;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .feature-card {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: left;
            transition: transform 0.2s ease-in-out;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-card i {
            color: #2563eb;
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .feature-card p {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .cta-btn {
            display: inline-flex;
            align-items: center;
            background-color: #059669;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 500;
            text-decoration: none;
            margin-top: 2rem;
            transition: background-color 0.2s ease-in-out;
        }

        .cta-btn:hover {
            background-color: #047857;
        }

        /* Footer styles */
        footer {
            background: #1f2937;
            color: white;
            padding: 2rem;
            text-align: center;
        }

        footer .social-icons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        footer .social-icons a {
            color: #d1d5db;
            font-size: 1.5rem;
            transition: color 0.2s ease-in-out;
        }

        footer .social-icons a:hover {
            color: #2563eb;
        }

        footer p {
            font-size: 0.875rem;
            color: #d1d5db;
            margin: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            header#navbar {
                padding: 1rem;
                flex-wrap: wrap;
            }

            header .nav-links {
                flex: 1;
                justify-content: center;
                gap: 1rem;
                order: 3;
                width: 100%;
                margin-top: 0.5rem;
            }

            header .logo {
                flex: 0;
            }

            header .login-btn {
                margin-left: auto;
            }

            main {
                margin: 10rem 1rem 2rem;
                padding: 1rem;
            }

            .hero {
                padding: 2rem;
            }

            .hero h1 {
                font-size: 1.75rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .features {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            header .nav-links {
                flex-direction: column;
                gap: 0.5rem;
            }

            header .login-btn {
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header id="navbar">
        <div class="logo">
            <i class='bx bx-bar-chart-alt-2'></i>
            <span>StatSync</span>
        </div>
        <div class="nav-links">
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact Us</a>
        </div>
        <a href="./auth/login.html" class="login-btn">Log In</a>
    </header>

    <!-- Main Content -->
    <main>
        <div class="hero">
            <h1>Welcome to StatSync</h1>
            <p>
                Unlock the power of your data with our intuitive platform. Upload datasets, create stunning visualizations,
                apply advanced filters, and annotate your insights—all in one place. Whether you're a data enthusiast or a
                professional analyst, our tools make data exploration seamless and engaging.
            </p>
            <div class="features">
                <div class="feature-card">
                    <i class='bx bx-upload'></i>
                    <h3>Easy Data Upload</h3>
                    <p>Quickly upload your datasets in various formats and start analyzing immediately.</p>
                </div>
                <div class="feature-card">
                    <i class='bx bx-line-chart'></i>
                    <h3>Advanced Visualizations</h3>
                    <p>Create bar, line, pie, and scatter charts to visualize your data dynamically.</p>
                </div>
                <div class="feature-card">
                    <i class='bx bx-filter-alt'></i>
                    <h3>Powerful Filters</h3>
                    <p>Refine your datasets with date ranges and custom criteria for precise insights.</p>
                </div>
                <div class="feature-card">
                    <i class='bx bx-note'></i>
                    <h3>Insightful Notes</h3>
                    <p>Add and manage notes to capture observations and share findings effortlessly.</p>
                </div>
            </div>
            <a href="./auth/login.html" class="cta-btn">
                <i class='bx bx-rocket mr-2'></i> Get Started
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="social-icons">
            <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
            <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
            <a href="https://github.com" target="_blank"><i class="fab fa-github"></i></a>
        </div>
        <p>© 2025 Statistics Project. All rights reserved.</p>
    </footer>
</body>

</html>