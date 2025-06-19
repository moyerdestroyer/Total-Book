// Improved e-reader pagination with better error handling and performance
export class EReaderPaginator {
  private pageHeight: number;
  private container: HTMLElement;
  private pages: string[];
  private currentPageContent: string;
  private currentHeight: number;
  private pageCount: number;

  constructor(pageHeight: number, container: HTMLElement) {
    this.pageHeight = pageHeight;
    this.container = container;
    this.pages = [];
    this.currentPageContent = '';
    this.currentHeight = 0;
    this.pageCount = 0;
  }

  // Main pagination method
  paginateSection(html: string, sectionIndex: number): string[] {
    try {
      // Clear container and render HTML
      this.container.innerHTML = html;
      const nodes = Array.from(this.container.childNodes);
      
      // Process each node
      for (let i = 0; i < nodes.length; i++) {
        const node = nodes[i];
        this.processNode(node, i);
      }

      // Add final page if there's remaining content
      this.finalizePagination();
      
      return this.pages;
    } catch (error) {
      console.error('Error during pagination:', error);
      throw new Error(`Pagination failed for section ${sectionIndex + 1}: ${error instanceof Error ? error.message : String(error)}`);
    }
  }

  // Process individual nodes with better error handling
  private processNode(node: Node, nodeIndex: number): void {
    try {
      if (node.nodeType !== Node.ELEMENT_NODE) {
        // Handle text nodes and other non-element nodes
        if (node.nodeType === Node.TEXT_NODE && node.textContent?.trim()) {
          this.addTextNode(node as Text);
        }
        return;
      }

      const element = node as HTMLElement;
      
      // Special handling for chapter titles
      if (this.isChapterTitle(element)) {
        this.handleChapterTitle(element);
        return;
      }

      // Handle regular elements
      this.handleElement(element);
      
    } catch (nodeError) {
      console.error('Error processing node:', nodeError, {
        nodeIndex,
        nodeType: node.nodeType,
        nodeContent: node.textContent?.substring(0, 100)
      });
      
      // Continue processing - don't let one node break the entire pagination
      this.addFallbackContent(node);
    }
  }

  // Improved chapter title handling
  private handleChapterTitle(element: HTMLElement): void {
    // Only start new page for actual chapter titles
    if (element.classList.contains('book-chapter-title')) {
      if (this.currentPageContent.trim()) {
        this.finalizePage('chapter title');
      }
    }
    
    this.addElementToPage(element);
  }

  // Enhanced element handling with better splitting logic
  private handleElement(element: HTMLElement): void {
    const nodeHeight = this.getElementHeight(element);
    const tagName = element.tagName.toLowerCase();

    // Check if element fits on current page
    if (this.currentHeight + nodeHeight <= this.pageHeight) {
      this.addElementToPage(element);
      return;
    }

    // Element doesn't fit - start new page if current page has content
    if (this.currentPageContent.trim()) {
      this.finalizePage('height overflow');
    }

    // Handle oversized elements - now we check if it would overflow the current page
    if (this.currentHeight + nodeHeight > this.pageHeight) {
      this.handleOversizedElement(element);
    } else {
      this.addElementToPage(element);
    }
  }

  // Improved oversized element handling
  private handleOversizedElement(element: HTMLElement): void {
    const tagName = element.tagName.toLowerCase();
    
    switch (tagName) {
      case 'p':
        this.splitParagraph(element);
        break;
      case 'div':
        this.splitContainer(element);
        break;
      case 'ul':
      case 'ol':
        this.splitList(element);
        break;
      default:
        console.warn(`Large ${tagName} element cannot be split, adding as-is`);
        this.addElementToPage(element);
    }
  }

  // Add this helper method to EReaderPaginator class
  private splitIntoSentences(text: string): string[] {
    // Simple sentence splitting using common sentence endings
    // This could be enhanced with a more sophisticated NLP library
    return text.match(/[^.!?]+[.!?]+(?:\s+|$)/g) || [text];
  }

  // Modified splitParagraph method
  private splitParagraph(paragraph: HTMLElement): void {
    const text = paragraph.textContent || '';
    const sentences = this.splitIntoSentences(text);
    
    if (sentences.length <= 1 && text.split(/\s+/).length <= 1) {
        this.addElementToPage(paragraph);
        return;
    }

    
    let currentChunk = '';
    const className = paragraph.className;
    const style = paragraph.getAttribute('style') || '';
    
    // Create a test element for measuring
    const testElement = document.createElement('p');
    testElement.className = className;
    if (style) testElement.setAttribute('style', style);
    testElement.style.visibility = 'hidden';
    testElement.style.position = 'absolute';
    this.container.appendChild(testElement);

    try {
        for (let sentence of sentences) {
            // Try adding the whole sentence
            const testChunk = currentChunk + sentence;
            testElement.textContent = testChunk;
            const chunkHeight = testElement.offsetHeight;

            if (this.currentHeight + chunkHeight <= this.pageHeight) {
                currentChunk = testChunk;
                continue;
            }

            // Sentence doesn't fit - try word wrapping if chunk has content
            if (currentChunk.trim()) {
                this.addParagraphChunk(currentChunk.trim(), className, style);
                this.finalizePage('sentence split');
                currentChunk = '';
            }

            // If sentence still doesn't fit, split by words
            const words = sentence.split(/(\s+)/); // Preserve whitespace
            let wordChunk = '';

            for (const word of words) {
                const testWordChunk = wordChunk + word;
                testElement.textContent = testWordChunk;
                const wordChunkHeight = testElement.offsetHeight;

                if (this.currentHeight + wordChunkHeight > this.pageHeight && wordChunk.trim()) {
                    // Add current word chunk
                    this.addParagraphChunk(wordChunk.trim(), className, style);
                    this.finalizePage('word wrap');
                    wordChunk = word;
                } else {
                    wordChunk = testWordChunk;
                }
            }

            if (wordChunk.trim()) {
                currentChunk = wordChunk;
            }
        }
        
        // Add remaining content
        if (currentChunk.trim()) {
            this.addParagraphChunk(currentChunk.trim(), className, style);
        }
    } finally {
        this.container.removeChild(testElement);
    }
  }

  // Split container elements (divs)
  private splitContainer(container: HTMLElement): void {
    const children = Array.from(container.childNodes);
    
    if (children.length === 0) {
      this.addElementToPage(container);
      return;
    }

    // Create wrapper with same attributes
    const wrapperTemplate = this.cloneElementStructure(container);
    
    children.forEach(child => {
      if (child instanceof HTMLElement) {
        const childClone = child.cloneNode(true) as HTMLElement;
        const tempWrapper = wrapperTemplate.cloneNode(false) as HTMLElement;
        tempWrapper.appendChild(childClone);
        
        const height = this.getElementHeight(tempWrapper);
        
        if (this.currentHeight + height > this.pageHeight && this.currentPageContent.trim()) {
          this.finalizePage('container split');
        }
        
        this.addToCurrentPage(tempWrapper.outerHTML, height);
      }
    });
  }

  // Split list elements
  private splitList(list: HTMLElement): void {
    const items = Array.from(list.children);
    const listType = list.tagName.toLowerCase();
    const className = list.className;
    const style = list.getAttribute('style') || '';
    
    let currentList: HTMLElement | null = null;
    
    items.forEach((item) => {
      if (item instanceof HTMLElement) {
        const itemHeight = this.getElementHeight(item);
        
        // Check if we need a new page
        if (this.currentHeight + itemHeight > this.pageHeight && this.currentPageContent.trim()) {
          if (currentList) {
            this.addToCurrentPage(currentList.outerHTML, this.getElementHeight(currentList));
          }
          this.finalizePage('list split');
          currentList = null;
        }
        
        // Create new list if needed
        if (!currentList) {
          currentList = document.createElement(listType);
          currentList.className = className;
          if (style) currentList.setAttribute('style', style);
        }
        
        currentList.appendChild(item.cloneNode(true));
      }
    });
    
    // Add final list
    if (currentList) {
      const listHtml = currentList.outerHTML;
      this.addToCurrentPage(listHtml, this.getElementHeight(currentList));
    }
  }

  // Helper methods
  private isChapterTitle(element: HTMLElement): boolean {
    // Check for actual chapter titles
    if (element.classList.contains('book-chapter-title')) {
      return true;
    }

    // Check for h1-h6 that are not part of front/back matter
    if (/^h[1-6]$/i.test(element.tagName)) {
      // Don't treat front/back matter headings as chapter titles
      return !element.classList.contains('book-title') &&
             !element.classList.contains('book-subtitle') &&
             !element.classList.contains('book-toc-title') &&
             !element.classList.contains('book-acknowledgments-title') &&
             !element.classList.contains('book-about-author-title') &&
             !element.classList.contains('book-description-title');
    }

    return false;
  }

  private getElementHeight(element: HTMLElement): number {
    if (!element.offsetHeight) {
      // For elements not in DOM, create temporary measurement
      const temp = element.cloneNode(true) as HTMLElement;
      temp.style.visibility = 'hidden';
      temp.style.position = 'absolute';
      temp.style.width = '100%';
      // Keep padding and margin for accurate measurement
      this.container.appendChild(temp);
      const height = temp.offsetHeight;
      this.container.removeChild(temp);
      return height;
    }
    return element.offsetHeight;
  }

  private addElementToPage(element: HTMLElement): void {
    const height = this.getElementHeight(element);
    this.addToCurrentPage(element.outerHTML, height);
  }

  private addParagraphChunk(text: string, className: string, style: string): void {
    const html = `<p${className ? ` class="${className}"` : ''}${style ? ` style="${style}"` : ''}>${text}</p>`;
    
    // Create temp element to measure height
    const temp = document.createElement('div');
    temp.innerHTML = html;
    temp.style.visibility = 'hidden';
    temp.style.position = 'absolute';
    this.container.appendChild(temp);
    const height = temp.offsetHeight;
    this.container.removeChild(temp);
    
    this.addToCurrentPage(html, height);
  }

  private addTextNode(textNode: Text): void {
    const text = textNode.textContent?.trim();
    if (!text) return;
    
    // Wrap text in span for measurement
    const span = document.createElement('span');
    span.textContent = text;
    const height = this.getElementHeight(span);
    
    this.addToCurrentPage(text, height);
  }

  private addToCurrentPage(content: string, height: number): void {
    this.currentPageContent += content;
    this.currentHeight += height;
  }

  private addFallbackContent(node: Node): void {
    // Fallback for problematic nodes
    const content = node instanceof HTMLElement ? node.outerHTML : node.textContent || '';
    if (content.trim()) {
      this.addToCurrentPage(content, 20); // Assume minimal height
    }
  }

  private cloneElementStructure(element: HTMLElement): HTMLElement {
    const clone = document.createElement(element.tagName);
    clone.className = element.className;
    const style = element.getAttribute('style');
    if (style) clone.setAttribute('style', style);
    return clone;
  }

  private finalizePage(reason: string): void {
    if (this.currentPageContent.trim()) {
      const contentPreview = this.currentPageContent
        .substring(0, 100)
        .replace(/\s+/g, ' ')
        .trim();
      
      console.log(`Page ${this.pageCount + 1}:`, {
        contentPreview,
        height: this.currentHeight
      });
      
      this.pages.push(this.currentPageContent);
      this.currentPageContent = '';
      this.currentHeight = 0;
      this.pageCount++;
    }
  }

  private finalizePagination(): void {
    this.finalizePage('end of section');
  }

  // Add reset method to EReaderPaginator class
  public reset(): void {
    this.pages = [];
    this.currentPageContent = '';
    this.currentHeight = 0;
    this.pageCount = 0;
  }
}

// Helper function to paginate an entire ebook
export function paginateEbook(sections: string[], pageHeight: number, container: HTMLElement): string[] {
  const paginator = new EReaderPaginator(pageHeight, container);
  const allPages: string[] = [];
  
  sections.forEach((html, index) => {
    // Reset paginator for each section
    paginator.reset();
    
    const sectionPages = paginator.paginateSection(html, index);
    allPages.push(...sectionPages);
  });
  
  console.log('Pagination complete:', {
    totalPages: allPages.length
  });
  
  return allPages;
} 