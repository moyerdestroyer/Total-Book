import React, { useRef, useEffect, useState, useCallback, forwardRef, useImperativeHandle } from 'react';
import { BookContent } from './loadBook';
import debounce from 'lodash/debounce';
import { paginateEbookWithMetadata, SectionMetadata } from '../utils/EReaderPaginator';

interface BookContentSectionsProps {
  bookData: BookContent;
  currentPage: number;
  onPageChange: (page: number, total: number) => void;
}

export interface BookContentSectionsRef {
  getCurrentSection: () => SectionMetadata | null;
  getChapterBoundaries: () => Array<{
    id: number;
    title: string;
    startPage: number;
    endPage: number;
    pageCount: number;
  }>;
}

const BookContentSections = forwardRef<BookContentSectionsRef, BookContentSectionsProps>(({ 
  bookData, 
  currentPage,
  onPageChange 
}, ref) => {
  const [pages, setPages] = useState<string[]>([]); // Array of HTML strings for each page
  const [sections, setSections] = useState<SectionMetadata[]>([]); // Array of section metadata
  const contentRef = useRef<HTMLDivElement>(null); // Ref for visible content
  const hiddenRef = useRef<HTMLDivElement>(null); // Ref for hidden pagination calculation
  const [containerSize, setContainerSize] = useState({ width: 0, height: 0 });
  const [pageHeight, setPageHeight] = useState<number>(0); // Store the visible container height

  // Expose helper functions to parent component
  useImperativeHandle(ref, () => ({
    getCurrentSection: () => {
      if (sections.length === 0) return null;
      
      return sections.find(section => 
        currentPage >= section.startPage && currentPage <= section.endPage
      ) || null;
    },
    getChapterBoundaries: () => {
      const chapterSections = sections.filter(section => section.type === 'chapter');
      return chapterSections.map(chapter => ({
        id: chapter.id!,
        title: chapter.title!,
        startPage: chapter.startPage + 1, // Convert to 1-based
        endPage: chapter.endPage + 1,
        pageCount: chapter.pageCount
      }));
    }
  }), [sections, currentPage]);

  // Track content container size
  useEffect(() => {
    if (!contentRef.current) return;

    const resizeObserver = new ResizeObserver(entries => {
      for (const entry of entries) {
        const { width, height } = entry.contentRect;
        setContainerSize({ width, height });
        
        // Use CSS-computed height instead of ResizeObserver height
        const computedStyle = window.getComputedStyle(contentRef.current!);
        const cssHeight = parseFloat(computedStyle.height);
        setPageHeight(cssHeight); // Use the CSS-computed height
        
        // Debug logging
        console.log('[Height Debug]', {
          resizeObserverHeight: height,
          cssComputedHeight: cssHeight,
          difference: cssHeight - height,
          usingHeight: cssHeight
        });
      }
    });

    resizeObserver.observe(contentRef.current);
    return () => resizeObserver.disconnect();
  }, []);

  // Extract all sections into a flat array with page break indicators
  const getContentSections = () => {
    // Safety check for bookData structure
    if (!bookData?.content) {
      console.warn('BookContentSections: bookData or content is undefined');
      return [];
    }

    // Front matter sections in order - filter out empty ones
    const frontMatter = [
      { html: bookData.content.cover?.html, type: 'front' as const, title: 'Cover' },
      { html: bookData.content.title_page?.html, type: 'front' as const, title: 'Title Page' },
      { html: bookData.content.author_page?.author, type: 'front' as const, title: 'Author Page' },
      { html: bookData.content.copyright_page?.html, type: 'front' as const, title: 'Copyright' },
      { html: bookData.content.dedication_page?.html, type: 'front' as const, title: 'Dedication' },
      { html: bookData.content.table_of_contents_page?.html, type: 'front' as const, title: 'Table of Contents' }
    ].filter(section => section.html && section.html.trim() !== '');

    // Chapters in order - filter out empty ones
    const chapters = bookData.content.main_body?.chapters
      ?.sort((a, b) => a.order - b.order)
      .map(chapter => ({ 
        html: chapter.html, 
        type: 'chapter' as const, 
        title: chapter.title,
        id: chapter.id,
        order: chapter.order
      }))
      .filter(section => section.html && section.html.trim() !== '') || [];

    // Back matter sections in order - filter out empty ones
    const backMatter = [
      { html: bookData.content.acknowledgments_page?.html, type: 'back' as const, title: 'Acknowledgments' },
      { html: bookData.content.about_author_page?.html, type: 'back' as const, title: 'About the Author' },
      { html: bookData.content.description_page?.html, type: 'back' as const, title: 'Description' }
    ].filter(section => section.html && section.html.trim() !== '');

    // Combine all sections in order
    const allSections = [...frontMatter, ...chapters, ...backMatter];

    // Log section details for debugging
    console.log('Section details:', allSections.map((section, index) => ({
      index,
      type: section.type,
      title: section.title,
      contentPreview: section.html.substring(0, 50).replace(/\s+/g, ' ').trim()
    })));

    return allSections;
  };

  // Process sections and update pages
  const processSections = useCallback(() => {
    if (!hiddenRef.current || !bookData || !pageHeight) return;

    const sections = getContentSections();
    console.log('Total sections to process:', sections.length);

    // Handle case where no sections are available
    if (sections.length === 0) {
      console.warn('BookContentSections: No content sections available');
      setPages(['<div class="no-content"><p>No content available for this book.</p></div>']);
      setSections([]);
      onPageChange(1, 1);
      return;
    }

    // Use the VISIBLE container height as the pageHeight limit
    const visiblePageHeight = pageHeight;
    console.log('Page height (from visible .book-content):', visiblePageHeight);

    // Paginate the content with metadata
    const paginationResult = paginateEbookWithMetadata(
      sections,
      visiblePageHeight,
      hiddenRef.current
    );

    // Update pages and section metadata
    setPages(paginationResult.pages);
    setSections(paginationResult.sections);
    
    // Log section boundaries for debugging
    console.log('Section boundaries:', paginationResult.sections.map(section => ({
      type: section.type,
      title: section.title,
      startPage: section.startPage + 1, // Convert to 1-based for display
      endPage: section.endPage + 1,
      pageCount: section.pageCount
    })));
    
    onPageChange(currentPage, paginationResult.pages.length);
  }, [bookData, hiddenRef, currentPage, onPageChange, pageHeight]);

  // Debounced pagination for resize events
  const debouncedPaginate = debounce(processSections, 200);

  // Run pagination on mount and when bookData changes
  useEffect(() => {
    processSections();
    window.addEventListener('resize', debouncedPaginate);
    return () => {
      window.removeEventListener('resize', debouncedPaginate);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [bookData, pageHeight]);

  return (
    <div className="e-reader">
      <div className="book-content-wrapper">
        <div
          ref={hiddenRef}
          className="book-content-measure"
        />
        {/* Visible content */}
        <div className="book-content" ref={contentRef}
            dangerouslySetInnerHTML={{
              __html: pages[currentPage] || pages[0] || '<div class="no-content"><p>No content available for this book.</p></div>',
            }}
          />
      </div>
    </div>
  );
});

BookContentSections.displayName = 'BookContentSections';

export default BookContentSections;