// src/services/api.js - FIXED VERSION
import axios from 'axios';
// Get API base URL from environment or use default
const API_BASE = process.env.REACT_APP_API_URL || 'http://localhost/digishelf/backend';
// Create axios instance with default config
const apiClient = axios.create({
  baseURL: API_BASE,
  timeout: 10000, // 10 second timeout
  headers: {
    'Content-Type': 'application/json',
  }
});
// Request interceptor to add auth token
apiClient.interceptors.request.use(
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
// Response interceptor for error handling
apiClient.interceptors.response.use(
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
// Authentication APIs
export const authAPI = {
  login: async (credentials) => {
    try {
      const response = await apiClient.post('/auth.php?action=login', credentials);
      return response;
    } catch (error) {
      // For demo purposes, provide mock response if API is down
      if (error.code === 'ECONNREFUSED' || error.code === 'NETWORK_ERROR') {
        console.warn('API not available, using mock data');
        return {
          data: {
            user: {
              id: 1,
              name: 'Demo User',
              email: credentials.email,
              student_id: '2024001',
              course: 'Computer Science'
            },
            token: 'demo_token_' + Date.now()
          }
        };
      }
      throw error;
    }
  },
  register: async (userData) => {
    try {
      const response = await apiClient.post('/auth.php?action=register', userData);
      return response;
    } catch (error) {
      if (error.code === 'ECONNREFUSED' || error.code === 'NETWORK_ERROR') {
        console.warn('API not available, using mock response');
        return {
          data: {
            success: true,
            message: 'Registration successful! (Demo Mode)'
          }
        };
      }
      throw error;
    }
  },
  adminLogin: async (credentials) => {
    try {
      const response = await apiClient.post('/auth.php?action=admin-login', credentials);
      return response;
    } catch (error) {
      if (error.code === 'ECONNREFUSED' || error.code === 'NETWORK_ERROR') {
        console.warn('API not available, using mock admin data');
        if (credentials.username === 'admin' && credentials.password === 'password') {
          return {
            data: {
              admin: {
                id: 1,
                name: 'System Administrator',
                username: 'admin',
                role: 'admin'
              },
              token: 'admin_demo_token_' + Date.now()
            }
          };
        } else {
          throw new Error('Invalid credentials');
        }
      }
      throw error;
    }
  },
  logout: () => apiClient.post('/auth.php?action=logout'),
  verifyToken: (token) => apiClient.get(`/auth.php?action=verify-token&token=${token}`)
};
// Books APIs
export const booksAPI = {
  getAll: async (page = 1, limit = 12) => {
    try {
      const response = await apiClient.get(`/books.php?action=all&page=${page}&limit=${limit}`);
      return response;
    } catch (error) {
      if (error.code === 'ECONNREFUSED' || error.code === 'NETWORK_ERROR') {
        console.warn('API not available, using mock books data');
        return {
          data: {
            books: [
              { id: 1, title: "The Great Gatsby", author: "F. Scott Fitzgerald", category_name: "Fiction", available_quantity: 3, quantity: 5 },
              { id: 2, title: "1984", author: "George Orwell", category_name: "Dystopian", available_quantity: 4, quantity: 6 },
              { id: 3, title: "To Kill a Mockingbird", author: "Harper Lee", category_name: "Fiction", available_quantity: 2, quantity: 4 },
              { id: 4, title: "A Brief History of Time", author: "Stephen Hawking", category_name: "Science", available_quantity: 2, quantity: 3 },
              { id: 5, title: "The Catcher in the Rye", author: "J.D. Salinger", category_name: "Fiction", available_quantity: 1, quantity: 3 },
              { id: 6, title: "Pride and Prejudice", author: "Jane Austen", category_name: "Romance", available_quantity: 3, quantity: 4 }
            ],
            pagination: {
              current_page: page,
              total_pages: 2,
              total_books: 12,
              per_page: limit
            }
          }
        };
      }
      throw error;
    }
  },
  search: async (query, category = '', page = 1) => {
    try {
      const response = await apiClient.get(`/books.php?action=search&q=${encodeURIComponent(query)}&category=${category}&page=${page}`);
      return response;
    } catch (error) {
      console.warn('Search API not available, using filtered mock data');
      const allBooks = [
        { id: 1, title: "The Great Gatsby", author: "F. Scott Fitzgerald", category_name: "Fiction", available_quantity: 3 },
        { id: 2, title: "1984", author: "George Orwell", category_name: "Dystopian", available_quantity: 4 },
        { id: 3, title: "To Kill a Mockingbird", author: "Harper Lee", category_name: "Fiction", available_quantity: 2 },
        { id: 4, title: "A Brief History of Time", author: "Stephen Hawking", category_name: "Science", available_quantity: 2 }
      ];  
      const filteredBooks = allBooks.filter(book => 
        book.title.toLowerCase().includes(query.toLowerCase()) ||
        book.author.toLowerCase().includes(query.toLowerCase()) ||
        book.category_name.toLowerCase().includes(query.toLowerCase())
      );
      return {
        data: {
          books: filteredBooks,
          pagination: { current_page: 1, total_pages: 1, total_books: filteredBooks.length }
        }
      };
    }
  },
  getById: (id) => apiClient.get(`/books.php?action=single&id=${id}`),
  getCategories: async () => {
    try {
      const response = await apiClient.get('/books.php?action=categories');
      return response;
    } catch (error) {
      console.warn('Categories API not available, using mock data');
      return {
        data: [
          { id: 1, name: "Fiction", book_count: 15 },
          { id: 2, name: "Science", book_count: 8 },
          { id: 3, name: "History", book_count: 12 },
          { id: 4, name: "Romance", book_count: 6 },
          { id: 5, name: "Dystopian", book_count: 4 }
        ]
      };
    }
  },
  getStats: () => apiClient.get('/books.php?action=stats'),
  add: (bookData) => apiClient.post('/books.php', bookData),
  update: (bookData) => apiClient.put('/books.php', bookData),
  delete: (id) => apiClient.delete(`/books.php?id=${id}`)
};
// User APIs
export const userAPI = {
  getDashboard: async (userId) => {
    try {
      const response = await apiClient.get(`/user.php?action=dashboard&user_id=${userId}`);
      return response;
    } catch (error) {
      console.warn('User dashboard API not available, using mock data');
      return {
        data: {
          stats: {
            total_borrowed: 12,
            currently_borrowed: 3,
            overdue_books: 1,
            total_fines: 15.50
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
            },
            {
              id: 3,
              title: "To Kill a Mockingbird",
              author: "Harper Lee",
              due_date: "2024-12-30",
              days_remaining: 10,
              status_type: "normal"
            }
          ],
          recent_activity: [
            {
              action_text: "Borrowed",
              book_title: "The Great Gatsby",
              created_at: "2024-12-10T10:30:00Z"
            },
            {
              action_text: "Returned",
              book_title: "Pride and Prejudice",
              created_at: "2024-12-08T14:20:00Z"
            },
            {
              action_text: "Borrowed",
              book_title: "1984",
              created_at: "2024-12-05T09:15:00Z"
            }
          ]
        }
      };
    }
  },
  getBorrowedBooks: (userId) => apiClient.get(`/user.php?action=borrowed-books&user_id=${userId}`),
  getHistory: (userId) => apiClient.get(`/user.php?action=history&user_id=${userId}`),
  getProfile: (userId) => apiClient.get(`/user.php?action=profile&user_id=${userId}`),
  requestBook: (userId, bookId) => apiClient.post('/user.php?action=request-book', { user_id: userId, book_id: bookId }),
  returnBook: (userId, bookId) => apiClient.post('/user.php?action=return-book', { user_id: userId, book_id: bookId }),
  reviewBook: (reviewData) => apiClient.post('/user.php?action=review-book', reviewData),
  updateProfile: (profileData) => apiClient.post('/user.php?action=update-profile', profileData)
};
// Admin APIs
export const adminAPI = {
  getDashboardStats: async () => {
    try {
      const response = await apiClient.get('/admin.php?action=dashboard-stats');
      return response;
    } catch (error) {
      console.warn('Admin stats API not available, using mock data');
      return {
        data: {
          stats: {
            total_books: 1247,
            total_users: 856,
            issued_books: 234,
            overdue_books: 23,
            pending_requests: 8,
            total_fines: 1250.75
          },
          recent_activities: [
            {
              created_at: '2024-12-12T10:30:00Z',
              user_name: 'John Smith',
              student_id: '2024001',
              action_text: 'Book Issued',
              book_title: 'The Great Gatsby',
              status: 'issued'
            },
            {
              created_at: '2024-12-12T09:45:00Z',
              user_name: 'Sarah Johnson',
              student_id: '2024002',
              action_text: 'Book Returned',
              book_title: '1984',
              status: 'returned'
            },
            {
              created_at: '2024-12-11T16:20:00Z',
              user_name: 'Mike Chen',
              student_id: '2024003',
              action_text: 'Book Requested',
              book_title: 'To Kill a Mockingbird',
              status: 'pending'
            }
          ]
        }
      };
    }
  },
  getAllUsers: async () => {
    try {
      const response = await apiClient.get('/admin.php?action=all-users');
      return response;
    } catch (error) {
      console.warn('Users API not available, using mock data');
      return {
        data: [
          {
            id: 1,
            student_id: '2024001',
            name: 'John Smith',
            email: 'john@college.edu',
            course: 'Computer Science',
            year: '3rd Year',
            status: 'active',
            currently_borrowed: 3,
            total_borrowed: 12,
            total_fines: 5.00
          },
          {
            id: 2,
            student_id: '2024002',
            name: 'Sarah Johnson',
            email: 'sarah@college.edu',
            course: 'Literature',
            year: '2nd Year',
            status: 'active',
            currently_borrowed: 1,
            total_borrowed: 8,
            total_fines: 0.00
          },
          {
            id: 3,
            student_id: '2024003',
            name: 'Mike Chen',
            email: 'mike@college.edu',
            course: 'Engineering',
            year: '4th Year',
            status: 'blocked',
            currently_borrowed: 0,
            total_borrowed: 5,
            total_fines: 25.00
          }
        ]
      };
    }
  },
  getPendingRequests: async () => {
    try {
      const response = await apiClient.get('/admin.php?action=pending-requests');
      return response;
    } catch (error) {
      console.warn('Pending requests API not available, using mock data');
      return {
        data: [
          {
            id: 1,
            user_name: 'Mike Chen',
            student_id: '2024003',
            book_title: 'Steve Jobs',
            book_author: 'Walter Isaacson',
            request_date: '2024-12-12',
            available_quantity: 1
          },
          {
            id: 2,
            user_name: 'Emma Wilson',
            student_id: '2024004',
            book_title: 'To Kill a Mockingbird',
            book_author: 'Harper Lee',
            request_date: '2024-12-11',
            available_quantity: 2
          }
        ]
      };
    }
  },
  getIssuedBooks: async () => {
    try {
      const response = await apiClient.get('/admin.php?action=issued-books');
      return response;
    } catch (error) {
      console.warn('Issued books API not available, using mock data');
      return {
        data: [
          {
            id: 1,
            user_name: 'John Smith',
            student_id: '2024001',
            book_title: 'The Great Gatsby',
            book_author: 'F. Scott Fitzgerald',
            issue_date: '2024-12-10',
            due_date: '2024-12-25',
            days_remaining: 5,
            status_type: 'normal'
          },
          {
            id: 2,
            user_name: 'Sarah Johnson',
            student_id: '2024002',
            book_title: '1984',
            book_author: 'George Orwell',
            issue_date: '2024-12-05',
            due_date: '2024-12-18',
            days_remaining: -2,
            status_type: 'overdue'
          }
        ]
      };
    }
  },
  getOverdueBooks: () => apiClient.get('/admin.php?action=overdue-books'),
  approveRequest: (requestId) => apiClient.post('/admin.php?action=approve-request', { request_id: requestId }),
  rejectRequest: (requestId, reason) => apiClient.post('/admin.php?action=reject-request', { request_id: requestId, reason }),
  issueBook: (userId, bookId) => apiClient.post('/admin.php?action=issue-book', { user_id: userId, book_id: bookId }),
  returnBook: (transactionId) => apiClient.post('/admin.php?action=return-book', { transaction_id: transactionId }),
  blockUser: (userId, reason) => apiClient.post('/admin.php?action=block-user', { user_id: userId, reason }),
  unblockUser: (userId) => apiClient.post('/admin.php?action=unblock-user', { user_id: userId })
};
// Enhanced error handler
export const handleApiError = (error) => {
  console.error('API Error:', error);
  if (error.response) {
    // Server responded with error status
    const status = error.response.status;
    const message = error.response.data?.error || error.response.data?.message || 'Server error occurred';
    switch (status) {
      case 400:
        return `Invalid request: ${message}`;
      case 401:
        return 'Authentication required. Please login again.';
      case 403:
        return 'Access denied. You do not have permission for this action.';
      case 404:
        return 'Resource not found.';
      case 500:
        return 'Server error. Please try again later.';
      default:
        return message;
    }
  } else if (error.request) {
    // Network error
    return 'Unable to connect to server. Please check your internet connection and try again.';
  } else if (error.code === 'ECONNREFUSED') {
    return 'Backend server is not running. Using demo mode.';
  } else {
    // Something else happened
    return error.message || 'An unexpected error occurred. Please try again.';
  }
};
// Network status checker
export const checkNetworkStatus = async () => {
  try {
    await apiClient.get('/health-check.php', { timeout: 3000 });
    return true;
  } catch (error) {
    return false;
  }
};
export default apiClient;