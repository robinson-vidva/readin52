<?php
$pageTitle = 'About';
$parentSiteUrl = Database::getSetting('parent_site_url', '');
$parentSiteName = Database::getSetting('parent_site_name', '');
$githubRepoUrl = Database::getSetting('github_repo_url', 'https://github.com/askdevotions/readin52');
$adminEmail = Database::getSetting('admin_email', '');

ob_start();
?>

<div class="about-page">
    <div class="container">
        <div class="about-content">
            <h1>About <?php echo e(ReadingPlan::getAppName()); ?></h1>

            <section class="about-section">
                <h2>Our Mission</h2>
                <p><?php echo e(ReadingPlan::getAppName()); ?> is designed to help individuals and communities journey through the Bible in 52 weeks. Our goal is to make Scripture accessible and encourage consistent engagement with God's Word.</p>
                <p>With a structured reading plan covering four categories - Poetry & Wisdom, Law & History, Prophetic Books, and Gospel & Letters - you'll experience the full breadth of Scripture throughout the year.</p>
            </section>

            <?php if ($parentSiteName || $parentSiteUrl): ?>
            <section class="about-section">
                <h2>About <?php echo e($parentSiteName ?: 'Our Organization'); ?></h2>
                <p>This instance of <?php echo e(ReadingPlan::getAppName()); ?> is hosted by <?php echo e($parentSiteName ?: 'our organization'); ?> to serve our community's Bible reading journey.</p>
                <?php if ($parentSiteUrl): ?>
                    <p><a href="<?php echo e($parentSiteUrl); ?>" target="_blank" rel="noopener" class="btn btn-primary">Visit <?php echo e($parentSiteName ?: 'Our Website'); ?></a></p>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <section class="about-section">
                <h2>Open Source</h2>
                <p>ReadIn52 is open source software, freely available for churches, ministries, and organizations to host for their own communities. We believe in making Bible reading tools accessible to everyone.</p>
                <?php if ($githubRepoUrl): ?>
                    <div class="about-links">
                        <a href="<?php echo e($githubRepoUrl); ?>" target="_blank" rel="noopener" class="about-link">
                            <span class="link-icon">&#x1F4BB;</span>
                            <span class="link-text">
                                <strong>View on GitHub</strong>
                                <small>Source code, documentation, and releases</small>
                            </span>
                        </a>
                        <a href="<?php echo e($githubRepoUrl); ?>/issues" target="_blank" rel="noopener" class="about-link">
                            <span class="link-icon">&#x1F41B;</span>
                            <span class="link-text">
                                <strong>Report Issues</strong>
                                <small>Bug reports and feature requests</small>
                            </span>
                        </a>
                        <a href="<?php echo e($githubRepoUrl); ?>#readme" target="_blank" rel="noopener" class="about-link">
                            <span class="link-icon">&#x1F4D6;</span>
                            <span class="link-text">
                                <strong>Host Your Own</strong>
                                <small>Setup guide for your church or community</small>
                            </span>
                        </a>
                    </div>
                <?php endif; ?>
            </section>

            <section class="about-section">
                <h2>Contact & Support</h2>
                <?php if ($adminEmail): ?>
                    <p>For questions, support, or feedback about this instance, contact the administrator:</p>
                    <p><a href="mailto:<?php echo e($adminEmail); ?>" class="btn btn-outline"><?php echo e($adminEmail); ?></a></p>
                <?php endif; ?>
                <?php if ($githubRepoUrl): ?>
                    <p style="margin-top: 1rem;">For issues with the application itself (bugs, features), please use the <a href="<?php echo e($githubRepoUrl); ?>/issues" target="_blank" rel="noopener">GitHub Issues</a> page.</p>
                <?php endif; ?>
            </section>

            <section class="about-section">
                <h2>Credits</h2>
                <ul class="credits-list">
                    <li><strong>Scripture Content:</strong> <a href="https://bible.helloao.org/" target="_blank" rel="noopener">HelloAO Bible API</a></li>
                    <li><strong>Reading Plan:</strong> Based on Professor Grant Horner's Bible Reading System</li>
                    <li><strong>Development:</strong> Built with PHP, MySQL, and modern web technologies</li>
                </ul>
            </section>

            <div class="about-back">
                <a href="<?php echo Auth::isLoggedIn() ? '/?route=dashboard' : '/'; ?>" class="btn btn-secondary">Back to <?php echo Auth::isLoggedIn() ? 'Dashboard' : 'Home'; ?></a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$extraStyles = '
<style>
.about-page {
    padding: 2rem 0;
    min-height: calc(100vh - 200px);
}
.about-content {
    max-width: 800px;
    margin: 0 auto;
    background: var(--card-bg, #fff);
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.about-content h1 {
    color: var(--text-primary, #212121);
    margin-bottom: 2rem;
    text-align: center;
}
.about-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color, #e0e0e0);
}
.about-section:last-of-type {
    border-bottom: none;
}
.about-section h2 {
    font-size: 1.25rem;
    color: var(--primary, #5D4037);
    margin-bottom: 1rem;
}
.about-section p {
    color: var(--text-secondary, #555);
    line-height: 1.7;
    margin-bottom: 0.75rem;
}
.about-links {
    display: grid;
    gap: 0.75rem;
    margin-top: 1rem;
}
.about-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--background, #f8f8f8);
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
    border: 1px solid var(--border-color, #e0e0e0);
}
.about-link:hover {
    background: var(--card-bg, #fff);
    border-color: var(--primary, #5D4037);
    transform: translateX(4px);
}
.link-icon {
    font-size: 1.5rem;
}
.link-text {
    display: flex;
    flex-direction: column;
}
.link-text strong {
    color: var(--text-primary, #333);
}
.link-text small {
    color: var(--text-muted, #888);
    font-size: 0.85rem;
}
.credits-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.credits-list li {
    padding: 0.5rem 0;
    color: var(--text-secondary, #555);
}
.credits-list a {
    color: var(--primary, #5D4037);
}
.about-back {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color, #e0e0e0);
    text-align: center;
}
</style>
';

require TEMPLATE_PATH . '/layout.php';
