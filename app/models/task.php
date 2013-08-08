<?php

class Task {
	var $db;
	var $f3;
	
	function __construct()
	{
		$this->f3 = \Base::instance();
		$this->db = $this->f3->get('db');
	}
    function get()
    {}  
    function post()
    {
    	// First verify this tasklist matches the boardnumber
        
        $sql = "SELECT tasklists.id
                FROM tasklists
                INNER JOIN boards ON boards.id=tasklists.boardid
                WHERE tasklists.id=:id AND boards.name=:bname";

        $params = array(
                        ':id'=>$this->f3->get('PARAMS.tasklistid'),
                        ':bname'=>$this->f3->get('PARAMS.boardcode'));
        $rows = $this->db->exec($sql,$params);
        
        if(count($rows)>0)
        {
            //Let's update the db!
            $json_data =  json_decode($this->f3->get('BODY'),true);
            $sql = "INSERT INTO tasks (tasklistid,taskname) VALUES (:id,:taskname)";
            $params = array(':taskname'=>$json_data['taskname'],':id'=>$this->f3->get('PARAMS.tasklistid'));
            $rows = $this->db->exec($sql,$params);
            if($rows>0)
            {
                $id = $this->db->lastInsertId();
                $json_data = array('id'=>$id);
                header("HTTP/1.0 200 OK");
                echo utf8_encode(json_encode($json_data));
            }
            else header("HTTP/1.0 402 Request Failed");            
        }
        else
        {
            header("HTTP/1.0 400 Bad Request");
        }
    }
    function put() 
    {
        // First verify this tasklist matches the boardnumber
        
        $sql = "SELECT tasklists.id
                FROM tasklists
                INNER JOIN boards ON boards.id=tasklists.boardid
                WHERE tasklists.id=:id AND boards.name=:bname";

        $params = array(
                        ':id'=>$this->f3->get('PARAMS.tasklistid'),
                        ':bname'=>$this->f3->get('PARAMS.boardcode'));
        $rows = $this->db->exec($sql,$params);
        
        if(count($rows)>0)
        {
            //Let's update the db!
            $json_data =  json_decode($this->f3->get('BODY'),true);
            $sql = "UPDATE tasklists SET listname=:listname WHERE id=:id";
            $params = array(':listname'=>$json_data['listname'],':id'=>$this->f3->get('PARAMS.tasklistid'));
            $rows = $this->db->exec($sql,$params);
            if($rows>0) header("HTTP/1.0 200 OK");
            else header("HTTP/1.0 402 Request Failed");            
        }
        else
        {
            header("HTTP/1.0 400 Bad Request");
        }
        
        
        //echo $this->f3->get('PARAMS.boardcode');
    }
    function delete() 
    {
        // First verify this tasklist matches the boardnumber
        
        $sql = "SELECT tasklists.id
                FROM tasklists
                INNER JOIN boards ON boards.id=tasklists.boardid
                WHERE tasklists.id=:id AND boards.name=:bname";

        $params = array(
                        ':id'=>$this->f3->get('PARAMS.tasklistid'),
                        ':bname'=>$this->f3->get('PARAMS.boardcode'));
        $rows = $this->db->exec($sql,$params);
        
        if(count($rows)>0)
        {
            //Let's update the db!
            $json_data =  json_decode($this->f3->get('BODY'),true);
            $sql = "DELETE FROM tasks WHERE id=:id AND tasklistid=:tasklistid LIMIT 1";
            $params = array(':id'=>$json_data['taskid'],':tasklistid'=>$this->f3->get('PARAMS.tasklistid'));
            $rows = $this->db->exec($sql,$params);
            if($rows>0) header("HTTP/1.0 200 OK");
            else header("HTTP/1.0 402 Request Failed");            
        }
        else
        {
            header("HTTP/1.0 400 Bad Request");
        }
    }
}

?>