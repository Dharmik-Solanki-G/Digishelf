// src/pages/RegisterPage.js - FIXED VERSION  
import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { authAPI, handleApiError } from '../services/api';

const RegisterPage = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    student_id: '',
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
    phone: '',
    course: '',
    year: ''
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Clear messages when user starts typing
    if (error) setError('');
    if (success) setSuccess('');
  };

  const validateForm = () => {
    // Check required fields
    const requiredFields = ['student_id', 'name', 'email', 'password', 'course', 'year'];
    for (let field of requiredFields) {
      if (!formData[field].trim()) {
        return `${field.replace('_', ' ')} is required`;
      }
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
      return 'Please enter a valid email address';
    }

    // Validate password length
    if (formData.password.length < 6) {
      return 'Password must be at least 6 characters long';
    }

    // Validate passwords match
    if (formData.password !== formData.confirmPassword) {
      return 'Passwords do not match';
    }

    // Validate student ID format (basic check)
    if (formData.student_id.length < 4) {
      return 'Student ID must be at least 4 characters long';
    }

    return null;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');

    // Validate form
    const validationError = validateForm();
    if (validationError) {
      setError(validationError);
      setLoading(false);
      return;
    }

    try {
      // Remove confirmPassword from submission data
      const { confirmPassword, ...submitData } = formData;
      
      console.log('Attempting registration with:', { 
        ...submitData, 
        password: '[HIDDEN]' 
      });
      
      const response = await authAPI.register(submitData);
      
      if (response.data) {
        setSuccess('Registration successful! Redirecting to login page...');
        
        // Clear form
        setFormData({
          student_id: '',
          name: '',
          email: '',
          password: '',
          confirmPassword: '',
          phone: '',
          course: '',
          year: ''
        });
        
        // Redirect to login after 2 seconds
        setTimeout(() => {
          navigate('/login', { 
            state: { 
              message: 'Registration successful! Please login with your credentials.',
              email: submitData.email 
            }
          });
        }, 2000);
      }
    } catch (error) {
      console.error('Registration error:', error);
      const errorMessage = handleApiError(error);
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const fillDemoData = () => {
    setFormData({
      student_id: '2024' + Math.floor(Math.random() * 1000).toString().padStart(3, '0'),
      name: 'Demo Student',
      email: 'demo' + Math.floor(Math.random() * 100) + '@student.com',
      password: 'password123',
      confirmPassword: 'password123',
      phone: '+91 98765 43210',
      course: 'Computer Science',
      year: '2nd Year'
    });
    setError('');
    setSuccess('');
  };

  return (
    <div className="page">
      <div className="container">
        <div className="form-container" style={{ maxWidth: '600px' }}>
          <h2 className="text-center mb-4" style={{ color: '#bf6b2c' }}>
            <i className="fas fa-user-plus"></i> Student Registration
          </h2>
          
          {error && (
            <div className="alert alert-error">
              <i className="fas fa-exclamation-triangle"></i> {error}
            </div>
          )}

          {success && (
            <div className="alert alert-success">
              <i className="fas fa-check-circle"></i> {success}
            </div>
          )}

          <div className="alert alert-info mb-3">
            <div className="d-flex justify-between align-center">
              <div>
                <strong>Quick Demo:</strong> Fill form with sample data
              </div>
              <button 
                type="button"
                className="btn btn-secondary btn-sm"
                onClick={fillDemoData}
                disabled={loading}
              >
                Fill Demo Data
              </button>
            </div>
          </div>

          <form onSubmit={handleSubmit}>
            <div className="grid grid-2">
              <div className="form-group">
                <label htmlFor="student_id">Student ID *:</label>
                <input
                  type="text"
                  id="student_id"
                  name="student_id"
                  className="form-control"
                  value={formData.student_id}
                  onChange={handleChange}
                  placeholder="e.g., 2024001"
                  required
                  disabled={loading}
                />
              </div>

              <div className="form-group">
                <label htmlFor="name">Full Name *:</label>
                <input
                  type="text"
                  id="name"
                  name="name"
                  className="form-control"
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Enter your full name"
                  required
                  disabled={loading}
                />
              </div>
            </div>

            <div className="form-group">
              <label htmlFor="email">Email Address *:</label>
              <input
                type="email"
                id="email"
                name="email"
                className="form-control"
                value={formData.email}
                onChange={handleChange}
                placeholder="your.email@college.edu"
                required
                disabled={loading}
              />
            </div>

            <div className="grid grid-2">
              <div className="form-group">
                <label htmlFor="course">Course *:</label>
                <input
                  type="text"
                  id="course"
                  name="course"
                  className="form-control"
                  placeholder="e.g., Computer Science"
                  value={formData.course}
                  onChange={handleChange}
                  required
                  disabled={loading}
                />
              </div>

              <div className="form-group">
                <label htmlFor="year">Academic Year *:</label>
                <select
                  id="year"
                  name="year"
                  className="form-control"
                  value={formData.year}
                  onChange={handleChange}
                  required
                  disabled={loading}
                >
                  <option value="">Select Year</option>
                  <option value="1st Year">1st Year</option>
                  <option value="2nd Year">2nd Year</option>
                  <option value="3rd Year">3rd Year</option>
                  <option value="4th Year">4th Year</option>
                  <option value="Graduate">Graduate</option>
                </select>
              </div>
            </div>

            <div className="form-group">
              <label htmlFor="phone">Phone Number (Optional):</label>
              <input
                type="tel"
                id="phone"
                name="phone"
                className="form-control"
                value={formData.phone}
                onChange={handleChange}
                placeholder="+91 98765 43210"
                disabled={loading}
              />
            </div>

            <div className="grid grid-2">
              <div className="form-group">
                <label htmlFor="password">Password *:</label>
                <input
                  type="password"
                  id="password"
                  name="password"
                  className="form-control"
                  value={formData.password}
                  onChange={handleChange}
                  placeholder="At least 6 characters"
                  required
                  disabled={loading}
                />
              </div>

              <div className="form-group">
                <label htmlFor="confirmPassword">Confirm Password *:</label>
                <input
                  type="password"
                  id="confirmPassword"
                  name="confirmPassword"
                  className="form-control"
                  value={formData.confirmPassword}
                  onChange={handleChange}
                  placeholder="Re-enter password"
                  required
                  disabled={loading}
                />
              </div>
            </div>

            <button
              type="submit"
              className="form-submit"
              disabled={loading}
            >
              {loading ? (
                <>
                  <i className="fas fa-spinner fa-spin"></i> Creating Account...
                </>
              ) : (
                <>
                  <i className="fas fa-user-plus"></i> Create Account
                </>
              )}
            </button>
          </form>

          <div className="text-center mt-3">
            <p>Already have an account? <Link to="/login">Login here</Link></p>
            <p><Link to="/">Back to Home</Link></p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default RegisterPage;