import React, { createContext, useState, useContext, useEffect } from 'react';
import axios from 'axios';

const AuthContext = createContext();

export const useAuth = () => {
  return useContext(AuthContext);
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [accessibleFeatures, setAccessibleFeatures] = useState([]);
  const [permissions, setPermissions] = useState({});
  const [loading, setLoading] = useState(true);

  const setAuthToken = (token) => {
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      localStorage.setItem('token', token);
    } else {
      delete axios.defaults.headers.common['Authorization'];
      localStorage.removeItem('token');
    }
  };

  // Login user
  const login = async (email, password) => {
    try {
      const res = await axios.post('/api/auth/login', { email, password });
      const { token, data } = res.data;

      setAuthToken(token);
      setUser(data.user);
      await loadUserFeatures();

      return { success: true, data: data.user };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Login failed'
      };
    }
  };

  // Load user features and permissions
  const loadUserFeatures = async () => {
    try {
      const res = await axios.get('/api/users/profile');
      const { accessibleFeatures, permissions } = res.data.data;
      setAccessibleFeatures(accessibleFeatures);
      setPermissions(permissions);
    } catch (error) {
      console.error('Failed to load user features:', error);
    }
  };

  // Logout user
  const logout = () => {
    setAuthToken(null);
    setUser(null);
    setAccessibleFeatures([]);
    setPermissions({});
  };

  // Check if user has access to a feature
  const hasFeatureAccess = (feature) => {
    return accessibleFeatures.includes(feature);
  };

  // Check if user has specific permission
  const hasPermission = (feature, action) => {
    return permissions[feature]?.includes(action) || false;
  };

  // Check if user is admin
  const isAdmin = () => {
    return user?.role === 'admin';
  };

  // Get current user
  const getCurrentUser = async () => {
    try {
      const res = await axios.get('/api/users/profile');
      const { user, accessibleFeatures, permissions } = res.data.data;
      setUser(user);
      setAccessibleFeatures(accessibleFeatures);
      setPermissions(permissions);
    } catch (error) {
      logout();
    }
  };

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (token) {
      setAuthToken(token);
      getCurrentUser().finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  const value = {
    user,
    accessibleFeatures,
    permissions,
    loading,
    login,
    logout,
    hasFeatureAccess,
    hasPermission,
    isAdmin
  };

  return (
    <AuthContext.Provider value={value}>
      {!loading && children}
    </AuthContext.Provider>
  );
};
