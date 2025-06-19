// Improved e-reader pagination with better error handling and performance
export class EReaderPaginator {
  private pageHeight: number;
  private container: HTMLElement;

  constructor(pageHeight: number, container: HTMLElement) {
    this.pageHeight = pageHeight;
    this.container = container;
  }

  // Paginate a single section of HTML
  paginateSection(html: string): string[] {
    const pages: string[] = [];
    // Parse the HTML into a temporary container
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    const nodes = Array.from(tempDiv.childNodes);
    let nodeIndex = 0;

    while (nodeIndex < nodes.length) {
      this.container.innerHTML = '';
      let lastGoodIndex = nodeIndex;
      // Add nodes one by one until overflow
      for (let i = nodeIndex; i < nodes.length; i++) {
        this.container.appendChild(nodes[i].cloneNode(true));
        if (this.container.offsetHeight > this.pageHeight) {
          // Remove the last node that caused overflow
          this.container.removeChild(this.container.lastChild!);
          console.log('[Paginator] Overflow detected, removing last node:', {
            currentHTML: this.container.innerHTML,
            currentHeight: this.container.offsetHeight,
            pageHeight: this.pageHeight,
            nodeIndex: i,
          });
          break;
        }
        lastGoodIndex = i + 1;
      }
      // Save the current page
      pages.push(this.container.innerHTML);
      // Move to the next set of nodes
      nodeIndex = lastGoodIndex;
    }
    // Clear the container after pagination
    this.container.innerHTML = '';
    return pages;
  }
}

// Helper function to paginate an entire ebook
export function paginateEbook(sections: string[], pageHeight: number, container: HTMLElement): string[] {
  const paginator = new EReaderPaginator(pageHeight, container);
  const allPages: string[] = [];
  sections.forEach((html) => {
    const sectionPages = paginator.paginateSection(html);
    allPages.push(...sectionPages);
  });
  return allPages;
} 