<?php
$pageTitle = 'Privacy Policy';
ob_start();
?>

<div class="legal-page">
    <div class="container">
        <div class="legal-content">
            <h1>Privacy Policy</h1>
            <p class="legal-updated">Last updated: <?php echo date('F j, Y'); ?></p>

            <section>
                <h2>Information We Collect</h2>
                <p>When you use <?php echo e(ReadingPlan::getAppName()); ?>, we collect:</p>
                <ul>
                    <li><strong>Account Information:</strong> Name and email address when you register</li>
                    <li><strong>Reading Progress:</strong> Your Bible reading progress and completed chapters</li>
                    <li><strong>Notes:</strong> Any notes you create while reading</li>
                    <li><strong>Preferences:</strong> Your selected Bible translation and theme settings</li>
                </ul>
            </section>

            <section>
                <h2>How We Use Your Information</h2>
                <p>We use your information to:</p>
                <ul>
                    <li>Provide and maintain your account</li>
                    <li>Track and display your reading progress</li>
                    <li>Store and retrieve your notes</li>
                    <li>Send password reset emails when requested</li>
                </ul>
            </section>

            <section>
                <h2>Third-Party Services</h2>
                <p>We use the following third-party services:</p>
                <ul>
                    <li><strong>HelloAO Bible API:</strong> Provides Bible text content</li>
                    <li><strong>Email Service:</strong> For sending password reset and verification emails</li>
                </ul>
            </section>

            <section>
                <h2>Data Storage</h2>
                <p>Your data is stored securely on our servers. Passwords are hashed and never stored in plain text.</p>
            </section>

            <section>
                <h2>Your Rights</h2>
                <p>You can:</p>
                <ul>
                    <li>Access and update your account information in Settings</li>
                    <li>Reset your reading progress at any time</li>
                    <li>Delete your account and all associated data</li>
                </ul>
            </section>

            <section>
                <h2>Cookies</h2>
                <p>We use essential cookies to maintain your login session. No tracking or advertising cookies are used.</p>
            </section>

            <section>
                <h2>Contact</h2>
                <p>For privacy-related questions, please contact the site administrator.</p>
            </section>

            <div class="legal-back">
                <a href="<?php echo Auth::isLoggedIn() ? '/?route=dashboard' : '/'; ?>" class="btn btn-secondary">Back to <?php echo Auth::isLoggedIn() ? 'Dashboard' : 'Home'; ?></a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$extraStyles = '
<style>
.legal-page {
    padding: 2rem 0;
    min-height: calc(100vh - 200px);
}
.legal-content {
    max-width: 800px;
    margin: 0 auto;
    background: var(--card-bg, #fff);
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.legal-content h1 {
    color: var(--text-primary, #212121);
    margin-bottom: 0.5rem;
}
.legal-updated {
    color: var(--text-muted, #888);
    font-size: 0.875rem;
    margin-bottom: 2rem;
}
.legal-content section {
    margin-bottom: 1.5rem;
}
.legal-content h2 {
    font-size: 1.125rem;
    color: var(--text-primary, #333);
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-color, #e0e0e0);
}
.legal-content p {
    color: var(--text-secondary, #555);
    line-height: 1.7;
    margin-bottom: 0.75rem;
}
.legal-content ul {
    margin: 0.5rem 0 0.75rem 1.5rem;
    color: var(--text-secondary, #555);
}
.legal-content li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}
.legal-back {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color, #e0e0e0);
}
</style>
';

require TEMPLATE_PATH . '/layout.php';
