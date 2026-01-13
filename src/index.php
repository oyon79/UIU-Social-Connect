<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UIU Social Connect</title>
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
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            color: white;
            box-shadow: 0 4px 14px rgba(255, 122, 0, 0.35);
        }

        .auth-navbar-btn-signup {
            background: transparent;
            color: var(--primary-orange);
        }

        .auth-navbar-btn-signup:hover {
            background: var(--primary-orange);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 122, 0, 0.3);
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

        /* Premium Animated Background Elements */
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

        /* Premium Login Container with Glass Morphism */
        .login-container {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 1px rgba(255, 122, 0, 0.1);
            padding: 3rem;
            position: relative;
            z-index: 10;
            animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .login-container::before {
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

        .login-container:hover {
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.2), 0 0 1px rgba(255, 122, 0, 0.2);
            transform: translateY(-2px);
        }

        /* Premium Logo with Glow Effect */
        .logo-container {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-orange-light) 100%);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2.5rem;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 8px 24px rgba(255, 122, 0, 0.35);
            position: relative;
        }

        .logo-container::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 26px;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
            filter: blur(8px);
        }

        .logo-container:hover {
            transform: rotate(8deg) scale(1.08);
            box-shadow: 0 12px 32px rgba(255, 122, 0, 0.5);
        }

        .logo-container:hover::before {
            opacity: 0.6;
            animation: pulse-glow 2s ease-in-out infinite;
        }

        .logo-container span {
            color: var(--white);
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            letter-spacing: 1px;
        }

        /* Header Styling with Gradient Text */
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-header h2 {
            margin-bottom: 0.75rem;
            background: linear-gradient(135deg, var(--dark-text), var(--gray-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
        }

        .login-header p {
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

        .input-group .form-control {
            padding-left: 3.5rem;
        }

        .input-icon {
            left: 1.25rem;
        }

        /* Premium Divider with Gradient */
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

        /* Fancy Link with Animated Underline */
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

        .fancy-link:hover {
            color: var(--primary-orange-hover);
            transform: translateX(2px);
        }

        /* Responsive */
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

            .login-container {
                padding: 2.5rem 2rem;
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
        <!-- Premium Floating Background Elements -->
        <div class="floating-bg floating-bg-1"></div>
        <div class="floating-bg floating-bg-2"></div>
        <div class="floating-bg floating-bg-3"></div>

        <!-- Login Container -->
        <div class="login-container">
            <!-- Header -->
            <div class="login-header">
                <h2>Welcome Back!</h2>
                <p>Login to continue to UIU Social Connect</p>
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

            <!-- Login Form -->
            <form id="loginForm">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="Enter your email"
                            required>
                    </div>
                    <div class="invalid-feedback" id="emailError"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            required>
                    </div>
                    <div class="invalid-feedback" id="passwordError"></div>
                </div>

                <div class="text-right mb-3">
                    <a href="#" id="forgotPasswordLink" class="text-primary hover-scale-sm" style="font-size: 0.9375rem; font-weight: 500;">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3" style="padding: 1rem; font-size: 1.0625rem;">
                    <span id="loginBtnText">Login to Account</span>
                    <div class="spinner spinner-sm" id="loginSpinner" style="display: none; border-top-color: white;"></div>
                </button>

                <div class="divider">
                    <span>or</span>
                </div>

                <div class="text-center">
                    <span style="color: var(--gray-dark); font-size: 0.9375rem;">Don't have an account?</span>
                    <a href="register.php" class="fancy-link">Sign up</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-backdrop" onclick="closeForgotPasswordModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Forgot Password?</h3>
                <button type="button" class="modal-close" onclick="closeForgotPasswordModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-center mb-4" style="color: var(--gray-dark);">
                    Enter your email address and we'll send you a link to reset your password
                </p>
                <form id="forgotPasswordForm">
                    <div class="form-group">
                        <input
                            type="email"
                            id="resetEmail"
                            name="resetEmail"
                            class="form-control"
                            placeholder="Enter your email"
                            required>
                        <div class="invalid-feedback" id="resetEmailError"></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>

</html>