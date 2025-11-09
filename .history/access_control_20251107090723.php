<?php
// Role-Based Access Control Utility Functions
// Include this file to check user permissions

/**
 * Check if current user has admin role
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'administrator';
}
