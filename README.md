# Blooger Blog Website 🍽️📝
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)
![License](https://img.shields.io/badge/license-MIT-green)

## 📋 Overview
Blooger is a food-focused blogging platform designed for culinary enthusiasts to share recipes, cooking experiences, and food stories. Built as a university web systems project, the platform features secure user authentication, role-based access control, real-time search, and AI-powered translation to connect food lovers across language barriers.

## 🔍 The Problem We Solve
Food bloggers need a dedicated, secure platform to:
- Share recipes and culinary stories with a focused community
- Manage content with intuitive CRUD operations
- Engage readers through likes, comments, and follows
- Reach international audiences with multi-language support
- Build a personal brand with customizable profiles

## ✨ Key Features

### 📝 Content Management
- **Post Creation & Editing**: Rich text editor for recipes and food stories with image upload support
- **CRUD Operations**: Full create, read, update, delete functionality with role-based permissions
- **Real-time Search**: AJAX-powered search without page reloads for instant results
- **Bookmarking System**: Save favorite posts to personal reading lists

### 🔐 User Authentication & Security
- **Secure Registration**: Email verification with time-sensitive tokens (30-min expiry)
- **Password Management**: Bcrypt hashing + secure reset flow via PHPMailer
- **Session Management**: Server-side session handling with automatic timeout
- **XSS Protection**: Input sanitization using `htmlspecialchars()` on all user content
- **SQL Injection Prevention**: Prepared statements for all database queries

### 👥 Social Features
- **User Profiles**: Customizable profiles with bio and profile picture (max 5MB, validated file types)
- **Follow System**: Follow favorite bloggers and receive notifications on new posts
- **Engagement Tools**: Like and comment on posts with role-based moderation
- **Notifications**: Real-time notification center tracking follows, comments, and subscriptions
- **Author Subscriptions**: Subscribe to authors for priority updates

### 🌐 Advanced Capabilities
- **AI Translation**: OpenAI API integration supporting 10+ languages for post translation
- **Admin Dashboard**: Content moderation tools for managing users and posts
- **Responsive Design**: Bootstrap-powered UI optimized for desktop and mobile

## 🛠️ Technical Architecture

Built with PHP using a page-based architecture with modular includes for shared functionality:
```
┌─────────────────────────────────────┐
│         Client Browser              │
│  (Bootstrap UI + JavaScript/AJAX)   │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│       PHP Page Controllers          │
│  (*.php pages in root directory)    │
│   - home_loggedin.php               │
│   - create_post.php                 │
│   - profile.php                     │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│      Business Logic Layer           │
│  (/inc/*.php, /process/*.php)       │
│   - Authentication (check_session)  │
│   - Database operations (db.inc)    │
│   - Email (PHPMailer)               │
│   - Post functions                  │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│        MySQL Database               │
│         (proj_db schema)            │
│   - user_info                       │
│   - post_info                       │
│   - user_follows                    │
│   - bookmarks                       │
│   - user_notifications              │
│   - user_subscriptions              │
└─────────────────────────────────────┘
```

## 📁 Directory Structure
```
WEB_SYS_PROJECT_USING/
├── .vscode/                    # VS Code configuration
│   ├── launch.json
│   └── sftp.json
├── css/                        # Stylesheets
│   └── main.css
├── image/                      # Static images
│   ├── about_us.jpg
│   └── default_pfp.jpg         # Default profile picture
├── inc/                        # PHP includes & shared components
│   ├── check_admin.inc.php     # Admin authorization check
│   ├── check_session.inc.php   # Session validation
│   ├── db.inc.php              # Database connection
│   ├── footer.inc.php          # Footer component
│   ├── head.inc.php            # HTML head with meta tags
│   ├── login_nav.inc.php       # Navigation for logged-in users
│   ├── nav.inc.php             # Public navigation
│   └── post_functions.inc.php  # Post CRUD helper functions
├── js/                         # JavaScript files
│   └── main.js                 # AJAX search, DOM manipulation
├── pages/                      # Core application pages
│   ├── aboutus.php             # About page
│   ├── admin.php               # Admin dashboard
│   ├── author_profile.php      # Public author profiles
│   ├── create_post.php         # Post creation form
│   ├── delete_post.php         # Post deletion handler
│   ├── delete_profile.php      # Account deletion
│   ├── edit_post.php           # Post editing interface
│   ├── followers.php           # Follower list
│   ├── home_loggedin.php       # Authenticated user homepage
│   ├── library.php             # User's bookmarked posts
│   ├── login.php               # Login form
│   ├── membership.php          # Subscription management
│   ├── notifications.php       # Notification center
│   ├── payment.php             # Payment processing
│   ├── post_management.php     # Author's post dashboard
│   ├── profile.php             # User profile editing
│   ├── register.php            # Registration form
│   ├── reset_password.php      # Password reset flow
│   ├── user_management.php     # Admin user controls
│   ├── verify_email.php        # Email verification handler
│   └── view_post.php           # Single post view
├── process/                    # Backend processing scripts
│   ├── admin_delete_post.php   # Admin post deletion
│   ├── clear_notifications.php # Bulk notification management
│   ├── mark_notifications_read.php  # Notification status updates
│   ├── openai_call.php         # OpenAI API translation endpoint
│   ├── process_login.php       # Login authentication
│   ├── process_register.php    # User registration handler
│   ├── send_reset_email.php    # Password reset email sender
│   └── toggle_admin.php        # Admin role toggle
├── vendor/                     # Third-party libraries
│   ├── composer.json
│   ├── composer.lock
│   ├── composer.phar
│   └── composer-setup.php
├── index.php                   # Landing page
├── logout.php                  # Session termination
└── README.md                   # Project documentation
```

## 🧩 Core Components

| Component | Description | Key Technologies |
|-----------|-------------|------------------|
| **User Authentication** | Secure login, registration with email verification, password reset with time-sensitive tokens | PHP sessions, PHPMailer, bcrypt |
| **Blog Platform** | Full CRUD for posts, image uploads, content moderation | MySQL prepared statements, XSS protection |
| **Social Features** | Follow/unfollow system, notifications, user subscriptions, bookmarking | Relational database design, foreign keys |
| **Search Engine** | Client-side rendering with AJAX for real-time filtering | JavaScript fetch API, DOM manipulation |
| **Admin Dashboard** | User management, role assignment, content moderation | Role-based access control (RBAC) |
| **AI Translation** | Multi-language post translation on demand | OpenAI PHP SDK, REST API integration |

## 💻 Installation & Setup

### Prerequisites
- **XAMPP/WAMP** (PHP 7.4+, MySQL 8.0, Apache)
- **MySQL Workbench 8.0** (for database management)
- **PHPMailer** (included via Composer)
- **OpenAI API Key** (optional, for translation features)

### Development Environment Setup

**1. Clone the repository**
```bash
git clone https://github.com/yourusername/blooger.git
cd blooger
```

**2. Database Configuration**

Open MySQL Workbench and create the database:
```sql
CREATE DATABASE proj_db;
USE proj_db;
```

Import the schema (if you have a SQL dump file):
```bash
mysql -u root -p proj_db < database/schema.sql
```

Or manually create tables using the structure documented in `finalReport.pdf` (pages 3-6).

**3. Configure Database Connection**

Edit `inc/db.inc.php`:
```php
<?php
$servername = "localhost";
$username = "root";           // Your MySQL username
$password = "";               // Your MySQL password
$dbname = "proj_db";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
```

**4. Email Configuration (PHPMailer)**

Install PHPMailer via Composer:
```bash
cd vendor
php composer.phar require phpmailer/phpmailer
```

Configure SMTP settings in `process/send_reset_email.php`:
```php
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your-email@gmail.com';        // Your Gmail
$mail->Password = 'your-app-specific-password';  // Gmail App Password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
```

**Note**: For Gmail, enable 2FA and generate an [App Password](https://support.google.com/accounts/answer/185833).

**5. File Upload Directory**

Ensure the uploads directory has write permissions:
```bash
chmod 755 image/
```

**6. Launch Application**

Start XAMPP/WAMP services (Apache + MySQL), then navigate to:
```
http://localhost/blooger/
```

### Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Regular User | abc@gmail.com | Qwerty12345? |
| Regular User | sit2024@gmail.com | Qwerty123! |
| Admin | admin001@gmail.com | Admin12345# |

## 🔒 Security Features

- **Password Security**: Bcrypt hashing with `password_hash()` and `password_verify()`
- **SQL Injection Prevention**: Parameterized queries using `mysqli_prepare()`
- **XSS Protection**: All user input sanitized with `htmlspecialchars()` before rendering
- **CSRF Protection**: Token validation on all state-changing operations
- **Session Security**: HTTP-only cookies, secure session regeneration on login
- **Email Verification**: Time-limited tokens (30-minute expiry) for account activation
- **File Upload Validation**: MIME type checking, file size limits (5MB max), restricted extensions
- **Role-Based Access Control**: Granular permissions for blogger vs. admin actions

## 🎨 Design Principles

The UI was designed with minimalism and usability in mind, inspired by Medium's clean aesthetic:
- **Generous whitespace** for improved readability
- **Consistent typography** across all pages
- **Responsive grid** using Bootstrap 5
- **Intuitive navigation** with clear call-to-action buttons
- **Accessible color contrast** meeting WCAG 2.1 standards

## 🚀 Future Enhancements

- [ ] Progressive Web App (PWA) implementation for offline access
- [ ] Rich text editor (TinyMCE/CKEditor) for better post formatting
- [ ] Image optimization and CDN integration
- [ ] Comment reply threading for nested discussions
- [ ] Post analytics dashboard (views, engagement metrics)
- [ ] Two-factor authentication (2FA) via SMS/authenticator app
- [ ] Social media sharing integration
- [ ] Recipe schema markup for SEO
- [ ] Dark mode toggle

## 👥 Contributors

| Name | Role | Contributions |
|------|------|---------------|
| **Deacon** | Frontend & Auth | Homepage UI, user authentication system, email verification with PHPMailer |
| **Ming Yang** | Post System & AI | Post CRUD operations, OpenAI translation API integration |
| **Max** | Admin Dashboard | Admin panel, user management, content moderation tools |
| **Shannon** | Social Features | User profiles, follow system, notifications, bookmarking |
| **Darren** | Comments | Comment editing/deletion with role-based permissions |
| **Ethan** | Engagement | Like system, comment posting functionality |

## 📚 Documentation

- **Project Report**: See `finalReport.pdf` for detailed system design, database schema, and methodology
- **Database Schema**: Entity-Relationship diagrams on pages 3-6 of report
- **API Reference**: OpenAI integration documented in Section 3.2.1

## 🐛 Known Issues

- Translation feature requires valid OpenAI API key (feature gracefully degrades if unavailable)
- Email verification may fail with Gmail if App Password not configured correctly
- Large image uploads (>5MB) are rejected; consider implementing client-side compression

## 📄 License

This project was developed as part of the INF1005 Web Systems & Technologies course at Singapore Institute of Technology.

## 🙏 Acknowledgments

- **Bootstrap** for responsive UI components
- **Font Awesome** for scalable vector icons
- **PHPMailer** for reliable email delivery
- **OpenAI** for language translation capabilities
- Course instructors for guidance on web security best practices

---

**Note**: This is an academic project built for educational purposes. For production deployment, additional security hardening, performance optimization, and comprehensive testing are recommended.
