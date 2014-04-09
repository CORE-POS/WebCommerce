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

if (!class_exists('BasicPage')) include($IS4C_PATH.'gui-class-lib/BasicPage.php');
if (!function_exists('checkLogin')) include($IS4C_PATH.'auth/login.php');

class createAccount extends BasicPage {

	function js_content(){
		?>
		$(document).ready(function(){
			$('#fullname').focus();
		});
		<?php
	}

	var $entries;
	private $msgs = '';

	function main_content(){
		global $IS4C_PATH;
		echo $this->msgs;
		?>
		<div id="loginTitle">Create an Account<br />
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table cellspacing="4" cellpadding="4">
		<tr>
		<th>Full Name</th>
		<td><input id="fullname" type="text" name="fn" value="<?php echo $this->entries['name']; ?>" /></td>
		</tr><tr>
		<th>E-mail address</th>
		<td><input type="text" name="email" value="<?php echo $this->entries['email']; ?>" /></td>
		</tr><tr>
		<th>Password</th>
		<td><input type="password" name="passwd" value="<?php echo $this->entries['passwd']; ?>" /></td>
		</tr><tr>
		<th>Re-Type Password</th>
		<td><input type="password" name="passwd2" value="<?php echo $this->entries['passwd']; ?>" /></td>
		</tr><tr>
		<th>Owner</th>
		<td>
        No
		</td>
		</tr>
        <tr>
        <td colspan="2"><i>Enter Last name &amp; Number to update Owner status (optional)</i></td>
        </tr>
        <tr>
        <th>Owner Last Name</th>
        <td><input type="text" id="vln" name="vln" /></td>
        </tr>
        <tr> 
        <th>Owner # or Barcode</th>
        <td><input type="text" id="vnum" name="vnum" /></td>
        </tr>
        <tr>
		<th><input type="submit" value="Create Account" name="submit" /></th>
		<td>&nbsp;</td>
		</tr>
		</table>
		</form>
		</div>
		<?php
	}

	function preprocess(){
		global $IS4C_PATH;
		$this->entries = array(
			'name'=>(isset($_REQUEST['fn'])?$_REQUEST['fn']:''),
			'email'=>(isset($_REQUEST['email'])?$_REQUEST['email']:''),
			'passwd'=>(isset($_REQUEST['passwd'])?$_REQUEST['passwd']:''),
			'owner'=>0
		);

		if (isset($_REQUEST['submit'])){
			// validate
			$errors = False;

			if (!isEmail($this->entries['email'],FILTER_VALIDATE_EMAIL)){
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Not a valid e-mail address: '.$this->entries['email'];
				$this->msgs .= '</div>';
				$this->entries['email'] = '';
				$errors = True;
			}

			if ($_REQUEST['passwd'] !== $_REQUEST['passwd2']){
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Passwords do not match';
				$this->msgs .= '</div>';
				$this->entries['passwd'] = '';
				$errors = True;
			}

			if (empty($_REQUEST['passwd'])){
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Password is required';
				$this->msgs .= '</div>';
				$this->entries['passwd'] = '';
				$errors = True;
			}

			if (empty($this->entries['name'])){
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Name is required';
				$this->msgs .= '</div>';
				$this->entries['name'] = '';
				$errors = True;
			}

			if (isset($_REQUEST['vln']) && !empty($_REQUEST['vln']) && isset($_REQUEST['vnum']) && !empty($_REQUEST['vnum'])) {
                $lastname = $_REQUSET['vln'];
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
                    $this->msgs .= '<br />';
                    $this->msgs .= 'You may omit Owner information and update your account\'s
                        status later.';
                    $this->msgs .= '</div>';
		    $errors = True;
                } else {
                    $row = $dbc->fetch_row($result);

                    $this->entries['owner'] = $row['personNum'] == 1 ? $row['CardNo'] : 9;
                }
            }

			if (!$errors){
				$created = createLogin($this->entries['email'],
					$this->entries['passwd'],
					$this->entries['name'],
					$this->entries['owner']);

				if ($created){
					login($this->entries['email'],$this->entries['passwd']);
					header("Location: {$IS4C_PATH}gui-modules/storefront.php");
					return False;
				}
				else {
					$this->msgs .= '<div class="errorMsg">';
					$this->msgs .= 'Account already exists: '.$this->entries['email'];
					$this->msgs .= '</div>';
					$this->entries['email'] = '';
				}
			}
		}
		return True;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new createAccount();
}

?>
