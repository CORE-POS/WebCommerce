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
these functions manage user priviledges which are stored in
a table called userPrivs.  Records in the table specify a user id
number (uid), authorization class, and a sub-class start and end
authorization class will probably be like admin, addproducts,
updateproducts,editmembers, etc.  sub-class start and end are
in place to potentially add finer-grained control (subset of member
numbers, range of departments).  The standard (for now) will be
to set both start and end to 'all' if full access is desired.

Unless otherwise noted, functions return true on success
and false on failure
*/

class AuthPriv
{

static public function addAuth($name,$auth_class,$sub_start='all',$sub_end='all'){
  $sql = AuthUtilities::dbconnect();
  if (!AuthUtilities::isAlphanumeric($name) or !AuthUtilities::isAlphanumeric($auth_class) or
      !AuthUtilities::isAlphanumeric($sub_start) or !AuthUtilities::isAlphanumeric($sub_end)){
    return false;
  }
  $uid = AuthUtilities::getUID($name);
  if (!$uid){
    return $uid;
  }

  if (!validateUser('admin')){
    return false;
  }

  $addP = $sql->prepare_statement('INSERT INTO userPrivs VALUES (?, ?, ?, ?)');
  $addR = $sql->exec_statement($addP,array($uid,$auth_class,$sub_start,$sub_end));
  return true;
}

static public function deleteAuth($name,$auth_class){
  if (!AuthUtilities::isAlphanumeric($name) or !AuthUtilities::isAlphanumeric($auth_class)){
    return false;
  }
  
  if (!validateUser('admin')){
    return false;
  }

  $uid = AuthUtilities::getUID($name);
  if (!$uid){
    return false;
  }
  $sql = AuthUtilities::dbconnect();
  $delP = $sql->prepare_statement("delete from userPrivs where uid=? and auth_class=?");
  $delR = $sql->exec_statement($delP,array($uid,$auth_class));
  return true;
}

static public function showAuths($name){
  if (!AuthUtilities::isAlphanumeric($name)){
    echo "Invalid name<p />";
    return false;
  }
  
  if (!validateUser('admin')){
    return false;
  }

  $uid = AuthUtilities::getUID($name);
  if (!$uid){
    echo "No such user '$name'<p />";
    return false;
  }
  echo "Showing authorizations for $name";
  echo "<table cellspacing=2 cellpadding=2 border=1><tr>";
  echo "<th>Authorization class</th><th>Subclass start</th><th>Subclass end</th>";
  echo "</tr>";
  $sql = AuthUtilities::dbconnect();
  $fetchP = $sql->prepare_statement("select auth_class,sub_start,sub_end from userPrivs where uid=?");
  $fetchR = $sql->exec_statement($fetchP, array($uid));
  while ($row = $sql->fetch_array($fetchR)){
    echo "<tr>";
    echo "<td>$row[0]</td><td>$row[1]</td><td>$row[2]</td>";
    echo "</tr>";
  }
  echo "</table>";
  return true;
}

/*
with how authorization checking currently works, sub classes
must be countable (i.e., a sub class must be able to be 
tested as to whether or not it's 'between' start and end
*/
static public function checkAuth($name,$auth_class,$sub='all'){
  if (AuthUtilities::init_check())
    return 'init';
  if (!AuthUtilities::isAlphanumeric($name) or !AuthUtilities::isAlphanumeric($auth_class) or !AuthUtilities::isAlphanumeric($sub)){
    return false;
  }
  $uid = AuthUtilities::getUID($name);
  if (!$uid){
    return false;
  }
  $sql = AuthUtilities::dbconnect();
  $checkP = $sql->prepare_statement("select * from userPrivs where uid=? and auth_class=? and
             ((? between sub_start and sub_end) or (sub_start='all' and sub_end='all'))");
  $checkR = $sql->exec_statement($checkP,array($uid,$auth_class,$sub));
  if ($sql->num_rows($checkR) == 0){
    return false;
  }
  return true;
}

}

?>
