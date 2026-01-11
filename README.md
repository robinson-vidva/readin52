# ReadIn52

**Journey Through Scripture in 52 Weeks**

ReadIn52 is a free, open-source Progressive Web App (PWA) designed to help individuals and church communities read through the entire Bible in one year with a structured 52-week reading plan. Built for the glory of God and freely available to all.

> *"Your word is a lamp for my feet, a light on my path."* — Psalm 119:105

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Installation](#installation)
- [Configuration](#configuration)
- [Admin Guide](#admin-guide)
- [Security](#security)
- [FAQ](#faq)
- [API Reference](#api-reference)
- [Development](#development)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)

---

## Features

### Core Reading Features
- **52-Week Reading Plan** — Structured Bible reading across 4 categories per week
- **Four Reading Categories** — Poetry & Wisdom, History & Law, Chronicles & Prophecy, Gospels & Epistles
- **Chapter-Level Progress** — Track completion with visual progress indicators
- **Built-in Bible Reader** — Modal-based reader with HelloAO Bible API integration
- **Personal Notes** — Take notes while reading, organized by book/chapter with color tags

### Bible & Translations
- **50+ Bible Translations** — Support for multiple languages via HelloAO API
- **Dual Translation Mode** — Compare two translations side-by-side
- **No API Keys Required** — Free, open Bible API with no usage limits

### User Experience
- **PWA Support** — Install on devices for offline access
- **Theme Support** — Light, dark, and auto (system) theme modes
- **Responsive Design** — Mobile-first design that works on all devices
- **Badge System** — 25+ achievement badges for reading milestones

### Security Features
- **Bot Protection** — Optional Cloudflare Turnstile integration
- **Rate Limiting** — Brute-force protection on login
- **CSRF Protection** — Token validation on all forms
- **Secure Sessions** — HttpOnly, SameSite cookies

### Administration
- **User Management** — View, edit, delete users and manage roles
- **Progress Monitoring** — View individual user reading progress
- **Reading Plan Editor** — Modify weekly readings with import/export
- **Customizable Branding** — Custom logo and app name

---

## Requirements

- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled
- HTTPS recommended for production

---

## Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/robinson-vidva/readin52.git

# 2. Configure database
cp config/db.example.php config/db.php
# Edit config/db.php with your database credentials

# 3. Run local server
php -S localhost:8000

# 4. Visit http://localhost:8000/install.php
```

Default admin login after installation:
- **Email:** `setup@localhost`
- **Password:** `ChangeMe52!`

You will be prompted to change these credentials on first login.

---

## Installation

### Option 1: Cloudways (Recommended)

1. Create a Custom PHP application on Cloudways
2. Connect your GitHub repository
3. Deploy to `public_html/`
4. Create `config/db.php` via SSH:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   ```
5. Visit `https://yourdomain.com/install.php`
6. **Delete `install.php` after setup**

### Option 2: Standard PHP Hosting

1. Upload all files to your web root
2. Create `config/db.php` with database credentials
3. Ensure `.htaccess` rules are active
4. Visit `/install.php`
5. **Delete `install.php` after setup**

### Option 3: Docker (Coming Soon)

Docker support is planned for future releases.

---

## Configuration

### Database (`config/db.php`)

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'database_name');
define('DB_USER', 'database_user');
define('DB_PASS', 'database_password');
define('DB_PORT', '3306');        // Optional
define('DB_CHARSET', 'utf8mb4');
```

### Email Service (`config/email.php`)

For password reset emails, configure [Brevo](https://www.brevo.com/) (free tier available):

```php
<?php
define('BREVO_API_KEY', 'your-brevo-api-key');
define('EMAIL_FROM_NAME', 'ReadIn52');
define('EMAIL_FROM_ADDRESS', 'noreply@yourdomain.com');
define('EMAIL_REPLY_TO', 'support@yourdomain.com');
```

### Bot Protection (Cloudflare Turnstile)

Turnstile provides invisible bot protection. Configure in **Admin → Settings → Bot Protection**:

1. Get keys from [Cloudflare Turnstile Dashboard](https://dash.cloudflare.com/?to=/:account/turnstile)
2. Enter Site Key and Secret Key
3. Enable protection

Protected forms: Login, Registration, Forgot Password

---

## Admin Guide

### First-Time Setup

1. Log in with default credentials
2. You'll be prompted to set your own name, email, and password
3. Configure app settings in **Admin → Settings**

### Managing Users

- **View all users:** Admin → Users
- **Edit user:** Click on user row
- **Reset password:** Create temporary password (user must change on login)
- **Delete user:** Removes all user data including progress

### Customizing the App

| Setting | Location | Description |
|---------|----------|-------------|
| App Name | Admin → Settings | Displayed in header and title |
| Logo | Admin → Settings → Branding | Custom logo image |
| Registration | Admin → Settings | Enable/disable new signups |
| Bot Protection | Admin → Settings → Turnstile | Enable Cloudflare protection |

### Reading Plan Management

- **View plan:** Admin → Reading Plan
- **Edit readings:** Click on any week
- **Export:** Download JSON backup
- **Import:** Upload JSON file

---

## Security

### Implemented Security Measures

| Feature | Description |
|---------|-------------|
| SQL Injection | PDO prepared statements |
| XSS Prevention | Output escaping via `e()` helper |
| CSRF Protection | Token validation on all forms |
| Password Hashing | bcrypt via `password_hash()` |
| Session Security | HttpOnly, SameSite=Lax, Secure cookies |
| Rate Limiting | 5 login attempts per 15 minutes |
| Bot Protection | Cloudflare Turnstile (optional) |
| Directory Protection | `.htaccess` blocks sensitive directories |
| HTTPS Enforcement | Automatic redirect in production |

### Content Security Policy

```
default-src 'self';
script-src 'self' 'unsafe-inline' https://challenges.cloudflare.com;
style-src 'self' 'unsafe-inline';
img-src 'self' data: https:;
connect-src 'self' https://bible.helloao.org;
frame-src https://challenges.cloudflare.com;
```

---

## FAQ

### General

**Q: Is ReadIn52 really free?**
A: Yes! ReadIn52 is completely free and open source under the MIT License. Use it for your personal reading, your family, your church, or any ministry.

**Q: Can I customize the reading plan?**
A: Yes. Admins can modify the 52-week reading plan through Admin → Reading Plan. You can also import/export plans as JSON.

**Q: Does it work offline?**
A: Yes, as a PWA it can be installed on devices and works offline for previously loaded content.

### Installation

**Q: I get a database connection error**
A: Check that `config/db.php` exists with correct credentials. Verify the database user has full permissions.

**Q: The installer says "Application already installed"**
A: The database already has tables. To reinstall, drop all tables or use a fresh database.

**Q: Should I delete install.php?**
A: Yes! Always delete `install.php` after installation for security.

### Features

**Q: How do I enable email features?**
A: Create `config/email.php` with your Brevo API credentials. See [Email Configuration](#email-service-configemailphp).

**Q: What is Cloudflare Turnstile?**
A: Turnstile is a free, privacy-focused alternative to CAPTCHA that protects forms from bots. Enable it in Admin → Settings → Bot Protection.

**Q: Can users see each other's progress?**
A: No. Each user only sees their own progress. Admins can view all user progress.

### Troubleshooting

**Q: Login doesn't work / Session issues**
A: Ensure your server supports PHP sessions. Check that `session.save_path` is writable.

**Q: Styles look broken**
A: Ensure `.htaccess` is being processed. Check that mod_rewrite is enabled.

**Q: Bible API not loading**
A: The Bible API requires internet access. Check your server's outbound connections.

---

## API Reference

All API endpoints require user authentication via session.

### Progress

```http
GET  /?route=api/progress           # Get all reading progress
POST /?route=api/progress           # Toggle reading completion
     Body: { week, category, csrf_token }

GET  /?route=api/chapter-progress   # Get chapter progress for week
POST /?route=api/chapter-progress   # Toggle chapter completion
     Body: { week, category, book, chapter, csrf_token }

GET  /?route=api/stats              # Get user statistics
```

### Notes

```http
GET  /?route=api/notes/{id}                      # Get specific note
GET  /?route=api/notes/chapter?book=X&chapter=N  # Get notes for chapter
POST /?route=notes/save                          # Create/update note
POST /?route=notes/delete                        # Delete note
```

### Week Data

```http
GET  /?route=api/week/{n}           # Get reading plan for week N
```

---

## Development

### Local Setup

```bash
git clone https://github.com/robinson-vidva/readin52.git
cd readin52
cp config/db.example.php config/db.php
# Configure config/db.php
php -S localhost:8000
```

### File Structure

```
readin52/
├── index.php              # Main router
├── install.php            # Installation script
├── manifest.json          # PWA manifest
├── sw.js                  # Service Worker
├── .htaccess              # URL rewriting & security
│
├── assets/
│   ├── css/style.css      # All styles
│   ├── js/app.js          # Main app logic
│   └── images/            # Icons and images
│
├── config/
│   ├── config.php         # Main configuration
│   ├── db.php             # Database credentials (gitignored)
│   ├── email.php          # Email credentials (gitignored)
│   └── reading-plan.json  # 52-week schedule
│
├── src/                   # PHP classes
│   ├── Auth.php           # Authentication
│   ├── Badge.php          # Achievement system
│   ├── Database.php       # PDO wrapper
│   ├── Email.php          # Brevo integration
│   ├── Progress.php       # Progress tracking
│   └── helpers.php        # Utility functions
│
└── templates/             # HTML templates
    ├── layout.php
    ├── dashboard.php
    ├── reader.php
    └── admin/
```

### Code Style

- PHP: PSR-12 compatible
- JavaScript: ES6+ vanilla JS
- CSS: BEM-like with CSS custom properties

---

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### Ways to Contribute

- Report bugs via [GitHub Issues](https://github.com/robinson-vidva/readin52/issues)
- Suggest features
- Submit pull requests
- Improve documentation
- Translate to other languages

---

## License

MIT License — See [LICENSE](LICENSE) for full text.

**What this means:**
- Free to use for any purpose (personal, church, ministry, commercial)
- Free to modify and customize
- Free to distribute and share
- Free to use in your own projects

We only ask that you keep the copyright notice if you redistribute the code.

---

## Credits

### Bible API

Scripture content provided by [HelloAO Free Use Bible API](https://bible.helloao.org/) — MIT licensed, no API keys required, no usage limits.

### Berean Standard Bible

The Holy Bible, Berean Standard Bible (BSB) is produced in cooperation with [Bible Hub](https://biblehub.com/) and dedicated to the public domain.

### Development

Built with love for the Church by the [ASK Devotions](https://askdevotions.com) community.

---

## Support

- **Documentation:** This README
- **Issues:** [GitHub Issues](https://github.com/robinson-vidva/readin52/issues)
- **Contact:** seek@askdevotions.com

---

*Soli Deo Gloria* — To God alone be the glory.
