<?php

require_once __DIR__ . '/Database.php';

class BaseDao {
    protected $table;
    protected $connection;
    protected $primaryKey = 'id';

    
    public function __construct($table) {
        $this->table = $table;
        $this->connection = Database::connect();
    }

   
    public function getAll($limit = 10, $offset = 0) {
        $stmt = $this->connection->prepare(
            "SELECT * FROM " . $this->table . " LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function getById($id) {
        $stmt = $this->connection->prepare(
            "SELECT * FROM " . $this->table . " WHERE {$this->primaryKey} = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    
    public function add($data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO " . $this->table . " ($columns) VALUES ($placeholders)";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($data);
        
        return (int)$this->connection->lastInsertId();
    }

   
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

    
    public function delete($id) {
        $stmt = $this->connection->prepare(
            "DELETE FROM " . $this->table . " WHERE {$this->primaryKey} = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

  
    public function query($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function queryUnique($query, $params = []) {
        $results = $this->query($query, $params);
        return !empty($results) ? $results[0] : null;
    }
    
 
    public function countAll() {
        $stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM " . $this->table);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    
    public function beginTransaction() {
        $this->connection->beginTransaction();
    }
    
   
    public function commit() {
        $this->connection->commit();
    }
    
    
    public function rollBack() {
        $this->connection->rollBack();
    }
}
