<?php
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/access_control.php';

// All users can access dashboard (read-only for users)
$content = file_get_contents(__DIR__ . '/dashboard.html');

// Check for login greeting and inject it into the dashboard
if (isset($_SESSION['login_greeting'])) {
    $greeting = $_SESSION['login_greeting'];
    unset($_SESSION['login_greeting']); // Remove it after displaying

    // Inject greeting into the dashboard content
    $greeting_html = '<div id="login-greeting" class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>' . htmlspecialchars($greeting) . '</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <script>
        // Auto-dismiss login greeting after 5 seconds
        setTimeout(function() {
            const greetingAlert = document.getElementById('login-greeting');
            if (greetingAlert) {
                // Fade out the alert
                greetingAlert.style.transition = 'opacity 0.5s ease-out';
                greetingAlert.style.opacity = '0';

                // Remove from DOM after fade out
                setTimeout(function() {
                    if (greetingAlert.parentNode) {
                        greetingAlert.parentNode.removeChild(greetingAlert);
                    }
                }, 500);
            }
        }, 5000);
    </script>

    // Insert the greeting after the breadcrumb navigation
    $content = preg_replace(
        '/(<ul class="flex space-x-2 rtl:space-x-reverse">.*?<\/ul>)/s',
        '$1' . "\n" . $greeting_html,
        $content,
        1
    );
}

echo $content;
