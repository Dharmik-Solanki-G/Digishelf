// src/App.js - FIXED VERSION
import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import axios from 'axios';

// Import components
import Header from './components/Header';
import Footer from './components/Footer';
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import BooksPage from './pages/BooksPage';
import AboutPage from './pages/AboutPage';
import ReviewPage from './pages/ReviewPage';
import UserDashboard from './pages/UserDashboard';
import AdminDashboard from './pages/AdminDashboard';
import AdminLogin from './pages/AdminLogin';

// Import CSS
import './App.css';

// Set up axios base URL - Update this to match your backend server
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost/digishelf/backend';
axios.defaults.baseURL = API_BASE_URL;

// Add request interceptor for authentication
axios.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Add response interceptor to handle token expiration
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('user');
      localStorage.removeItem('admin');
      localStorage.removeItem('userToken');
      localStorage.removeItem('adminToken');
      window.location.href = '/';
    }
    return Promise.reject(error);
  }
);

function App() {
  const [user, setUser] = useState(null);
  const [admin, setAdmin] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    initializeAuth();
  }, []);

  const initializeAuth = async () => {
    try {
      const storedUser = localStorage.getItem('user');
      const storedAdmin = localStorage.getItem('admin');
      const userToken = localStorage.getItem('userToken');
      const adminToken = localStorage.getItem('adminToken');
      
      if (storedUser && userToken) {
        const userData = JSON.parse(storedUser);
        // Verify token is still valid
        try {
          await verifyToken(userToken);
          setUser(userData);
        } catch (error) {
          console.log('User token invalid, clearing storage');
          clearUserStorage();
        }
      }
      
      if (storedAdmin && adminToken) {
        const adminData = JSON.parse(storedAdmin);
        // Verify token is still valid
        try {
          await verifyToken(adminToken);
          setAdmin(adminData);
        } catch (error) {
          console.log('Admin token invalid, clearing storage');
          clearAdminStorage();
        }
      }
    } catch (error) {
      console.error('Auth initialization failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const verifyToken = async (token) => {
    try {
      const response = await axios.get(`/auth.php?action=verify-token&token=${token}`);
      if (!response.data.valid) {
        throw new Error('Invalid token');
      }
      return response.data;
    } catch (error) {
      throw error;
    }
  };

  const clearUserStorage = () => {
    localStorage.removeItem('user');
    localStorage.removeItem('userToken');
    setUser(null);
  };

  const clearAdminStorage = () => {
    localStorage.removeItem('admin');
    localStorage.removeItem('adminToken');
    setAdmin(null);
  };

  const loginUser = (userData) => {
    try {
      setUser(userData.user);
      localStorage.setItem('user', JSON.stringify(userData.user));
      localStorage.setItem('userToken', userData.token);
      console.log('User logged in successfully');
    } catch (error) {
      console.error('Error during user login:', error);
    }
  };

  const loginAdmin = (adminData) => {
    try {
      setAdmin(adminData.admin);
      localStorage.setItem('admin', JSON.stringify(adminData.admin));
      localStorage.setItem('adminToken', adminData.token);
      console.log('Admin logged in successfully');
    } catch (error) {
      console.error('Error during admin login:', error);
    }
  };

  const logout = () => {
    try {
      // Call logout API
      axios.post('/auth.php?action=logout').catch(console.error);
      
      // Clear all auth data
      clearUserStorage();
      clearAdminStorage();
      
      console.log('Logged out successfully');
    } catch (error) {
      console.error('Error during logout:', error);
    }
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner">
          <div className="spinner"></div>
          <p>Loading DigiShelf...</p>
        </div>
      </div>
    );
  }

  return (
    <Router>
      <div className="App">
        <Header user={user} admin={admin} logout={logout} />
        
        <main className="main-content">
          <Routes>
            {/* Public Routes */}
            <Route path="/" element={<HomePage />} />
            <Route path="/books" element={<BooksPage user={user} />} />
            <Route path="/about" element={<AboutPage />} />
            <Route path="/reviews" element={<ReviewPage />} />
            
            {/* User Authentication Routes */}
            <Route 
              path="/login" 
              element={user ? <Navigate to="/dashboard" replace /> : <LoginPage onLogin={loginUser} />} 
            />
            <Route 
              path="/register" 
              element={user ? <Navigate to="/dashboard" replace /> : <RegisterPage />} 
            />
            
            {/* Protected User Routes */}
            <Route 
              path="/dashboard" 
              element={user ? <UserDashboard user={user} /> : <Navigate to="/login" replace />} 
            />
            
            {/* Admin Authentication Routes */}
            <Route 
              path="/admin/login" 
              element={admin ? <Navigate to="/admin/dashboard" replace /> : <AdminLogin onLogin={loginAdmin} />} 
            />
            
            {/* Protected Admin Routes */}
            <Route 
              path="/admin/dashboard" 
              element={admin ? <AdminDashboard admin={admin} /> : <Navigate to="/admin/login" replace />} 
            />
            
            {/* Catch all route */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </main>
        
        <Footer />
      </div>
    </Router>
  );
}

export default App;