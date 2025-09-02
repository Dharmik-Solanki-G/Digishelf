// src/pages/HomePage.js
import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { booksAPI } from '../services/api';

const HomePage = () => {
  const [featuredBooks, setFeaturedBooks] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    loadFeaturedBooks();
  }, []);

  const loadFeaturedBooks = async () => {
    try {
      const response = await booksAPI.getAll(1, 4);
      setFeaturedBooks(response.data.books);
    } catch (error) {
      console.error('Failed to load featured books:', error);
      // Use fallback data if API fails
      setFeaturedBooks([
        { id: 1, title: "The Great Gatsby", author: "F. Scott Fitzgerald", category_name: "Fiction", available_quantity: 3 },
        { id: 2, title: "1984", author: "George Orwell", category_name: "Dystopian", available_quantity: 4 },
        { id: 3, title: "To Kill a Mockingbird", author: "Harper Lee", category_name: "Fiction", available_quantity: 2 },
        { id: 4, title: "A Brief History of Time", author: "Stephen Hawking", category_name: "Science", available_quantity: 2 }
      ]);
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      window.location.href = `/books?search=${encodeURIComponent(searchQuery)}`;
    }
  };

  return (
    <div className="home-page">
      {/* Hero Section */}
      <section className="hero">
        <div className="hero-content">
          <div className="container">
            <h1>Welcome to DigiShelf</h1>
            <p>Your Digital Gateway to Knowledge and Literature</p>
            <Link to="/books" className="btn btn-primary" style={{ fontSize: '1.1rem', padding: '1rem 2rem' }}>
              Explore Books
            </Link>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="page" style={{ background: 'white' }}>
        <div className="container">
          <h2 className="page-title">Why Choose DigiShelf?</h2>
          <div className="grid grid-3">
            <div className="card fade-in">
              <div className="card-body text-center">
                <div className="stat-icon">
                  <i className="fas fa-book-reader"></i>
                </div>
                <h3>Vast Collection</h3>
                <p>Access thousands of books across multiple genres and categories</p>
              </div>
            </div>
            <div className="card fade-in">
              <div className="card-body text-center">
                <div className="stat-icon">
                  <i className="fas fa-search"></i>
                </div>
                <h3>Smart Search</h3>
                <p>Find your favorite books quickly with our advanced search system</p>
              </div>
            </div>
            <div className="card fade-in">
              <div className="card-body text-center">
                <div className="stat-icon">
                  <i className="fas fa-mobile-alt"></i>
                </div>
                <h3>Mobile Friendly</h3>
                <p>Access your library anytime, anywhere from any device</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Search Section */}
      <section className="page">
        <div className="container">
          <div className="search-container">
            <h2 style={{ marginBottom: '2rem', color: '#bf6b2c' }}>
              Find Your Next Great Read
            </h2>
            <form onSubmit={handleSearch}>
              <div className="search-box">
                <input
                  type="text"
                  className="search-input"
                  placeholder="Search books by title, author, or genre..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
                <button type="submit" className="search-button">
                  <i className="fas fa-search"></i>
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>

      {/* Featured Books */}
      <section className="page" style={{ background: 'linear-gradient(135deg, #f8f9fa, #e9ecef)' }}>
        <div className="container">
          <h2 className="page-title">Featured Books</h2>
          <div className="grid grid-4">
            {featuredBooks.map((book) => (
              <div key={book.id} className="book-card fade-in">
                <div className="book-cover">
                  <i className="fas fa-book"></i>
                </div>
                <div className="book-info">
                  <div className="book-title">{book.title}</div>
                  <div className="book-author">by {book.author}</div>
                  <div className="book-genre">{book.category_name}</div>
                  <span className={`book-status ${book.available_quantity > 0 ? 'status-available' : 'status-issued'}`}>
                    {book.available_quantity > 0 ? 'Available' : 'Issued'}
                  </span>
                </div>
              </div>
            ))}
          </div>
          <div className="text-center mt-4">
            <Link to="/books" className="btn btn-primary">View All Books</Link>
          </div>
        </div>
      </section>
    </div>
  );
};

export default HomePage;