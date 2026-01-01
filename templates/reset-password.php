<?php
ob_start();
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Reset Password</h1>
                <?php if (isset($validToken) && $validToken): ?>
                    <p>Enter your new password below.</p>
                <?php endif; ?>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="/?route=login" class="btn btn-primary">Sign In</a>
                </div>
            <?php elseif (isset($validToken) && $validToken): ?>
                <form method="POST" action="/?route=reset-password" class="auth-form">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required minlength="6" autofocus>
                        <small class="form-hint">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Confirm Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                </form>
            <?php else: ?>
                <div class="invalid-token">
                    <div class="invalid-icon">&#9888;</div>
                    <p>This password reset link is invalid or has expired.</p>
                    <a href="/?route=forgot-password" class="btn btn-primary">Request New Link</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    background: var(--background, #f5f5f5);
}

.auth-container {
    width: 100%;
    max-width: 420px;
}

.auth-card {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.auth-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.auth-header h1 {
    font-size: 1.75rem;
    margin: 0 0 0.5rem 0;
    color: var(--primary-brown, #5D4037);
}

.auth-header p {
    color: var(--text-muted, #666);
    margin: 0;
    font-size: 0.95rem;
}

.auth-form .form-group {
    margin-bottom: 1.25rem;
}

.auth-form .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.auth-form input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color, #ddd);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.auth-form input:focus {
    outline: none;
    border-color: var(--primary-brown, #5D4037);
    box-shadow: 0 0 0 3px rgba(93, 64, 55, 0.1);
}

.btn-block {
    width: 100%;
    padding: 0.875rem;
    font-size: 1rem;
}

.invalid-token {
    text-align: center;
    padding: 1rem 0;
}

.invalid-icon {
    font-size: 3rem;
    color: #F44336;
    margin-bottom: 1rem;
}

.invalid-token p {
    color: var(--text-muted, #666);
    margin-bottom: 1.5rem;
}
</style>

<?php
$content = ob_get_clean();
$pageTitle = 'Reset Password';
$bodyClass = 'no-nav';
require TEMPLATE_PATH . '/layout.php';
