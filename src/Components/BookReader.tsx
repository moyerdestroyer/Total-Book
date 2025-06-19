import React, { useState, useEffect, useRef } from 'react';
import '../styles/BookReader.scss';
import { loadBook, BookContent } from './loadBook';
import BookContentSections from './BookContentSections';

interface BookReaderProps {
  bookId: string;
}

type Theme = 'light' | 'dark';
type FontSize = 'small' | 'medium' | 'large';

const fontSizeMap = {
  small: '16px',
  medium: '18px',
  large: '22px',
};

const TOC_PLACEHOLDER = 'https://via.placeholder.com/120x160?text=Book+Cover';

const BookReader: React.FC<BookReaderProps> = ({ bookId }) => {
  const [isLoading, setIsLoading] = useState(true);
  const [bookData, setBookData] = useState<BookContent | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [showSettings, setShowSettings] = useState(false);
  const [theme, setTheme] = useState<Theme>('light');
  const [fontSize, setFontSize] = useState<FontSize>('medium');
  const [showTOC, setShowTOC] = useState(false);
  const [currentPage, setCurrentPage] = useState(0);
  const [totalPages, setTotalPages] = useState(0);
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const fetchBook = async () => {
      try {
        setIsLoading(true);
        const data = await loadBook(bookId);
        setBookData(data);
      } catch (err) {
        setError('Failed to load book');
        console.error('Error loading book:', err);
      } finally {
        setIsLoading(false);
      }
    };
    fetchBook();
  }, [bookId]);

  // Optionally, persist settings
  useEffect(() => {
    const savedTheme = localStorage.getItem('bookReaderTheme');
    const savedFontSize = localStorage.getItem('bookReaderFontSize');
    if (savedTheme === 'light' || savedTheme === 'dark') setTheme(savedTheme);
    if (savedFontSize === 'small' || savedFontSize === 'medium' || savedFontSize === 'large') setFontSize(savedFontSize);
  }, []);
  useEffect(() => {
    localStorage.setItem('bookReaderTheme', theme);
    localStorage.setItem('bookReaderFontSize', fontSize);
  }, [theme, fontSize]);

  const handleHeaderClick = () => {
    if (containerRef.current) {
      containerRef.current.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  };

  const handlePageChange = (page: number, total: number) => {
    setCurrentPage(page);
    setTotalPages(total);
  };

  const goToNextPage = () => {
    if (currentPage < totalPages - 1) {
      setCurrentPage(currentPage + 1);
    }
  };

  const goToPreviousPage = () => {
    if (currentPage > 0) {
      setCurrentPage(currentPage - 1);
    }
  };

  if (isLoading) {
    return (
      <div className={`book-reader-container theme-${theme}`} style={{ fontSize: fontSizeMap[fontSize] }}>
        <div className="loading-spinner">
          <div className="spinner"></div>
          <p>Loading book...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className={`book-reader-container theme-${theme}`} style={{ fontSize: fontSizeMap[fontSize] }}>
        <div className="error-message">
          <h3>Error</h3>
          <p>{error}</p>
        </div>
      </div>
    );
  }

  if (!bookData) {
    return (
      <div className={`book-reader-container theme-${theme}`} style={{ fontSize: fontSizeMap[fontSize] }}>
        <div className="error-message">
          <h3>Error</h3>
          <p>No book data available</p>
        </div>
      </div>
    );
  }

  const coverUrl = bookData.image_url?.url || TOC_PLACEHOLDER;

  return (
    <div
      ref={containerRef}
      className={`book-reader-container theme-${theme}`}
      style={{ fontSize: fontSizeMap[fontSize], position: 'relative' }}
    >
      {/* TOC Button */}
      <button className={`toc-btn${showTOC ? ' open' : ''}`} onClick={() => setShowTOC(t => !t)} title="Table of Contents">☰</button>
      {/* Settings Button */}
      <button className="settings-btn" onClick={() => setShowSettings(true)} title="Settings">⚙️</button>
      {/* TOC Panel */}
      {showTOC && (
        <nav className="toc-panel-inside">
          <div className="toc-cover">
            <img src={coverUrl} alt="Book cover" />
          </div>
          <div className="toc-title">{bookData.title}</div>
          <ul className="toc-list">
            {/* Main Body */}
            {bookData.content.main_body.chapters.map((chapter) => (
              <li key={chapter.id}>
                <button>{chapter.title}</button>
              </li>
            ))}
          </ul>
        </nav>
      )}
      <div className="book-header">
        <h2
          onClick={handleHeaderClick}
          tabIndex={0}
          role="button"
          aria-label="Center reader"
        >
          {bookData.title}
        </h2>
        <div className="page-info">
          {currentPage + 1} / {totalPages}
        </div>
      </div>
      <BookContentSections 
        bookData={bookData} 
        currentPage={currentPage}
        onPageChange={handlePageChange}
      />
      {/* Navigation controls */}
      <div className="navigation">
        <button 
          className="prev" 
          onClick={goToPreviousPage} 
          disabled={currentPage === 0}
          aria-label="Previous page"
        >
          ←
        </button>
        <button 
          className="next" 
          onClick={goToNextPage} 
          disabled={currentPage === totalPages - 1}
          aria-label="Next page"
        >
          →
        </button>
        <div className="page-info current-page">
          {currentPage + 1}
        </div>
        <div className="page-info total-pages">
          {totalPages}
        </div>
      </div>
      {showSettings && (
        <div className="settings-modal" onClick={() => setShowSettings(false)}>
          <div className="settings-panel" onClick={e => e.stopPropagation()}>
            <h3>Reader Settings</h3>
            <div className="setting-group">
              <label>Theme:</label>
              <button
                className={theme === 'light' ? 'active' : ''}
                onClick={() => setTheme('light')}
              >Light</button>
              <button
                className={theme === 'dark' ? 'active' : ''}
                onClick={() => setTheme('dark')}
              >Dark</button>
            </div>
            <div className="setting-group">
              <label>Font Size:</label>
              <button
                className={fontSize === 'small' ? 'active' : ''}
                onClick={() => setFontSize('small')}
              >A-</button>
              <button
                className={fontSize === 'medium' ? 'active' : ''}
                onClick={() => setFontSize('medium')}
              >A</button>
              <button
                className={fontSize === 'large' ? 'active' : ''}
                onClick={() => setFontSize('large')}
              >A+</button>
            </div>
            <button className="close-settings" onClick={() => setShowSettings(false)}>Close</button>
          </div>
        </div>
      )}
    </div>
  );
};

export default BookReader;