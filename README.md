# ReadIn52

**Journey Through Scripture in 52 Weeks**

ReadIn52 is a full-featured Progressive Web App (PWA) designed to help users read through the entire Bible in one year with a structured 52-week reading plan. Built as a modern PHP application with offline capabilities, user authentication, progress tracking, and gamification features.

## Features

### Core Features
- **52-Week Reading Plan**: Structured Bible reading across 4 categories per week
- **Four Reading Categories**: Poetry & Wisdom, History & Law, Chronicles & Prophecy, Gospels & Epistles
- **Chapter-Level Progress Tracking**: Track completion with visual progress indicators
- **Built-in Bible Reader**: Modal-based reader with HelloAO Bible API integration
- **Personal Notes**: Take notes while reading, organized by book/chapter with color tags and search

### Bible & Translations
- **50+ Bible Translations**: Support for multiple languages via HelloAO API
- **Dual Translation Mode**: Compare two translations side-by-side
- **No API Keys Required**: Free, open Bible API with no usage limits

### User Experience
- **PWA Support**: Install on devices for offline access with Service Worker caching
- **Theme Support**: Light, dark, and auto (system) theme modes
- **Responsive Design**: Mobile-first design that works on all devices
- **Badge/Achievement System**: 25+ badges for reading milestones and engagement

### Administration
- **User Management**: View, edit, delete users and manage roles
- **Progress Monitoring**: View individual user reading progress and statistics
- **Reading Plan Editor**: Modify weekly readings with import/export support
- **Translation Sync**: Sync Bible translations from API
- **App Branding**: Custom logo upload and settings

## Requirements

- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled
- HTTPS (enforced in production)

## Installation

### Cloudways Deployment (Recommended)

This app is structured for direct deployment to Cloudways Custom PHP via GitHub.

1. **Create Application**: Create a Custom PHP application on Cloudways
2. **Connect Repository**: Connect your GitHub repository
3. **Deploy**: Deploy to `public_html/` (repo contents go directly to public_html)
4. **Configure Database**:
   - Go to Cloudways > Application > Access Details > Database
   - Copy DB Name, Username, and Password
   - Create `config/db.php` via SSH or File Manager:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   define('DB_PORT', '3306');
   define('DB_CHARSET', 'utf8mb4');
   ```
5. **Run Installer**: Visit `https://yourdomain.com/install.php`
6. **Delete Installer**: Remove `install.php` after installation for security

### Standard PHP Hosting

1. Upload all files to your web root
2. Create `config/db.php` with your database credentials
3. Ensure `.htaccess` rules are active (Apache mod_rewrite)
4. Visit `/install.php` to initialize the database
5. Delete `install.php` after setup

### Default Admin Account

After installation, a temporary admin account is created:

| Email | Password |
|-------|----------|
| setup@localhost | ChangeMe52! |

**Security**: On first login, you will be prompted to set up your own name, email, and password. This ensures no default credentials remain in production.

## Configuration

### Database (`config/db.php`)

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'database_name');
define('DB_USER', 'database_user');
define('DB_PASS', 'database_password');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');
```

This file is gitignored and won't be overwritten on deployment.

### Email Service (`config/email.php`)

For password reset and email verification features, configure Brevo (formerly Sendinblue):

```php
<?php
define('BREVO_API_KEY', 'your-brevo-api-key');
define('EMAIL_FROM_NAME', 'ReadIn52');
define('EMAIL_FROM_ADDRESS', 'noreply@yourdomain.com');
define('EMAIL_REPLY_TO', 'support@yourdomain.com');
```

Get your API key from [Brevo](https://www.brevo.com/).

### Reading Plan (`config/reading-plan.json`)

The 52-week reading plan is stored as JSON and imported to the database on first run. Admins can modify the plan via the admin panel and export/import JSON files.

## API Endpoints

### Authentication Required

All API endpoints require user authentication via session.

### Progress API

```
GET  /?route=api/progress           - Get all reading progress
POST /?route=api/progress           - Toggle reading completion
     Body: { week, category, csrf_token }

GET  /?route=api/chapter-progress?week=N  - Get chapter progress for week
POST /?route=api/chapter-progress   - Toggle chapter completion
     Body: { week, category, book, chapter, csrf_token }

GET  /?route=api/stats              - Get user statistics
```

### Notes API

```
GET  /?route=api/notes/{id}         - Get a specific note
GET  /?route=api/notes/chapter?book=X&chapter=N  - Get notes for chapter
POST /?route=notes/save             - Create/update note (form data)
POST /?route=notes/delete           - Delete note (JSON body)
```

### Week Data

```
GET  /?route=api/week/{n}           - Get reading plan for week N
```

## Security Features

### Implemented Security Measures

| Feature | Implementation |
|---------|---------------|
| **SQL Injection** | PDO prepared statements throughout |
| **XSS Prevention** | Output escaping via `e()` helper function |
| **CSRF Protection** | Token validation on all forms and API endpoints |
| **Password Hashing** | bcrypt via `password_hash()` |
| **Session Security** | HttpOnly, SameSite=Lax, Secure cookies |
| **Rate Limiting** | 5 login attempts per 15 minutes |
| **Directory Protection** | `.htaccess` blocks /config, /src, /templates, /data |
| **HTTPS Enforcement** | Automatic redirect in production |
| **Security Headers** | X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, CSP |
| **Host Header Validation** | Regex validation prevents header injection |
| **Sensitive Action Confirmation** | Password required for dangerous admin actions |

### Content Security Policy

```
default-src 'self';
script-src 'self' 'unsafe-inline';
style-src 'self' 'unsafe-inline';
img-src 'self' data: https:;
connect-src 'self' https://bible.helloao.org;
frame-ancestors 'self'
```

## Badge System

Users earn badges for reading achievements:

### Book Completion Badges
- Genesis Journey, Exodus Explorer, Psalms Singer, etc.
- Awarded when all chapters of a book are completed

### Category Badges
- Poetry Master, History Scholar, Prophecy Student, Gospel Bearer
- Awarded when all readings in a category are completed

### Engagement Badges
- First Steps (1 reading), Getting Started (10), Dedicated Reader (50), etc.
- Week Warrior: Complete all 4 readings in one week

### Milestone Badges
- Halfway There (50%), Almost Done (90%), Finisher (100%)

### Streak Badges
- On Fire (7-day streak), Consistent (30-day), Devoted (100-day)

## Tech Stack

- **Backend**: PHP 8.0+ (vanilla, no framework)
- **Database**: MySQL/MariaDB with PDO
- **Frontend**: Vanilla HTML/CSS/JS (no jQuery or frameworks)
- **Bible API**: [HelloAO](https://bible.helloao.org/) (MIT license, no API key)
- **Email**: Brevo API for transactional emails
- **PWA**: Service Worker + Web App Manifest

## File Structure

```
public_html/
├── index.php              # Main router (all requests)
├── install.php            # Installation script (delete after setup)
├── manifest.json          # PWA web app manifest
├── sw.js                  # Service Worker for offline support
├── .htaccess              # URL rewriting & security headers
│
├── assets/
│   ├── css/style.css      # All application styles
│   ├── js/app.js          # Main application logic
│   ├── js/bible-api.js    # Bible API integration
│   └── images/            # Logo and app icons
│
├── config/                # Protected directory
│   ├── config.php         # Main configuration
│   ├── db.php             # Database credentials (gitignored)
│   ├── db.example.php     # Template for db.php
│   ├── email.php          # Email credentials (gitignored)
│   ├── email.example.php  # Template for email.php
│   └── reading-plan.json  # 52-week reading schedule
│
├── src/                   # Protected directory - PHP classes
│   ├── Auth.php           # Authentication & CSRF
│   ├── Badge.php          # Achievement system
│   ├── Bible.php          # Book definitions
│   ├── Database.php       # PDO singleton & schema
│   ├── Email.php          # Brevo email integration
│   ├── Note.php           # Personal notes
│   ├── Progress.php       # Reading progress tracking
│   ├── ReadingPlan.php    # Reading plan logic
│   ├── User.php           # User management
│   └── helpers.php        # Utility functions
│
├── templates/             # Protected directory - HTML templates
│   ├── layout.php         # Main layout wrapper
│   ├── home.php           # Landing page
│   ├── login.php          # Login form
│   ├── register.php       # Registration form
│   ├── dashboard.php      # Main reading interface
│   ├── reader.php         # Bible reader modal
│   ├── profile.php        # User profile
│   ├── settings.php       # User settings
│   ├── notes.php          # Notes manager
│   ├── books.php          # Book browser
│   └── admin/             # Admin templates
│       ├── layout.php
│       ├── dashboard.php
│       ├── users.php
│       ├── user-progress.php
│       ├── reading-plan.php
│       └── settings.php
│
├── uploads/               # User uploads
│   └── logos/             # Custom app logos
│
└── data/                  # Protected data directory
```

## Database Schema

### Core Tables
- `users` - User accounts with preferences
- `reading_progress` - Week-level completion tracking
- `chapter_progress` - Chapter-level tracking
- `notes` - Personal reading notes

### Reference Tables
- `reading_plan` - 52 weeks x 4 categories of readings
- `reading_categories` - Category definitions with colors
- `bible_translations` - Available translations

### Security Tables
- `login_attempts` - Rate limiting for brute-force prevention
- `password_resets` - Password reset tokens (1-hour expiry)
- `email_verifications` - Email change verification (24-hour expiry)

### Gamification
- `badges` - Badge definitions with criteria
- `user_badges` - Earned badges per user

## Development

### Local Setup

1. Clone the repository
2. Create `config/db.php` with local database credentials
3. Run a local PHP server: `php -S localhost:8000`
4. Visit `http://localhost:8000/install.php`

### Code Style

- PHP: PSR-12 compatible
- JavaScript: ES6+ with vanilla JS (no frameworks)
- CSS: BEM-like naming with CSS custom properties

### Adding New Features

1. Add routes in `index.php`
2. Create/modify classes in `src/`
3. Add templates in `templates/`
4. Run migrations automatically via `Database::migrate()`

## Credits & Attribution

### Bible API

Scripture content is provided by the [HelloAO Free Use Bible API](https://bible.helloao.org/). The API and its source code are freely available under the MIT license with no usage limits, no API keys required, and no copyright restrictions.

### Berean Standard Bible

The Holy Bible, Berean Standard Bible (BSB) is produced in cooperation with [Bible Hub](https://biblehub.com/), Discovery Bible, [OpenBible.com](https://openbible.com/), and the Berean Bible Translation Committee. This text of God's Word has been dedicated to the public domain.

### Other Translations

Various Bible translations available through the API are provided by their respective publishers and may have different licensing terms.

## License

MIT License - See [LICENSE](LICENSE) file for details.

## Support

For issues and feature requests, please use the GitHub issue tracker.
