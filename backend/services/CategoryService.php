<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/CategoryDao.php';

class CategoryService extends BaseService {
    public function __construct() {
        parent::__construct(new CategoryDao());
    }

    protected function validate($category) {
        if (empty($category['name'])) {
            throw new Exception("Category name is required");
        }
        if (strlen($category['name']) > 100) {
            throw new Exception("Category name must be less than 100 characters");
        }
    }

    public function getBooksByCategory($categoryId) {
        return $this->dao->getBooksByCategory($categoryId);
    }
}
