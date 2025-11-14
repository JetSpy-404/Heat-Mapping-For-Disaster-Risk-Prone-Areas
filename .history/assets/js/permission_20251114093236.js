    document.addEventListener('alpine:init', () => {
        Alpine.store('user', {});
    });

    // Define permissions matrix matching access_control.php
    const permissions = {
        'administrator': {
            'dashboard': true,
            'statistics': true,
            'heatmap': true,
            'chloropleth': true,
            'hazard_types': true,
            'hazard_data': true,
            'barangay': true,
            'municipality': true,
            'municipality_users': true,
            'chat': true,
        },
        'user': {
            'dashboard': true,
            'statistics': true,
            'heatmap': true,
            'chloropleth': false,
            'hazard_types': false,
            'hazard_data': true,
            'barangay': true,
            'municipality': false,
            'municipality_users': false,
        }
    };

    // Global function for Alpine.js x-show directives
    window.canAccessFeature = function(feature) {
        const user = Alpine.store('user');
        if (!user || !user.role) {
            return false;
        }
        const role = user.role;
        return permissions[role] && permissions[role][feature] === true;
    };