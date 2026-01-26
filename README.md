# 🎓 UIU Social Connect

<div align="center">

![UIU Social Connect](https://img.shields.io/badge/Version-1.0.0-orange?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.0+-blue?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange?style=for-the-badge&logo=mysql)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**A Professional Social Media Platform for United International University**

[Features](#-features) • [Installation](#-installation) • [Usage](#-usage) • [Documentation](#-documentation) • [Contributing](#-contributing)

</div>

---

## 📋 Table of Contents

- [About](#-about)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Database Setup](#-database-setup)
- [Usage](#-usage)
- [API Documentation](#-api-documentation)
- [Admin Panel](#-admin-panel)
- [Screenshots](#-screenshots)
- [Security](#-security)
- [Contributing](#-contributing)
- [License](#-license)
- [Contact](#-contact)

---

## 🎯 About

**UIU Social Connect** is a comprehensive social networking platform specifically designed for the United International University community. It provides a secure, feature-rich environment where students, faculty, alumni, and staff can connect, collaborate, and share knowledge.

### 🌟 Why UIU Social Connect?

- **University-Focused**: Tailored specifically for academic communities
- **Role-Based Access**: Different features for Students, Faculty, Alumni, Staff, and Club Forums
- **Content Moderation**: Admin approval system ensures quality content
- **Professional Design**: Modern, responsive UI with premium glass-morphism effects
- **Secure**: Built with security best practices and input validation

---

## ✨ Features

### 👥 User Features

#### 🔐 Authentication & Profiles

- **Secure Registration** with email verification
- **Login System** with session management
- **User Profiles** with customizable bio, skills, and contact information
- **Profile & Cover Photos** with image upload
- **Role-Based Registration**: Student, Faculty, Alumni, Staff, Club Forum

#### 📰 Social Networking

- **News Feed** with real-time posts
- **Create Posts** with text, images, and videos
- **Like & Comment** on posts
- **Share Posts** with your network
- **Follow System** to connect with others
- **Activity Feed** showing recent interactions

#### 💬 Communication

- **Direct Messaging** with real-time chat
- **Message Notifications** with unread badges
- **Group Messaging** support
- **File Sharing** in messages
- **Online Status Indicators**

#### 🎓 Academic Features

- **Course Groups** organized by department and batch
- **Document Sharing** with PDF, DOC, and PPT support
- **Academic Notices** from university administration
- **Event Calendar** for university events
- **Job Board** for internships and career opportunities

#### 🛍️ Community Features

- **Marketplace** for buying/selling items
- **Event Management** with RSVP
- **Groups & Forums** for clubs and interests
- **Search Functionality** to find users, posts, and groups
- **Notifications System** for all activities

### 🔧 Admin Features

#### 📊 Dashboard

- **Statistics Overview** with user, post, and engagement metrics
- **Real-Time Monitoring** of platform activity
- **User Analytics** by role, department, and batch
- **Content Metrics** (posts, comments, likes)

#### ✅ Content Moderation

- **User Approval System** for new registrations
- **Post Moderation** before publishing
- **Event Approval** for campus events
- **Job Listing Approval** for career opportunities
- **Bulk Actions** for efficient moderation

#### 👨‍💼 User Management

- **View All Users** with filtering and search
- **User Details** with full activity history
- **Ban/Unban Users** for policy violations
- **Role Management** and permissions
- **Batch Management** for students

#### 📝 Content Management

- **Manage Posts** (approve, delete, feature)
- **Manage Events** (approve, edit, cancel)
- **Manage Jobs** (approve, expire, feature)
- **Manage Groups** (create, moderate, dissolve)
- **Document Management** with version control

#### 📢 Notices & Announcements

- **Create Notices** for university-wide announcements
- **Target Specific Roles** (students, faculty, alumni)
- **Schedule Notices** for future publishing
- **Notice Templates** for common announcements

---

## 🛠️ Tech Stack

### Backend

- **PHP 8.0+** - Server-side scripting
- **MySQL 5.7+** - Relational database
- **PDO** - Database abstraction layer
- **Sessions** - User authentication

### Frontend

- **HTML5** - Semantic markup
- **CSS3** - Modern styling with custom properties
- **JavaScript (Vanilla)** - Client-side interactions
- **AJAX/Fetch API** - Asynchronous requests

### Design

- **Custom CSS Framework** - Tailored components
- **Glass Morphism** - Premium UI effects
- **Responsive Design** - Mobile-first approach
- **CSS Animations** - Smooth transitions and effects
- **Google Fonts (Poppins)** - Professional typography

### Development Tools

- **XAMPP** - Local development environment
- **Git** - Version control
- **VS Code** - Code editor

---

## 📁 Project Structure

```
UIU-Social-Connect/
│
├── 📄 index.php                 # Login page
├── 📄 landing.php               # Landing page
├── 📄 register.php              # Registration page
│
├── 📂 admin/                    # Admin panel
│   ├── index.php                # Admin dashboard
│   ├── users.php                # User management
│   ├── approvals.php            # Content approval
│   ├── content.php              # Content management
│   ├── documents.php            # Document management
│   └── create_course_groups.php # Course group creation
│
├── 📂 dashboard/                # User dashboard
│   ├── newsfeed.php             # Main feed
│   ├── profile.php              # User profile
│   ├── messages.php             # Direct messaging
│   ├── groups.php               # Groups & forums
│   ├── events.php               # Event calendar
│   ├── jobs.php                 # Job board
│   ├── notices.php              # University notices
│   ├── marketplace.php          # Buy/sell platform
│   ├── documents.php            # Document repository
│   └── settings.php             # User settings
│
├── 📂 api/                      # REST API endpoints
│   ├── auth.php                 # Authentication
│   ├── posts.php                # Post operations
│   ├── users.php                # User operations
│   ├── messages.php             # Messaging
│   ├── groups.php               # Group operations
│   ├── events.php               # Event operations
│   ├── jobs.php                 # Job operations
│   ├── marketplace.php          # Marketplace operations
│   ├── notices.php              # Notice operations
│   ├── notifications.php        # Notification system
│   ├── approvals.php            # Approval operations
│   ├── admin.php                # Admin operations
│   └── documents.php            # Document operations
│
├── 📂 includes/                 # PHP includes
│   ├── config.php               # Configuration
│   ├── db.php                   # Database class
│   ├── functions.php            # Helper functions
│   ├── header.php               # Common header
│   ├── navbar.php               # Navigation bar
│   ├── sidebar.php              # Dashboard sidebar
│   └── courses.php              # Course data
│
├── 📂 assets/                   # Static assets
│   ├── css/
│   │   ├── main.css             # Main styles
│   │   ├── components.css       # Component styles
│   │   └── animations.css       # Animation styles
│   ├── js/
│   │   └── auth.js              # Authentication JS
│   └── uploads/                 # User uploads
│       ├── profiles/            # Profile images
│       ├── posts/               # Post images/videos
│       ├── groups/              # Group images
│       └── marketplace/         # Marketplace images
│
├── 📂 database/                 # Database files
│   ├── schema.sql               # Database schema
│   ├── sample_notifications.sql # Sample data
│   └── add_batch_to_groups.sql  # Batch group setup
│
└── 📂 guidelines/               # Project guidelines
    └── Guidelines.md            # Development guidelines
```

---

## 🚀 Installation

### Prerequisites

Before you begin, ensure you have the following installed:

- **XAMPP** (or similar LAMP/WAMP stack)
  - PHP 8.0 or higher
  - MySQL 5.7 or higher
  - Apache Web Server
- **Web Browser** (Chrome, Firefox, Safari, or Edge)
- **Git** (for cloning the repository)

### Step-by-Step Installation

#### 1. Clone the Repository

```bash
# Navigate to your XAMPP htdocs directory
cd c:/xampp/htdocs/

# Clone the repository
git clone https://github.com/yourusername/UIU-Social-Connect.git

# Navigate to project directory
cd UIU-Social-Connect
```

#### 2. Start XAMPP Services

- Open **XAMPP Control Panel**
- Start **Apache** and **MySQL** services
- Ensure both services are running (green indicator)

#### 3. Create Database

**Option A: Using phpMyAdmin**

1. Open browser and go to `http://localhost/phpmyadmin`
2. Click on "New" to create a database
3. Name it: `uiu_social_connect`
4. Set collation to: `utf8mb4_unicode_ci`
5. Click "Create"

**Option B: Using MySQL Command Line**

```bash
mysql -u root -p
CREATE DATABASE uiu_social_connect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

#### 4. Import Database Schema

**Option A: Using phpMyAdmin**

1. Select `uiu_social_connect` database
2. Click on "Import" tab
3. Choose file: `database/schema.sql`
4. Click "Go" to import

**Option B: Using MySQL Command Line**

```bash
mysql -u root -p uiu_social_connect < database/schema.sql
```

#### 5. Configure Database Connection

Edit the `includes/config.php` file:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Your MySQL username
define('DB_PASS', '');              // Your MySQL password
define('DB_NAME', 'uiu_social_connect');

// Application Configuration
define('SITE_URL', 'http://localhost/UIU-Social-Connect');
```

#### 6. Set Permissions

Ensure the upload directories have write permissions:

```bash
# For Windows (using XAMPP)
# No action needed - Windows handles this automatically

# For Linux/Mac
chmod -R 755 assets/uploads/
```

#### 7. Access the Application

Open your web browser and navigate to:

- **Landing Page**: `http://localhost/UIU-Social-Connect/landing.php`
- **Login Page**: `http://localhost/UIU-Social-Connect/index.php`
- **Register**: `http://localhost/UIU-Social-Connect/register.php`

---

## ⚙️ Configuration

### Application Settings

Edit `includes/config.php` to customize:

```php
// File Upload Limits
define('MAX_FILE_SIZE', 10 * 1024 * 1024);      // 10MB for images
define('MAX_VIDEO_SIZE', 50 * 1024 * 1024);     // 50MB for videos

// Allowed File Types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);

// Timezone
date_default_timezone_set('Asia/Dhaka');
```

### Security Settings

```php
// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Set to 1 when using HTTPS
```

### Production Configuration

For production deployment:

1. **Disable error display**:

```php
error_reporting(0);
ini_set('display_errors', 0);
```

2. **Enable HTTPS**:

```php
ini_set('session.cookie_secure', 1);
```

3. **Change default admin password**
4. **Set up proper file permissions**
5. **Configure email settings** for notifications

---

## 💾 Database Setup

### Database Schema Overview

The database consists of 20+ tables:

- **users** - User accounts and profiles
- **admins** - Admin accounts
- **posts** - User posts with content
- **comments** - Post comments
- **post_likes** - Post likes
- **messages** - Direct messages
- **groups** - User groups and clubs
- **events** - Campus events
- **jobs** - Job listings
- **marketplace** - Marketplace items
- **notices** - University notices
- **notifications** - User notifications
- **friendships** - Friend connections
- **documents** - Shared documents
- **And more...**

### Default Admin Account

After importing the schema, use these credentials:

```
Email: admin@gmail.com
Password: 123456
```

⚠️ **Important**: Change the default admin password immediately after first login!

### Sample Data (Optional)

To add sample notifications for testing:

```bash
mysql -u root -p uiu_social_connect < database/sample_notifications.sql
```

### Creating Course Groups

To automatically create course groups for all departments and batches:

```bash
mysql -u root -p uiu_social_connect < database/add_batch_to_groups.sql
```

---

## 📖 Usage

### For Students

1. **Register** at `/register.php` with your UIU email
2. Wait for **admin approval** (you'll receive notification)
3. **Login** at `/index.php`
4. **Complete your profile** with bio and skills
5. **Join course groups** based on your department and batch
6. **Start posting**, connecting, and exploring!

### For Faculty

1. **Register** selecting "Faculty" role
2. After approval, access enhanced features
3. **Create academic content**
4. **Manage course groups**
5. **Post notices** for students

### For Alumni

1. **Register** with "Alumni" role
2. **Share career opportunities**
3. **Post on job board**
4. **Mentor current students**
5. **Stay connected** with alma mater

### For Admins

1. **Login** at `/admin/index.php`
2. **Review pending approvals** (users, posts, events, jobs)
3. **Manage users** and content
4. **Create notices** for university announcements
5. **Monitor platform statistics**

---

## 🔌 API Documentation

### Authentication Endpoints

#### Login

```http
POST /api/auth.php?action=login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

#### Register

```http
POST /api/auth.php?action=register
Content-Type: application/json

{
  "full_name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "role": "Student",
  "student_id": "011201234",
  "department": "CSE",
  "batch": "51",
  "trimester": "Spring 2025"
}
```

### Post Endpoints

#### Get Posts

```http
GET /api/posts.php?action=get_posts
```

#### Create Post

```http
POST /api/posts.php?action=create
Content-Type: multipart/form-data

{
  "content": "Post content here",
  "image": [file]
}
```

#### Like Post

```http
POST /api/posts.php?action=like
Content-Type: application/json

{
  "post_id": 123
}
```

### Message Endpoints

#### Get Conversations

```http
GET /api/messages.php?action=get_conversations
```

#### Send Message

```http
POST /api/messages.php?action=send
Content-Type: application/json

{
  "receiver_id": 456,
  "message": "Hello!"
}
```

### Response Format

All API endpoints return JSON:

**Success Response**:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error Response**:

```json
{
  "success": false,
  "message": "Error message here"
}
```

---

## 🔐 Admin Panel

### Access

- **URL**: `/admin/index.php`
- **Default Credentials**:
  - Email: `admin@gmail.com`
  - Password: `123456`

### Features

#### Dashboard (`/admin/index.php`)

- Total users by role
- Posts statistics
- Recent registrations
- Pending approvals count
- System health metrics

#### User Management (`/admin/users.php`)

- View all registered users
- Filter by role, department, batch
- Search by name or email
- Approve/reject new users
- Ban/unban users
- View user activity

#### Content Approvals (`/admin/approvals.php`)

- **User Approvals**: Review new registrations
- **Post Approvals**: Moderate user posts
- **Event Approvals**: Approve campus events
- **Job Approvals**: Review job listings
- Bulk approval actions

#### Content Management (`/admin/content.php`)

- Manage all posts
- Edit/delete content
- Feature important posts
- Monitor engagement

#### Document Management (`/admin/documents.php`)

- Upload course materials
- Organize by department
- Version control
- Access permissions

#### Course Groups (`/admin/create_course_groups.php`)

- Auto-create groups for all batches
- Assign students automatically
- Manage group permissions
- Monitor group activity

---

## 📸 Screenshots

### Landing Page

Modern landing page with hero section, features showcase, and call-to-action buttons.

### User Dashboard

Clean, intuitive dashboard with sidebar navigation and dynamic content feed.

### Profile Page

Customizable user profiles with cover photo, bio, skills, and post history.

### Admin Dashboard

Comprehensive admin panel with statistics, charts, and management tools.

---

## 🔒 Security

### Implemented Security Measures

✅ **Password Security**

- Passwords hashed using `bcrypt` (PHP `password_hash()`)
- Minimum 6 characters required
- No plain text password storage

✅ **SQL Injection Prevention**

- All queries use **PDO prepared statements**
- Input validation and sanitization
- Parameterized queries throughout

✅ **XSS Protection**

- Output escaping with `htmlspecialchars()`
- Content Security Policy headers
- Input sanitization

✅ **CSRF Protection**

- Session-based authentication
- Token validation for forms
- SameSite cookie attribute

✅ **Session Security**

- HTTP-only session cookies
- Session regeneration on login
- Secure session configuration

✅ **File Upload Security**

- File type validation
- File size limits
- Unique filename generation
- Secure upload directory

✅ **Authentication**

- Role-based access control
- Admin approval system
- Ban system for violations
- Session timeout

### Security Best Practices

1. **Change default admin credentials**
2. **Use HTTPS in production**
3. **Keep PHP and MySQL updated**
4. **Set proper file permissions**
5. **Regular database backups**
6. **Monitor error logs**
7. **Implement rate limiting**

---

## 🤝 Contributing

We welcome contributions from the community! Here's how you can help:

### How to Contribute

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. **Commit your changes**
   ```bash
   git commit -m "Add amazing feature"
   ```
4. **Push to the branch**
   ```bash
   git push origin feature/amazing-feature
   ```
5. **Open a Pull Request**

### Development Guidelines

- Follow PSR-12 coding standards for PHP
- Write clean, documented code
- Test all features before submitting
- Update documentation for new features
- Maintain responsive design principles

### Reporting Issues

Found a bug? Have a suggestion?

1. Check if the issue already exists
2. Create a new issue with:
   - Clear title and description
   - Steps to reproduce (for bugs)
   - Expected vs actual behavior
   - Screenshots (if applicable)

---

## 📝 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

```
MIT License

Copyright (c) 2026 UIU Social Connect

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

---

## 📞 Contact

### Project Team

- **Project Lead**: Your Name
- **Email**: contact@uiusocialconnect.com
- **GitHub**: [@yourusername](https://github.com/yourusername)

### University

- **Institution**: United International University (UIU)
- **Location**: Dhaka, Bangladesh
- **Website**: [www.uiu.ac.bd](https://www.uiu.ac.bd)

### Support

- **Documentation**: [View Docs](docs/)
- **Issues**: [GitHub Issues](https://github.com/yourusername/UIU-Social-Connect/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/UIU-Social-Connect/discussions)

---

## 🎓 Acknowledgments

- **UIU Community** for inspiration and feedback
- **PHP Community** for excellent documentation
- **Open Source Contributors** for various libraries and tools
- **Design Community** for UI/UX inspiration

---

## 📊 Project Statistics

![Lines of Code](https://img.shields.io/badge/Lines%20of%20Code-15000%2B-blue)
![Files](https://img.shields.io/badge/Files-50%2B-green)
![Database Tables](https://img.shields.io/badge/Database%20Tables-20%2B-orange)
![API Endpoints](https://img.shields.io/badge/API%20Endpoints-40%2B-red)

---

## 🗺️ Roadmap

### Version 1.0 (Current) ✅

- ✅ User authentication and profiles
- ✅ Post creation and interactions
- ✅ Direct messaging
- ✅ Admin panel
- ✅ Content moderation

### Version 1.1 (Planned) 🔜

- [ ] Real-time chat with WebSocket
- [ ] Push notifications
- [ ] Email notifications
- [ ] Advanced search with filters
- [ ] User analytics dashboard

### Version 2.0 (Future) 🚀

- [ ] Mobile app (React Native)
- [ ] Video calling
- [ ] AI-powered content recommendations
- [ ] Multi-language support
- [ ] Advanced analytics and reporting
- [ ] Integration with university systems

---

<div align="center">

## ⭐ Star This Repository!

If you find this project useful, please give it a ⭐ on GitHub!

**Made with ❤️ for the UIU Community**

[Report Bug](https://github.com/yourusername/UIU-Social-Connect/issues) • [Request Feature](https://github.com/yourusername/UIU-Social-Connect/issues) • [View Demo](#)

---

© 2026 UIU Social Connect. All Rights Reserved.

</div>
