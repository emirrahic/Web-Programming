<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/AuthorDao.php';

class AuthorService extends BaseService {
  
    public function __construct($pdo = null) {
        parent::__construct(new AuthorDao('authors', $pdo));
    }

   
  
    protected function validate($author) {
        if (empty($author['name'])) {
            throw new InvalidArgumentException("Author name is required");
        }
        
        if (strlen($author['name']) > 100) {
            throw new InvalidArgumentException("Author name must be less than 100 characters");
        }
        
        if (!empty($author['biography']) && strlen($author['biography']) > 2000) {
            throw new InvalidArgumentException("Biography must be less than 2000 characters");
        }
        
        
        $existing = $this->dao->queryUnique(
            "SELECT * FROM authors WHERE LOWER(name) = LOWER(:name) AND id != :id LIMIT 1",
            [
                'name' => $author['name'],
                'id' => $author['id'] ?? 0
            ]
        );
        
        if ($existing) {
            throw new InvalidArgumentException("An author with this name already exists");
        }
    }

  
    public function getBooksByAuthor($authorId) {
        if (!is_numeric($authorId) || $authorId <= 0) {
            throw new InvalidArgumentException("Invalid author ID");
        }
        
        return $this->dao->getBooksByAuthor($authorId);
    }
    
 
    public function search($query) {
        if (empty($query)) {
            throw new InvalidArgumentException("Search query cannot be empty");
        }
        
        return $this->dao->search($query);
    }
    
   
    public function getPaginated($page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, min(100, (int)$limit)); 
        
        $offset = ($page - 1) * $limit;
        
        return [
            'data' => $this->dao->getAll($limit, $offset),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $this->dao->countAll()
            ]
        ];
    }
    
  
    public function delete($id) {
        
        $books = $this->getBooksByAuthor($id);
        if (count($books) > 0) {
            throw new Exception("Cannot delete author with associated books");
        }
        
        return parent::delete($id);
    }
}
