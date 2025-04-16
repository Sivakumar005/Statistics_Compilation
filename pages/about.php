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
    <title>About Us - Statistics Project</title>
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

        /* Background gradient */
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

        header .nav-links a.active {
            color: #2563eb;
            font-weight: 600;
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

        .about-section {
            background: white;
            border-radius: 1rem;
            padding: 3rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .about-section h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .about-section p {
            font-size: 1.125rem;
            color: #4b5563;
            line-height: 1.75;
            margin-bottom: 2rem;
        }

        .team {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .team-member {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s ease-in-out;
        }

        .team-member:hover {
            transform: translateY(-5px);
        }

        .team-member i {
            color: #2563eb;
            font-size: 3rem;
            margin-bottom: 0.75rem;
        }

        .team-member h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .team-member p {
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

            .about-section {
                padding: 2rem;
            }

            .about-section h1 {
                font-size: 1.75rem;
            }

            .about-section p {
                font-size: 1rem;
            }

            .team {
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
            <a href="dashboard.php">Home</a>
            <a href="about.php" class="active">About Us</a>
            <a href="contact.php">Contact Us</a>
        </div>
        <a href="./auth/login.html" class="login-btn">Log In</a>
    </header>

    <!-- Main Content -->
    <main>
        <div class="about-section">
            <h1>About Our Statistics Project</h1>
            <p>
                At Statistics Project, we’re passionate about making data accessible and actionable. Our platform empowers
                users to upload datasets, create dynamic visualizations, apply advanced filters, and annotate insights with
                ease. Whether you're analyzing trends, sharing findings, or exploring new datasets, we provide the tools to
                turn raw data into meaningful stories.
            </p>
            <p>
                Founded with a mission to simplify data analysis, we combine intuitive design with powerful functionality.
                Our goal is to support data enthusiasts, researchers, and professionals in uncovering insights without the
                complexity of traditional tools.
            </p>
            <div class="team">
                <div class="team-member">
                    <i class='bx bx-user'></i>
                    <h3>Nagireddy Sivakumar</h3>
                </div>
                <div class="team-member">
                    <i class='bx bx-user'></i>
                    <h3>Sanju sri</h3>
                </div>
                <div class="team-member">
                    <i class='bx bx-user'></i>
                    <h3>Sai Mourya</h3>
                </div>
            </div>
            <a href="./auth/login.html" class="cta-btn">
                <i class='bx bx-rocket mr-2'></i> Start Exploring
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