<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UIU Social Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #FFF5EB 0%, #FFFFFF 50%, #FFE5D1 100%);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        /* Premium Navbar */
        .auth-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            animation: slideDown 0.5s ease;
        }

        .auth-navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .auth-navbar-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            text-decoration: none;
        }

        .auth-navbar-logo-icon {
            width: 80px;
            height: 80px;
        }

        .auth-navbar-logo-text {
            font-size: 1.375rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-navbar-buttons {
            display: flex;
            gap: 1rem;
        }

        .auth-navbar-btn {
            padding: 0.75rem 2rem;
            min-width: 140px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--primary-orange);
        }

        .auth-navbar-btn-login {
            background: transparent;
            color: var(--primary-orange);
        }

        .auth-navbar-btn-login:hover {
            background: var(--primary-orange);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 122, 0, 0.3);
        }

        .auth-navbar-btn-signup {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            color: white;
            box-shadow: 0 4px 14px rgba(255, 122, 0, 0.35);
        }

        /* Main Container */
        .auth-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 2rem 3rem;
            position: relative;
        }

        /* Floating Backgrounds */
        .floating-bg {
            position: absolute;
            border-radius: 50%;
            opacity: 0.08;
            filter: blur(40px);
            pointer-events: none;
        }

        .floating-bg-1 {
            top: 10%;
            left: -5%;
            width: 20rem;
            height: 20rem;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            animation: float 8s ease-in-out infinite;
        }

        .floating-bg-2 {
            bottom: 10%;
            right: -5%;
            width: 25rem;
            height: 25rem;
            background: linear-gradient(135deg, var(--primary-orange-light), var(--primary-orange));
            animation: floatSlow 12s ease-in-out infinite;
            animation-delay: 2s;
        }

        .floating-bg-3 {
            top: 50%;
            right: 15%;
            width: 15rem;
            height: 15rem;
            background: linear-gradient(135deg, #FFB366, #FF7A00);
            animation: bounce 3s ease-in-out infinite;
        }

        /* Register Container */
        .register-container {
            width: 100%;
            max-width: 650px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 3rem;
            position: relative;
            z-index: 10;
            animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 24px;
            padding: 2px;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.2), transparent);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        .logo-container {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2.5rem;
            box-shadow: 0 8px 24px rgba(255, 122, 0, 0.35);
            position: relative;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .logo-container:hover {
            transform: rotate(8deg) scale(1.08);
            box-shadow: 0 12px 32px rgba(255, 122, 0, 0.5);
        }

        .logo-container span {
            color: var(--white);
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            letter-spacing: 1px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .register-header h2 {
            margin-bottom: 0.75rem;
            background: linear-gradient(135deg, var(--dark-text), var(--gray-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
        }

        .register-header p {
            color: var(--gray-dark);
            margin: 0;
            font-size: 1rem;
        }

        /* Form Improvements */
        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--dark-text);
            font-size: 0.9375rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            background-color: var(--white);
            border: 2px solid var(--gray-medium);
            border-radius: 12px;
            transition: all var(--transition-base);
            box-shadow: var(--shadow-sm);
        }

        .form-control::placeholder {
            color: #9CA3AF;
            font-size: 0.9375rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .divider {
            position: relative;
            text-align: center;
            margin: 2rem 0;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gray-medium), transparent);
        }

        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 1.5rem;
            position: relative;
            color: var(--gray-dark);
            font-size: 0.9375rem;
            font-weight: 500;
        }

        .fancy-link {
            color: var(--primary-orange);
            font-weight: 600;
            position: relative;
            transition: all 0.3s ease;
        }

        .fancy-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-orange), var(--primary-orange-light));
            transition: width 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .fancy-link:hover::after {
            width: 100%;
        }

        @media (max-width: 768px) {
            .auth-navbar-container {
                padding: 0 1.5rem;
            }

            .auth-navbar-btn {
                padding: 0.625rem 1.5rem;
                min-width: 110px;
                font-size: 0.9rem;
            }

            .auth-main {
                padding: 100px 1.5rem 2rem;
            }

            .register-container {
                padding: 2.5rem 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .logo-container {
                width: 90px;
                height: 90px;
            }

            .logo-container span {
                font-size: 1.75rem;
            }
        }
    </style>
</head>

<body>
    <!-- Premium Navbar -->
    <nav class="auth-navbar">
        <div class="auth-navbar-container">
            <a href="landing.php" class="auth-navbar-logo">
                <img class="auth-navbar-logo-icon" src="./assets/uiu-logo.png" alt="UIU Logo">
                <span class="auth-navbar-logo-text">UIU Social Connect</span>
            </a>
            <div class="auth-navbar-buttons">
                <a href="index.php" class="auth-navbar-btn auth-navbar-btn-login">Login</a>
                <a href="register.php" class="auth-navbar-btn auth-navbar-btn-signup">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="auth-main">
        <!-- Floating Backgrounds -->
        <div class="floating-bg floating-bg-1"></div>
        <div class="floating-bg floating-bg-2"></div>
        <div class="floating-bg floating-bg-3"></div>

        <!-- Register Container -->
        <div class="register-container">

            <!-- Header -->
            <div class="register-header">
                <h2>Create Account</h2>
                <p>Join UIU Social Connect today</p>
            </div>

            <!-- Success Message -->
            <div id="successMessage" class="alert alert-success" style="display: none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span id="successText"></span>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="alert alert-error" style="display: none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span id="errorText"></span>
            </div>

            <!-- Registration Form -->
            <form id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input
                            type="text"
                            id="fullName"
                            name="fullName"
                            class="form-control"
                            placeholder="John Doe"
                            required>
                        <div class="invalid-feedback" id="fullNameError"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email (UIU)</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="student@uiu.ac.bd"
                            required>
                        <div class="invalid-feedback" id="emailError"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Min. 6 characters"
                            required>
                        <div class="invalid-feedback" id="passwordError"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input
                            type="password"
                            id="confirmPassword"
                            name="confirmPassword"
                            class="form-control"
                            placeholder="Re-enter password"
                            required>
                        <div class="invalid-feedback" id="confirmPasswordError"></div>
                    </div>
                </div>
<div class="form-row">
  <div class="form-group">
                    <label class="form-label">Role</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Select your role</option>
                        <option value="Student">Student</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Alumni">Alumni</option>
                        <option value="Staff">Staff</option>
                        <option value="Club Forum">Club Forum</option>
                    </select>
                    <div class="invalid-feedback" id="roleError"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Student/Employee ID (Optional)</label>
                    <input
                        type="text"
                        id="studentId"
                        name="studentId"
                        class="form-control"
                        placeholder="e.g., 011201234">
                </div>
</div>

              

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <span id="registerBtnText">Create Account</span>
                    <div class="spinner spinner-sm" id="registerSpinner" style="display: none; border-top-color: white;"></div>
                </button>

                <div class="divider">
                    <span>or</span>
                </div>

                <div class="text-center">
                    <span style="color: var(--gray-dark);">Already have an account?</span>
                    <a href="index.php" class="fancy-link">Login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const registerForm = document.getElementById('registerForm');
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');

        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Clear previous errors
            document.querySelectorAll('.form-control').forEach(input => {
                input.classList.remove('is-invalid');
            });
            document.querySelectorAll('.invalid-feedback').forEach(error => {
                error.textContent = '';
            });
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';

            // Get form data (trim & normalize to snake_case for API)
            const fullName = document.getElementById('fullName').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const role = document.getElementById('role').value;
            const studentId = document.getElementById('studentId').value.trim();

            // Client-side validation
            let isValid = true;

            // Email validation (must be a UIU email under a UIU subdomain)
            const uiuEmailRegex = /^[^\s@]+@[^\s@]+\.uiu\.ac\.bd$/i;
            if (!uiuEmailRegex.test(email)) {
                document.getElementById('email').classList.add('is-invalid');
                document.getElementById('emailError').textContent = 'Must be a UIU email (e.g., user@bscse.uiu.ac.bd)';
                isValid = false;
            }

            // Password validation
            if (password.length < 6) {
                document.getElementById('password').classList.add('is-invalid');
                document.getElementById('passwordError').textContent = 'Password must be at least 6 characters';
                isValid = false;
            }

            // Confirm password
            if (password !== confirmPassword) {
                document.getElementById('confirmPassword').classList.add('is-invalid');
                document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
                isValid = false;
            }

            if (!isValid) return;

            // Show loading
            document.getElementById('registerBtnText').style.display = 'none';
            document.getElementById('registerSpinner').style.display = 'block';

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'register',
                        full_name: fullName,
                        email: email,
                        password: password,
                        role: role,
                        student_id: studentId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    successMessage.style.display = 'flex';
                    document.getElementById('successText').textContent = data.message;
                    registerForm.reset();

                    // Redirect to login after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    errorMessage.style.display = 'flex';
                    document.getElementById('errorText').textContent = data.message || 'Registration failed';
                }
            } catch (error) {
                errorMessage.style.display = 'flex';
                document.getElementById('errorText').textContent = 'Connection error. Please try again.';
            } finally {
                document.getElementById('registerBtnText').style.display = 'block';
                document.getElementById('registerSpinner').style.display = 'none';
            }
        });
    </script>
</body>

</html>