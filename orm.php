<?php


class Orm  {
    private $_table   = "users";
    private $_id      = "";
    private $_sql     = "";
    private $_last_sql     = "";
    public  $_host    = "localhost";
    public  $_username= "root";
    public  $_password= "hello";
    public  $_db      ;
    public  $_db_name = "lsapp";
    public  $_result;
    public  $_fields=array();
    public  $_field_list=array();
    public  $_error   = "";
    
    public function __construct($table=''){
        
        $this->_db = new mysqli($this->_host,$this->_username,$this->_password,$this->_db_name);
        if($table){
            $this->_table = $table;
            $this->init();
        }
       
        
    }
    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    public function set($field,$value){
        $this->_fields[$field]['value'] = $value;
        $this->$field = $value;
        return $this;
    }
    
    
    public function db(){
        return $this->_db;
    }
    public function table($table=""){
        $this->_table = $table;
        $this->init();
    }
    public function sql(){
        return $this->_sql;
    }
    
    public function all(){
        $this->_sql = "SELECT * FROM {$this->_table}";
        return $this;
    }
    
    
    public function result(){
        return $this->_result;
    }
    public function insert_id(){
        return $this->_db->insert_id;        
    }
    public function row(){
        $data = $this->_result->fetch_object();
        $this->_result->free();
        return $data;        
    }
    
    public function data(){
        return $this->_result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function last_sql(){
        
        return $this->_last_sql;
    }
    
    public function init(){
        $this->_fields     = array();
        $this->_field_list = array();
        if($this->columns()->get()->result()){
            while($column   = $this->_result->fetch_assoc())
            {    
                
                $column_name        = $column['column_name'];
                unset($column['column_name']);
                $column['value']    = '';
                $this->$column_name = '';
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
    
   
    
    public function get($id=''){
        
        if($id){
            $this->_id = $id;
            $this->_sql = "SELECT * FROM {$this->_table} where id='{$this->_id}'";
        }elseif($this->_sql){
            
        }elseif($this->_id){
            $this->_sql = "SELECT * FROM {$this->_table} where id='{$this->_id}'";
        }else{
            $this->all();
        }
        $this->query($this->_sql);
        return $this;
    }
    
    
    
    public function find($id){
        $data = $this->get($id)->row();
       
        if(!$data){
            $this->_error = "No data found";
            return $this;
        }
        
        foreach($this->_field_list as $field){
            $this->$field = $data->$field;
        }
        return $this;
    }
    
    public function query($sql='',$resultmode = MYSQLI_USE_RESULT){
        if(!$sql)
            $sql = $this->_sql;
        $this->_result = $this->_db->query($sql,$resultmode);
        
        if(!$this->_result){
            $this->_error = $this->_db->error;
            die("Error Occured : {$sql} ".$this->_error);
        }
        $this->_last_sql = $sql;
        $this->_sql      = '';
        
        return $this;
    }
    
    public function save(){
       
       if($this->_id){
           $this->_sql = "UPDATE {$this->_table} set ";
           foreach($this->_fields as $field=>$field_data){
               $this->_sql .= " $field='{$this->$field}',";
           }
           $this->_sql  = rtrim($this->_sql,',');
           $this->_sql .= " where id='{$this->_id}'";
           $this->query($this->_sql);
           return $this;
       }
       $this->id = '';
       $insert_sql = "INSERT INTO {$this->_table} (`".implode('`,`',$this->_field_list)."`) VALUES (" ;
       $this->_sql  = $insert_sql;
       foreach($this->_fields as $field=>$field_data){
           $this->_sql .= "'{$this->$field}',";
       }
       $this->_sql  = rtrim($this->_sql,',');
       $this->_sql .= ")";
       $this->query($this->_sql);
       $this->_id   = $this->insert_id();
       $this->id    = $this->_id;
       return $this; 
    }
    
    public function delete($id=''){
        if(!$id)
            $id = $this->_id;
        $this->_sql = "Delete FROM {$this->_table} where id = '$id'";
        $this->query();
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

$orm = new Orm("posts");

$orm->find(5);
$orm->title = "ovi";
$orm->body  = "no body";
$orm->save();



 