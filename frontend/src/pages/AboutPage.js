// src/pages/AboutPage.js
import React from 'react';

const AboutPage = () => {
  return (
    <div className="page">
      <div className="container">
        <h1 className="page-title">About DigiShelf</h1>
        
        <div className="card fade-in" style={{ maxWidth: '800px', margin: '0 auto' }}>
          <div className="card-body text-center">
            <div className="stat-icon">
              <i className="fas fa-graduation-cap"></i>
            </div>
            <h3>Empowering Education Through Digital Innovation</h3>
            <p style={{ fontSize: '1.1rem', lineHeight: '1.8', marginTop: '1rem' }}>
              DigiShelf is a modern e-library management system designed specifically for educational institutions. 
              We provide students and faculty with seamless access to a vast collection of digital resources, 
              making learning more accessible and efficient. Our platform combines the traditional library experience 
              with cutting-edge technology to create an intuitive and engaging digital environment.
            </p>
            <br />
            <p style={{ color: '#bf6b2c', fontWeight: 'bold' }}>
              Join thousands of readers who have already discovered the future of library management!
            </p>
          </div>
        </div>

        {/* Features Grid */}
        <div className="grid grid-3 mt-4">
          <div className="card fade-in">
            <div className="card-body text-center">
              <div className="stat-icon">
                <i className="fas fa-book-reader" style={{ color: '#3498db' }}></i>
              </div>
              <h4>Digital Collection</h4>
              <p>Access thousands of e-books, research papers, and academic resources from anywhere.</p>
            </div>
          </div>
          
          <div className="card fade-in">
            <div className="card-body text-center">
              <div className="stat-icon">
                <i className="fas fa-users" style={{ color: '#2ecc71' }}></i>
              </div>
              <h4>Community Driven</h4>
              <p>Share reviews, recommendations, and connect with fellow readers in your institution.</p>
            </div>
          </div>
          
          <div className="card fade-in">
            <div className="card-body text-center">
              <div className="stat-icon">
                <i className="fas fa-chart-line" style={{ color: '#f39c12' }}></i>
              </div>
              <h4>Analytics & Insights</h4>
              <p>Track your reading progress and discover new books based on your interests.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AboutPage;