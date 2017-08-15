<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

/***********************************************************************************************

 Functions transcribed from connect.asp on 07.13.03 by Brandon.

***********************************************************************************************/

class Database
{



public static function tDataConnect()
{
	global $IS4C_LOCAL;

	$sql = new SqlManager($IS4C_LOCAL->get("localhost"),$IS4C_LOCAL->get("DBMS"),$IS4C_LOCAL->get("tDatabase"),
			      $IS4C_LOCAL->get("localUser"),$IS4C_LOCAL->get("localPass"),False);
	$sql->query("SET time_zone='America/Chicago'");
	return $sql;
}

public static function pDataConnect()
{
	global $IS4C_LOCAL;

	$sql = new SqlManager($IS4C_LOCAL->get("localhost"),$IS4C_LOCAL->get("DBMS"),$IS4C_LOCAL->get("pDatabase"),
			      $IS4C_LOCAL->get("localUser"),$IS4C_LOCAL->get("localPass"),False);
	$sql->query("SET time_zone='America/Chicago'");
	return $sql;
}

// ----------gettransno($CashierNo /int)----------
//
// Given $CashierNo, gettransno() will look up the number of the most recent transaction.
// modified for web use. Check localtemptrans for an ongoing transaction first, then
// check for any other transactions that day
public static function gettransno($CashierNo) 
{
	global $IS4C_LOCAL;

	$register_no = $IS4C_LOCAL->get("laneno");
	$query1 = "SELECT max(trans_no) as maxtransno from localtemptrans 
		where emp_no = ? AND register_no=?
		GROUP BY register_no, emp_no";
	$query2 = "SELECT max(trans_no) as maxtransno from localtranstoday 
		where emp_no = ? AND register_no=?
		GROUP BY register_no, emp_no";
	$connection = self::tDataConnect();
	$prep1 = $connection->prepare_statement($query1);
	$result = $connection->exec_statement($prep1,array($CashierNo,$register_no));
	$row = $connection->fetch_array($result);
	if ($row) return $row['maxtransno'];

	$prep2 = $connection->prepare_statement($query2);
	$result = $connection->exec_statement($prep2,array($CashierNo,$register_no));
	$row = $connection->fetch_array($result);
	if (!$row || !$row["maxtransno"]) {
		$trans_no = 1;
	}
	else {
		$trans_no = $row["maxtransno"] + 1;
	}
	return $trans_no;
}

public static function getDTransNo($emp_no) 
{
    $dbc = Database::tDataConnect();
    $prep = $dbc->prepare("SELECT MAX(trans_no) FROM dtransactions WHERE emp_no=? AND datetime >= CURDATE()");
    $res = $dbc->execute($prep, array($emp_no));
    $row = $dbc->fetchRow($res);
    if (!$row || !$row[0]) {
        return 1;
    }

    return $row[0] + 1;
}

// ------------------------------------------------------------------

/* get a list of columns in both tables
 * unlike getMatchingColumns, this compares tables
 * on the same database & server
 */
public static function localMatchingColumns($connection,$table1,$table2)
{
	$poll1 = $connection->table_definition($table1);
	$cols1 = array();
	foreach($poll1 as $name=>$v)
		$cols1[$name] = True;
	$poll2 = $connection->table_definition($table2);
	$matching_cols = array();
	foreach($poll2 as $name=>$v){
		if (isset($cols1[$name]))
			$matching_cols[] = $name;
	}

	$ret = "";
	foreach($matching_cols as $col)
		$ret .= $col.",";
	return rtrim($ret,",");
}

}

?>
