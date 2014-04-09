<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/*
utility functions
*/
class AuthUtilities
{

/*
connect to the database
having this as a separate function makes changing
the database easier
*/
public static function dbconnect()
{
	if (!class_exists("Database")){
		include(dirname(__FILE__) . "/../lib/Database.php");
	}
	$dbc = Database::pDataConnect();
	return $dbc;
}

public static function guesspath()
{
	$path = "";
	$found = False;
	$uri = $_SERVER["REQUEST_URI"];
	$tmp = explode("?",$uri);
	if (count($tmp) > 1) $uri = $tmp[0];
	foreach(explode("/",$uri) as $x){
		if (strpos($x,".php") === False
			&& strlen($x) != 0){
			$path .= "../";
		}
		if (!$found && stripos($x,"fannie") !== False){
			$found = True;
			$path = "";
		}
		
	}
	return $path;
}

public static function init_check()
{
    return file_exists(dirname(__FILE__) . '/init.php');
}

/*
checking whether a string is alphanumeric is
a good idea to prevent sql injection
*/
public static function isAlphanumeric($str)
{
  if (preg_match("/^\\w*$/",$str) == 0){
    return false;
  }
  return true;
}

public static function isEmail($str)
{
	if (!preg_match('/@.+\./', $str)) return false;
	return filter_var($str,FILTER_VALIDATE_EMAIL);
}

public static function getUID($name)
{
  $sql = self::dbconnect();
  $name = $sql->escape($name);
  $fetchP = $sql->prepare_statement("select uid from Users where name=?");
  $fetchR = $sql->exec_statement($fetchP,array($name));
  if ($sql->num_rows($fetchR) == 0){
    return false;
  }
  $uid = $sql->fetch_array($fetchR);
  $uid = $uid[0];
  return $uid;
}

public static function getRealName($name)
{
  $sql = self::dbconnect();
  $name = $sql->escape($name);
  $fetchP = $sql->prepare_statement("select real_name from Users where name=?");
  $fetchR = $sql->exec_statement($fetchP,array($name));
  if ($sql->num_rows($fetchR) == 0){
    return false;
  }
  $rn = $sql->fetch_array($fetchR);
  $rn = $rn[0];
  return $rn;
}

public static function getOwner($name)
{
	$sql = self::dbconnect();
	$name = $sql->escape($name);
	$fetchP = $sql->prepare_statement("select owner from Users where name=?");
	$fetchR = $sql->exec_statement($fetchP,array($name));
	if ($sql->num_rows($fetchR) == 0)
		return false;
	return array_pop($sql->fetch_array($fetchR));
}

public static function getGID($group)
{
  if (!isAlphaNumeric($group))
    return false;
  $sql = self::dbconnect();

  $gidP = $sql->prepare_statement('SELECT git FROM userGroups WHERE name=?');
  $gidR = $sql->exec_statement($gidP, array($group));

  if ($sql->num_rows($gidR) == 0)
    return false;

  $row = $sql->fetch_array($gidR);
  return $row[0];
}

public static function genSessID()
{
  $session_id = '';
  srand(time());
  for ($i = 0; $i < 50; $i++){
    $digit = (rand() % 35) + 48;
    if ($digit > 57){
      $digit+=7;
    }
    $session_id .= chr($digit);
  }
  return $session_id;
}

public static function doLogin($name)
{
	$session_id = self::genSessID();	

	$sql = self::dbconnect();
	$name = $sql->escape($name);
	$sessionP = $sql->prepare_statement("update Users set session_id = ? where name=?");
	$sessionR = $sql->exec_statement($sessionP,array($session_id,$name));

	$session_data = array("name"=>$name,"session_id"=>$session_id);
	$cookie_data = serialize($session_data);

	setcookie('is4c-web',base64_encode($cookie_data),0,'/');
}

public static function syncUserLDAP($name,$uid,$fullname)
{
	$currentUID = self::getUID($name);
	$sql = self::dbconnect();

	if (!$currentUID){
		$addP = $sql->prepare_statement("INSERT INTO Users 
			(name,password,salt,uid,session_id,real_name)
			VALUES (?,'','',?,'',?)");
		$sql->exec_statement($addP,array($name,$uid,$realname));
	}
	else {
		$upP1 = $sql->prepare_statement("UPDATE Users SET real_name=?
				WHERE name=?");
		$sql->exec_statement($upP1,array($realname,$name));
	}
}

public static function table_check()
{
	$sql = self::dbconnect();
	if (!$sql->table_exists('Users')){
		$sql->query("CREATE TABLE Users (
			name varchar(50) NOT NULL,
			password varchar(50),
			salt varchar(10),
			uid int NOT NULL AUTO_INCREMENT,
			session_id varchar(50),
			real_name varchar(75),
			owner int,
			PRIMARY KEY (name),
			INDEX (uid)
			)");
	}
	if (!$sql->table_exists('userPrivs')){
		$sql->query("CREATE TABLE userPrivs (
			uid varchar(4),
			auth_class varchar(50),
			sub_start varchar(50),
			sub_end varchar(50)
			)");
	}
	if (!$sql->table_exists('userGroups')){
		$sql->query("CREATE TABLE userGroups (
			gid int,
			name varchar(50),
			username varchar(50)
			)");
	}
	if (!$sql->table_exists('userGroupPrivs')){
		$sql->query("CREATE TABLE userGroupPrivs (
			gid int,
			auth varchar(50),
			sub_start varchar(50),
			sub_end varchar(50)
			)");
	}
}

}

?>
