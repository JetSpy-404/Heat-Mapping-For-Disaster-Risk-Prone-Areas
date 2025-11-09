// Define feature access for each role
const featureAccess = {
  admin: [
    'dashboard',
    'statistics',
    'heatmap',
    'chloropleth',
    'hazard_types',
    'hazard_data',
    'barangay',
    'municipality',
    'municipality_users'
  ],
  user: [
    'dashboard',
    'statistics',
    'heatmap',
    'hazard_data',
    'barangay'
  ]
};

// CRUD permissions for each feature
const crudPermissions = {
  admin: {
    barangay: ['create', 'read', 'update', 'delete'],
    municipality: ['create', 'read', 'update', 'delete'],
    municipality_users: ['create', 'read', 'update', 'delete'],
    hazard_data: ['create', 'read', 'update', 'delete'],
    hazard_types: ['create', 'read', 'update', 'delete']
  },
  user: {
    barangay: ['create', 'read', 'update', 'delete'], // Users can CRUD barangay
    hazard_data: ['read'], // Users can only read hazard data
    dashboard: ['read'],
    statistics: ['read'],
    heatmap: ['read']
  }
};

// Check if user has access to a feature
exports.hasFeatureAccess = (userRole, feature) => {
  return featureAccess[userRole]?.includes(feature) || false;
};

// Check if user has specific CRUD permission for a feature
exports.hasPermission = (userRole, feature, action) => {
  return crudPermissions[userRole]?.[feature]?.includes(action) || false;
};

// Get all accessible features for a role
exports.getAccessibleFeatures = (userRole) => {
  return featureAccess[userRole] || [];
};

// Get all permissions for a role
exports.getRolePermissions = (userRole) => {
  return crudPermissions[userRole] || {};
};
