<?php
require_once("ShingekiAPIBaseClass2.php");
$err_msg = "";

class ShingekiAPI_Room extends ShingekiAPIBaseClass2 {
	protected $shingeki_dir = "/tmp/shingeki";
	protected $room_dir = "/tmp/shingeki/room";

	function username2Id($userlist, $username) {
		foreach($userlist as $key => $val) {
			if ($username == $val) {
				return $key;
			}
		}
		return -1;
	}
	
	function userlist_file_path() {
		return $this->shingeki_dir."/userlist";
	}
	
	function get_userlist() {
		$userlist_file = $this->userlist_file_path();

    	$line = file($userlist_file);
    	$userlist = array();
    	for ($i = 0; $i < count($line); $i++) {
		    $data = split("\t", trim($line[$i]));
		    if ($data[0] == "") break;
		    $userlist[$data[0]] = $data[1];
		}
		return $userlist;
	}
	
	function getId2Username($id) {
		$userlist = $this->get_userlist();
		if (isset($userlist[$id])) {
			return $userlist[$id];
		}
		return "";
	}
	
	function get_room_list() {
		$room_array = array();
		$open_dir = opendir($this->room_dir);
		while(false !== ($file_name = readdir($open_dir))){
	        if ($file_name != "." && $file_name != "..") {
	        	$room_info = array();
	        	$room_info['id'] = intval($file_name);
	        	$line = file($this->room_dir."/".$file_name);
	        	for ($i = 0; $i < count($line); $i++) {
				    $data = split("\t", trim($line[$i]));
				    if ($data[0] == 'owner') {
						$room_info['owner'] = $data[1];
					    break;
				    }
				}
				$room_array[] = $room_info;
	        }
		}
		return $room_array;
	}
	
	function get_create_roomId($username) {
		$room_array = $this->get_room_list();
		$roomid = -1;
		foreach($room_array as $key => $val) {
			if ($username == $val['owner']) {
				return $val['id'];
			}
			if ($roomid < $val['id']) {
				$roomid = $val['id'];
			}
		}
		return $roomid + 1;
	}

	// act:login
	function login() {
		$room_dir = $this->room_dir;
		$userlist_file = $this->userlist_file_path();
		$userlist = $this->get_userlist($userlist_file);
		$username = $this->pPost['userName'];
		$userid = $this->username2Id($userlist, $username);
		if ($userid == -1) {
			$userid = count($userlist);
			$userlist[$userid] = $username;
			$fp_w = fopen($userlist_file, "w");
			if ($fp_w) {
				if (flock($fp_w, LOCK_EX)) {
				    ftruncate($fp_w, 0);
					foreach ($userlist as $key => $val) {
				    	fwrite($fp_w, $key . "\t" . $val .  "\n");
				    }
				    fflush($fp_w);
				    flock($fp_w, LOCK_UN);
				} else {
				    die("file lock error.");
				}
				fclose($fp_w);
			} else {
				die("file open error.");
			}
		}
		$output = array();
		$output['id'] = intval($userid);
		$output['name'] = $username;
		print_r(json_encode($output));
	}

	// act:create_room
	function create_room() {
		$userid = $this->pPost['userId'];
		$username = $this->getId2Username($userid);

		$room_dir = $this->room_dir;
		$roomid = $this->get_create_roomId($username);
		$file_path = $this->room_dir."/".$roomid;
		$fp = fopen($file_path, "w");
		if ($fp) {
			fwrite($fp, "owner"."\t".$username."\n");
			fclose($fp);
		} else {
			die("fopen err");
		}
		$output = array();
		$output['ownerId'] = intval($userid);
		$output['roomId'] = $roomid;
		$userdata = array();
		$userdata['userId'] = intval($userid); 
		$userdata['userName'] = $username; 
		$output['users'] = array($userdata);
		print_r(json_encode($output));
	}
	
	// act:room_list
	function room_list() {
		$room_dir = $this->room_dir;
		if (!file_exists($room_dir)) {
			if (!mkdir($room_dir, 0755, true)) {
				die("mkdir error.");
			}
		}
		$room_array = $this->get_room_list();
		$output = array();
		$output['rooms'] = $room_array;
		print_r(json_encode($output));
	}
	
	// act:room_status
	function room_status() {
		
	}

	function parseInputData() {
		if (isset($this->pPost['act'])) {
			switch($this->pPost['act']) {
				case "login":
					$this->login();
					break;
				case "create_room":
					$this->create_room();
					break;
				case "room_list":
					$this->room_list();
					break;
				case "room_status":
					$this->room_status();
					break;
				default:
					$this->err_msg = "act not found.";
					header('HTTP', true, 400);
					return false;
			}
		} else {
			$this->err_msg = "act not found.";
			header('HTTP', true, 400);
			return false;
		}
		return true;
	}
	
	public function execute() {
		$this->convertInputJson();
		if (!$this->parseInputData()) {
			$this->outputError();
			return;
		}
	}
}

$shingekiAPI_Room = new ShingekiAPI_Room();
$shingekiAPI_Room->execute();