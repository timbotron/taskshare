<?php

class Board {
	var $db;
	var $f3;
	
	function __construct()
	{
		$this->f3 = \Base::instance();
		$this->db = $this->f3->get('db');
	}
    function get()
    {
        $sql = "SELECT  tasklists.id as listid,
                        tasklists.listname,
                        tasks.id as taskid,
                        tasks.taskname 
                        FROM boards
                        INNER JOIN tasklists ON tasklists.boardid=boards.id
                        LEFT JOIN tasks ON tasks.tasklistid=tasklists.id
                        WHERE boards.name=?
                        ORDER BY listid ASC,taskid DESC";
        $results = $this->db->exec($sql,$this->f3->get('PARAMS.boardcode'));

        $json_array = array();

        if(count($results)>0) //is at least 1 list
        {
            $temp_array = array();

            foreach($results as $result)
            {
                if(isset($temp_array['listid']) && $result['listid']==$temp_array['listid'])
                {
                    // still in same list
                    $supertemp_array = array();
                    $supertemp_array['taskid'] = $result['taskid'];
                    $supertemp_array['taskname'] = $result['taskname'];
                    array_push($temp_array['listitem'], $supertemp_array);
                    unset($supertemp_array);
                }
                else
                {
                    // we've hit a new list!
                    if(count($temp_array)>0) array_push($json_array, $temp_array);
                    $temp_array = array();
                    $temp_array['listid'] = $result['listid'];
                    $temp_array['listname'] = $result['listname'];
                    $temp_array['listitem'] = array();
                    if(isset($result['taskid']))
                    {
                        $supertemp_array = array();
                        $supertemp_array['taskid'] = $result['taskid'];
                        $supertemp_array['taskname'] = $result['taskname'];
                        array_push($temp_array['listitem'], $supertemp_array);
                        unset($supertemp_array);
                    }
                }
            }
            // push the last list onto array
            if(count($temp_array)>0) array_push($json_array, $temp_array);
        }

        if($this->f3->get('internal')===true)
        {
            $this->f3->set('internal',false);
            return $json_array;
        } 
        else echo utf8_encode(json_encode($json_array));
        
    }

    function new_board()
    {
    	if(isset($_POST['board_name'])) $new_name = $_POST['board_name']; // for future, when users can create a specifically-named board
    	else $new_name = bin2hex(openssl_random_pseudo_bytes(4)); 

    	$rows = $this->db->exec("SELECT id FROM boards WHERE name=?",$new_name);

    	while (count($rows) != 0) // loops until it generates a name that isn't in use
    	{
    		$new_name = bin2hex(openssl_random_pseudo_bytes(4)); 
    		$rows = $this->db->exec("SELECT id FROM boards WHERE name=?",$new_name);
    	}

    	if($this->db->exec("INSERT INTO boards (name) VALUES (?)",$new_name))
    	{
    		$this->f3->reroute('/b/'.$new_name);
    	}
    	else $this->f3->reroute('/error/1000'); //TODO make an error handler page.
    	//var_dump($this->db);
    }

    function post()
    {
        $rows = $this->db->exec("SELECT id FROM boards WHERE name=?",$this->f3->get('PARAMS.boardcode'));

        $boardid=$rows[0]['id'];

        if(count($rows)==0)
        {
            // there wasn't a board
            // TODO error this
        }
        $rows = $this->db->exec("INSERT INTO tasklists (boardid, listname) VALUES (?,'New List')",$boardid);

        if($rows>0)
            {
                $id = $this->db->lastInsertId();
                $json_data = array('id'=>$id);
                header("HTTP/1.0 200 OK");
                echo utf8_encode(json_encode($json_data));
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
    }

    function delete()
    {
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
            
            $this->db->begin();

            $sql = "DELETE FROM tasks WHERE tasklistid=:id";
            $params = array(':id'=>$this->f3->get('PARAMS.tasklistid'));
            $taskrows = $this->db->exec($sql,$params);

            $sql = "DELETE FROM tasklists WHERE id=:id";
            $params = array(':id'=>$this->f3->get('PARAMS.tasklistid'));
            $rows = $this->db->exec($sql,$params);

            $this->db->commit();

            
            if($rows>0) header("HTTP/1.0 200 OK");
            else header("HTTP/1.0 402 Request Failed");            
        }
        else
        {
            header("HTTP/1.0 400 Bad Request");
        }
    }

    function loadboard()
    {
        $this->f3->set('internal',true);
        $this->f3->set('task_array',$this->get());
        $this->f3->set('board_code',$this->f3->get('PARAMS.boardcode'));
        //print_r($board_array);
        echo View::instance()->render('main.html');
    }
}

?>