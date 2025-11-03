<?php
require_once __DIR__ . '/BaseDao.php';

class BookCategoryDao extends BaseDao {
    public function __construct() {
        parent::__construct("BookCategory");
    }

    public function addCategoryToBook($bookId, $categoryId) {
        return $this->insert([
            'book_id' => $bookId,
            'category_id' => $categoryId
        ]);
    }

    public function removeCategoryFromBook($bookId, $categoryId) {
        $stmt = $this->connection->prepare(
            "DELETE FROM BookCategory 
             WHERE book_id = :book_id AND category_id = :category_id"
        );
        return $stmt->execute([
            'book_id' => $bookId,
            'category_id' => $categoryId
        ]);
    }

    public function getBookCategories($bookId) {
        return $this->query(
            "SELECT c.* 
             FROM Category c 
             INNER JOIN BookCategory bc ON c.category_id = bc.category_id 
             WHERE bc.book_id = :book_id",
            ['book_id' => $bookId]
        );
    }

    public function getBooksByCategory($categoryId) {
        return $this->query(
            "SELECT b.* 
             FROM Book b 
             JOIN BookCategory bc ON b.book_id = bc.book_id 
             WHERE bc.category_id = :category_id",
            ['category_id' => $categoryId]
        );
    }
}
?>
