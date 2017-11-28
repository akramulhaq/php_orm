<?php


class Orm  {
    public $_table     = "users";
    private $_id        = "";
    private $_sql       = "";
    private $_where_sql = "";
    private $_last_sql  = "";
    public  $_host      = "localhost";
    public  $_username  = "root";
    public  $_password  = "hello";
    public  $_db                 ;
    public  $_db_name   = "lsapp";
    public  $_result;
    public  $_fields    = array();
    public  $_where     = array();
    public  $_field_list= array();
    public  $_error     = "";
    
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
    
    public function __get($name){
        return $name;
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
        $data = $this->_result->fetch_all(MYSQLI_ASSOC);
        $this->_result->free();
        return $data ;
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
        $this->query();
        return $this;
    }
    
    
    
    public function find($id=""){
        
        if($id){
            $data = $this->get($id)->row();
        }else{
            $data = $this->all()->query()->row();
        }
        
        if(!$data){
            $this->_error = "No data found";
            return $this;
        }
        
        foreach($this->_field_list as $field){
            $this->$field = $data->$field;
        }
        return $this;
    }
    
    public function findall(){
       return $this->all()->query()->data();
    }
    
    public function findall_object(){
        $this->all()->query();
        $all_data = array();
        while($data = $this->_result->fetch_object()){
            $all_data[] = $data;
        }
        $this->_result->free();
        return $all_data;
    }
    
    public function findall_orm(){
        $this->all()->query();
        $temp_orm = $this;
        $all_data = array();
        while($data = $this->_result->fetch_object()){
            foreach($this->_field_list as $field){
                $temp_orm->$field = $data->$field;
            }
            $all_data[] = $temp_orm;
        }
        //$this->_result->free();
        return $all_data;
    }
    
    public function query($sql='',$resultmode = MYSQLI_STORE_RESULT){
        if(!$sql)
            $sql = $this->_sql;
        $where_sql = '';
        if($this->_where){
            foreach($this->_where as $key=>$value){
                $where_sql .= " `$key`='$value' AND ";                
            }
            $where_sql  = " WHERE ".$where_sql."1";
            $this->_where_sql = $where_sql;
        }
        
        $sql .= $where_sql; 
        
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
           $this->where("id",$this->_id);
           $this->query();
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
       $this->query();
       $this->_id   = $this->insert_id();
       $this->id    = $this->_id;
       return $this; 
    }
    
    public function delete($id=''){
        if(!$id)
            $id = $this->_id;
        $this->_sql = "Delete FROM {$this->_table} ";
        $this->where("id",$id);
        $this->query();
        $this->id = '';
        $this->_id = '';
        return $this;
    }
    
    public function where($name,$value){
        if(!is_array($name)){
            $this->_where[$name] = $value;            
        }else{
            $this->_where = $name;
        }   
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

$data = $orm->where("title", "Farhad")->findall_orm();

print_r($data);





 