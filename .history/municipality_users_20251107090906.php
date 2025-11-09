<?php
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/access_control.php';

// Only admins can access municipality users
requireAccess('municipality_users');
readfile(__DIR__ . '/municipality_users.html');
