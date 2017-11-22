<?php


class Orm  {
    private $_table   = "users";
    private $_sql     = "";
    public  $_host    = "localhost";
    public  $_username= "root";
    public  $_password= "hello";
    public  $_db      ;
    public  $_db_name = "lsapp";
    public  $_result;
    public  $_fields=array();
    public  $_field_list=array();
    public  $_error   = "";
    
    public function __construct(){
        
        $this->_db = new mysqli($this->_host,$this->_username,$this->_password,$this->_db_name);
        $this->init();
    }
    public function db(){
        return $this->_db;
    }
    public function sql(){
        return $this->_sql;
    }
    public function init(){
        if($this->columns()->get()->result()){
            while($column   = $this->_result->fetch_assoc())
            {    
                
                $column_name        = $column['column_name'];
                unset($column['column_name']);
                $column['value']    = '';
                $this->_fields[$column_name] = $column;
                $this->_field_list[] = $column_name;
            }
        }
        
        return $this;
    }
    
    public function change_db($database){
        $this->_db_name = $database;
        $this->_db->select_db($database);
    }
    
    public function __set($name, $value)
    {
        if(in_array($name,$this->_field_list)){
            $this->$name = $value;
            $this->_fields[$name]['value'] = $value;
        }else{
            $this->_error = "Column not found";
            return false;
        }
    }
    
    public function get($id=''){
        if($id){
            $this->_sql = "SELECT * FROM {$this->_table} where id='$id'";
        }
        if(!$this->_sql){
            $this->all();
        }
        $this->query($this->_sql);
        return $this;
    }
    
    public function all(){
        $this->_sql = "SELECT * FROM {$this->_table}";
        return $this;
    }
    
    public function query($sql='',$resultmode = MYSQLI_USE_RESULT){
        if(!$sql)
            $sql = $this->_sql;
        $this->_result = $this->_db->query($this->_sql,$resultmode);
        if(!$this->_result){
            die("Error Occured : {$this->_sql} ".$this->error);
        }
        return $this;
    }
    
    public function result(){
        return $this->_result;
    }
    
    public function data(){
        return $this->_result->fetch_all();
    }
    
    public function save(){
       $insert_sql = "INSERT INTO {$this->_table} (`".implode('`,`',$this->_field_list)."`) VALUES (" ;
       $this->_sql  = $insert_sql;
       foreach($this->_fields as $field){
           $this->_sql .= "'{$field['value']}',";
       }
       $this->_sql  = rtrim($this->_sql,',');
       $this->_sql .= ")";
       $this->query($this->_sql);
       return $this; 
    }
    
    public function columns(){
        $this->_sql = "SELECT  
                            column_name,
                            data_type,
                            column_key,
                            character_maximum_length length,
                            numeric_precision  int_length
                        FROM `INFORMATION_SCHEMA`.`COLUMNS` 
                        WHERE `table_SCHEMA`='{$this->_db_name}' 
                            AND `table_NAME`='{$this->_table}'";
        
        return $this;
    }
}

$orm = new Orm();
$orm->get_all()->get();
var_dump($orm->result()->fetch_all());

 