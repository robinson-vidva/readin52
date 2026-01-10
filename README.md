# ReadIn52

**Journey Through Scripture in 52 Weeks**

ReadIn52 is a progressive web app that helps you read through the entire Bible in one year with a structured 52-week reading plan.

## Features

- **52-Week Reading Plan**: Four readings per week across different categories
- **Four Categories**: Poetry & Wisdom, History & Law, Chronicles & Prophecy, Gospels & Epistles
- **Progress Tracking**: Track your reading progress with chapter-level visual indicators
- **Bible Reader**: Built-in reader with HelloAO Bible API integration (modal popup)
- **Multiple Translations**: Support for 50+ translations across multiple languages
- **Dual Translation Mode**: Compare two translations side-by-side in settings
- **Personal Notes**: Take notes while reading, organized by book/chapter with search
- **PWA Support**: Install on your device for offline access
- **Admin Panel**: Manage users, reading plan, translations, and settings

## Requirements

- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+

## Cloudways Deployment (GitHub)

This app is structured for direct deployment to Cloudways Custom PHP via GitHub.
The config system ensures your database credentials are NOT overwritten on deploy.

### First-Time Setup

1. Create a Custom PHP application on Cloudways
2. Connect your GitHub repository
3. Deploy to `public_html/` (repo contents go directly to public_html)
4. **Configure database credentials (one-time setup):**
   - Go to Cloudways > Application > Access Details > Database
   - Copy DB Name, Username, and Password
   - Via SSH or File Manager, create `config/db.php`:
     ```php
     <?php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'your_database_name');
     define('DB_USER', 'your_database_user');
     define('DB_PASS', 'your_database_password');
     define('DB_PORT', '3306');
     define('DB_CHARSET', 'utf8mb4');
     ```
   - Or copy `config/db.example.php` to `config/db.php` and edit it
5. Visit `https://yourdomain.com/install.php`
6. **Delete `install.php` after installation**

### Why db.php?

- `db.php` is gitignored - won't be overwritten when you deploy
- Your database credentials stay safe across all future deployments
- The main `config.php` loads db.php automatically

## Default Accounts

After installation, two accounts are created:

| Account | Email | Password |
|---------|-------|----------|
| Admin | admin@readin52.app | Admin@123 |
| Test User | testuser@readin52.app | Test@123 |

## Tech Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL/MariaDB
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
│   ├── config.php            # Main config (loads db.php)
│   ├── db.php                # YOUR credentials (gitignored)
│   ├── db.example.php        # Template for db.php
│   └── reading-plan.json
├── data/                 # Protected - no web access
│   ├── .htaccess
│   └── .gitkeep
├── src/                  # Protected - no web access
│   ├── .htaccess
│   ├── Database.php
│   ├── Auth.php
│   ├── User.php
│   ├── Progress.php
│   ├── ReadingPlan.php
│   ├── Note.php
│   ├── Email.php
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
│   ├── settings.php
│   ├── notes.php
│   └── admin/
├── .gitignore
├── README.md
└── LICENSE
```

## Security

- Sensitive directories (config, src, templates, data) are protected via .htaccess
- HTTPS is enforced automatically in production
- CSRF protection on all forms and API endpoints
- Password hashing with bcrypt
- SQL injection prevention via PDO prepared statements
- XSS prevention via output escaping
- Host header validation to prevent injection attacks
- Secure variable extraction in template rendering

## Credits & Attribution

### Bible API

Scripture content is provided by the [HelloAO Free Use Bible API](https://bible.helloao.org/). The API and its source code are freely available under the MIT license with no usage limits, no API keys required, and no copyright restrictions.

### Berean Standard Bible

The Holy Bible, Berean Standard Bible (BSB) is produced in cooperation with [Bible Hub](https://biblehub.com/), Discovery Bible, [OpenBible.com](https://openbible.com/), and the Berean Bible Translation Committee. This text of God's Word has been dedicated to the public domain.

### Other Translations

Various Bible translations available through the API are provided by their respective publishers and may have different licensing terms. Please refer to individual translation documentation for specific usage rights.

## License

MIT License - See LICENSE file for details.
