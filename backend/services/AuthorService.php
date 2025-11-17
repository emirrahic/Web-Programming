<?php
/**
 * AuthorService
 * 
 * Handles business logic for author operations
 */
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/AuthorDao.php';

class AuthorService extends BaseService {
    /**
     * Constructor
     * @param PDO|null $pdo Optional PDO connection
     */
    public function __construct($pdo = null) {
        parent::__construct(new AuthorDao('authors', $pdo));
    }

    /**
     * Validate author data
     * @param array $author Author data to validate
     * @throws InvalidArgumentException If validation fails
     */
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
        
        // Check for duplicate author names (case-insensitive)
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

    /**
     * Get books by author ID
     * @param int $authorId Author ID
     * @return array List of books
     */
    public function getBooksByAuthor($authorId) {
        if (!is_numeric($authorId) || $authorId <= 0) {
            throw new InvalidArgumentException("Invalid author ID");
        }
        
        return $this->dao->getBooksByAuthor($authorId);
    }
    
    /**
     * Search authors by name
     * @param string $query Search query
     * @return array Matching authors
     */
    public function search($query) {
        if (empty($query)) {
            throw new InvalidArgumentException("Search query cannot be empty");
        }
        
        return $this->dao->search($query);
    }
    
    /**
     * Get authors with pagination
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Paginated authors and metadata
     */
    public function getPaginated($page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, min(100, (int)$limit)); // Limit to 100 items per page max
        
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
    
    /**
     * Delete an author and handle related data
     * @param int $id Author ID
     * @return bool True if successful
     */
    public function delete($id) {
        // Check if author has any books
        $books = $this->getBooksByAuthor($id);
        if (count($books) > 0) {
            throw new Exception("Cannot delete author with associated books");
        }
        
        return parent::delete($id);
    }
}
