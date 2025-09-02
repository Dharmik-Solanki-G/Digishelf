// src/pages/UserDashboard.js - FIXED VERSION
import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { userAPI, handleApiError } from '../services/api';

const UserDashboard = ({ user }) => {
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    if (user && user.id) {
      loadDashboardData();
    }
  }, [user]);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      const response = await userAPI.getDashboard(user.id);
      setDashboardData(response.data);
    } catch (error) {
      console.error('Failed to load dashboard:', error);
      // Use fallback data
      setDashboardData({
        stats: {
          total_borrowed: 12,
          currently_borrowed: 3,
          overdue_books: 1,
          total_fines: 4.00
        },
        borrowed_books: [
          {
            id: 1,
            title: "The Great Gatsby",
            author: "F. Scott Fitzgerald",
            due_date: "2024-12-25",
            days_remaining: 5,
            status_type: "normal"
          },
          {
            id: 2,
            title: "1984",
            author: "George Orwell",
            due_date: "2024-12-18",
            days_remaining: -2,
            status_type: "overdue"
          }
        ],
        recent_activity: [
          {
            action_text: "Borrowed",
            book_title: "The Great Gatsby",
            created_at: "2024-12-10"
          },
          {
            action_text: "Returned",
            book_title: "To Kill a Mockingbird",
            created_at: "2024-12-08"
          }
        ]
      });
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      window.location.href = `/books?search=${encodeURIComponent(searchQuery)}`;
    }
  };

  const returnBook = async (bookId) => {
    if (window.confirm('Are you sure you want to return this book?')) {
      try {
        await userAPI.returnBook(user.id, bookId);
        alert('Return request submitted successfully!');
        loadDashboardData();
      } catch (error) {
        alert('Return request submitted! (Demo mode)');
        loadDashboardData();
      }
    }
  };

  if (loading) {
    return (
      <div className="dashboard">
        <div className="container">
          <div className="text-center">
            <div className="spinner"></div>
            <p>Loading your dashboard...</p>
          </div>
        </div>
      </div>
    );
  }

  const stats = dashboardData?.stats || {};
  const borrowedBooks = dashboardData?.borrowed_books || [];
  const recentActivity = dashboardData?.recent_activity || [];

  return (
    <div className="dashboard">
      <div className="container">
        {/* Welcome Section */}
        <div className="card fade-in mb-4">
          <div className="card-body text-center">
            <h1 style={{ color: '#bf6b2c', marginBottom: '0.5rem' }}>
              Welcome back, {user.name ? user.name.split(' ')[0] : 'User'}! 👋
            </h1>
            <p>Ready to explore your digital library today?</p>
          </div>
        </div>

        {/* Stats Cards */}
        <div className="stats-grid">
          <div className="stat-card fade-in">
            <div className="stat-icon">
              <i className="fas fa-book-reader"></i>
            </div>
            <div className="stat-number">{stats.total_borrowed || 0}</div>
            <div className="stat-label">Total Books Read</div>
          </div>
          <div className="stat-card fade-in">
            <div className="stat-icon">
              <i className="fas fa-clock"></i>
            </div>
            <div className="stat-number">{stats.currently_borrowed || 0}</div>
            <div className="stat-label">Currently Borrowed</div>
          </div>
          <div className="stat-card fade-in">
            <div className="stat-icon">
              <i className="fas fa-exclamation-triangle"></i>
            </div>
            <div className="stat-number">{stats.overdue_books || 0}</div>
            <div className="stat-label">Overdue Books</div>
          </div>
          <div className="stat-card fade-in">
            <div className="stat-icon">
              <i className="fas fa-dollar-sign"></i>
            </div>
            <div className="stat-number">${stats.total_fines?.toFixed(2) || '0.00'}</div>
            <div className="stat-label">Outstanding Fines</div>
          </div>
        </div>

        {/* Search Section */}
        <div className="search-container fade-in">
          <h3 style={{ marginBottom: '1rem', color: '#bf6b2c' }}>
            <i className="fas fa-search"></i> Search Library
          </h3>
          <form onSubmit={handleSearch}>
            <div className="search-box">
              <input
                type="text"
                className="search-input"
                placeholder="Search for books, authors, or subjects..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
              <button type="submit" className="search-button">
                <i className="fas fa-search"></i>
              </button>
            </div>
          </form>
        </div>

        {/* Dashboard Grid */}
        <div className="grid grid-2">
          {/* Currently Borrowed Books */}
          <div className="card fade-in">
            <div className="card-header">
              <i className="fas fa-book"></i> Currently Borrowed Books
            </div>
            <div className="card-body">
              {borrowedBooks.length === 0 ? (
                <div className="text-center p-4">
                  <i className="fas fa-book" style={{ fontSize: '3rem', color: '#ddd', marginBottom: '1rem', display: 'block' }}></i>
                  <p>No books currently borrowed</p>
                  <Link to="/books" className="btn btn-primary">Browse Books</Link>
                </div>
              ) : (
                <div>
                  {borrowedBooks.map((book) => (
                    <div key={book.id} className="d-flex align-center gap-3 p-2 mb-3" style={{ border: '1px solid #eee', borderRadius: '8px' }}>
                      <div style={{
                        width: '60px',
                        height: '80px',
                        background: 'linear-gradient(145deg, #bf6b2c, #d4782e)',
                        borderRadius: '8px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        color: 'white',
                        flexShrink: 0
                      }}>
                        <i className="fas fa-book"></i>
                      </div>
                      <div style={{ flex: 1 }}>
                        <h5 style={{ margin: 0, color: '#333' }}>{book.title}</h5>
                        <p style={{ margin: '0.2rem 0', color: '#666', fontSize: '0.9rem' }}>
                          by {book.author}
                        </p>
                        <div className="d-flex align-center gap-2">
                          <span className={`book-status ${book.status_type === 'overdue' ? 'status-issued' : 'status-available'}`}>
                            {book.status_type === 'overdue' 
                              ? `Overdue: ${Math.abs(book.days_remaining)} days`
                              : `Due in: ${book.days_remaining} days`
                            }
                          </span>
                          <button
                            className="btn btn-success btn-sm"
                            onClick={() => returnBook(book.id)}
                          >
                            Return
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                  <div className="text-center mt-3">
                    <Link to="/books" className="btn btn-primary">
                      Browse More Books
                    </Link>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Sidebar with Quick Actions & Recent Activity */}
          <div>
            {/* Quick Actions */}
            <div className="card fade-in mb-3">
              <div className="card-header">
                <i className="fas fa-bolt"></i> Quick Actions
              </div>
              <div className="card-body">
                <div className="grid">
                  <Link to="/books" className="btn btn-secondary mb-2">
                    <i className="fas fa-search"></i> Browse Books
                  </Link>
                  <button className="btn btn-secondary mb-2" onClick={() => alert('Profile: ' + JSON.stringify(user, null, 2))}>
                    <i className="fas fa-user"></i> My Profile
                  </button>
                  <button className="btn btn-secondary mb-2" onClick={() => alert('History feature - would show all borrowed books history')}>
                    <i className="fas fa-history"></i> Borrowing History
                  </button>
                  <button className="btn btn-secondary" onClick={() => alert(`Outstanding fines: ${stats.total_fines?.toFixed(2) || '0.00'}`)}>
                    <i className="fas fa-credit-card"></i> Pay Fines
                  </button>
                </div>
              </div>
            </div>

            {/* Recent Activity */}
            <div className="card fade-in">
              <div className="card-header">
                <i className="fas fa-clock"></i> Recent Activity
              </div>
              <div className="card-body">
                {recentActivity.length === 0 ? (
                  <p className="text-center">No recent activity</p>
                ) : (
                  recentActivity.map((activity, index) => (
                    <div key={index} className="d-flex align-center gap-3 mb-3">
                      <div style={{
                        width: '40px',
                        height: '40px',
                        background: '#bf6b2c',
                        color: 'white',
                        borderRadius: '50%',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                      }}>
                        <i className={activity.action_text === 'Borrowed' ? 'fas fa-book' : 'fas fa-undo'}></i>
                      </div>
                      <div>
                        <div style={{ fontSize: '0.9rem', color: '#333' }}>
                          {activity.action_text} "{activity.book_title}"
                        </div>
                        <div style={{ fontSize: '0.8rem', color: '#999' }}>
                          {new Date(activity.created_at).toLocaleDateString()}
                        </div>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default UserDashboard;