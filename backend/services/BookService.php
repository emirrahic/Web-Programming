<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/BookDao.php';

class BookService extends BaseService {
    public function __construct() {
        parent::__construct(new BookDao());
    }

    protected function validate($book) {
        if (empty($book['title'])) {
            throw new Exception("Book title is required");
        }
        if (strlen($book['title']) > 255) {
            throw new Exception("Book title must be less than 255 characters");
        }
        if (empty($book['author_id'])) {
            throw new Exception("Author is required");
        }
        if (empty($book['isbn']) || !preg_match('/^\d{10,13}$/', $book['isbn'])) {
            throw new Exception("Valid ISBN (10-13 digits) is required");
        }
        if (isset($book['publication_year']) && ($book['publication_year'] < 1000 || $book['publication_year'] > (int)date('Y'))) {
            throw new Exception("Invalid publication year");
        }
    }

    public function getBooksByCategory($categoryId) {
        return $this->dao->getBooksByCategory($categoryId);
    }

    public function searchBooks($searchTerm) {
        return $this->dao->searchBooks($searchTerm);
    }

    public function getAvailableCopies($bookId) {
        return $this->dao->getAvailableCopies($bookId);
    }
}
