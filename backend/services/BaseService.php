<?php

require_once __DIR__ . '/../dao/BaseDao.php';

abstract class BaseService {
    protected $dao;

   
    public function __construct($dao) {
        $this->dao = $dao;
    }

   
    public function getAll($limit = 10, $offset = 0) {
        return $this->dao->getAll($limit, $offset);
    }

   
    public function getById($id) {
        $this->validateId($id);
        return $this->dao->getById($id);
    }

   
    public function add($entity) {
        $this->validate($entity);
        return $this->dao->add($entity);
    }

   
    public function update($id, $entity) {
        $this->validateId($id);
        $this->validate($entity);
        return $this->dao->update($id, $entity);
    }

  
    public function delete($id) {
        $this->validateId($id);
        return $this->dao->delete($id);
    }

    
    protected function validateId($id) {
        if (empty($id) || !is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException("Invalid ID");
        }
    }

  
    abstract protected function validate($entity);

   
    public function getPaginated($page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, min(100, (int)$limit)); 
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
