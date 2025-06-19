// Utility functions for working with chapter boundaries

export interface ChapterBoundary {
  id: number;
  title: string;
  startPage: number;
  endPage: number;
  pageCount: number;
}

export interface SectionMetadata {
  type: 'front' | 'chapter' | 'back';
  title?: string;
  id?: number;
  order?: number;
  startPage: number;
  endPage: number;
  pageCount: number;
}

/**
 * Get the last page of a specific chapter
 * @param chapterId - The ID of the chapter
 * @param chapterBoundaries - Array of chapter boundaries
 * @returns The last page number of the chapter, or null if not found
 */
export function getChapterLastPage(chapterId: number, chapterBoundaries: ChapterBoundary[]): number | null {
  const chapter = chapterBoundaries.find(ch => ch.id === chapterId);
  return chapter ? chapter.endPage : null;
}

/**
 * Get the first page of a specific chapter
 * @param chapterId - The ID of the chapter
 * @param chapterBoundaries - Array of chapter boundaries
 * @returns The first page number of the chapter, or null if not found
 */
export function getChapterFirstPage(chapterId: number, chapterBoundaries: ChapterBoundary[]): number | null {
  const chapter = chapterBoundaries.find(ch => ch.id === chapterId);
  return chapter ? chapter.startPage : null;
}

/**
 * Get the current chapter based on the current page
 * @param currentPage - Current page number (1-based)
 * @param chapterBoundaries - Array of chapter boundaries
 * @returns The current chapter info, or null if not in a chapter
 */
export function getCurrentChapter(currentPage: number, chapterBoundaries: ChapterBoundary[]): ChapterBoundary | null {
  if (chapterBoundaries.length === 0) return null;
  
  return chapterBoundaries.find(chapter => 
    currentPage >= chapter.startPage && currentPage <= chapter.endPage
  ) || null;
}

/**
 * Get all chapters that span a specific page range
 * @param startPage - Start page number (1-based)
 * @param endPage - End page number (1-based)
 * @param chapterBoundaries - Array of chapter boundaries
 * @returns Array of chapters that overlap with the page range
 */
export function getChaptersInRange(startPage: number, endPage: number, chapterBoundaries: ChapterBoundary[]): ChapterBoundary[] {
  return chapterBoundaries.filter(chapter => 
    (chapter.startPage <= endPage && chapter.endPage >= startPage)
  );
}

/**
 * Calculate the total number of pages across all chapters
 * @param chapterBoundaries - Array of chapter boundaries
 * @returns Total page count
 */
export function getTotalChapterPages(chapterBoundaries: ChapterBoundary[]): number {
  return chapterBoundaries.reduce((total, chapter) => total + chapter.pageCount, 0);
}

/**
 * Get chapter progress as a percentage
 * @param currentPage - Current page number (1-based)
 * @param chapterBoundaries - Array of chapter boundaries
 * @returns Progress percentage (0-100), or null if not in a chapter
 */
export function getChapterProgress(currentPage: number, chapterBoundaries: ChapterBoundary[]): number | null {
  const currentChapter = getCurrentChapter(currentPage, chapterBoundaries);
  if (!currentChapter) return null;
  
  const pagesIntoChapter = currentPage - currentChapter.startPage + 1;
  return Math.round((pagesIntoChapter / currentChapter.pageCount) * 100);
}

/**
 * Get overall book progress as a percentage
 * @param currentPage - Current page number (1-based)
 * @param totalPages - Total number of pages in the book
 * @returns Progress percentage (0-100)
 */
export function getBookProgress(currentPage: number, totalPages: number): number {
  return Math.round((currentPage / totalPages) * 100);
}

/**
 * Find the next chapter after the current page
 * @param currentPage - Current page number (1-based)
 * @param chapterBoundaries - Array of chapter boundaries
 * @returns The next chapter, or null if at the end
 */
export function getNextChapter(currentPage: number, chapterBoundaries: ChapterBoundary[]): ChapterBoundary | null {
  const sortedChapters = [...chapterBoundaries].sort((a, b) => a.startPage - b.startPage);
  return sortedChapters.find(chapter => chapter.startPage > currentPage) || null;
}

/**
 * Find the previous chapter before the current page
 * @param currentPage - Current page number (1-based)
 * @param chapterBoundaries - Array of chapter boundaries
 * @returns The previous chapter, or null if at the beginning
 */
export function getPreviousChapter(currentPage: number, chapterBoundaries: ChapterBoundary[]): ChapterBoundary | null {
  const sortedChapters = [...chapterBoundaries].sort((a, b) => b.startPage - a.startPage);
  return sortedChapters.find(chapter => chapter.endPage < currentPage) || null;
}

/**
 * Generate a table of contents with page numbers
 * @param chapterBoundaries - Array of chapter boundaries
 * @returns Formatted TOC array
 */
export function generateTableOfContents(chapterBoundaries: ChapterBoundary[]): Array<{
  id: number;
  title: string;
  startPage: number;
  endPage: number;
  pageRange: string;
}> {
  return chapterBoundaries.map(chapter => ({
    id: chapter.id,
    title: chapter.title,
    startPage: chapter.startPage,
    endPage: chapter.endPage,
    pageRange: `${chapter.startPage}-${chapter.endPage}`
  }));
}

/**
 * Example usage: How to calculate the last page of a chapter
 * 
 * ```typescript
 * // In your component:
 * const chapterBoundaries = contentSectionsRef.current?.getChapterBoundaries() || [];
 * 
 * // Get the last page of chapter with ID 5
 * const lastPage = getChapterLastPage(5, chapterBoundaries);
 * console.log(`Chapter 5 ends on page ${lastPage}`);
 * 
 * // Get current chapter info
 * const currentChapter = getCurrentChapter(currentPage + 1, chapterBoundaries);
 * if (currentChapter) {
 *   console.log(`Currently reading: ${currentChapter.title} (pages ${currentChapter.startPage}-${currentChapter.endPage})`);
 * }
 * 
 * // Get chapter progress
 * const progress = getChapterProgress(currentPage + 1, chapterBoundaries);
 * console.log(`Chapter progress: ${progress}%`);
 * ```
 */ 