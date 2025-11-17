<?php
/**
 * BaseService
 * 
 * Provides common CRUD operations and validation for all services
 */
require_once __DIR__ . '/../dao/BaseDao.php';

abstract class BaseService {
    protected $dao;

    /**
     * Constructor
     * @param BaseDao $dao The Data Access Object
     */
    public function __construct($dao) {
        $this->dao = $dao;
    }

    /**
     * Get all entities
     * @param int $limit Number of items to return
     * @param int $offset Offset for pagination
     * @return array List of entities
     */
    public function getAll($limit = 10, $offset = 0) {
        return $this->dao->getAll($limit, $offset);
    }

    /**
     * Get entity by ID
     * @param mixed $id Entity ID
     * @return array|null Entity data or null if not found
     */
    public function getById($id) {
        $this->validateId($id);
        return $this->dao->getById($id);
    }

    /**
     * Add a new entity
     * @param array $entity Entity data
     * @return array Created entity data
     * @throws Exception If validation fails
     */
    public function add($entity) {
        $this->validate($entity);
        return $this->dao->add($entity);
    }

    /**
     * Update an existing entity
     * @param mixed $id Entity ID
     * @param array $entity New entity data
     * @return array Updated entity data
     * @throws Exception If validation fails or entity not found
     */
    public function update($id, $entity) {
        $this->validateId($id);
        $this->validate($entity);
        return $this->dao->update($id, $entity);
    }

    /**
     * Delete an entity
     * @param mixed $id Entity ID
     * @return bool True if deletion was successful
     * @throws Exception If entity not found or deletion fails
     */
    public function delete($id) {
        $this->validateId($id);
        return $this->dao->delete($id);
    }

    /**
     * Validate entity ID
     * @param mixed $id Entity ID to validate
     * @throws InvalidArgumentException If ID is invalid
     */
    protected function validateId($id) {
        if (empty($id) || !is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException("Invalid ID");
        }
    }

    /**
     * Validate entity data
     * @param array $entity Entity data to validate
     * @throws Exception If validation fails
     */
    abstract protected function validate($entity);

    /**
     * Get paginated results
     * @param int $page Page number (1-based)
     * @param int $limit Items per page
     * @return array Paginated results with metadata
     */
    public function getPaginated($page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, min(100, (int)$limit)); // Limit to 100 items per page max
        $offset = ($page - 1) * $limit;
        
        return [
            'data' => $this->getAll($limit, $offset),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $this->dao->countAll()
            ]
        ];
    }
}
