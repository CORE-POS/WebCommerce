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
this file contains user authentication-related functions
all functions return true on success, false on failure
unless otherwise noted
*/

if (!class_exists('AuthUtilities')) {
    include_once(dirname(__FILE__) . '/AuthUtilities.php');
}
if (!class_exists('AuthGroup')) {
    include_once(dirname(__FILE__) . '/AuthGroup.php');
}

class AuthLogin
{

/*
a user is logged in using cookies
when a user logs in, a cookie name 'session_data' is created containing
two pieces of data: the user's name and a session_id which is
a 50 character random string of digits and capital letters
to access this data, unserialize the cookie's value and use
keys 'name' and 'session_id' to access the array
because this static public function sets a cookie, nothing before this function
call can produce output
*/
static public function login($name,$password,$testing=false)
{
  $name = AuthUtilities::isEmail($name);
  if (!$name){
    return false;
  }

  $sql = AuthUtilities::dbconnect();
  $gatherP = $sql->prepare_statement("select password,salt from Users where name=?");
  $gatherR = $sql->exec_statement($gatherP,array($name));
  if ($sql->num_rows($gatherR) == 0){
    return false;
  }
  
  $gatherRow = $sql->fetch_array($gatherR);
  $crypt_pass = $gatherRow[0];
  $salt = $gatherRow[1];
  if (crypt($password,$salt) != $crypt_pass){
    return false;
  }

  if (!$testing)
    AuthUtilities::doLogin($name);

  return true;
}

/* login using an ldap server 
 * 
 * Constants need to be defined:
 * $LDAP_HOST => hostname or url of ldap server
 * $LDAP_PORT => ldap port on server
 * $LDAP_BASE_DN => DN to search for users
 * $LDAP_SEARCH_FIELD => entry containing the username
 *
 * Optional constants for importing LDAP users
 * into SQL automatically:
 * $LDAP_UID_FIELD => entry containing the user ID number
 * $LDAP_FULLNAME_FIELDS => entry or entries containing
			    the user's full name
 *
 * Tested against openldap 2.3.27
 */
static public function ldap_login($name,$passwd)
{
	$LDAP_HOST = "locke.wfco-op.store";
	$LDAP_PORT = 389;
	$LDAP_BASE_DN = "ou=People,dc=wfco-op,dc=store";
	$LDAP_SEARCH_FIELD = "uid";

	$LDAP_UID_FIELD = "uidnumber";
	$LDAP_FULLNAME_FIELDS = array("cn");

	$conn = ldap_connect($LDAP_HOST,$LDAP_PORT);
	if (!$conn) return false;

	$search_result = ldap_search($conn,$LDAP_BASE_DN,
				     $LDAP_SEARCH_FIELD."=".$name);
	if (!$search_result) return false;

	$ldap_info = ldap_get_entries($conn,$search_result);
	if (!$ldap_info) return false;

	$user_dn = $ldap_info[0]["dn"];
	$uid = $ldap_info[0][$LDAP_UID_FIELD][0];
	$fullname = "";
	foreach($LDAP_FULLNAME_FIELDS as $f)
		$fullname .= $ldap_info[0][$f][0]." ";
	$fullname = rtrim($fullname);

	if (ldap_bind($conn,$user_dn,$passwd)){
		AuthUtilities::syncUserLDAP($name,$uid,$fullname);	
		AuthUtilities::doLogin($name);
		return true;
	}
	return false;
}

/*
sets a cookie.  nothing before this static public function call can have output
*/
static public function logout()
{
  $name = self::checkLogin();
  if (!$name){
    return true;
  }
  setcookie('is4c-web','',0,'/');
  unset($_COOKIE['is4c-web']);
  return true;
}

/*
logins are stored in a table called users
information in the table includes an alphanumeric
user name, an alphanumeric password (stored in crypted form),
the salt used to crypt the password (time of user creation),
and a unique user-id number between 0001 and 9999
a session id is also stored in this table, but that is created
when the user actually logs in
*/
static public function createLogin($name,$password,$fn="",$owner=0)
{
  AuthUtilities::table_check();

  $sql = AuthUtilities::dbconnect();
  $checkP = $sql->prepare_statement("select name from Users where name=?");
  $checkR = $sql->exec_statement($checkP,array($name));
  if ($sql->num_rows($checkR) != 0){
    return false;
  }
  
  $salt = time();
  $crypt_pass = crypt($password,$salt);
  
  $addP = $sql->prepare_statement("insert into Users (name,password,salt,real_name,owner) 
		values (?,?,?,?,?)");
  $addR = $sql->exec_statement($addP,array($name,$crypt_pass,$salt,$fn,$owner));
  if ($addR === false) return false;

  return true;
}

static public function deleteLogin($name){
  if (!AuthUtilities::isAlphanumeric($name)){
    return false;
  }
  
  if (!self::validateUser('admin')){
    return false;
  }

  $sql=AuthUtilities::dbconnect();
  $uid = AuthUtilities::getUID($name);
  $delP = $sql->prepare_statement("delete from userPrivs where uid=?");
  $delR = $sql->exec_statement($delP,array($uid));

  $deleteP = $sql->prepare_statement("delete from Users where name=?");
  $deleteR = $sql->exec_statement($deleteP,array($name));

  $groupP = $sql->prepare_statement("DELETE FROM userGroups WHERE name=?");
  $groupR = $sql->exec_statement($groupP,array($name));

  return true;
}

/* 
this static public function returns the name of the logged in
user on success, false on failure
*/
static public function checkLogin()
{
  if (AuthUtilities::init_check())
    return 'init';

  if (!isset($_COOKIE['is4c-web'])){
    return false;
  }

  $cookie_data = base64_decode($_COOKIE['is4c-web']);
  $session_data = unserialize($cookie_data);

  $name = $session_data['name'];
  $session_id = $session_data['session_id'];

  if (!AuthUtilities::isEmail($name) or !AuthUtilities::isAlphanumeric($session_id)){
    return false;
  }

  $sql = AuthUtilities::dbconnect();
  $checkP = $sql->prepare_statement("select * from Users where name=? and session_id=?");
  $checkR = $sql->exec_statement($checkP,array($name,$session_id));

  if ($sql->num_rows($checkR) == 0){
    return false;
  }

  return $name;
}

static public function showUsers()
{
  if (!self::validateUser('admin')){
    return false;
  }
  echo "Displaying current users";
  echo "<table cellspacing=2 cellpadding=2 border=1>";
  echo "<tr><th>Name</th><th>User ID</th></tr>";
  $sql = AuthUtilities::dbconnect();
  $usersQ = $sql->prepare_statement("select name,uid from Users order by name");
  $usersR = $sql->exec_statement($usersQ);
  while ($row = $sql->fetch_array($usersR)){
    echo "<tr>";
    echo "<td>$row[0]</td>";
    echo "<td>$row[1]</td>";
    echo "</tr>";
  }
  echo "</table>";
}

/* 
this static public function uses login to verify the user's presented
name and password (thus creating a new session) rather
than using self::checkLogin to verify the correct user is
logged in.  This is nonstandard usage.  Normally self::checkLogin
should be used to determine who (if anyone) is logged in
(this way users don't have to constantly provide passwords)
However, since the current password is provided, checking
it is slightly more secure than checking a cookie
*/
static public function changePassword($name,$oldpassword,$newpassword)
{
  $sql = AuthUtilities::dbconnect();
  if (!self::login($name,$oldpassword,true)){
    return false;
  }

  $salt = time();
  $crypt_pass = crypt($newpassword,$salt);

  $name = $sql->escape($name);
  $updateP = $sql->prepare_statement("update Users set password=?,salt=? where name=?");
  $updateR = $sql->exec_statement($updateP,array($crypt_pass,$salt,$name));
  
  return true;
}

static public function changeAnyPassword($name,$newpassword)
{
  $salt = time();
  $crypt_pass = crypt($newpassword,$salt);

  $sql = AuthUtilities::dbconnect();
  $name = $sql->escape($name);
  $updateP = $sql->prepare_statement("update Users set password=?,salt=? where name=?");
  $updateR = $sql->exec_statement($updateP,array($crypt_pass,$salt,$name));

  return true;
}

/*
this static public function is here to reduce user validation checks to
a single static public function call.  since this task happens ALL the time,
it just makes code cleaner.  It returns the current user on
success just because that information might be useful  
*/
static public function validateUser($auth,$sub='all')
{
     if (AuthUtilities::init_check())
	return 'init';

     $current_user = self::checkLogin();
     if (!$current_user){
       echo "You must be logged in to use this function";
       return false;
     }

     $groupPriv = AuthGroup::checkGroupAuth($current_user,$auth,$sub);
     if ($groupPriv){
       return $current_user;
     }

     $priv = AuthPriv::checkAuth($current_user,$auth,$sub);
     if (!$priv){
       echo "Your account doesn't have permission to use this function";
       return false;
     }
     return $current_user;
}

static public function validateUserQuiet($auth,$sub='all')
{
     if (AuthUtilities::init_check())
	return 'init';

     $current_user = self::checkLogin();
     if (!$current_user){
       return false;
     }

     $groupPriv = AuthGroup::checkGroupAuth($current_user,$auth,$sub);
     if ($groupPriv){
       return $current_user;
     }

     $priv = AuthPriv::checkAuth($current_user,$auth,$sub);
     if (!$priv){
       return false;
     }
     return $current_user;
}

// re-sets expires timer on the cookie if the
// user is currently logged in
// must be called prior to any output
static public function refreshSession(){
  if (!isset($_COOKIE['is4c-web']))
    return false;
  setcookie('is4c-web',$_COOKIE['is4c-web'],time()+(60*40),'/');
  return true;
}

static public function pose($username){
	if (!isset($_COOKIE['is4c-web']))
		return false;
	if (!AuthUtilities::isAlphanumeric($username))
		return false;

	$cookie_data = base64_decode($_COOKIE['is4c-web']);
	$session_data = unserialize($cookie_data);

	$session_id = $session_data['session_id'];

	$sql = AuthUtilities::dbconnect();
	$sessionP = $sql->prepare_statement("update Users set session_id = ? where name=?");
	$sessionR = $sql->query($sessionP,array($session_id,$username));

	$session_data = array("name"=>$username,"session_id"=>$session_id);
	$cookie_data = serialize($session_data);

	setcookie('is4c-web',base64_encode($cookie_data),time()+(60*40),'/');

	return true;
}

}

?>
