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
    <title>Contact Us - Statistics Project</title>
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

        .contact-section {
            background: white;
            border-radius: 1rem;
            padding: 3rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .contact-section h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .contact-section p {
            font-size: 1.125rem;
            color: #4b5563;
            line-height: 1.75;
            margin-bottom: 2rem;
        }

        .contact-form {
            max-width: 32rem;
            margin: 0 auto;
            text-align: left;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            color: #1f2937;
            transition: border-color 0.2s ease-in-out;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 8rem;
        }

        .submit-btn {
            display: inline-flex;
            align-items: center;
            background-color: #059669;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .submit-btn:hover {
            background-color: #047857;
        }

        .submit-btn:disabled {
            background-color: #6b7280;
            cursor: not-allowed;
        }

        .contact-info {
            margin-top: 2rem;
            text-align: center;
        }

        .contact-info p {
            font-size: 1rem;
            margin: 0.5rem 0;
        }

        .contact-info a {
            color: #2563eb;
            text-decoration: none;
            transition: color 0.2s ease-in-out;
        }

        .contact-info a:hover {
            color: #1d4ed8;
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

            .contact-section {
                padding: 2rem;
            }

            .contact-section h1 {
                font-size: 1.75rem;
            }

            .contact-section p {
                font-size: 1rem;
            }

            .contact-form {
                max-width: 100%;
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

            .submit-btn {
                width: 100%;
                justify-content: center;
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
            <a href="about.php">About Us</a>
            <a href="contact.php" class="active">Contact Us</a>
        </div>
        <a href="login.php" class="login-btn">Log In</a>
    </header>

    <!-- Main Content -->
    <main>
        <div class="contact-section">
            <h1>Contact Us</h1>
            <p>
                Have questions or feedback? We’d love to hear from you! Fill out the form below, and our team will get back
                to you as soon as possible.
            </p>
            <form id="contactForm" action="submit_contact.php" method="POST" class="contact-form">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required placeholder="Your Name">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Your Email">
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required placeholder="Your Message"></textarea>
                </div>
                <button type="submit" class="submit-btn">
                    <i class='bx bx-send mr-2'></i> Send Message
                </button>
            </form>
            <div class="contact-info">
                <p>Email: <a href="mailto:nagireddysivakumar952@gmail.com">nagireddysivakumar952@gmail.com</a></p>
                <p>Follow us on social media for updates!</p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="social-icons">
            <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
            <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
            <a href="https://github.com" target="_blank"><i class="fab fa-github"></i></a>
        </div>
        <p>© 2025 StatSync. All rights reserved.</p>
    </footer>

    <script>
        // Client-side form validation and submission handling
        document.getElementById('contactForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const message = document.getElementById('message').value.trim();
            const submitBtn = document.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;

            // Basic validation
            if (!name || !email || !message) {
                showError('Please fill out all fields.');
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('Please enter a valid email address.');
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin mr-2"></i> Sending...';

            // Send form data to backend
            fetch('submit_contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ name, email, message })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    submitBtn.innerHTML = '<i class="bx bx-check mr-2"></i> Sent!';
                    submitBtn.classList.replace('bg-green-600', 'bg-green-500');
                    document.getElementById('contactForm').reset();
                } else {
                    throw new Error(data.error || 'Failed to send message');
                }
            })
            .catch(error => {
                showError(error.message || 'Failed to send message. Please try again.');
                submitBtn.innerHTML = '<i class="bx bx-x mr-2"></i> Error';
                submitBtn.classList.replace('bg-green-600', 'bg-red-600');
            })
            .finally(() => {
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    submitBtn.classList.replace('bg-green-500', 'bg-green-600');
                    submitBtn.classList.replace('bg-red-600', 'bg-green-600');
                }, 2000);
            });
        });

        // Error message display
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-md transition-opacity duration-500';
            errorDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(errorDiv);

            setTimeout(() => {
                errorDiv.style.opacity = '0';
                setTimeout(() => errorDiv.remove(), 500);
            }, 3000);
        }
    </script>
</body>

</html>