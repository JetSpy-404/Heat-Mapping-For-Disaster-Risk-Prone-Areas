<?php
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/access_control.php';

// All users can access hazard data (read-only for users)
readfile(__DIR__ . '/dashboard');
