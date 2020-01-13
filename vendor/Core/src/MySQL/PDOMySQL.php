<?php

namespace Core\MySQL;

use Core\MySQL\ResultParser;
use Core\Config\Config;
use Core\Log\Logger;
use Core\Helper\stdObject;

class PDOMySQL extends \PDO{
    
    public $db_con;
    
    public function __construct($config = null) {
        if ($config === null){
            $config=$this->getConfig();
        }
        
        try {
            $dsn = "mysql:host=$config->host;dbname=$config->db;charset=$config->charset";
            $opt = [
                self::ATTR_ERRMODE            => self::ERRMODE_EXCEPTION,
                self::ATTR_DEFAULT_FETCH_MODE => self::FETCH_ASSOC,
            ];
            $this->db_con=parent::__construct($dsn, $config->user, $config->password, $opt);
        } catch (\PDOException $e) {
            Logger::MySQLError('Error Code: '.$e->getCode().' ('.$e->getMessage().')');
            exit('Error establishing a database connection.');
        }
    }


        
    private function getConfig(){
        return (object)Config::getConfig('database');
    }
    
    /**
     * 
     * @param string $entity
     * @param array $param
     * @return array
     */
    public function findByOne($entity, array $param = null, array $order = null){
        if ($param!==null){
            foreach ($param as $key => $value) {
                if (!isset($where)){
                    $where=" WHERE `".$key."`=".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
                else {
                    $where.=" AND `".$key."`=".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
            }
        }
        
        if ($order!==null && is_array($order)){
            foreach ($order as $key => $value) {
                $order_by=" ORDER BY ".$key." ".$value;
            }
            $where.=$order_by;
        }
        
        $stmt=$this->prepare('SELECT * FROM '.$this->tableNameParser($entity).(isset($where)?$where:'').' LIMIT 1');
        try {
            $this->errorParser($stmt->queryString);
            $stmt->execute();
            $result=new ResultParser($stmt, $entity);
            return $result->getResult();
        } catch (\PDOException $e) {
            Logger::MySQLError('Error Code: '.$e->getCode().' ('.$e->getMessage().')');
            exit('Error establishing a database connection.');
            
        }
    }
    
    /**
     * 
     * @param string $entity
     * @param array $order
     * @return array
     */
    public function findAll($entity, array $order = null, array $limit = null){
        $where="";
        if ($limit!==null && is_array($limit)){
            if (count($limit)===2){
                $where.=" LIMIT ".$limit[0].", ".$limit[1];
            }
            else if (count($limit)===1) {
                $where.=" LIMIT ".$limit[0];
            }
        }
        
        if ($order!==null && is_array($order)){
            foreach ($order as $key => $value) {
                $order_by=" ORDER BY ".$key." ".$value;
            }
            $where.=$order_by;
        }
        
        $stmt=$this->prepare('SELECT * FROM '.$this->tableNameParser($entity).$where);
        try {
            $this->errorParser($stmt->queryString);
            $stmt->execute();
            $result=new ResultParser($stmt, $entity);
            return $result->getResult();
        } catch (\PDOException $e) {
            Logger::MySQLError('Error Code: '.$e->getCode().' ('.$e->getMessage().')');
            exit('Error establishing a database connection.');
        }
    }
    
    /**
     * 
     * @param string $entity
     * @param array $param
     * @param array $like
     * @param array $order
     * @return array
     */
    public function find($entity, array $param = null, array $like = null, array $order = null, array $limit = null){
        if ($param!==null){
            foreach ($param as $key => $value) {
                if (!isset($where)){
                    $where=" WHERE `".$key."`=".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
                else {
                    $where.=" AND `".$key."`=".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
            }
        }
        
        if ($like!==null){
            foreach ($like as $key => $value) {
                if (!isset($where)){
                    $where=" WHERE `".$key."` LIKE '%".(is_int($value)||is_long($value)?$value:$this->toString($value)."%'");
                }
                else {
                    $where.=" AND `".$key."` LIKE '%".(is_int($value)||is_long($value)?$value:$this->toString($value)."%'");
                }
            }
        }
        
        if ($order!==null && is_array($order)){
            foreach ($order as $key => $value) {
                $order_by=" ORDER BY ".$key." ".$value;
            }
            $where.=$order_by;
        }
        
        if ($limit!==null && is_array($limit)){
            if (count($limit)>1){
                $where.=" LIMIT ".$limit[0].", ".$limit[1];
            }
            else if (count($limit)===1) {
                $where.=" LIMIT ".$limit[0];
            }
        }
        
        $stmt=$this->prepare('SELECT * FROM '.$this->tableNameParser($entity).(isset($where)?$where:''));
        try {
            $this->errorParser($stmt->queryString);
            $stmt->execute();
            $result=new ResultParser($stmt, $entity);
            return $result->getResult();
        } catch (\PDOException $e) {
            Logger::MySQLError('Error Code: '.$e->getCode().' ('.$e->getMessage().')');
            exit('Error establishing a database connection.');
        }
    }
    
    /**
     * 
     * @param string $entity
     * @param array $param
     * @param array $where
     */
    public function update($entity, array $param = null, array $where = null){
        if ($where!==null){
            foreach ($where as $key => $value) {
                if (!isset($where_str)){
                    $where_str=" WHERE `".$key."`=".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
                else {
                    $where_str.=" AND `".$key."`=".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
            }
        }
        $stmt=$this->prepare('UPDATE '.$this->tableNameParser($entity).' SET '.$this->pdoSet($param).$where_str);
        try {
            $this->errorParser($stmt->queryString);
            $stmt->execute();
        } catch (\PDOException $e) {
            Logger::MySQLError('Error Code: '.$e->getCode().' ('.$e->getMessage().')');
            exit('Error establishing a database connection.');
        }
    }
    
    /**
     * 
     * @param string $entity
     * @param array $param
     */
    public function insert($entity, array $param = null){
        $stmt=$this->prepare('INSERT INTO '.$this->tableNameParser($entity).' SET '.$this->pdoSet($param));
        try {
            $this->errorParser($stmt->queryString);
            $stmt->execute();
        } catch (\PDOException $e) {
            Logger::MySQLError('Error Code: '.$e->getCode().' ('.$e->getMessage().')');
            exit('Error establishing a database connection.');
        }
    }
    
    public function remove($entity, array $where = null){
        if ($where!==null){
            foreach ($where as $key => $value) {
                if (!isset($where_str)){
                    $where_str=" WHERE `".$key."`=".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
                else {
                    $where_str.=" AND `".$key."`=".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
            }
        }
        $stmt=$this->prepare('DELETE FROM '.$this->tableNameParser($entity).$where_str);
        try {
            $this->errorParser($stmt->queryString);
            $stmt->execute();
        } catch (\PDOException $e) {
            Logger::MySQLError('Error Code: '.$e->getCode().' ('.$e->getMessage().')');
            exit('Error establishing a database connection.');
        }
    }
    
    public function callProcedure($name, array $param = null, $entity = 'stdClass'){
        $where='';
        if ($param!==null){
            foreach ($param as $value) {
                if ($where!==''){
                    $where.=", ".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
                else {
                    $where=(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
            }
        }
        
        $stmt=$this->prepare("CALL ".$name."(".$where.")");
        $this->errorParser($stmt->queryString);
        if ($param!==null){
            $stmt->execute($param);
        }
        else {
            $stmt->execute();
        }
        if ($entity!==null){
            $result=new ResultParser($stmt, $entity);
            return $result->getResult();
        }
    }
    
    public function callFunction($name, array $param = null, $entity = stdObject::class){
        $where='';
        if ($param!==null){
            foreach ($param as $value) {
                if ($where!==''){
                    $where.=", ".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
                else {
                    $where=(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
            }
        }
        
        $stmt=$this->prepare("SELECT ".$name."(".$where.")");
        $this->errorParser($stmt->queryString);
        if ($param!==null){
            $stmt->execute($param);
        }
        else {
            $stmt->execute();
        }
        if ($entity!==null){
            $result=new ResultParser($stmt, $entity);
            return $result->getResult();
        }
    }
    
    public function executeQuery($sql_string, $entity = stdObject::class, array $param = null){
        $stmt=$this->prepare($sql_string);
        $this->errorParser($stmt->queryString);
        if ($param!==null){
            $stmt->execute($param);
        }
        else {
            $stmt->execute();
        }
        if ($entity!==null){
            $result=new ResultParser($stmt, $entity);
            return $result->getResult();
        }
    }
    
    /**
     * 
     * @return string
     */
    public function prepareQuery($entity, array $param = null, array $like = null, array $limit = null){
        if ($param!==null){
            foreach ($param as $key => $value) {
                if (!isset($where)){
                    $where=" WHERE `".$key."`=".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
                else {
                    $where.=" AND `".$key."`=".(is_int($value)||is_long($value)?$value:"'".$this->toString($value)."'");
                }
            }
        }
        
        if ($like!==null){
            foreach ($like as $key => $value) {
                if (!isset($where)){
                    $where=" WHERE `".$key."` LIKE '%".(is_int($value)||is_long($value)?$value:$this->toString($value)."'");
                }
                else {
                    $where.=" AND `".$key."` LIKE '%".(is_int($value)||is_long($value)?$value:$this->toString($value))."'";
                }
            }
        }
        if ($limit!==null && is_array($limit)){
            if (count($limit)===2){
                $where.=" LIMIT ".$limit[0].", ".$limit[1];
            }
            else if (count($limit)===1) {
                $where.=" LIMIT ".$limit[0];
            }
        }
        
        $stmt=$this->prepare('SELECT * FROM '.$this->tableNameParser($entity).(isset($where)?$where:''));
        
        return $stmt->queryString;
    }
    
    protected function tableNameParser($entity=''){
        $name=strtolower(str_replace(['Core','App','\\','Entity'], ['','',''], $entity));
        return $name;
    }
    
    protected function errorParser($str = null){
        if ($this->errorCode()!=='00000'){
            Logger::MySQLError('Error Code: '.$this->errorInfo()[1].' ('.$this->errorInfo()[2].')');
        }
        else {
            Logger::MySQLExecuteInfo($str);
        }
    }
    
    protected function pdoSet($param = array()) {
        $set = '';
        foreach ($param as $field => $val) {
            if ($val!==null){
                $set.="`".str_replace("`","``",$field)."`". "=".(is_int($val)?$val:"'".$this->toString($val)."'").", ";
            }
        }
        return substr($set, 0, -2); 
    }
    
    protected function toString($str){
        $str=str_replace("'", "\'", $str);
        return $str;
    }
}
