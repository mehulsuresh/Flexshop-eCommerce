<?php
error_reporting(E_ALL ^ E_WARNING); 
require_once("config.php");

class Sessions{
	
	protected $lifetime;
	private $conn;
	public function __construct()
    {
        // Constructor's functionality here, if you have any.
    }

	
	function Sessions() 
	{
		$this->lifetime = 60*120;

		session_set_save_handler(
			array(&$this,"open"),
			array(&$this,"close"),
			array(&$this,"read"),
			array(&$this,"write"),
			array(&$this,"destroy"),
			array(&$this,"gc")
		);		
	}
	
	public function open()
	{
		if($this->conn = mysqli_connect(HOST,USER,PW)) {	
			$res = mysqli_select_db(DB, $this->conn);
			$this->gc();
			return $res;
		}
	}
	
	public function close()
	{
		mysqli_close($this->conn);
		return true;
	}
	
	public function read($session_id)
	{
		$time=time();

		$sql="SELECT data FROM sessions WHERE sid = '".$session_id."' AND sexpire > ".$time;

		if($result = mysqli_query($sql, $this->conn)){
			if(mysqli_num_rows($result)){
				$record = mysqli_fetch_assoc($result);
				$_SESSION['username'] = $record['data'];
			}
		}
		return '';
	}
	
	public function write($session_id)
	{
		$time = time()+$this->lifetime;
		
		if(!isset($_SESSION['username']))
			return false;

		$data = $_SESSION['username'];
		$sql = "REPLACE INTO sessions VALUES (\"".$session_id."\",\"".$data."\",\"".$time."\")";

		return mysqli_query($sql,$this->conn) or die (mysqli_error());
	}
	
	public function destroy($session_id)
	{

		$sql = "DELETE FROM sessions WHERE sid ='".$session_id."'";
		mysqli_query($sql,$this->conn) or die (mysqli_error());

		return true;
	}
	
	public function gc()
	{
		$time = time();

		if(!mysqli_query("LOCK TABLES cart WRITE, sessions WRITE",$this->conn))
			return mysqli_error();

		
		$sql = "DELETE FROM sessions WHERE sexpire < ".$time;
		if(!mysqli_query($sql,$this->conn)) {
			return mysqli_error();
		}

		$sql = "DELETE FROM cart WHERE sid NOT IN (SELECT sdata FROM sessions)";
		if(!mysqli_query($sql,$this->conn)) {
			return mysqli_error(); 
		}
				
		if(mysqli_query("UNLOCK TABLES",$this->conn)) {
			return true;
		}
	}
}
?>
