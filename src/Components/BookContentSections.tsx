import React, { useRef, useEffect, useState, useCallback } from 'react';
import { BookContent } from './loadBook';
import debounce from 'lodash/debounce';
import { paginateEbook } from '../utils/EReaderPaginator';

interface BookContentSectionsProps {
  bookData: BookContent;
  currentPage: number;
  onPageChange: (page: number, total: number) => void;
}

const BookContentSections: React.FC<BookContentSectionsProps> = ({ 
  bookData, 
  currentPage,
  onPageChange 
}) => {
  const [pages, setPages] = useState<string[]>([]); // Array of HTML strings for each page
  const contentRef = useRef<HTMLDivElement>(null); // Ref for visible content
  const hiddenRef = useRef<HTMLDivElement>(null); // Ref for hidden pagination calculation
  const [containerSize, setContainerSize] = useState({ width: 0, height: 0 });

  // Track content container size
  useEffect(() => {
    if (!contentRef.current) return;

    const resizeObserver = new ResizeObserver(entries => {
      for (const entry of entries) {
        const { width, height } = entry.contentRect;
        setContainerSize({ width, height });
      }
    });

    resizeObserver.observe(contentRef.current);
    return () => resizeObserver.disconnect();
  }, []);

  // Extract all sections into a flat array with page break indicators
  const getContentSections = () => {
    // Front matter sections in order
    const frontMatter = [
      { html: bookData.content.cover.html, forcePageBreak: true },
      { html: bookData.content.title_page.html, forcePageBreak: true },
      { html: bookData.content.author_page.author, forcePageBreak: true },
      { html: bookData.content.copyright_page.html, forcePageBreak: true },
      { html: bookData.content.dedication_page.html, forcePageBreak: true },
      { html: bookData.content.table_of_contents_page.html, forcePageBreak: true }
    ];

    // Chapters in order
    const chapters = bookData.content.main_body.chapters
      .sort((a, b) => a.order - b.order)
      .map(chapter => ({ html: chapter.html, forcePageBreak: true }));

    // Back matter sections in order
    const backMatter = [
      { html: bookData.content.acknowledgments_page.html, forcePageBreak: true },
      { html: bookData.content.about_author_page.html, forcePageBreak: true },
      { html: bookData.content.description_page.html, forcePageBreak: true }
    ];

    // Combine all sections in order
    const allSections = [...frontMatter, ...chapters, ...backMatter];

    // Log section details for debugging
    console.log('Section details:', allSections.map((section, index) => ({
      index,
      type: index < frontMatter.length ? 'front' : 
            index < frontMatter.length + chapters.length ? 'chapter' : 'back',
      contentPreview: section.html.substring(0, 50).replace(/\s+/g, ' ').trim(),
      forcePageBreak: section.forcePageBreak
    })));

    return allSections;
  };

  // Process sections and update pages
  const processSections = useCallback(() => {
    if (!hiddenRef.current || !bookData) return;

    const sections = getContentSections();
    console.log('Total sections to process:', sections.length);

    // Get the content container height
    const contentHeight = hiddenRef.current.offsetHeight;
    console.log('Page height:', contentHeight);

    // Paginate the content
    const pages = paginateEbook(
      sections.map(section => section.html),
      contentHeight,
      hiddenRef.current
    );

    // Update pages and notify parent
    setPages(pages);
    onPageChange(currentPage, pages.length);
  }, [bookData, hiddenRef, currentPage, onPageChange]);

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
  }, [bookData]);

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
              __html: pages[currentPage] || '',
            }}
          />
      </div>
    </div>
  );
};

export default BookContentSections;