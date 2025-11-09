<?php
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/access_control.php';

// All users can access statistics
readfile(__DIR__ . '/statistics.html');
