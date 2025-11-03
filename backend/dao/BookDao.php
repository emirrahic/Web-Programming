<?php
require_once __DIR__ . '/BaseDao.php';

class BookDao extends BaseDao {
    public function __construct() {
        parent::__construct("Book");
    }

    public function getAvailableBooks() {
        return $this->query("SELECT b.*, a.name as author_name 
                           FROM Book b 
                           JOIN Author a ON b.author_id = a.author_id 
                           WHERE b.amount > 0");
    }

    public function searchBooks($searchTerm) {
        $searchTerm = "%$searchTerm%";
        return $this->query("SELECT b.*, a.name as author_name 
                           FROM Book b 
                           JOIN Author a ON b.author_id = a.author_id 
                           WHERE b.title LIKE :search 
                           OR a.name LIKE :search 
                           OR b.genre LIKE :search", 
                           ["search" => $searchTerm]);
    }

    public function getBooksByAuthor($authorId) {
        return $this->query("SELECT * FROM Book WHERE author_id = :author_id", 
                           ["author_id" => $authorId]);
    }

    public function getBooksByCategory($categoryId) {
        return $this->query("SELECT b.* FROM Book b 
                           JOIN BookCategory bc ON b.book_id = bc.book_id 
                           WHERE bc.category_id = :category_id", 
                           ["category_id" => $categoryId]);
    }
}
?>
