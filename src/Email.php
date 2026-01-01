<?php
/**
 * Email class - Brevo API integration
 */
class Email
{
    private const BREVO_API_URL = 'https://api.brevo.com/v3/smtp/email';

    /**
     * Check if email is configured
     */
    public static function isConfigured(): bool
    {
        return defined('BREVO_API_KEY') && BREVO_API_KEY && BREVO_API_KEY !== 'your-brevo-api-key-here';
    }

    /**
     * Send an email via Brevo API
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlContent, ?string $textContent = null): array
    {
        if (!self::isConfigured()) {
            return ['success' => false, 'error' => 'Email not configured. Please set up config/email.php'];
        }

        $data = [
            'sender' => [
                'name' => defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'ReadIn52',
                'email' => defined('EMAIL_FROM_ADDRESS') ? EMAIL_FROM_ADDRESS : 'noreply@readin52.app'
            ],
            'to' => [
                [
                    'email' => $toEmail,
                    'name' => $toName
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];

        if ($textContent) {
            $data['textContent'] = $textContent;
        }

        if (defined('EMAIL_REPLY_TO') && EMAIL_REPLY_TO) {
            $data['replyTo'] = ['email' => EMAIL_REPLY_TO];
        }

        $ch = curl_init(self::BREVO_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'api-key: ' . BREVO_API_KEY
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'Connection error: ' . $error];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'messageId' => $responseData['messageId'] ?? null];
        }

        $errorMessage = $responseData['message'] ?? 'Unknown error';
        return ['success' => false, 'error' => $errorMessage, 'code' => $httpCode];
    }

    /**
     * Send password reset email
     */
    public static function sendPasswordReset(string $toEmail, string $toName, string $resetToken): array
    {
        $appName = defined('APP_NAME') ? APP_NAME : 'ReadIn52';
        $baseUrl = self::getBaseUrl();
        $resetLink = $baseUrl . '/?route=reset-password&token=' . urlencode($resetToken);

        $subject = 'Reset Your Password - ' . $appName;

        $htmlContent = self::getEmailTemplate('password-reset', [
            'name' => $toName,
            'appName' => $appName,
            'resetLink' => $resetLink,
            'expiresIn' => '1 hour'
        ]);

        $textContent = "Hi {$toName},\n\n" .
            "You requested to reset your password for {$appName}.\n\n" .
            "Click this link to reset your password:\n{$resetLink}\n\n" .
            "This link will expire in 1 hour.\n\n" .
            "If you didn't request this, you can safely ignore this email.\n\n" .
            "- The {$appName} Team";

        return self::send($toEmail, $toName, $subject, $htmlContent, $textContent);
    }

    /**
     * Send email verification (for email change)
     */
    public static function sendEmailVerification(string $toEmail, string $toName, string $verifyToken): array
    {
        $appName = defined('APP_NAME') ? APP_NAME : 'ReadIn52';
        $baseUrl = self::getBaseUrl();
        $verifyLink = $baseUrl . '/?route=verify-email&token=' . urlencode($verifyToken);

        $subject = 'Verify Your New Email - ' . $appName;

        $htmlContent = self::getEmailTemplate('email-verification', [
            'name' => $toName,
            'appName' => $appName,
            'verifyLink' => $verifyLink,
            'expiresIn' => '24 hours'
        ]);

        $textContent = "Hi {$toName},\n\n" .
            "You requested to change your email address for {$appName}.\n\n" .
            "Click this link to verify your new email:\n{$verifyLink}\n\n" .
            "This link will expire in 24 hours.\n\n" .
            "If you didn't request this, you can safely ignore this email.\n\n" .
            "- The {$appName} Team";

        return self::send($toEmail, $toName, $subject, $htmlContent, $textContent);
    }

    /**
     * Get base URL for links
     */
    private static function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Get email template with variables replaced
     */
    private static function getEmailTemplate(string $template, array $vars): string
    {
        $primaryColor = '#5D4037';
        $appName = $vars['appName'] ?? 'ReadIn52';

        $baseStyle = "
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; padding: 20px 0; border-bottom: 3px solid {$primaryColor}; }
            .header h1 { color: {$primaryColor}; margin: 0; font-size: 24px; }
            .content { padding: 30px 0; }
            .button { display: inline-block; background: {$primaryColor}; color: white !important; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
            .button:hover { background: #4E342E; }
            .footer { text-align: center; padding: 20px 0; border-top: 1px solid #eee; color: #666; font-size: 14px; }
            .note { background: #f5f5f5; padding: 15px; border-radius: 6px; font-size: 14px; color: #666; margin-top: 20px; }
        ";

        if ($template === 'password-reset') {
            return "
            <!DOCTYPE html>
            <html>
            <head><style>{$baseStyle}</style></head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>{$appName}</h1>
                    </div>
                    <div class='content'>
                        <p>Hi {$vars['name']},</p>
                        <p>You requested to reset your password. Click the button below to create a new password:</p>
                        <p style='text-align: center;'>
                            <a href='{$vars['resetLink']}' class='button'>Reset Password</a>
                        </p>
                        <div class='note'>
                            <strong>This link will expire in {$vars['expiresIn']}.</strong><br>
                            If you didn't request this password reset, you can safely ignore this email.
                        </div>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " {$appName}. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
        }

        if ($template === 'email-verification') {
            return "
            <!DOCTYPE html>
            <html>
            <head><style>{$baseStyle}</style></head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>{$appName}</h1>
                    </div>
                    <div class='content'>
                        <p>Hi {$vars['name']},</p>
                        <p>You requested to change your email address. Click the button below to verify this new email:</p>
                        <p style='text-align: center;'>
                            <a href='{$vars['verifyLink']}' class='button'>Verify Email</a>
                        </p>
                        <div class='note'>
                            <strong>This link will expire in {$vars['expiresIn']}.</strong><br>
                            If you didn't request this email change, please contact support immediately.
                        </div>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " {$appName}. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
        }

        return '';
    }
}
