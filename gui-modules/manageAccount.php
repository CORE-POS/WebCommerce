<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

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

if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

if (!class_exists('UserPage')) include($IS4C_PATH.'gui-class-lib/UserPage.php');
if (!function_exists('checkLogin')) include($IS4C_PATH.'auth/login.php');
if (!function_exists('pDataConnect')) include($IS4C_PATH.'lib/connect.php');

class manageAccount extends BasicPage {

	function js_content(){
		?>
		$(document).ready(function(){
			$('#fullname').focus();
		});
		<?php
	}

	var $entries;
	var $logged_in_user;
	private $msgs = '';

	function main_content(){
		global $IS4C_PATH;
		echo $this->msgs;
		?>
		<div id="loginTitle">Manage your Account<br />
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table cellspacing="4" cellpadding="4">
		<tr>
		<th>Full Name</th>
		<td><input id="fullname" type="text" name="fn" value="<?php echo $this->entries['name']; ?>" /></td>
		</tr><tr>
		<th>E-mail address</th>
		<td><input type="text" name="email" value="<?php echo $this->entries['email']; ?>" /></td>
		</tr><tr>
		<th>Owner</th>
		<td>
        <?php
        if ($this->entries['owner'] == 0) {
            echo 'No';
        } else if ($this->entries['owner'] == 9) {
            echo 'Yes (Unverified)';
        } else {
            echo '#'.$this->entries['owner'];
        }
        ?>
		</td>
		</tr><tr>
        <tr>
        <td colspan="2"><i>Enter Last name &amp; Number to update Owner status (optional)</i></td>
        </tr>
        <tr>
        <th>Last Name</th>
        <td><input type="text" id="vln" name="vln" /></td>
        </tr>
        <tr> 
        <th>Owner # or Barcode</th>
        <td><input type="text" id="vnum" name="vnum" /></td>
        </tr>
        <tr> 
		<th><input type="submit" value="Update Account" name="submit" /></th>
		<td><a href="<?php echo $IS4C_PATH;?>gui-modules/changePassword.php">Change Password</a></td>
		</tr>
		</table>
		</form>
		</div>
		<?php
	}

	function preprocess(){
		global $IS4C_PATH;
		$this->logged_in_user = checkLogin();

		$dbc = pDataConnect();

		$q = $dbc->prepare_statement('SELECT name,real_name,owner FROM Users WHERE name=?');
		$r = $dbc->exec_statement($q, array($this->logged_in_user));
		if ($dbc->num_rows($r) == 0){
			// sanity check; shouldn't ever happen
			header("Location: {$IS4C_PATH}gui-modules/loginPage.php");
			return False;
		}
		$w = $dbc->fetch_row($r);

		$this->entries = array(
			'name'=>$w['real_name'],
			'email'=>$w['name'],
			'owner'=>$w['owner']
		);

		if (isset($_REQUEST['submit'])){
			// validate

			if ($_REQUEST['email'] != $this->entries['email']){
				if (!isEmail($_REQUEST['email'],FILTER_VALIDATE_EMAIL)){
					$this->msgs .= '<div class="errorMsg">';
					$this->msgs .= 'Not a valid e-mail address: '.$_REQUEST['email'];
					$this->msgs .= '</div>';
				} else {
					$newemail = $_REQUEST['email'];
					$upP = $dbc->prepare_statement('UPDATE Users SET name=? WHERE name=?');
					$dbc->exec_statement($upP,array($newemail,$this->logged_in_user));
					doLogin($newemail);
					$this->logged_in_user = $newemail;
					$this->entries['email'] = $newemail;
					$this->msgs .= '<div class="successMsg">';
					$this->msgs .= 'E-mail address has been updated';
					$this->msgs .= '</div>';
				}
			}

			if ($_REQUEST['fn'] != $this->entries['name']){
				if (empty($_REQUEST['fn'])){
					$this->msgs .= '<div class="errorMsg">';
					$this->msgs .= 'Name is required';
					$this->msgs .= '</div>';
				}
				else {
					$upP = $dbc->prepare_statement('UPDATE Users SET real_name=? WHERE name=?');
					$dbc->exec_statement($upP,array($_REQUEST['fn'],$this->logged_in_user));
					$this->entries['name'] = $_REQUEST['fn'];
					$this->msgs .= '<div class="successMsg">';
					$this->msgs .= 'Name has been updated';
					$this->msgs .= '</div>';
				}
			}

			if (isset($_REQUEST['vln']) && !empty($_REQUEST['vln']) && isset($_REQUEST['vnum']) && !empty($_REQUEST['vnum'])) {
		$lastname = $_REQUEST['vln'];
		$num = $_REQUEST['vnum'];
		$num = str_replace(' ','',$num);
		if (strlen($num)>=10){ // likely a card
		    if ($num[0] == '2')  // add lead digit
                        $num = '4'.$num;
                    if (strlen($num) >= 12) // remove check digit
                        $num = substr($num,0,11);
                    $num = str_pad($num,13,'0',STR_PAD_LEFT);
                }
                $query = 'SELECT c.CardNo, c.personNum FROM custdata AS c
                        LEFT JOIN membercards AS m ON c.CardNo=m.card_no
                        WHERE (c.CardNo=? OR m.upc=?) AND c.LastName=?
                        AND Type=\'PC\' ORDER BY personNum';
                $prep = $dbc->prepare_statement($query);
                $result = $dbc->exec_statement($prep, array($num, $num, $lastname));
                if ($result === false || $dbc->num_rows($result) == 0) {
                    $this->msgs .= '<div class="errorMsg">';
                    $this->msgs .= 'No owner account found for '.$_REQUEST['vnum'].' ('.$lastname.')';
                    $this->msgs .= '</div>';
                } else {
                    $row = $dbc->fetch_row($result);

                    $owner = $row['personNum'] == 1 ? $row['CardNo'] : 9;

                    $upP = $dbc->prepare_statement('UPDATE Users SET owner=? WHERE name=?');
                    $dbc->exec_statement($upP,array($owner, $this->logged_in_user));
                    $this->entries['owner'] = $owner;
                    $this->msgs .= '<div class="successMsg">';
                    $this->msgs .= 'Owner status has been updated';
                    $this->msgs .= '</div>';
                }
			}


		}

		return True;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new manageAccount();
}

?>
