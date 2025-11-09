<?php
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/access_control.php';

// All users can access heatmap
readfile(__DIR__ . '/heatmap.html');
