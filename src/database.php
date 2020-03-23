<?php
/*
Class for working with SQLite3 database
Author: t.me/RavenFaus
*/
require_once('lib/user.php');

class database
{
	private $path;
	private $db;
	public function __construct($path)
	{
		$this->path = $path;
		$this->db = new SQLite3($path);
		$this->db->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY,
															is_bot INTEGER,
															is_admin INTEGER,
															first_name TEXT,
															last_name TEXT,
															username TEXT,
															lang TEXT,
															last_msg TEXT,
															last_msg_id INTEGER)");
    $this->db->exec("CREATE TABLE IF NOT EXISTS files(
															id INTEGER PRIMARY KEY AUTOINCREMENT,
															date INTEGER,
															chat_id INTEGER NOT NULL,
															file_id TEXT NOT NULL,
															name TEXT,
															type TEXT NOT NULL)");
	}

	public function find_files($chat_id, $request, $type='document')
	{
		$request = SQLite3::EscapeString($request);
		$type = SQLite3::EscapeString($type);
		return $this->db->query("SELECT * FROM files WHERE name
			LIKE '%".$request."%' AND type = '" . $type . "'");
	}

	public function add_file($chat_id, $file_id, $name, $type)
	{
		$stmt = $this->db->prepare("INSERT INTO files(date,chat_id,file_id,name,type)
		 VALUES(:date,:chat_id,:file_id,:name,:type)");
		$date = time();
		$stmt->bindParam(':date', $date, SQLITE3_INTEGER);
		$stmt->bindParam(':chat_id', $chat_id, SQLITE3_INTEGER);
		$stmt->bindParam(':file_id', $file_id, SQLITE3_TEXT);
		$stmt->bindParam(':name', $name, SQLITE3_TEXT);
		$stmt->bindParam(':type', $type, SQLITE3_TEXT);
		$stmt->execute();
		return $this->db->lastInsertRowID();
	}

	public function get_file_id($id, $chat_id)
	{
		$stmt = $this->db->prepare("SELECT file_id FROM files WHERE id = :id AND
			 chat_id = :chat_id");
		$stmt->bindParam(':id', $id, SQLITE3_INTEGER);
		$stmt->bindParam(':chat_id', $chat_id, SQLITE3_INTEGER);
		$r = $stmt->execute()->fetchArray();
		if (empty($r))
			return false;
		return $r['file_id'];
	}

	public function get_file($id, $chat_id)
	{
		$stmt = $this->db->prepare("SELECT id, file_id, name, type, date FROM files WHERE id = :id AND
			 chat_id = :chat_id");
		$stmt->bindParam(':id', $id, SQLITE3_INTEGER);
		$stmt->bindParam(':chat_id', $chat_id, SQLITE3_INTEGER);
		$r = $stmt->execute()->fetchArray();
		if (empty($r))
			return false;
		return $r;
	}

	public function update_filename($id, $name)
	{
		$sql = "UPDATE files SET name = '" . $name . "',
											date = " . time() . " WHERE id = " . $id;
		return $this->db->query($sql);
	}

	public function delete_file($id, $chat_id)
	{
		$this->db->query("DELETE FROM files WHERE id = " . $id . ' AND chat_id = ' . $chat_id);
	}

	public function erase_files($chat_id)
	{
		$this->db->query("DELETE FROM files WHERE chat_id = " . $chat_id);
	}

	public function get_files_count($chat_id, $type='')
	{
		$sql = "SELECT Count(id) AS count FROM files
			WHERE chat_id = " . $chat_id;
		if (!empty($type))
			$sql .= " AND type = '" . $type . "'";
		$r = $this->db->query($sql);
		return $r->fetchArray()['count'];
	}

	public function get_all($chat_id, $count, $offset = 0)
	{
		$r = $this->db->query("SELECT * FROM files
			WHERE chat_id = " . $chat_id .
			" ORDER BY date DESC LIMIT " . $count . ' OFFSET ' . $offset);
		return $r;
	}

	public function get_files($chat_id, $count, $offset = 0)
	{
		$r = $this->db->query("SELECT * FROM files
			WHERE chat_id = " . $chat_id . " AND type = 'document'
			 ORDER BY date DESC LIMIT " . $count . ' OFFSET ' . $offset);
		return $r;
	}

	public function get_photos($chat_id, $count, $offset = 0)
	{
		$r = $this->db->query("SELECT * FROM files
			WHERE chat_id = " . $chat_id . " AND type = 'photo'
			ORDER BY date DESC LIMIT " . $count . ' OFFSET ' . $offset);
		return $r;
	}

	public function add_user($user)
	{
		$this->db->query("INSERT INTO users(id,is_bot,is_admin,first_name,last_name,username,lang,last_msg) VALUES(
						".$user->id.", ".
						($user->is_bot ? '1' : '0') .", ".
						($user->is_admin ? '1' : '0').", ".
						"'".$user->first_name."', ".
						"'".$user->last_name."', ".
						"'".$user->username."', ".
						"'".$user->lang."', ".
						"'".$user->last_msg."')");
	}

	public function set_last_msg_id($id, $msg_id)
	{
		$this->db->query("UPDATE users SET last_msg_id = '" . $msg_id . "' WHERE id = " . $id);
	}

	public function get_last_msg_id($id)
	{
		return $this->db->query("SELECT last_msg_id FROM users WHERE id = " . $id)->fetchArray()['last_msg_id'];
	}

	public function get_last_msg($id)
	{
		$last_msg = $this->db->query("SELECT last_msg FROM users WHERE id = " . $id);
		return $last_msg->fetchArray()['last_msg'];
	}

	public function set_last_msg($id, $msg)
	{
		$this->db->query("UPDATE users SET last_msg = '".$msg."' WHERE id = " . $id);
	}

	public function get_users()
	{
		return $this->db->query("SELECT * FROM users");
	}

	public function user_exists($id)
	{
		$stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id");
		$stmt->bindParam(':id', $id, SQLITE3_INTEGER);
		$is_exists = $stmt->execute();
		if (empty($is_exists->fetchArray()))
			return false;
		return true;
	}

	public function add_admin($id)
	{
		$this->db->query("INSERT INTO users(id, is_bot, is_admin, lang) VALUES (".$id.", 0, 1, 'en')");
	}

	public function user_admin($id)
	{
		$is_exists = $this->db->query("SELECT is_admin FROM users WHERE id = " . $id . " and is_admin = 1");
		if (empty($is_exists->fetchArray()))
			return false;
		else return true;
	}
}
?>
