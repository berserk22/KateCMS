<?php

namespace Core\MySQL;

/**
 * Description of ResultParser
 *
 * @author Sergey Tevs
 */

class ResultParser {
    
    protected $stmt;

    protected $entity;
    
    protected $column_type=array();

    public function __construct(\PDOStatement $stmt, $entity='') {
        $this->stmt=$stmt;
        $this->entity=$entity;        
    }
    
    public function getCount(){
        return $this->stmt->rowCount();
    }
    
    public function getResult(){
        if ($this->getCount()>1){
            return $this->stmt->fetchAll(\PDO::FETCH_CLASS, $this->entity);
        }
        else {
            return $this->stmt->fetchObject($this->entity);
        }
    }
    
    public function getColumnMeta(){
        foreach(range(0, $this->stmt->columnCount() - 1) as $column_index){
            $meta[] = $this->stmt->getColumnMeta($column_index);
        }
        return $meta;
    }
        
}
