import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Navigation from './components/Navigation';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Barangay from './components/Barangay';

function App() {
  return (
    <AuthProvider>
      <Router>
        <Navigation />
        <Routes>
          <Route path="/login" element={<Login />} />

          {/* Protected Routes with Feature Access */}
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute feature="dashboard">
                <Dashboard />
              </ProtectedRoute>
            }
          />

          <Route
            path="/barangay"
            element={
              <ProtectedRoute feature="barangay">
                <Barangay />
              </ProtectedRoute>
            }
          />

          {/* Add more routes as needed */}
        </Routes>
      </Router>
    </AuthProvider>
  );
}

export default App;
