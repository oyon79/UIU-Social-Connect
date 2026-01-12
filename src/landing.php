<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UIU Social Connect - Connect, Share, Grow</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        /* Premium Navbar */
        .landing-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.25rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            animation: slideDown 0.5s ease;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .navbar-logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(255, 122, 0, 0.3);
            transition: all 0.3s ease;
        }

        .navbar-logo-icon:hover {
            transform: rotate(5deg) scale(1.05);
            box-shadow: 0 6px 16px rgba(255, 122, 0, 0.4);
        }

        .navbar-logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-buttons {
            display: flex;
            gap: 1rem;
        }

        .navbar-btn {
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
        }

        .navbar-btn-login {
            background: transparent;
            color: var(--primary-orange);
            border: 2px solid var(--primary-orange);
        }

        .navbar-btn-login:hover {
            background: var(--primary-orange);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 122, 0, 0.3);
        }

        .navbar-btn-signup {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            color: white;
            border: 2px solid transparent;
            box-shadow: 0 4px 14px rgba(255, 122, 0, 0.35);
        }

        .navbar-btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 122, 0, 0.45);
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, #FFF5EB 0%, #FFFFFF 50%, #FFE5D1 100%);
            position: relative;
            overflow: hidden;
            padding-top: 100px;
        }

        /* Background Pattern */
        .hero-background {
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1200');
            background-size: cover;
            background-position: center;
            opacity: 0.15;
            z-index: 0;
        }

        .hero-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, #FFF5EB 0%, transparent 100%);
        }

        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 4rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content {
            padding-right: 2rem;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            background: rgba(255, 122, 0, 0.1);
            border-radius: 50px;
            color: var(--primary-orange);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            animation: bounceIn 0.6s ease;
        }

        .hero-title {
            font-size: 3.75rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--dark-text), var(--gray-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fadeInUp 0.6s ease 0.2s both;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--gray-dark);
            line-height: 1.8;
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.6s ease 0.4s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1.25rem;
            animation: fadeInUp 0.6s ease 0.6s both;
        }

        .hero-btn {
            padding: 1rem 2.5rem;
            min-width: 180px;
            font-size: 1.125rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .hero-btn-primary {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            color: white;
            border: none;
            box-shadow: 0 8px 20px rgba(255, 122, 0, 0.35);
        }

        .hero-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(255, 122, 0, 0.45);
        }

        .hero-btn-secondary {
            background: white;
            color: var(--primary-orange);
            border: 2px solid var(--gray-medium);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .hero-btn-secondary:hover {
            border-color: var(--primary-orange);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 122, 0, 0.2);
        }

        .hero-image {
            position: relative;
            animation: fadeInRight 0.8s ease 0.4s both;
        }

        .hero-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        /* Features Section */
        .features-section {
            background: white;
            padding: 6rem 4rem;
        }

        .features-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .features-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .features-title {
            font-size: 2.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--dark-text), var(--gray-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .features-subtitle {
            font-size: 1.125rem;
            color: var(--gray-dark);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: white;
            border: 2px solid var(--gray-light);
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-orange), var(--primary-orange-light));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            border-color: var(--primary-orange);
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(255, 122, 0, 0.15);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.1), rgba(255, 179, 102, 0.1));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
            transform: scale(1.1) rotate(5deg);
        }

        .feature-icon svg {
            width: 32px;
            height: 32px;
            color: var(--primary-orange);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon svg {
            color: white;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-text);
        }

        .feature-description {
            font-size: 1rem;
            color: var(--gray-dark);
            line-height: 1.7;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .navbar-container {
                padding: 0 2rem;
            }

            .hero-container {
                padding: 3rem 2rem;
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .hero-content {
                padding-right: 0;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .features-section {
                padding: 4rem 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .navbar-container {
                padding: 0 1.5rem;
            }

            .navbar-buttons {
                gap: 0.75rem;
            }

            .navbar-btn {
                padding: 0.625rem 1.5rem;
                min-width: 110px;
                font-size: 0.9rem;
            }

            .hero-container {
                padding: 2rem 1.5rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .hero-btn {
                width: 100%;
            }

            .features-section {
                padding: 3rem 1.5rem;
            }

            .features-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Premium Navbar -->
    <nav class="landing-navbar">
        <div class="navbar-container">
            <div class="navbar-logo">
                <div class="navbar-logo-icon">UIU</div>
                <span class="navbar-logo-text">UIU Social Connect</span>
            </div>
            <div class="navbar-buttons">
                <a href="index.php" class="navbar-btn navbar-btn-login">Login</a>
                <a href="register.php" class="navbar-btn navbar-btn-signup">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background"></div>
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    UIU's Official Social Platform
                </div>
                
                <h1 class="hero-title">Connect, Share & Grow Together</h1>
                
                <p class="hero-subtitle">
                    Join UIU Social Connect - The ultimate platform for students, faculty, and alumni to network, collaborate, and stay updated with campus life.
                </p>
                
                <div class="hero-buttons">
                    <a href="register.php" class="hero-btn hero-btn-primary">
                        Get Started Free
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                    <a href="#features" class="hero-btn hero-btn-secondary">
                        Learn More
                    </a>
                </div>
            </div>
            
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800" alt="Students collaborating">
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="features-container">
            <div class="features-header">
                <h2 class="features-title">Everything You Need in One Place</h2>
                <p class="features-subtitle">
                    A comprehensive platform designed specifically for the UIU community
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card animate-fade-in">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </div>
                    <h3 class="feature-title">Dynamic Newsfeed</h3>
                    <p class="feature-description">
                        Stay updated with posts, events, and announcements from your university community in real-time.
                    </p>
                </div>

                <div class="feature-card animate-fade-in" style="animation-delay: 0.1s">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Groups & Clubs</h3>
                    <p class="feature-description">
                        Join clubs, create study groups, and connect with students who share your interests and goals.
                    </p>
                </div>

                <div class="feature-card animate-fade-in" style="animation-delay: 0.2s">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="feature-title">Events & Workshops</h3>
                    <p class="feature-description">
                        Discover and RSVP to campus events, workshops, and seminars. Never miss an important event again.
                    </p>
                </div>

                <div class="feature-card animate-fade-in" style="animation-delay: 0.3s">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Jobs & Internships</h3>
                    <p class="feature-description">
                        Access exclusive job postings and internship opportunities tailored for UIU students and alumni.
                    </p>
                </div>

                <div class="feature-card animate-fade-in" style="animation-delay: 0.4s">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Marketplace</h3>
                    <p class="feature-description">
                        Buy and sell textbooks, electronics, and other items within the trusted UIU community.
                    </p>
                </div>

                <div class="feature-card animate-fade-in" style="animation-delay: 0.5s">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Direct Messaging</h3>
                    <p class="feature-description">
                        Connect instantly with peers, faculty, and alumni through secure, real-time messaging.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/main.js"></script>
</body>
</html>
