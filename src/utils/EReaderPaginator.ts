// Improved e-reader pagination with better error handling and performance
export class EReaderPaginator {
  private pageHeight: number;
  private container: HTMLElement;

  constructor(pageHeight: number, container: HTMLElement) {
    this.pageHeight = pageHeight;
    this.container = container;
  }

  // Paginate a single section of HTML
  paginateSection(html: string, isFirstSection: boolean = false): string[] {
    const pages: string[] = [];
    // Parse the HTML into a temporary container
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    const nodes = Array.from(tempDiv.childNodes);
    let nodeIndex = 0;
    let lastProcessedNode: Node | null = null;

    while (nodeIndex < nodes.length) {
      // Clear container for this page iteration
      this.container.innerHTML = '';
      let lastGoodIndex = nodeIndex;
      let currentPageContent: Node[] = [];
      
      // Add nodes one by one until overflow
      for (let i = nodeIndex; i < nodes.length; i++) {
        const currentNode = nodes[i];
        lastProcessedNode = currentNode;
        
        // Skip height calculation for the first section (book cover)
        if (isFirstSection && pages.length === 0) {
          currentPageContent.push(currentNode.cloneNode(true));
          lastGoodIndex = i + 1;
          continue;
        }
        
        // Clone the node for testing
        const clonedNode = currentNode.cloneNode(true);
        
        // Add node to container for height testing
        this.container.appendChild(clonedNode);
        
        // Check if this addition caused overflow
        if (this.container.offsetHeight > this.pageHeight) {
          // Remove the test node from container
          this.container.removeChild(clonedNode);
          
          // Try to split the last node if it's a paragraph
          if (currentNode.nodeType === Node.ELEMENT_NODE && 
              (currentNode as Element).tagName === 'P') {
            
            const splitResult = this.splitParagraph(currentNode as Element, this.container);
            
            if (splitResult.fitsOnPage && splitResult.fittingPart) {
              // The split paragraph fits, add it to current page
              currentPageContent.push(splitResult.fittingPart);
              
              // Keep the remaining content for the next page
              if (splitResult.remainingPart) {
                // Insert the remaining part at the current position for next iteration
                nodes.splice(i, 1, splitResult.remainingPart);
                lastGoodIndex = i; // Stay on this node for next iteration
              } else {
                lastGoodIndex = i + 1; // Move to next node
              }
            } else {
              // Even the split doesn't fit, keep the node for next page
              lastGoodIndex = i;
            }
          } else {
            // Non-paragraph node doesn't fit, keep it for next page
            lastGoodIndex = i;
          }
          break;
        }
        
        // Node fits, add it to current page content
        currentPageContent.push(clonedNode);
        lastGoodIndex = i + 1;
      }

      // Create the page content
      if (currentPageContent.length > 0) {
        const pageDiv = document.createElement('div');
        currentPageContent.forEach(node => {
          pageDiv.appendChild(node.cloneNode(true));
        });
        pages.push(pageDiv.innerHTML);
      } else {
        // Fallback: if no content fits, create a minimal page
        pages.push('<div class="page-break"></div>');
      }
      
      // Move to the next set of nodes
      // SAFEGUARD: Always advance nodeIndex to avoid infinite loops
      if (lastGoodIndex === nodeIndex) {
        // Only increment nodeIndex if the node at nodeIndex is the same as before (not a new split paragraph)
        if (nodes[nodeIndex] === lastProcessedNode) {
          nodeIndex++;
        } else {
          // A new node (split paragraph) was inserted, so try again at the same index
          nodeIndex = lastGoodIndex;
        }
      } else {
        nodeIndex = lastGoodIndex;
      }
    }
    
    // Clear the container after pagination
    this.container.innerHTML = '';
    return pages;
  }

  // Helper method to split a paragraph
  private splitParagraph(paragraph: Element, container: HTMLElement): {
    fitsOnPage: boolean;
    fittingPart: Element | null;
    remainingPart: Element | null;
  } {
    const text = paragraph.textContent || '';
    const words = text.split(' ').filter(word => word.trim().length > 0);
    
    if (words.length <= 1) {
      return { fitsOnPage: false, fittingPart: null, remainingPart: paragraph };
    }
    
    // First, check if the entire paragraph fits
    const fullParagraph = document.createElement('p');
    fullParagraph.textContent = text;
    Array.from(paragraph.attributes).forEach(attr => {
      fullParagraph.setAttribute(attr.name, attr.value);
    });
    
    container.appendChild(fullParagraph);
    const fullHeight = container.offsetHeight;
    container.removeChild(fullParagraph);
    
    if (fullHeight <= this.pageHeight) {
      return { fitsOnPage: true, fittingPart: paragraph, remainingPart: null };
    }
    
    // Binary search for the maximum number of words that fit
    let left = 0;
    let right = words.length;
    let bestFit = 0;
    
    while (left <= right) {
      const mid = Math.floor((left + right) / 2);
      const testWords = words.slice(0, mid);
      
      if (testWords.length === 0) {
        left = mid + 1;
        continue;
      }
      
      // Create test paragraph
      const testParagraph = document.createElement('p');
      testParagraph.textContent = testWords.join(' ') + ' ';
      Array.from(paragraph.attributes).forEach(attr => {
        testParagraph.setAttribute(attr.name, attr.value);
      });
      
      // Temporarily add to container
      container.appendChild(testParagraph);
      const height = container.offsetHeight;
      container.removeChild(testParagraph);
      
      if (height <= this.pageHeight) {
        bestFit = mid;
        left = mid + 1;
      } else {
        right = mid - 1;
      }
    }
    
    // Create the fitting part
    const fittingPart = bestFit > 0 ? document.createElement('p') : null;
    if (fittingPart) {
      fittingPart.textContent = words.slice(0, bestFit).join(' ') + ' ';
      // Copy original paragraph attributes
      Array.from(paragraph.attributes).forEach(attr => {
        fittingPart.setAttribute(attr.name, attr.value);
      });
    }
    
    // Create the remaining part
    const remainingPart = bestFit < words.length ? document.createElement('p') : null;
    if (remainingPart) {
      remainingPart.textContent = words.slice(bestFit).join(' ') + ' ';
      // Copy original paragraph attributes
      Array.from(paragraph.attributes).forEach(attr => {
        remainingPart.setAttribute(attr.name, attr.value);
      });
    }
    
    return {
      fitsOnPage: bestFit > 0,
      fittingPart,
      remainingPart
    };
  }
}

// Interface for section metadata
export interface SectionMetadata {
  type: 'front' | 'chapter' | 'back';
  title?: string;
  id?: number;
  order?: number;
  startPage: number;
  endPage: number;
  pageCount: number;
}

// Interface for pagination result with metadata
export interface PaginationResult {
  pages: string[];
  sections: SectionMetadata[];
}

// Helper function to paginate an entire ebook with section tracking
export function paginateEbookWithMetadata(
  sections: Array<{ html: string; type: 'front' | 'chapter' | 'back'; title?: string; id?: number; order?: number }>,
  pageHeight: number, 
  container: HTMLElement
): PaginationResult {
  const paginator = new EReaderPaginator(pageHeight, container);
  const allPages: string[] = [];
  const sectionMetadata: SectionMetadata[] = [];
  let currentPageIndex = 0;

  sections.forEach((section, sectionIndex) => {
    const isFirstSection = sectionIndex === 0; // First section is the book cover
    const sectionPages = paginator.paginateSection(section.html, isFirstSection);
    
    // Calculate section boundaries
    const startPage = currentPageIndex;
    const endPage = currentPageIndex + sectionPages.length - 1;
    const pageCount = sectionPages.length;
    
    // Add section metadata
    sectionMetadata.push({
      type: section.type,
      title: section.title,
      id: section.id,
      order: section.order,
      startPage,
      endPage,
      pageCount
    });
    
    // Add pages to the main array
    allPages.push(...sectionPages);
    currentPageIndex += sectionPages.length;
  });

  return {
    pages: allPages,
    sections: sectionMetadata
  };
}

// Legacy function for backward compatibility
export function paginateEbook(sections: string[], pageHeight: number, container: HTMLElement): string[] {
  const paginator = new EReaderPaginator(pageHeight, container);
  const allPages: string[] = [];
  sections.forEach((html, index) => {
    const isFirstSection = index === 0; // First section is the book cover
    const sectionPages = paginator.paginateSection(html, isFirstSection);
    allPages.push(...sectionPages);
  });
  return allPages;
} 