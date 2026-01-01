<?php
/**
 * Email Configuration (Brevo)
 *
 * Copy this file to email.php and fill in your Brevo credentials.
 * The email.php file is gitignored and will not be overwritten on deploy.
 *
 * Get your API key from: Brevo Dashboard → SMTP & API → API Keys
 */

// Brevo API Key
define('BREVO_API_KEY', 'your-brevo-api-key-here');

// Sender details (must be verified in Brevo)
define('EMAIL_FROM_ADDRESS', 'noreply@yourdomain.com');
define('EMAIL_FROM_NAME', 'ReadIn52');

// Optional: Reply-to address
define('EMAIL_REPLY_TO', 'support@yourdomain.com');
