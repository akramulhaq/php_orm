<?php


class Orm extends mysqli {
    private $table   = "users";
    private $sql     = "";
    public  $host    = "localhost";
    public  $username= "root";
    public  $password= "hello";
    public  $db      = "lsapp";
    public  $result;
    public  $fields=array();
    public  $field_list=array();
    public  $error   = "";
    
    public function __construct(){
        parent::__construct($this->host,$this->username,$this->password,$this->db);
        $this->init();
    }
    
    public function init(){
        if($this->columns()->get()->result()){
            while($column   = $this->result->fetch_assoc())
            {    
                
                $column_name        = $column['column_name'];
                unset($column['column_name']);
                $column['value']    = '';
                $this->fields[$column_name] = $column;
                $this->field_list[] = $column_name;
            }
        }
        //$this->fields = $this->columns()->get()->result()->fetch_all(MYSQLI_ASSOC);
        return $this;
    }
    
    public function change_db($database){
        $this->db = $database;
        $this->select_db($database);
    }
    
    public function __set($name, $value)
    {
        if(in_array($name,$this->field_list)){
            $this->$name = $value;
            $this->fields[$name]['value'] = $value;
        }else{
            $this->error = "Column not found";
            return false;
        }
    }
    
    public function get(){
        if(!$this->sql){
            $this->get_all();
        }
        $this->query($this->sql);
        return $this;
    }
    
    public function get_all(){
        $this->sql = "SELECT * FROM {$this->table}";
        return $this;
    }
    
    public function query($sql='',$resultmode = MYSQLI_USE_RESULT){
        if(!$sql)
            $sql = $this->sql;
        $this->result = parent::query($sql,$resultmode);
        if(!$this->result){
            die("Error Occured : ".$this->error);
        }
        return $this;
    }
    
    public function result(){
        return $this->result;
    }
    
    
    public function save(){
       $insert_sql = "INSERT INTO {$this->table} (`".implode('`,`',$this->field_list)."`) VALUES (" ;
       $this->sql  = $insert_sql;
       foreach($this->fields as $field){
           $this->sql .= "'{$field['value']}',";
       }
       $this->sql  = rtrim($this->sql,',');
       $this->sql .= ")";
       $this->query($this->sql);
       return $this; 
    }
    
    public function columns(){
        $this->sql = "SELECT  
                            column_name,
                            data_type,
                            column_key,
                            character_maximum_length length,
                            numeric_precision  int_length
                        FROM `INFORMATION_SCHEMA`.`COLUMNS` 
                        WHERE `TABLE_SCHEMA`='{$this->db}' 
                            AND `TABLE_NAME`='{$this->table}'";
        
        return $this;
    }
}

$orm = new Orm();

$orm->name  = "Md Ekramul Hoque12";
$orm->email = "akramul.haq5@gmail.com";
$orm->save();