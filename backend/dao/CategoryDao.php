<?php
require_once __DIR__ . '/BaseDao.php';

class CategoryDao extends BaseDao {
    public function __construct() {
        parent::__construct("Category");
    }

    public function getCategoriesWithBookCount() {
        return $this->query("SELECT c.*, COUNT(bc.book_id) as book_count 
                           FROM Category c 
                           LEFT JOIN BookCategory bc ON c.category_id = bc.category_id 
                           GROUP BY c.category_id");
    }
}
?>
