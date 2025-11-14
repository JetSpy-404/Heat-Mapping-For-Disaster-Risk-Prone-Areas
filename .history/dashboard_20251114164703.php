<?php
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/access_control.php';

// Set timezone to ensure correct time
date_default_timezone_set('Asia/Manila');

// All users can access dashboard (read-only for users)
$content = file_get_contents(__DIR__ . '/dashboard.html');

// Check for login greeting and inject it into the dashboard
if (isset($_SESSION['user_id'])) {
    // Get time-based greeting dynamically (12-hour format)
    $current_hour = date('H');
    $current_minute = date('i');
    $time_greeting = '';

    if ($current_hour >= 5 && $current_hour < 12) {
        $time_greeting = 'Good morning';
    } elseif ($current_hour >= 12 && $current_hour < 17) {
        $time_greeting = 'Good afternoon';
    } elseif ($current_hour >= 17 && $current_hour < 21) {
        $time_greeting = 'Good evening';
    } else {
        $time_greeting = 'Good night';
    }

    $greeting = $time_greeting . ", " . $_SESSION['user_name'] . "!";

    // Inject greeting into the dashboard content
    $greeting_html = '<div id="login-greeting" class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <img src="assets/greetings.gif" alt="Greeting" class="me-2" style="width: 150px; height: 100px; border-radius: 50%;">
        <strong>' . htmlspecialchars($greeting) . '</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <script>
        // Auto-dismiss login greeting after 5 seconds
        setTimeout(function() {
            const greetingAlert = document.getElementById(\'login-greeting\');
            if (greetingAlert) {
                // Fade out the alert
                greetingAlert.style.transition = \'opacity 0.5s ease-out\';
                greetingAlert.style.opacity = \'0\';

                // Remove from DOM after fade out
                setTimeout(function() {
                    if (greetingAlert.parentNode) {
                        greetingAlert.parentNode.removeChild(greetingAlert);
                    }
                }, 500);
            }
        }, 5000);
    </script>';

    // Insert the greeting after the breadcrumb navigation
    $content = preg_replace(
        '/(<ul class="flex space-x-2 rtl:space-x-reverse">.*?<\/ul>)/s',
        '$1' . "\n" . $greeting_html,
        $content,
        1
    );
}

echo $content;
