<?php
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/access_control.php';

// Only admins can access municipality
requireAccess('municipality');
readfile(__DIR__ . '/municipality.html');
