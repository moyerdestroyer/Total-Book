import React from 'react';
import { createRoot } from 'react-dom/client';
import BookReader from './components/BookReader';

// Auto-initialize function
function initializeBookReaders() {
  const containers = document.querySelectorAll('#book-reader');
  
  containers.forEach((container: HTMLElement) => {
    // Prevent double initialization
    if (container.dataset.initialized === 'true') return;
    
    const bookId = container.getAttribute('data-book-id');
    if (!bookId) {
      console.warn('BookReader: No book-id provided');
      return;
    }
    
    // Add loading state
    container.innerHTML = '<div class="book-reader-loading">Loading book reader...</div>';
    
    try {
      const root = createRoot(container);
      root.render(<BookReader bookId={bookId} />);
      
      // Mark as initialized
      container.dataset.initialized = 'true';
    } catch (error) {
      console.error('BookReader initialization failed:', error);
      container.innerHTML = '<div class="book-reader-error">Failed to load book reader</div>';
    }
  });
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeBookReaders);
} else {
  initializeBookReaders();
}

// Watch for dynamically added elements
const observer = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    mutation.addedNodes.forEach((node) => {
      if (node.nodeType === Node.ELEMENT_NODE) {
        const element = node as Element;
        if (element.id === 'book-reader' || element.querySelector('#book-reader')) {
          initializeBookReaders();
        }
      }
    });
  });
});

observer.observe(document.body, { childList: true, subtree: true });

// Expose globally for manual initialization if needed
(window as any).BookReader = {
  init: initializeBookReaders,
  version: '1.0.0'
};