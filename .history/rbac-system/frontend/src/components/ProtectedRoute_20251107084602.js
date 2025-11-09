import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const ProtectedRoute = ({ children, feature, permission }) => {
  const { user, hasFeatureAccess, hasPermission } = useAuth();

  if (!user) {
    return <Navigate to="/login" />;
  }

  if (feature && !hasFeatureAccess(feature)) {
    return <Navigate to="/unauthorized" />;
  }

  if (permission && !hasPermission(permission.feature, permission.action)) {
    return <Navigate to="/unauthorized" />;
  }

  return children;
};

export default ProtectedRoute;
