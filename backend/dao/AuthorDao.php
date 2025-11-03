<?php
require_once __DIR__ . '/BaseDao.php';

class AuthorDao extends BaseDao {
    public function __construct() {
        parent::__construct("Author");
    }

    public function searchByName($name) {
        return $this->query("SELECT * FROM Author WHERE name LIKE :name", ["name" => "%$name%"]);
    }

    public function getAuthorsWithBookCount() {
        return $this->query("SELECT a.*, COUNT(b.book_id) as book_count 
                            FROM Author a 
                            LEFT JOIN Book b ON a.author_id = b.author_id 
                            GROUP BY a.author_id");
    }
}
?>
