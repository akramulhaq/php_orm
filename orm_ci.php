<?php


class Orm  {
    public  $_table     = "";
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
    public  $_stmt;
    public  $_fields    = array();
    public  $_data      = array();
    public  $_where     = array();
    public  $_field_list= array();
    public  $_error     = "";
    public  $_validate  = false;
	private $_ci 	;
	private $_ci_db ;
	private $_affected_rows ;
    
    public function __construct($table=''){
        
        
		$this->_ci    	 = &get_instance();
		$this->_ci_db 	 = $this->_ci->load->database();
		
		$this->_host 	 = $this->_ci->db->hostname;
		$this->_username = $this->_ci->db->username;
		$this->_password = $this->_ci->db->password;
		$this->_db_name  = $this->_ci->db->database;
		
		$this->_db = new mysqli($this->_host,$this->_username,$this->_password,$this->_db_name);
        $this->_db->set_charset("utf8");
        if($table){
            $this->_table = $table;
            $this->init();
        }
       
        
    }
    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    
    public function __get($name)
	{
        return $name;
    }
    
	public function __call($name, $arguments)
    {
		$this->_ci->db->$name($arguments);
	}
	
    public function set($field,$value){
        $this->_fields[$field]['value'] = $value;
        $this->_fields[$field]['set']   = 1;
        
        $this->$field = $value;
        return $this;
    }
	
    public function id($id){
        $this->_id = $id;
        $this->id  = $id;
        return $this;
    }
    
    public function db(){
        return $this->_db;
    }
    public function table($table=""){
        $this->_table = $table;
        $this->init();
		return $this;
    }
    public function sql(){
        return $this->_sql;
    }
    public function affected_rows(){
		return $this->_affected_rows;
	}
	public function error(){
		return $this->_error;
	}
    public function all(){
        $this->_sql = "SELECT * FROM `{$this->_table}`";
        return $this;
    }
    
    
    public function result(){
        return $this->_result;
    }
    public function insert_id(){
        return $this->_stmt->insert_id;       
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
        if($this->columns()->query()->result()){
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
    
    
    public function find($id=""){
        
        if($id){
            $this->where("id",$id);
        }
        
        $data = $this->all()->execute()->row();
        $this->_data = $data;
        if(!$data){
            $this->_error = "No data found";
            return $this;
        }
        
        foreach($this->_field_list as $field){
            $this->$field = $data->$field;
        }
        $this->_id = $data->id;
        return $this;
    }
    
    
    
    public function findall(){
       return $this->all()->execute()->data();
    }
    
    public function findall_object(){
        $this->all()->execute();
        $all_data = array();
        while($data = $this->_result->fetch_object()){
            $all_data[] = $data;
        }
        $this->_result->free();
        return $all_data;
    }
    
    public function findall_orm(){
        $this->all()->execute();
        $temp_orm = $this;
        $all_data = array();
        while($data = $this->_result->fetch_object()){
            foreach($this->_field_list as $field){
                $temp_orm->$field = $data->$field;
            }
            $all_data[] = $temp_orm;
        }
        
        return $all_data;
    }
    
    
    
    public function query($sql='',$resultmode = MYSQLI_STORE_RESULT){
        if(!$sql)
            $sql = $this->_sql;
        $where_sql = '';
        if($this->_where){
            foreach($this->_where as $key=>$value){
                $value = $this->_db->escape_string($value);
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
       $fields_counter = count($this->_fields);
       $types = str_repeat("s",$fields_counter);
       $params = array($types);
       if(!$this->validation()){
           die($this->_error);
       }
       if($this->_id){
           
           if(!$this->id)
               $this->id = $this->_id;
           $this->_sql  = "UPDATE {$this->_table} set ";
           $this->_sql .= "`".implode("`=?,`",$this->_field_list).'`=?';
           $this->where("id",$this->_id);
           
           foreach($this->_fields as $field=>$field_data){
               $params[] = &$this->$field;
           } 
           
           $this->execute($params);
           
       }else{
           $this->id   = '';
           $insert_sql = "INSERT INTO {$this->_table} (`".implode('`,`',$this->_field_list)."`) VALUES (" ;
           
           $comma_bind  = str_repeat("?,",$fields_counter-1);
           $comma_bind .= "?)";
           $insert_sql .= $comma_bind;
           
           $this->_sql  = $insert_sql;
           foreach($this->_fields as $field=>$field_data){
               $params[] = &$this->$field;
           }
          
           $this->_where = array();    // no where query on insert
           $this->execute($params);
           $this->id  = $this->_stmt->insert_id;
           $this->_id = $this->_stmt->insert_id;
           
       }
       
       return $this; 
    }
    
    public function update_selected($req_where=false){
        
        if($req_where and !($this->_where)){
            $this->_error = "No where clause found";
            return $this;
        }
		
		
        $params  = array();
        $temp_field = array();
        $counter = 0;
        foreach($this->_fields as $field=>$field_data){
           
           if(!empty($field_data['set'])){
               $counter = $counter + 1;
               $params[]   = &$this->$field;
               $temp_field[] = $field;
           }
        }
        
        if(!$params){
            $this->_error = "No update parameter found";
            return $this;
        }
        
		if($this->id)	
			$this->where("id",$this->id);
		
        $types = str_repeat("s",$counter);
        array_unshift($params,$types); // put types in first parameter
       
        
        $this->_sql = "UPDATE {$this->_table} SET ". 
                    "`".implode("`=?,`",$temp_field)."`=?" ;
        $this->execute($params);            
        
        $this->_affected_rows =  $this->_db->affected_rows;
		return $this;
        
    } 
    
    
    public function execute($params=array()){
        
        if(!$params)
            $params[0] = '';
        
        $where_sql = '';
        if($this->_where){
            
            $where_index = array_keys($this->_where);
            $where_sql  = " WHERE `".implode("`=? AND `",$where_index).'`=?';
            $params[0] .= str_repeat("s",count($this->_where));   //$params[0] is type list            
            foreach($this->_where as $value){
                $params[] = &$value;
            } 
            $this->_where_sql = $where_sql;
        }
        
        
       $this->_sql .= $where_sql;
       if(!$stmt = $this->_db->prepare($this->_sql)){
           die($this->_sql."\n".$this->_db->error);
       }
       
       if($params[0]){ // if bind param found , on select query no types may be found.
           $ref_class    = new ReflectionClass('mysqli_stmt');
           $method       = $ref_class->getMethod("bind_param");
           $method->invokeArgs($stmt,$params);
       }
       $stmt->execute();
       
       if($stmt->error)
           die($this->_sql."\n".$stmt->error);
       
       $this->_result = $stmt->get_result();
       
       $this->_last_sql = $this->_sql;
       $this->_sql = '';
       
       $this->_stmt = $stmt;
	    
       return $this;
       
    }
    
    
    
    public function delete($id=''){
        if(!$id)
            $id = $this->_id;
        $this->_sql = "Delete FROM {$this->_table} ";
        $this->where("id",$id);
        $this->execute();
        $this->id = '';
        $this->_id = '';
        return $this;
    }
    
    public function where($name,$value){
        if(!is_array($name)){
            $this->_where[$name] = $value;            
        }else{
            foreach($name as $key=>$value)
                $this->_where[$key] = $value;
        }   
        return $this;
    }
    
    public function columns(){
        $this->_sql = "SELECT  
                            column_name,
                            data_type,
                            column_key,
                            character_maximum_length length,
                            numeric_precision  int_length,
                            is_nullable
                        FROM `INFORMATION_SCHEMA`.`COLUMNS` 
                        WHERE `table_SCHEMA`='{$this->_db_name}' 
                            AND `table_NAME`='{$this->_table}'";
        
        return $this;
    }
   
   
   public function validate($status=true){
        $this->_validate = true;
        return $this;
   }
   
   public function validation($config=array()){
        
        if(!$this->_validate)
            return true;
        $error = false;
        foreach($this->_fields as $key=>$field){
            if($key=="id")
                continue;
            $len = strlen($this->$key);
            if($field["is_nullable"] == "NO" AND (!$len)){
                $this->_error .= " \n $key can not be empty";
                $error = true;
            }
                
            
        } 
        return !$error; // if error occurred validation should be false;
        
   }
   
}
