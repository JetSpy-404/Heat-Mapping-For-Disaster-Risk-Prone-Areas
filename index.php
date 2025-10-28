<?php
// Simple index to prevent directory listing and provide a safe landing.
http_response_code(403);
echo 'Access forbidden.';
exit;
