import React from 'react';

const ReviewPage = () => {
  const reviews = [
    {
      id: 2,
      name: "Dhruval Kakadiya",
      role: "Professor",
      rating: 5,
      text: "As a faculty member, I love how easy it is to recommend books to students and track their reading progress."
    },
    {
      id: 3,
      name: "Rahul Mehta",
      role: "Student",
      rating: 4,
      text: "The mobile app is fantastic! I can read my favorite books anywhere and the offline feature is a game-changer."
    },
    {
      id: 4,
      name: "Dharmik Sharma",
      role: "Librarian",
      rating: 5,
      text: "The admin panel makes managing our library collection so much easier. Great analytics and reporting features!"
    },
    {
      id: 5,
      name: "Smeet Patel",
      role: "Student",
      rating: 4,
      text: "Love the recommendation system! It suggested some amazing books that I never would have found otherwise."
    },
    {
      id: 6,
      name: "Dr. Pooja nadoda",
      role: "Professor",
      rating: 5,
      text: "Excellent platform for academic research. The search filters and citation tools are incredibly helpful."
    }
  ];

  const renderStars = (rating) => {
    return Array.from({ length: 5 }, (_, i) => (
      <i
        key={i}
        className={`fas fa-star`}
        style={{ color: i < rating ? '#ffc107' : '#ddd' }}
      ></i>
    ));
  };

  return (
    <div className="page">
      <div className="container">
        <h1 className="page-title">What Our Users Say</h1>
        
        <div className="grid grid-2">
          {reviews.map((review) => (
            <div key={review.id} className="card fade-in">
              <div className="card-body">
                <div className="d-flex align-center gap-3 mb-3">
                  <div className="user-avatar" style={{ background: '#bf6b2c', color: 'white' }}>
                    {review.name.split(' ').map(n => n[0]).join('')}
                  </div>
                  <div>
                    <h4 style={{ margin: 0, color: '#333' }}>{review.name}</h4>
                    <p style={{ margin: 0, color: '#666', fontSize: '0.9rem' }}>{review.role}</p>
                  </div>
                </div>
                
                <div className="mb-2">
                  {renderStars(review.rating)}
                </div>
                
                <p style={{ fontStyle: 'italic', color: '#555' }}>
                  "{review.text}"
                </p>
              </div>
            </div>
          ))}
        </div>

        {/* Add Review Section */}
        <div className="card mt-4 fade-in" style={{ maxWidth: '600px', margin: '2rem auto 0' }}>
          <div className="card-header text-center">
            <i className="fas fa-edit"></i> Share Your Experience
          </div>
          <div className="card-body">
            <p className="text-center mb-3">
              Have you used DigiShelf? We'd love to hear about your experience!
            </p>
            <div className="text-center">
              <button className="btn btn-primary">
                <i className="fas fa-star"></i> Write a Review
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ReviewPage;