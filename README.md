# ReadIn52

**Journey Through Scripture in 52 Weeks**

ReadIn52 is a progressive web app that helps you read through the entire Bible in one year with a structured 52-week reading plan.

## Features

- **52-Week Reading Plan**: Four readings per week across different categories
- **Four Categories**: Poetry & Wisdom, History & Law, Chronicles & Prophecy, Gospels & Epistles
- **Progress Tracking**: Track your reading progress with visual indicators
- **Bible Reader**: Built-in reader with HelloAO Bible API integration
- **Multiple Translations**: Support for English (KJV) and Tamil (IRV)
- **PWA Support**: Install on your device for offline access
- **Admin Panel**: Manage users, reading plan, and settings

## Requirements

- PHP 8.0+
- SQLite 3 (pdo_sqlite extension)
- mod_rewrite enabled

## Cloudways Deployment (GitHub)

This app is structured for direct deployment to Cloudways Custom PHP via GitHub:

1. Create a Custom PHP application on Cloudways
2. Connect your GitHub repository
3. Deploy to `public_html/` (repo contents go directly to public_html)
4. Ensure PHP 8.0+ and sqlite3/pdo_sqlite extensions are enabled
5. Visit `https://yourdomain.com/install.php`
6. **Delete `install.php` after installation**

## Default Accounts

After installation, two accounts are created:

| Account | Email | Password |
|---------|-------|----------|
| Admin | admin@readin52.app | Admin@123 |
| Test User | testuser@readin52.app | Test@123 |

## Tech Stack

- **Backend**: PHP 8.0+
- **Database**: SQLite 3
- **Frontend**: Vanilla HTML/CSS/JS
- **Bible API**: HelloAO (bible.helloao.org)
- **PWA**: Service Worker + Web App Manifest

## File Structure

```
public_html/              # Document root (Cloudways)
├── index.php             # Main router
├── manifest.json         # PWA manifest
├── sw.js                 # Service worker
├── install.php           # Installation script (delete after setup)
├── .htaccess             # URL rewriting & security
├── assets/
│   ├── css/style.css
│   ├── js/app.js
│   ├── js/bible-api.js
│   └── images/
├── config/               # Protected - no web access
│   ├── .htaccess
│   ├── config.php
│   └── reading-plan.json
├── data/                 # Protected - no web access
│   ├── .htaccess
│   ├── .gitkeep
│   └── readin52.db       # Created on install
├── src/                  # Protected - no web access
│   ├── .htaccess
│   ├── Database.php
│   ├── Auth.php
│   ├── User.php
│   ├── Progress.php
│   ├── ReadingPlan.php
│   └── helpers.php
├── templates/            # Protected - no web access
│   ├── .htaccess
│   ├── layout.php
│   ├── home.php
│   ├── dashboard.php
│   ├── login.php
│   ├── register.php
│   ├── reader.php
│   ├── profile.php
│   └── admin/
├── .gitignore
├── README.md
└── LICENSE
```

## Security

- Sensitive directories (config, src, templates, data) are protected via .htaccess
- HTTPS is enforced automatically in production
- CSRF protection on all forms
- Password hashing with bcrypt
- SQL injection prevention via PDO prepared statements
- XSS prevention via output escaping

## License

MIT License - See LICENSE file for details.
