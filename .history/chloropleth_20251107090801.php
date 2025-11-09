<?php
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/access_control.php';

// Only admins can access chloropleth
requireAccess('chloropleth');
readfile(__DIR__ . '/chloropleth.html');
