<?php
// Role-Based Access Control Utility Functions
// Include this file to check user permissions

/**
 * Check if current user has admin role
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'administrator';
}

/**
 * Check if current user has user role
 */
function isUser() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
}

/**
 * Check if user can access a specific feature
 */
function canAccessFeature($feature) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }

    $role = $_SESSION['user_role'];

    // Define permissions matrix
    $permissions = [
        'administrator' => [
            'dashboard' => true,
            'statistics' => true,
            'heatmap' => true,
            'chloropleth' => true,
            'hazard_types' => true,
            'hazard_data' => true,
            'barangay' => true,
            'municipality' => true,
            'municipality_users' => true,
            'profile' => true,
            'chat' => true,
        ],
        'user' => [
            'dashboard' => true,
            'statistics' => true,
            'heatmap' => true,
            'chloropleth' => false,
            'hazard_types' => false,
            'hazard_data' => true,
            'barangay' => true,
            'municipality' => false,
            'municipality_users' => false,
            'profile' => true,
            'chat' => true,
        ]
    ];

    return isset($permissions[$role][$feature]) && $permissions[$role][$feature];
}

/**
 * Check if user can perform CRUD operation on a feature
 */
function canPerformCRUD($feature, $operation = null) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }

    $role = $_SESSION['user_role'];

    // Define CRUD permissions matrix
    $crudPermissions = [
        'administrator' => [
            'dashboard' => ['read'],
            'statistics' => ['read'],
            'heatmap' => ['read'],
            'chloropleth' => ['read'],
            'hazard_types' => ['create', 'read', 'update', 'delete'],
            'hazard_data' => ['create', 'read', 'update', 'delete'],
            'barangay' => ['create', 'read', 'update', 'delete'],
            'municipality' => ['create', 'read', 'update', 'delete'],
            'municipality_users' => ['create', 'read', 'update', 'delete'],
            'profile' => ['create', 'read', 'update', 'delete'],
            'chat' => ['create', 'read', 'update', 'delete'],
        ],
        'user' => [
            'dashboard' => ['read'],
            'statistics' => ['read'],
            'heatmap' => ['read'],
            'chloropleth' => [], // no access
            'hazard_types' => [], // no access
            'hazard_data' => ['read'],
            'barangay' => ['create', 'read', 'update', 'delete'],
            'municipality' => [], // no access
            'municipality_users' => [], // no access
            'profile' => ['create', 'read', 'update'],
            'chat' => ['create', 'read', 'update'],
        ]
    ];

    if (!isset($crudPermissions[$role][$feature])) {
        return false;
    }

    if ($operation === null) {
        // Check if user has any CRUD permissions for this feature
        return !empty($crudPermissions[$role][$feature]);
    }

    return in_array($operation, $crudPermissions[$role][$feature]);
}

/**
 * Redirect unauthorized users
 */
function requireAccess($feature, $redirectUrl = 'dashboard.php') {
    if (!canAccessFeature($feature)) {
        header("Location: $redirectUrl?error=access_denied");
        exit;
    }
}

/**
 * Get user role display name
 */
function getUserRoleDisplay() {
    if (!isset($_SESSION['user_role'])) {
        return 'User';
    }

    return $_SESSION['user_role'] === 'administrator' ? 'Administrator' : 'User';
}

/**
 * Get accessible features for current user
 */
function getAccessibleFeatures() {
    if (!isset($_SESSION['user_role'])) {
        return [];
    }

    $role = $_SESSION['user_role'];

    $allFeatures = [
        'dashboard', 'statistics', 'heatmap', 'chloropleth',
        'hazard_types', 'hazard_data', 'barangay', 'municipality', 'municipality_users', 'profile'
    ];

    return array_filter($allFeatures, function($feature) use ($role) {
        return canAccessFeature($feature);
    });
}
