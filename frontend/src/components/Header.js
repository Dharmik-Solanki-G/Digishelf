// src/components/Header.js - FIXED VERSION
import React from 'react';
import { Link } from 'react-router-dom';

const Header = ({ user, admin, logout }) => {
  return (
    <header className="header">
      <div className="header-content">
        <Link to="/" className="logo">
          <div className="logo-icon">
            <i className="fas fa-book-open"></i>
          </div>
          <div>
            <div style={{ fontSize: '1.8rem' }}>DIGISHELF</div>
            <div style={{ fontSize: '0.9rem', opacity: 0.8 }}>READING CLUB</div>
          </div>
        </Link>
        
        <nav>
          <ul className="nav-menu">
            <li><Link to="/" className="nav-link">Home</Link></li>
            <li><Link to="/books" className="nav-link">Books</Link></li>
            <li><Link to="/about" className="nav-link">About</Link></li>
            <li><Link to="/reviews" className="nav-link">Reviews</Link></li>
          </ul>
        </nav>
        
        <div className="auth-section">
          {user ? (
            <div className="user-info">
              <div className="user-avatar">
                {user.name ? user.name.split(' ').map(n => n[0]).join('') : 'U'}
              </div>
              <div>
                <div style={{ fontWeight: 'bold' }}>{user.name || 'User'}</div>
                <div style={{ fontSize: '0.8rem', opacity: 0.8 }}>
                  ID: {user.student_id || 'N/A'}
                </div>
              </div>
              <Link to="/dashboard" className="btn btn-secondary">Dashboard</Link>
              <button onClick={logout} className="btn btn-primary">Logout</button>
            </div>
          ) : admin ? (
            <div className="user-info">
              <div className="user-avatar" style={{ background: '#e74c3c' }}>
                {admin.name ? admin.name.split(' ').map(n => n[0]).join('') : 'A'}
              </div>
              <div>
                <div style={{ fontWeight: 'bold' }}>{admin.name || 'Admin'}</div>
                <div style={{ fontSize: '0.8rem', opacity: 0.8 }}>Administrator</div>
              </div>
              <Link to="/admin/dashboard" className="btn btn-secondary">Admin Panel</Link>
              <button onClick={logout} className="btn btn-primary">Logout</button>
            </div>
          ) : (
            <>
              <Link to="/login" className="btn btn-secondary">Login</Link>
              <Link to="/register" className="btn btn-primary">Register</Link>
              <Link to="/admin/login" className="btn btn-secondary">Admin</Link>
            </>
          )}
        </div>
      </div>
    </header>
  );
};

export default Header;