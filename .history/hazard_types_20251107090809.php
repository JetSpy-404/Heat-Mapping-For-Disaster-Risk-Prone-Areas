<?php
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/access_control.php';

// Only admins can access hazard types
requireAccess('hazard_types');
readfile(__DIR__ . '/hazard_types.html');
