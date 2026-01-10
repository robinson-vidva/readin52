<?php
$pageTitle = 'Terms & Conditions';
ob_start();
?>

<div class="legal-page">
    <div class="container">
        <div class="legal-content">
            <h1>Terms & Conditions</h1>
            <p class="legal-updated">Last updated: <?php echo date('F j, Y'); ?></p>

            <section>
                <h2>Acceptance of Terms</h2>
                <p>By accessing and using <?php echo e(ReadingPlan::getAppName()); ?>, you accept and agree to be bound by these Terms & Conditions.</p>
            </section>

            <section>
                <h2>Use of Service</h2>
                <p>This service is provided to help you track your Bible reading progress. You agree to:</p>
                <ul>
                    <li>Provide accurate registration information</li>
                    <li>Keep your account credentials secure</li>
                    <li>Use the service for personal, non-commercial purposes</li>
                    <li>Not attempt to disrupt or compromise the service</li>
                </ul>
            </section>

            <section>
                <h2>User Content</h2>
                <p>You retain ownership of any notes or content you create. By using this service, you grant us permission to store and display your content to you.</p>
            </section>

            <section>
                <h2>Bible Content</h2>
                <p>Bible text is provided through the HelloAO Bible API. We do not own or control the Bible content and it is subject to its own terms and licenses.</p>
            </section>

            <section>
                <h2>Account Termination</h2>
                <p>You may delete your account at any time through the Settings page. We reserve the right to suspend or terminate accounts that violate these terms.</p>
            </section>

            <section>
                <h2>Disclaimer</h2>
                <p>This service is provided "as is" without warranties of any kind. We are not responsible for any data loss or service interruptions.</p>
            </section>

            <section>
                <h2>Changes to Terms</h2>
                <p>We may update these terms from time to time. Continued use of the service constitutes acceptance of any changes.</p>
            </section>

            <section>
                <h2>Contact</h2>
                <p>For questions about these terms, please contact the site administrator.</p>
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
