export interface BookContent {
  id: number;
  title: string;
  categories: Array<{
    id: number;
    name: string;
    slug: string;
  }>;
  image_url: {
    url: string;
    width: number;
    height: number;
  } | null;
  table_of_contents: Array<{
    id: number;
    title: string;
    order: number;
  }>;
  content: {
    cover: {
      html: string;
    };
    title_page: {
      html: string;
    };
    author_page: {
      author: string;
    };
    copyright_page: {
      html: string;
    };
    dedication_page: {
      html: string;
    };
    table_of_contents_page: {
      html: string;
    };
    main_body: {
      chapters: Array<{
        id: number;
        title: string;
        html: string;
        order: number;
      }>;
    };
    acknowledgments_page: {
      html: string;
    };
    about_author_page: {
      html: string;
    };
    description_page: {
      html: string;
    };
  };
}

export async function loadBook(bookId: string): Promise<BookContent> {
  try {
    const response = await fetch(`/wp-json/total-book/v1/book/${bookId}`);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    console.log('Book Content:', data);
    return data;
  } catch (error) {
    console.error('Error loading book:', error);
    throw error;
  }
} 