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
                        ORDER BY listid ASC,taskid ASC";
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
    function post()
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
    function put() {}
    function delete() {}
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