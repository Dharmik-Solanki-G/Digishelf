import React from 'react';

const Footer = () => {
  return (
    <footer className="footer">
      <div className="footer-content">
        <div className="footer-section">
          <h3>DigiShelf</h3>
          <p>Your trusted digital library management system for modern educational institutions.</p>
          <div className="social-links">
            <a href="#"><i className="fab fa-facebook-f"></i></a>
            <a href="#"><i className="fab fa-twitter"></i></a>
            <a href="#"><i className="fab fa-linkedin-in"></i></a>
            <a href="#"><i className="fab fa-instagram"></i></a>
          </div>
        </div>
        <div className="footer-section">
          <h3>Quick Links</h3>
          <p><a href="/">Home</a></p>
          <p><a href="/books">Books</a></p>
          <p><a href="/about">About</a></p>
          <p><a href="/reviews">Reviews</a></p>
        </div>
        <div className="footer-section">
          <h3>Contact Info</h3>
          <p><i className="fas fa-envelope"></i> e-library@digishelf.com</p>
          <p><i className="fas fa-phone"></i> +91 1234567890</p>
          <p><i className="fas fa-map-marker-alt"></i> SUTEX BANK COLLEGE OF COMPUTER APPLICATION & SCIENCE AMROLI-SURAT.</p>
        </div>
      </div>
      <div style={{ borderTop: '1px solid #555', paddingTop: '1rem', marginTop: '2rem' }}>
        <p>&copy; 2024 DigiShelf Reading Club. All rights reserved.</p>
      </div>
    </footer>
  );
};

export default Footer;