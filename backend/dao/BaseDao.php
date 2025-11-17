<?php
/**
 * BaseDao
 * 
 * Provides common database operations for all DAO classes
 */
require_once __DIR__ . '/Database.php';

class BaseDao {
    protected $table;
    protected $connection;
    protected $primaryKey = 'id';

    /**
     * Constructor
     * @param string $table The database table name
     */
    public function __construct($table) {
        $this->table = $table;
        $this->connection = Database::connect();
    }

    /**
     * Get all records with pagination
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return array List of records
     */
    public function getAll($limit = 10, $offset = 0) {
        $stmt = $this->connection->prepare(
            "SELECT * FROM " . $this->table . " LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single record by ID
     * @param mixed $id The record ID
     * @return array|null The record or null if not found
     */
    public function getById($id) {
        $stmt = $this->connection->prepare(
            "SELECT * FROM " . $this->table . " WHERE {$this->primaryKey} = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Insert a new record
     * @param array $data Associative array of column => value
     * @return int The ID of the inserted record
     */
    public function add($data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO " . $this->table . " ($columns) VALUES ($placeholders)";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($data);
        
        return (int)$this->connection->lastInsertId();
    }

    /**
     * Update an existing record
     * @param mixed $id The record ID
     * @param array $data Associative array of column => value
     * @return array The updated record
     */
    public function update($id, $data) {
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "$key = :$key";
        }
        $fields = implode(", ", $fields);
        
        $sql = "UPDATE " . $this->table . " SET $fields WHERE {$this->primaryKey} = :id";
        $data['id'] = $id;
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($data);
        
        return $this->getById($id);
    }

    /**
     * Delete a record by ID
     * @param mixed $id The record ID
     * @return bool True if deletion was successful
     */
    public function delete($id) {
        $stmt = $this->connection->prepare(
            "DELETE FROM " . $this->table . " WHERE {$this->primaryKey} = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Execute a custom query and return all results
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for the query
     * @return array Query results
     */
    public function query($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a custom query and return a single result
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for the query
     * @return array|null Single result or null if not found
     */
    public function queryUnique($query, $params = []) {
        $results = $this->query($query, $params);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Count all records in the table
     * @return int Total number of records
     */
    public function countAll() {
        $stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM " . $this->table);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    /**
     * Begin a database transaction
     */
    public function beginTransaction() {
        $this->connection->beginTransaction();
    }
    
    /**
     * Commit the current transaction
     */
    public function commit() {
        $this->connection->commit();
    }
    
    /**
     * Roll back the current transaction
     */
    public function rollBack() {
        $this->connection->rollBack();
    }
}
