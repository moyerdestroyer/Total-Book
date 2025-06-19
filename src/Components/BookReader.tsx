import React, { useState, useEffect, useRef } from 'react';
import '../styles/BookReader.scss';
import { loadBook, BookContent } from './loadBook';
import BookContentSections, { BookContentSectionsRef } from './BookContentSections';

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
  const [chapterBoundaries, setChapterBoundaries] = useState<Array<{
    id: number;
    title: string;
    startPage: number;
    endPage: number;
    pageCount: number;
  }>>([]);
  const containerRef = useRef<HTMLDivElement>(null);
  const contentSectionsRef = useRef<BookContentSectionsRef>(null);

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
    
    // Update chapter boundaries when pages change
    if (contentSectionsRef.current) {
      const boundaries = contentSectionsRef.current.getChapterBoundaries();
      setChapterBoundaries(boundaries);
    }
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

  // Helper function to get current chapter info
  const getCurrentChapter = () => {
    if (chapterBoundaries.length === 0) return null;
    
    return chapterBoundaries.find(chapter => 
      currentPage + 1 >= chapter.startPage && currentPage + 1 <= chapter.endPage
    );
  };

  // Helper function to get the last page of a specific chapter
  const getChapterLastPage = (chapterId: number) => {
    const chapter = chapterBoundaries.find(ch => ch.id === chapterId);
    return chapter ? chapter.endPage : null;
  };

  // Helper function to get the first page of a specific chapter
  const getChapterFirstPage = (chapterId: number) => {
    const chapter = chapterBoundaries.find(ch => ch.id === chapterId);
    return chapter ? chapter.startPage : null;
  };

  // Helper function to go to a specific chapter
  const goToChapter = (chapterId: number) => {
    const firstPage = getChapterFirstPage(chapterId);
    if (firstPage !== null) {
      setCurrentPage(firstPage - 1); // Convert to 0-based index
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
  const currentChapter = getCurrentChapter();

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
                <button onClick={() => goToChapter(chapter.id)}>
                  {chapter.title}
                  {(() => {
                    const chapterInfo = chapterBoundaries.find(ch => ch.id === chapter.id);
                    return chapterInfo ? ` (${chapterInfo.startPage}-${chapterInfo.endPage})` : '';
                  })()}
                </button>
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
          {currentChapter && (
            <span className="chapter-info">
              {' '}• {currentChapter.title}
            </span>
          )}
        </div>
      </div>
      <BookContentSections 
        ref={contentSectionsRef}
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