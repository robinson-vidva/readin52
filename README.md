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
- SQLite 3
- mod_rewrite enabled

## Installation

1. Upload files to your web server
2. Ensure `data/` directory is writable
3. Visit `/install.php` in your browser
4. Follow the installation steps
5. **Delete `install.php` after installation**

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
readin52/
├── config/
│   ├── config.php
│   └── reading-plan.json
├── data/
│   └── readin52.db (created on install)
├── public/
│   ├── index.php
│   ├── manifest.json
│   ├── sw.js
│   ├── .htaccess
│   └── assets/
├── src/
│   ├── Database.php
│   ├── Auth.php
│   ├── User.php
│   ├── Progress.php
│   ├── ReadingPlan.php
│   └── helpers.php
├── templates/
│   ├── layout.php
│   ├── home.php
│   ├── dashboard.php
│   ├── login.php
│   ├── register.php
│   ├── reader.php
│   ├── profile.php
│   └── admin/
├── install.php
├── .htaccess
├── .gitignore
├── README.md
└── LICENSE
```

## License

MIT License - See LICENSE file for details.
