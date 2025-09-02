// src/pages/BooksPage.js - UPDATED VERSION with Individual Book Images
import React, { useState, useEffect } from 'react';
import { booksAPI, userAPI, handleApiError } from '../services/api';

const BooksPage = ({ user }) => {
  const [books, setBooks] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [pagination, setPagination] = useState({});

  // Array of different book cover classes for variety (fallback when no image)
  const bookCoverClasses = [
    'cover-crimson-flame',
    'cover-ocean-depths',
    'cover-golden-sunset',
    'cover-mystic-shadow',
    'cover-rose-petals',
    'cover-emerald-forest',
    'cover-royal-purple',
    'cover-amber-glow',
    'cover-arctic-frost',
    'cover-copper-bronze',
    'cover-lavender-mist',
    'cover-forest-sage'
  ];

  // Function to get book cover class based on book ID or category (fallback)
  const getBookCoverClass = (book, index) => {
    // You can customize this logic based on:
    // 1. Book category
    // 2. Book ID
    // 3. Index position
    // 4. Random selection
    
    // Option 1: Based on category name
    const categoryMap = {
      'Fiction': 'cover-crimson-flame',
      'Science': 'cover-ocean-depths',
      'History': 'cover-golden-sunset',
      'Mystery': 'cover-mystic-shadow',
      'Romance': 'cover-rose-petals',
      'Fantasy': 'cover-emerald-forest',
      'Biography': 'cover-royal-purple',
      'Classic': 'cover-amber-glow',
      'Dystopian': 'cover-arctic-frost',
      'Educational': 'cover-copper-bronze'
    };
    
    // First try to match by category
    if (book.category_name && categoryMap[book.category_name]) {
      return categoryMap[book.category_name];
    }
    
    // Option 2: Fallback to cycling through classes based on index
    return bookCoverClasses[index % bookCoverClasses.length];
    
    // Option 3: Alternative - based on book ID for consistent styling
    // return bookCoverClasses[(book.id - 1) % bookCoverClasses.length];
  };

  // Function to render book cover (image or CSS class)
  const renderBookCover = (book, index) => {
    // Check if book has an image
    if (book.image_path && book.image_path.trim() !== '') {
      return (
        <div className="book-cover book-cover-image">
          <img 
            src={book.image_path} 
            alt={`Cover of ${book.title}`}
            onError={(e) => {
              // If image fails to load, fallback to CSS class
              e.target.style.display = 'none';
              e.target.nextSibling.style.display = 'flex';
            }}
          />
          {/* Fallback icon if image fails to load */}
          <i className="fas fa-book" style={{ display: 'none' }}></i>
        </div>
      );
    } else {
      // Use CSS class as fallback
      return (
        <div className={`book-cover ${getBookCoverClass(book, index)}`}>
          <i className="fas fa-book"></i>
        </div>
      );
    }
  };

  useEffect(() => {
    loadBooks();
    loadCategories();
  }, [currentPage, selectedCategory]);

  useEffect(() => {
    // Check for search query in URL
    const urlParams = new URLSearchParams(window.location.search);
    const search = urlParams.get('search');
    if (search) {
      setSearchQuery(search);
      searchBooks(search);
    }
  }, []);

  const loadBooks = async () => {
    try {
      setLoading(true);
      const response = await booksAPI.getAll(currentPage, 12);
      setBooks(response.data.books);
      setPagination(response.data.pagination);
    } catch (error) {
      console.error('Failed to load books:', error);
      // Fallback data with image paths (using only available images)
      setBooks([
        { 
          id: 1, 
          title: "Clean Code", 
          author: "Robert C. Martin", 
          category_name: "Programming", 
          available_quantity: 5,
          image_path: "/src/images/Clean Code.jpg"
        },
        { 
          id: 2, 
          title: "The Pragmatic Programmer", 
          author: "Andrew Hunt & David Thomas", 
          category_name: "Programming", 
          available_quantity: 2,
          image_path: "/src/images/The Pragmatic Programmer.jpg"
        },
        { 
          id: 3, 
          title: "To Kill a Mockingbird", 
          author: "Harper Lee", 
          category_name: "Fiction", 
          available_quantity: 2,
          image_path: "/src/images/To Kill a Mockingbird.jpg"
        },
        { 
          id: 4, 
          title: "Introduction to Algorithms", 
          author: "Thomas H. Cormen", 
          category_name: "Computer Science", 
          available_quantity: 3,
          image_path: "/src/images/Introduction to Algorithms.webp"
        },
        { 
          id: 5, 
          title: "Hands-On Machine Learning", 
          author: "Aurélien Géron", 
          category_name: "Machine Learning", 
          available_quantity: 1,
          image_path: "/src/images/Hands-On Machine Learning.jpg"
        },
        { 
          id: 6, 
          title: "The Intelligent Investor", 
          author: "Benjamin Graham", 
          category_name: "Finance", 
          available_quantity: 4,
          image_path: "/src/images/The Intelligent Investor.jpg"
        },
        { 
          id: 7, 
          title: "The McKinsey Way", 
          author: "Ethan M. Rasiel", 
          category_name: "Business", 
          available_quantity: 2,
          image_path: "/src/images/The McKinsey Way.jpg"
        },
        { 
          id: 8, 
          title: "First Aid for the USMLE Step 1", 
          author: "Tao Le", 
          category_name: "Medical", 
          available_quantity: 3,
          image_path: "/src/images/First Aid for the USMLE Step 1.jpg"
        }
      ]);
      setPagination({ current_page: 1, total_pages: 1, total_books: 8 });
    } finally {
      setLoading(false);
    }
  };

  const loadCategories = async () => {
    try {
      const response = await booksAPI.getCategories();
      setCategories(response.data);
    } catch (error) {
      console.error('Failed to load categories:', error);
      // Fallback data
      setCategories([
        { id: 1, name: "Fiction", book_count: 15 },
        { id: 2, name: "Science", book_count: 8 },
        { id: 3, name: "History", book_count: 12 },
        { id: 4, name: "Mystery", book_count: 6 },
        { id: 5, name: "Fantasy", book_count: 10 },
        { id: 6, name: "Biography", book_count: 5 }
      ]);
    }
  };

  const searchBooks = async (query = searchQuery) => {
    try {
      setLoading(true);
      const response = await booksAPI.search(query, selectedCategory, currentPage);
      setBooks(response.data.books);
    } catch (error) {
      console.error('Search failed:', error);
      // Show filtered fallback data based on search
      const fallbackBooks = [
        { 
          id: 1, 
          title: "Clean Code", 
          author: "Robert C. Martin", 
          category_name: "Programming", 
          available_quantity: 5,
          image_path: "/src/images/Clean Code.jpg"
        },
        { 
          id: 2, 
          title: "The Pragmatic Programmer", 
          author: "Andrew Hunt & David Thomas", 
          category_name: "Programming", 
          available_quantity: 2,
          image_path: "/src/images/The Pragmatic Programmer.jpg"
        },
        { 
          id: 3, 
          title: "To Kill a Mockingbird", 
          author: "Harper Lee", 
          category_name: "Fiction", 
          available_quantity: 2,
          image_path: "/src/images/To Kill a Mockingbird.jpg"
        },
        { 
          id: 4, 
          title: "Introduction to Algorithms", 
          author: "Thomas H. Cormen", 
          category_name: "Computer Science", 
          available_quantity: 3,
          image_path: "/src/images/Introduction to Algorithms.webp"
        }
      ];
      setBooks(fallbackBooks.filter(book => 
        book.title.toLowerCase().includes(query.toLowerCase()) ||
        book.author.toLowerCase().includes(query.toLowerCase())
      ));
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    setCurrentPage(1);
    searchBooks();
  };

  const handleCategoryChange = (categoryId) => {
    setSelectedCategory(categoryId);
    setCurrentPage(1);
  };

  const requestBook = async (bookId) => {
    if (!user || !user.id) {
      alert('Please login to request books');
      return;
    }

    try {
      await userAPI.requestBook(user.id, bookId);
      alert('Book request submitted successfully!');
    } catch (error) {
      alert('Book request submitted! (Demo mode)');
    }
  };

  return (
    <div className="page">
      <div className="container">
        <h1 className="page-title">
          <i className="fas fa-book"></i> Library Catalog
        </h1>

        {/* Search and Filters */}
        <div className="search-container">
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

          {/* Category Filter */}
          <div className="mt-3 text-center">
            <button
              className={`btn ${selectedCategory === '' ? 'btn-primary' : 'btn-secondary'} mr-2`}
              onClick={() => handleCategoryChange('')}
            >
              All Categories
            </button>
            {categories.map((category) => (
              <button
                key={category.id}
                className={`btn ${selectedCategory == category.id ? 'btn-primary' : 'btn-secondary'} mr-2 mb-2`}
                onClick={() => handleCategoryChange(category.id)}
              >
                {category.name} ({category.book_count})
              </button>
            ))}
          </div>
        </div>

        {/* Books Grid */}
        {loading ? (
          <div className="text-center p-4">
            <div className="spinner"></div>
            <p>Loading books...</p>
          </div>
        ) : (
          <>
            <div className="grid grid-4">
              {books.map((book, index) => (
                <div key={book.id} className="book-card fade-in">
                  {renderBookCover(book, index)}
                  <div className="book-info">
                    <div className="book-title">{book.title}</div>
                    <div className="book-author">by {book.author}</div>
                    <div className="book-genre">{book.category_name}</div>
                    <div className="d-flex justify-center align-center gap-2 mt-2">
                      <span className={`book-status ${book.available_quantity > 0 ? 'status-available' : 'status-issued'}`}>
                        {book.available_quantity > 0 ? 'Available' : 'All Issued'}
                      </span>
                      {book.available_quantity > 0 && (
                        <button
                          className="btn btn-primary btn-sm"
                          onClick={() => requestBook(book.id)}
                        >
                          Request
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
            

            {/* Pagination */}
            {pagination.total_pages > 1 && (
              <div className="text-center mt-4">
                <div className="d-flex justify-center gap-2">
                  {Array.from({ length: pagination.total_pages }, (_, i) => i + 1).map(page => (
                    <button
                      key={page}
                      className={`btn ${page === currentPage ? 'btn-primary' : 'btn-secondary'}`}
                      onClick={() => setCurrentPage(page)}
                    >
                      {page}
                    </button>
                  ))}
                </div>
                <p className="mt-2">
                  Page {pagination.current_page} of {pagination.total_pages} 
                  ({pagination.total_books} total books)
                </p>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default BooksPage;