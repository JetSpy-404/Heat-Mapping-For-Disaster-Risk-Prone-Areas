import React from 'react';
import { useAuth } from '../contexts/AuthContext';

const Navigation = () => {
  const { user, hasFeatureAccess, isAdmin, logout } = useAuth();

  return (
    <nav className="navbar">
      <div className="nav-brand">Hazard Management System</div>

      <div className="nav-menu">
        {/* Dashboard - Accessible to all authenticated users */}
        {hasFeatureAccess('dashboard') && (
          <a href="/dashboard" className="nav-link">Dashboard</a>
        )}

        {/* Statistics - Accessible to all authenticated users */}
        {hasFeatureAccess('statistics') && (
          <a href="/statistics" className="nav-link">Statistics</a>
        )}

        {/* Heatmap - Accessible to all authenticated users */}
        {hasFeatureAccess('heatmap') && (
          <a href="/heatmap" className="nav-link">Heatmap</a>
        )}

        {/* Chloropleth - Admin only */}
        {hasFeatureAccess('chloropleth') && (
          <a href="/chloropleth" className="nav-link">Chloropleth</a>
        )}

        {/* Hazard Types - Admin only */}
        {hasFeatureAccess('hazard_types') && (
          <a href="/hazard-types" className="nav-link">Hazard Types</a>
        )}

        {/* Hazard Data - Accessible to all authenticated users */}
        {hasFeatureAccess('hazard_data') && (
          <a href="/hazard-data" className="nav-link">Hazard Data</a>
        )}

        {/* Barangay - Accessible to all authenticated users */}
        {hasFeatureAccess('barangay') && (
          <a href="/barangay" className="nav-link">Barangay</a>
        )}

        {/* Municipality - Admin only */}
        {hasFeatureAccess('municipality') && (
          <a href="/municipality" className="nav-link">Municipality</a>
        )}

        {/* Municipality Users - Admin only */}
        {hasFeatureAccess('municipality_users') && (
          <a href="/municipality-users" className="nav-link">Municipality Users</a>
        )}

        {/* User info and logout */}
        {user && (
          <div className="nav-user">
            <span>Welcome, {user.username} ({user.role})</span>
            <button onClick={logout} className="logout-btn">Logout</button>
          </div>
        )}
      </div>
    </nav>
  );
};

export default Navigation;
