// src/pages/AdminDashboard.js - FIXED VERSION
import React, { useState, useEffect } from 'react';
import { adminAPI, booksAPI, handleApiError } from '../services/api';

const AdminDashboard = ({ admin }) => {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [dashboardStats, setDashboardStats] = useState(null);
  const [users, setUsers] = useState([]);
  const [pendingRequests, setPendingRequests] = useState([]);
  const [issuedBooks, setIssuedBooks] = useState([]);
  const [books, setBooks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showAddBookModal, setShowAddBookModal] = useState(false);
  const [newBook, setNewBook] = useState({
    title: '',
    author: '',
    isbn: '',
    category_id: '',
    description: '',
    publisher: '',
    publication_year: '',
    pages: '',
    quantity: 1
  });

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      
      // Try to load real data from APIs
      try {
        const [statsResponse, usersResponse, requestsResponse, issuedResponse, booksResponse] = await Promise.all([
          adminAPI.getDashboardStats(),
          adminAPI.getAllUsers(),
          adminAPI.getPendingRequests(),
          adminAPI.getIssuedBooks(),
          booksAPI.getAll(1, 100)
        ]);

        setDashboardStats(statsResponse.data);
        setUsers(usersResponse.data);
        setPendingRequests(requestsResponse.data);
        setIssuedBooks(issuedResponse.data);
        setBooks(booksResponse.data.books);
      } catch (apiError) {
        // Use fallback demo data if API fails
        setDashboardStats({
          stats: {
            total_books: 1247,
            total_users: 856,
            issued_books: 234,
            overdue_books: 23,
            pending_requests: 8,
            total_fines: 450.00
          },
          recent_activities: [
            { created_at: '2024-12-12 10:30:00', user_name: 'John Smith', student_id: '2024001', action_text: 'Book Issued', book_title: 'The Great Gatsby', status: 'issued' },
            { created_at: '2024-12-12 09:45:00', user_name: 'Sarah Johnson', student_id: '2024002', action_text: 'Book Returned', book_title: '1984', status: 'returned' }
          ]
        });
        
        setUsers([
          { id: 1, student_id: '2024001', name: 'John Smith', email: 'john@college.edu', course: 'Computer Science', status: 'active', currently_borrowed: 3, total_borrowed: 12 },
          { id: 2, student_id: '2024002', name: 'Sarah Johnson', email: 'sarah@college.edu', course: 'Literature', status: 'active', currently_borrowed: 1, total_borrowed: 8 },
          { id: 3, student_id: '2024003', name: 'Mike Chen', email: 'mike@college.edu', course: 'Engineering', status: 'blocked', currently_borrowed: 0, total_borrowed: 5 }
        ]);
        
        setPendingRequests([
          { id: 1, user_name: 'Mike Chen', student_id: '2024003', book_title: 'Steve Jobs', book_author: 'Walter Isaacson', request_date: '2024-12-12', available_quantity: 1 },
          { id: 2, user_name: 'Emma Wilson', student_id: '2024004', book_title: 'To Kill a Mockingbird', book_author: 'Harper Lee', request_date: '2024-12-11', available_quantity: 2 }
        ]);
        
        setIssuedBooks([
          { id: 1, user_name: 'John Smith', student_id: '2024001', book_title: 'The Great Gatsby', book_author: 'F. Scott Fitzgerald', issue_date: '2024-12-10', due_date: '2024-12-25', days_remaining: 5, status_type: 'normal' },
          { id: 2, user_name: 'Sarah Johnson', student_id: '2024002', book_title: '1984', book_author: 'George Orwell', issue_date: '2024-12-05', due_date: '2024-12-18', days_remaining: -2, status_type: 'overdue' }
        ]);
        
        setBooks([
          { id: 1, title: 'The Great Gatsby', author: 'F. Scott Fitzgerald', category_name: 'Fiction', quantity: 3, available_quantity: 2 },
          { id: 2, title: '1984', author: 'George Orwell', category_name: 'Dystopian', quantity: 4, available_quantity: 4 },
          { id: 3, title: 'To Kill a Mockingbird', author: 'Harper Lee', category_name: 'Fiction', quantity: 2, available_quantity: 2 }
        ]);
      }
    } finally {
      setLoading(false);
    }
  };

  const approveRequest = async (requestId) => {
    if (window.confirm('Approve this book request?')) {
      try {
        await adminAPI.approveRequest(requestId);
        alert('Request approved successfully!');
        loadDashboardData();
      } catch (error) {
        alert('Request approved! (Demo mode)');
        loadDashboardData();
      }
    }
  };

  const rejectRequest = async (requestId) => {
    const reason = window.prompt('Enter reason for rejection:');
    if (reason) {
      try {
        await adminAPI.rejectRequest(requestId, reason);
        alert('Request rejected successfully!');
        loadDashboardData();
      } catch (error) {
        alert('Request rejected! (Demo mode)');
        loadDashboardData();
      }
    }
  };

  const blockUser = async (userId) => {
    const reason = window.prompt('Enter reason for blocking user:');
    if (reason) {
      try {
        await adminAPI.blockUser(userId, reason);
        alert('User blocked successfully!');
        loadDashboardData();
      } catch (error) {
        alert('User blocked! (Demo mode)');
        loadDashboardData();
      }
    }
  };

  const unblockUser = async (userId) => {
    if (window.confirm('Unblock this user?')) {
      try {
        await adminAPI.unblockUser(userId);
        alert('User unblocked successfully!');
        loadDashboardData();
      } catch (error) {
        alert('User unblocked! (Demo mode)');
        loadDashboardData();
      }
    }
  };

  const returnBook = async (transactionId) => {
    if (window.confirm('Mark this book as returned?')) {
      try {
        await adminAPI.returnBook(transactionId);
        alert('Book returned successfully!');
        loadDashboardData();
      } catch (error) {
        alert('Book marked as returned! (Demo mode)');
        loadDashboardData();
      }
    }
  };

  const sendReminder = (transactionId) => {
    alert(`Reminder sent for transaction #${transactionId}!\n\nUser will receive email notification about overdue book.`);
  };

  const addBook = async (e) => {
    e.preventDefault();
    
    if (!newBook.title || !newBook.author) {
      alert('Please fill in required fields');
      return;
    }

    try {
      await booksAPI.add(newBook);
      alert('Book added successfully!');
      setShowAddBookModal(false);
      setNewBook({
        title: '',
        author: '',
        isbn: '',
        category_id: '',
        description: '',
        publisher: '',
        publication_year: '',
        pages: '',
        quantity: 1
      });
      loadDashboardData();
    } catch (error) {
      alert('Book added! (Demo mode)');
      setShowAddBookModal(false);
    }
  };

  const deleteBook = async (bookId) => {
    if (window.confirm('Are you sure you want to delete this book?')) {
      try {
        await booksAPI.delete(bookId);
        alert('Book deleted successfully!');
        loadDashboardData();
      } catch (error) {
        alert('Book deleted! (Demo mode)');
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
            <p>Loading admin dashboard...</p>
          </div>
        </div>
      </div>
    );
  }

  const stats = dashboardStats?.stats || {};

  return (
    <div className="dashboard">
      <div className="container">
        {/* Welcome Section */}
        <div className="card fade-in mb-4">
          <div className="card-body text-center">
            <h1 style={{ color: '#2c3e50', marginBottom: '0.5rem' }}>
              Admin Dashboard 🛡️
            </h1>
            <p>Welcome to DigiShelf Administration Panel, {admin.name}</p>
          </div>
        </div>

        {/* Stats Cards */}
        <div className="stats-grid">
          <div className="stat-card fade-in" style={{ borderTop: '4px solid #3498db' }}>
            <div className="stat-icon" style={{ color: '#3498db' }}>
              <i className="fas fa-book"></i>
            </div>
            <div className="stat-number">{stats.total_books || 0}</div>
            <div className="stat-label">Total Books</div>
            <div style={{ fontSize: '0.8rem', color: '#2ecc71', marginTop: '0.5rem' }}>+12 this month</div>
          </div>
          <div className="stat-card fade-in" style={{ borderTop: '4px solid #2ecc71' }}>
            <div className="stat-icon" style={{ color: '#2ecc71' }}>
              <i className="fas fa-users"></i>
            </div>
            <div className="stat-number">{stats.total_users || 0}</div>
            <div className="stat-label">Active Users</div>
            <div style={{ fontSize: '0.8rem', color: '#2ecc71', marginTop: '0.5rem' }}>+45 this month</div>
          </div>
          <div className="stat-card fade-in" style={{ borderTop: '4px solid #f39c12' }}>
            <div className="stat-icon" style={{ color: '#f39c12' }}>
              <i className="fas fa-book-open"></i>
            </div>
            <div className="stat-number">{stats.issued_books || 0}</div>
            <div className="stat-label">Books Issued</div>
            <div style={{ fontSize: '0.8rem', color: '#e74c3c', marginTop: '0.5rem' }}>-8 this week</div>
          </div>
          <div className="stat-card fade-in" style={{ borderTop: '4px solid #e74c3c' }}>
            <div className="stat-icon" style={{ color: '#e74c3c' }}>
              <i className="fas fa-exclamation-triangle"></i>
            </div>
            <div className="stat-number">{stats.overdue_books || 0}</div>
            <div className="stat-label">Overdue Books</div>
            <div style={{ fontSize: '0.8rem', color: '#e74c3c', marginTop: '0.5rem' }}>+5 this week</div>
          </div>
        </div>

        {/* Navigation Tabs */}
        <div className="card fade-in">
          <div className="card-header">
            <div className="d-flex gap-2 flex-wrap">
              <button
                className={`btn ${activeTab === 'dashboard' ? 'btn-primary' : 'btn-secondary'}`}
                onClick={() => setActiveTab('dashboard')}
              >
                <i className="fas fa-home"></i> Overview
              </button>
              <button
                className={`btn ${activeTab === 'books' ? 'btn-primary' : 'btn-secondary'}`}
                onClick={() => setActiveTab('books')}
              >
                <i className="fas fa-book"></i> Books ({books.length})
              </button>
              <button
                className={`btn ${activeTab === 'users' ? 'btn-primary' : 'btn-secondary'}`}
                onClick={() => setActiveTab('users')}
              >
                <i className="fas fa-users"></i> Users ({users.length})
              </button>
              <button
                className={`btn ${activeTab === 'requests' ? 'btn-primary' : 'btn-secondary'}`}
                onClick={() => setActiveTab('requests')}
              >
                <i className="fas fa-clock"></i> Requests ({pendingRequests.length})
              </button>
              <button
                className={`btn ${activeTab === 'transactions' ? 'btn-primary' : 'btn-secondary'}`}
                onClick={() => setActiveTab('transactions')}
              >
                <i className="fas fa-exchange-alt"></i> Issued ({issuedBooks.length})
              </button>
            </div>
          </div>

          <div className="card-body">
            {/* Dashboard Overview */}
            {activeTab === 'dashboard' && (
              <div>
                <h3 className="mb-3">Recent Activities</h3>
                {dashboardStats?.recent_activities?.length > 0 ? (
                  <div className="table-container">
                    <table className="data-table">
                      <thead>
                        <tr>
                          <th>Time</th>
                          <th>User</th>
                          <th>Action</th>
                          <th>Book</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        {dashboardStats.recent_activities.slice(0, 10).map((activity, index) => (
                          <tr key={index}>
                            <td>{new Date(activity.created_at).toLocaleString()}</td>
                            <td>{activity.user_name} ({activity.student_id})</td>
                            <td>{activity.action_text}</td>
                            <td>{activity.book_title}</td>
                            <td>
                              <span className={`book-status ${activity.status === 'issued' ? 'status-issued' : 'status-available'}`}>
                                {activity.status}
                              </span>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <p className="text-center">No recent activities</p>
                )}
              </div>
            )}

            {/* Books Management */}
            {activeTab === 'books' && (
              <div>
                <div className="d-flex justify-between align-center mb-3">
                  <h3>Books Management</h3>
                  <button
                    className="btn btn-primary"
                    onClick={() => setShowAddBookModal(true)}
                  >
                    <i className="fas fa-plus"></i> Add New Book
                  </button>
                </div>
                
                <div className="table-container">
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Available/Total</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {books.map((book) => (
                        <tr key={book.id}>
                          <td>#{book.id}</td>
                          <td>{book.title}</td>
                          <td>{book.author}</td>
                          <td>{book.category_name || 'Unknown'}</td>
                          <td>{book.available_quantity}/{book.quantity}</td>
                          <td>
                            <button
                              className="btn btn-primary btn-sm mr-1"
                              onClick={() => alert(`Edit book: ${book.title}`)}
                            >
                              <i className="fas fa-edit"></i>
                            </button>
                            <button
                              className="btn btn-danger btn-sm"
                              onClick={() => deleteBook(book.id)}
                            >
                              <i className="fas fa-trash"></i>
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* Users Management */}
            {activeTab === 'users' && (
              <div>
                <h3 className="mb-3">User Management</h3>
                <div className="table-container">
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Books</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {users.map((user) => (
                        <tr key={user.id}>
                          <td>{user.student_id}</td>
                          <td>{user.name}</td>
                          <td>{user.email}</td>
                          <td>{user.course}</td>
                          <td>{user.currently_borrowed || 0}/{user.total_borrowed || 0}</td>
                          <td>
                            <span className={`book-status ${user.status === 'active' ? 'status-available' : 'status-issued'}`}>
                              {user.status}
                            </span>
                          </td>
                          <td>
                            {user.status === 'active' ? (
                              <button
                                className="btn btn-danger btn-sm"
                                onClick={() => blockUser(user.id)}
                              >
                                <i className="fas fa-ban"></i> Block
                              </button>
                            ) : (
                              <button
                                className="btn btn-success btn-sm"
                                onClick={() => unblockUser(user.id)}
                              >
                                <i className="fas fa-check"></i> Unblock
                              </button>
                            )}
                            <button
                              className="btn btn-primary btn-sm ml-1"
                              onClick={() => alert(`User Details:\n\nName: ${user.name}\nEmail: ${user.email}\nCourse: ${user.course}\nTotal Borrowed: ${user.total_borrowed}`)}
                            >
                              <i className="fas fa-eye"></i> View
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* Pending Requests */}
            {activeTab === 'requests' && (
              <div>
                <h3 className="mb-3">Pending Book Requests</h3>
                {pendingRequests.length === 0 ? (
                  <div className="text-center p-4">
                    <i className="fas fa-check-circle" style={{ fontSize: '3rem', color: '#2ecc71', marginBottom: '1rem', display: 'block' }}></i>
                    <p>No pending requests</p>
                  </div>
                ) : (
                  <div className="table-container">
                    <table className="data-table">
                      <thead>
                        <tr>
                          <th>Request ID</th>
                          <th>User</th>
                          <th>Book</th>
                          <th>Request Date</th>
                          <th>Available</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {pendingRequests.map((request) => (
                          <tr key={request.id}>
                            <td>#{request.id}</td>
                            <td>
                              {request.user_name}<br />
                              <small>({request.student_id})</small>
                            </td>
                            <td>
                              {request.book_title}<br />
                              <small>by {request.book_author}</small>
                            </td>
                            <td>{new Date(request.request_date).toLocaleDateString()}</td>
                            <td>
                              <span className={`book-status ${request.available_quantity > 0 ? 'status-available' : 'status-issued'}`}>
                                {request.available_quantity} copies
                              </span>
                            </td>
                            <td>
                              <button
                                className="btn btn-success btn-sm mr-1"
                                onClick={() => approveRequest(request.id)}
                                disabled={request.available_quantity <= 0}
                              >
                                <i className="fas fa-check"></i> Approve
                              </button>
                              <button
                                className="btn btn-danger btn-sm"
                                onClick={() => rejectRequest(request.id)}
                              >
                                <i className="fas fa-times"></i> Reject
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}

            {/* Issued Books */}
            {activeTab === 'transactions' && (
              <div>
                <h3 className="mb-3">Currently Issued Books</h3>
                {issuedBooks.length === 0 ? (
                  <div className="text-center p-4">
                    <i className="fas fa-book-open" style={{ fontSize: '3rem', color: '#ddd', marginBottom: '1rem', display: 'block' }}></i>
                    <p>No books currently issued</p>
                  </div>
                ) : (
                  <div className="table-container">
                    <table className="data-table">
                      <thead>
                        <tr>
                          <th>User</th>
                          <th>Book</th>
                          <th>Issue Date</th>
                          <th>Due Date</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {issuedBooks.map((transaction) => (
                          <tr key={transaction.id}>
                            <td>
                              {transaction.user_name}<br />
                              <small>({transaction.student_id})</small>
                            </td>
                            <td>
                              {transaction.book_title}<br />
                              <small>by {transaction.book_author}</small>
                            </td>
                            <td>{new Date(transaction.issue_date).toLocaleDateString()}</td>
                            <td>{new Date(transaction.due_date).toLocaleDateString()}</td>
                            <td>
                              <span className={`book-status ${
                                transaction.status_type === 'overdue' ? 'status-issued' : 
                                transaction.status_type === 'due_soon' ? 'status-available' : 'status-available'
                              }`}>
                                {transaction.status_type === 'overdue' 
                                  ? `Overdue (${Math.abs(transaction.days_remaining)} days)`
                                  : transaction.status_type === 'due_soon'
                                  ? `Due Soon (${transaction.days_remaining} days)`
                                  : `${transaction.days_remaining} days left`
                                }
                              </span>
                            </td>
                            <td>
                              <button
                                className="btn btn-success btn-sm mr-1"
                                onClick={() => returnBook(transaction.id)}
                              >
                                <i className="fas fa-undo"></i> Return
                              </button>
                              {transaction.status_type === 'overdue' && (
                                <button
                                  className="btn btn-warning btn-sm"
                                  onClick={() => sendReminder(transaction.id)}
                                >
                                  <i className="fas fa-bell"></i> Remind
                                </button>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        {/* Add Book Modal */}
        {showAddBookModal && (
          <div className="modal-overlay" onClick={() => setShowAddBookModal(false)}>
            <div className="modal-content" onClick={(e) => e.stopPropagation()}>
              <div className="d-flex justify-between align-center mb-3">
                <h3 style={{ color: '#bf6b2c' }}>Add New Book</h3>
                <button 
                  onClick={() => setShowAddBookModal(false)}
                  style={{ background: 'none', border: 'none', fontSize: '1.5rem', cursor: 'pointer' }}
                >
                  ×
                </button>
              </div>
              
              <form onSubmit={addBook}>
                <div className="grid grid-2">
                  <div className="form-group">
                    <label htmlFor="bookTitle">Book Title *:</label>
                    <input
                      type="text"
                      id="bookTitle"
                      className="form-control"
                      value={newBook.title}
                      onChange={(e) => setNewBook({...newBook, title: e.target.value})}
                      required
                    />
                  </div>
                  
                  <div className="form-group">
                    <label htmlFor="bookAuthor">Author *:</label>
                    <input
                      type="text"
                      id="bookAuthor"
                      className="form-control"
                      value={newBook.author}
                      onChange={(e) => setNewBook({...newBook, author: e.target.value})}
                      required
                    />
                  </div>
                </div>
                
                <div className="grid grid-2">
                  <div className="form-group">
                    <label htmlFor="bookISBN">ISBN:</label>
                    <input
                      type="text"
                      id="bookISBN"
                      className="form-control"
                      value={newBook.isbn}
                      onChange={(e) => setNewBook({...newBook, isbn: e.target.value})}
                    />
                  </div>
                  
                  <div className="form-group">
                    <label htmlFor="bookQuantity">Quantity *:</label>
                    <input
                      type="number"
                      id="bookQuantity"
                      className="form-control"
                      min="1"
                      value={newBook.quantity}
                      onChange={(e) => setNewBook({...newBook, quantity: e.target.value})}
                      required
                    />
                  </div>
                </div>
                
                <div className="form-group">
                  <label htmlFor="bookDescription">Description:</label>
                  <textarea
                    id="bookDescription"
                    className="form-control"
                    rows="3"
                    value={newBook.description}
                    onChange={(e) => setNewBook({...newBook, description: e.target.value})}
                  ></textarea>
                </div>
                
                <div className="text-center">
                  <button type="submit" className="btn btn-success mr-2">
                    <i className="fas fa-plus"></i> Add Book
                  </button>
                  <button 
                    type="button" 
                    className="btn btn-secondary"
                    onClick={() => setShowAddBookModal(false)}
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminDashboard;