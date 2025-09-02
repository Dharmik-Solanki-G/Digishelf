// src/pages/AdminLogin.js - FIXED VERSION
import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { authAPI, handleApiError } from '../services/api';

const AdminLogin = ({ onLogin }) => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    username: '',
    password: ''
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Clear error when user starts typing
    if (error) setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    // Basic validation
    if (!formData.username || !formData.password) {
      setError('Please fill in all fields');
      setLoading(false);
      return;
    }

    try {
      console.log('Attempting admin login with:', { username: formData.username });
      const response = await authAPI.adminLogin(formData);
      
      if (response.data && response.data.admin) {
        console.log('Admin login successful:', response.data.admin);
        onLogin(response.data);
        navigate('/admin/dashboard');
      } else {
        setError('Login failed. Invalid response from server.');
      }
    } catch (error) {
      console.error('Admin login error:', error);
      const errorMessage = handleApiError(error);
      
      // Provide specific error for wrong credentials
      if (error.message === 'Invalid credentials' || errorMessage.includes('credentials')) {
        setError('Invalid username or password. Please try again.');
      } else {
        setError(errorMessage);
      }
    } finally {
      setLoading(false);
    }
  };

  const fillDemoCredentials = () => {
    setFormData({
      username: 'admin',
      password: 'password'
    });
    setError('');
  };

  return (
    <div className="page">
      <div className="container">
        <div className="form-container">
          <h2 className="text-center mb-4" style={{ color: '#e74c3c' }}>
            <i className="fas fa-shield-alt"></i> Admin Login
          </h2>
          
          {error && (
            <div className="alert alert-error">
              <i className="fas fa-exclamation-triangle"></i> {error}
            </div>
          )}

          <div className="alert alert-info mb-3">
            <div className="d-flex justify-between align-center">
              <div>
                <strong>Demo Admin Credentials:</strong><br />
                Username: admin<br />
                Password: password
              </div>
              <button 
                type="button"
                className="btn btn-secondary btn-sm"
                onClick={fillDemoCredentials}
                disabled={loading}
              >
                Use Demo
              </button>
            </div>
          </div>

          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label htmlFor="username">Username:</label>
              <input
                type="text"
                id="username"
                name="username"
                className="form-control"
                value={formData.username}
                onChange={handleChange}
                placeholder="Enter admin username"
                required
                disabled={loading}
              />
            </div>

            <div className="form-group">
              <label htmlFor="password">Password:</label>
              <input
                type="password"
                id="password"
                name="password"
                className="form-control"
                value={formData.password}
                onChange={handleChange}
                placeholder="Enter admin password"
                required
                disabled={loading}
              />
            </div>

            <button
              type="submit"
              className="form-submit"
              style={{ background: '#e74c3c' }}
              disabled={loading}
            >
              {loading ? (
                <>
                  <i className="fas fa-spinner fa-spin"></i> Authenticating...
                </>
              ) : (
                <>
                  <i className="fas fa-shield-alt"></i> Login as Admin
                </>
              )}
            </button>
          </form>

          <div className="text-center mt-3">
            <p>
              <Link to="/login">Student Login</Link> | 
              <Link to="/"> Back to Home</Link>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminLogin;