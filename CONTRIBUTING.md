# Contributing to ReadIn52

Thank you for your interest in contributing to ReadIn52! This project exists to help people engage with Scripture, and we welcome contributions from the community.

## Ways to Contribute

### Report Bugs

Found a bug? Please open an issue on GitHub with:

1. A clear, descriptive title
2. Steps to reproduce the issue
3. Expected vs actual behavior
4. Screenshots if applicable
5. Your environment (browser, PHP version, etc.)

### Suggest Features

Have an idea? Open an issue with the "enhancement" label:

1. Describe the feature
2. Explain the use case
3. Consider how it fits with the project goals

### Submit Code

We accept pull requests! Here's the process:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Make your changes
4. Test thoroughly
5. Commit with clear messages
6. Push to your fork
7. Open a Pull Request

## Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/readin52.git
cd readin52

# Create config
cp config/db.example.php config/db.php
# Edit config/db.php with your local database

# Start local server
php -S localhost:8000

# Visit http://localhost:8000/install.php
```

## Code Style

### PHP

- Follow PSR-12 coding standards
- Use meaningful variable names
- Add PHPDoc comments for functions
- Use prepared statements for all database queries

```php
/**
 * Get user by email address
 *
 * @param string $email User's email
 * @return array|null User data or null if not found
 */
public static function findByEmail(string $email): ?array
{
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}
```

### JavaScript

- Use ES6+ syntax
- No jQuery or external frameworks
- Keep it simple and readable

```javascript
// Good
const toggleProgress = async (week, category) => {
    const response = await fetch('/?route=api/progress', {
        method: 'POST',
        body: formData
    });
    return response.json();
};

// Avoid
function toggleProgress(week, category, callback) {
    $.ajax({ ... });
}
```

### CSS

- Use CSS custom properties (variables)
- Follow BEM-like naming
- Mobile-first approach

```css
/* Good */
.reading-card {
    background: var(--surface);
    border-radius: var(--radius-md);
}

.reading-card__title {
    font-size: var(--font-size-lg);
}

.reading-card--completed {
    opacity: 0.7;
}
```

## Security Guidelines

Security is critical. Please follow these practices:

### Do

- Use prepared statements for all SQL queries
- Escape all output with `e()` helper
- Validate and sanitize all input
- Use CSRF tokens on all forms
- Check authentication on protected routes

### Don't

- Never trust user input
- Don't expose sensitive data in responses
- Avoid storing sensitive data in sessions unnecessarily
- Don't commit credentials or API keys

### Example

```php
// Good - Prepared statement
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// Bad - SQL injection risk
$pdo->query("SELECT * FROM users WHERE id = $userId");

// Good - Output escaping
echo e($user['name']);

// Bad - XSS risk
echo $user['name'];
```

## Pull Request Guidelines

### Before Submitting

- [ ] Test your changes locally
- [ ] Ensure no PHP errors or warnings
- [ ] Check JavaScript console for errors
- [ ] Test on mobile viewport
- [ ] Update documentation if needed

### PR Description

Include:
- What does this PR do?
- Why is this change needed?
- How was it tested?
- Screenshots (for UI changes)

### Commit Messages

Use clear, descriptive commit messages:

```
Good:
- Add Cloudflare Turnstile bot protection
- Fix login rate limiting bug
- Update README with installation FAQ

Bad:
- Fixed stuff
- Updates
- WIP
```

## Project Structure

```
readin52/
├── index.php          # Router - all routes defined here
├── src/               # PHP classes
│   ├── Auth.php       # Authentication logic
│   ├── Database.php   # Database operations
│   ├── helpers.php    # Utility functions
│   └── ...
├── templates/         # PHP templates (HTML)
├── assets/            # CSS, JS, images
└── config/            # Configuration files
```

### Adding a New Feature

1. **Route**: Add to `index.php`
2. **Logic**: Create/modify class in `src/`
3. **View**: Add template in `templates/`
4. **Database**: Add migration in `Database::migrate()`

## Testing

Currently, testing is manual. When testing:

1. Test all affected routes
2. Check authentication/authorization
3. Test form validation
4. Check mobile responsiveness
5. Verify database changes

## Questions?

- Open an issue for discussion
- Email: seek@askdevotions.com

## Recognition

Contributors will be acknowledged in the project. Thank you for helping make ReadIn52 better for the Church!

---

*"Whatever you do, work at it with all your heart, as working for the Lord."* — Colossians 3:23
