<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

class EmailReceipt extends BasicPage 
{
    private $fields = array(
        'vln' => '',
        'vnum' => '',
        'ereceipt' => 1,
        'email1' => '',
    );

    private $msgs = array();

    function css_content()
    {
        return '
            ul#messages {
                list-style-position: inside;
                margin: 5px;
            }
            fieldset {
                margin: 10px;
            }
        ';
    }

	function main_content()
    {
        if (isset($_REQUEST['sent'])) {
            echo 'Request submitted successfully';
            return true;
        }

        echo '<ul id="messages" class="format_text">';
        foreach ($this->msgs as $msg) {
            echo '<li>' . $msg . '</li>';
        }
        echo '</ul>';

        echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" class="format_text">';

        if ($this->showVerifyFields) {
            echo '<fieldset><legend>Verify Ownership</legend>';
            echo '<table>
                <tr>
                    <th>Last Name</th>
                    <td><input type="text" id="vln" name="vln" value="' . $this->fields['vln'] . '">
                </tr>
                <tr>
                    <th>Owner # or Barcode</th>
                    <td><input type="text" id="vnum" name="vnum" value="' . $this->fields['vnum'] . '">
                </tr>
                </table>';
            echo '</fieldset>';
        }
        echo '<fieldset><legend>Email Receipt Setting</legend>';
        echo '<table>
            <tr>
                <th>E-mail Receipts should be</th>
                <td><select name="ereceipt">
                    <option value="1">Enabled for my account</option>
                    <option value="0">Disabled for my account</option>
                </td>
            </tr>
            <tr>
                <th>E-mail Address</th>
                <td><input type="email" name="email1" value="' . $this->fields['email1'] . '" /></td>
            </tr>
            </table>';
        echo '</fieldset>';

        echo '<input type="submit" name="submit" value="Submit Request" />';
        echo '</form>';

        echo '<br />';
        echo '<h1>How do email receipts work?</h1>';
        echo '<p class="format_text">
            Whenever you make a purchase, the purchase information will be
            emailed to your designated address rather than printed on a paper
            receipt. Emails will be sent from receipts@wholefoods.coop
            and the subject line will start with "Receipt".
            Paper receipts will still be available upon request. Only
            one email address may be associated with an owner account.
            </p>';
	}

	function preprocess()
    {
        /**
          Always check login status
        */
        $name = AuthLogin::checkLogin();
        $owner = ($name) ? AuthUtilities::getOwner($name) : false;
        $this->showVerifyFields = true;
        $this->fields['email1'] = filter_var($name, FILTER_VALIDATE_EMAIL) ? $name : '';
        if ($name && $owner && $owner != 9) {
            $this->msgs[] = 'You are logged in as owner #' . $owner;
            $this->showVerifyFields = false;
        } elseif ($name && $owner && $owner == 9) {
            $this->msgs[] = 'Your login is not the primary account holder';
        } elseif ($name) {
            $this->msgs[] = 'Your login is not attached to an owner account';
        }

        /**
          Form submitted
        */
        if (isset($_REQUEST['ereceipt'])) {
            /**
              If verification needed, check submission
              and set error messages
            */
            $verified = !$this->showVerifyFields;
            $card_no = false;
            if ($this->showVerifyFields) {
                $this->fields['vln'] = isset($_REQUEST['vln']) ? $_REQUEST['vln'] : '';
                $this->fields['vnum'] = isset($_REQUEST['vnum']) ? $_REQUEST['vnum'] : '';
                $status = OwnerLib::verifyOwner($this->fields['vnum'], $this->fields['vln']);
                if ($status === false) {
                    $this->msgs[] = 'No owner found for ' . $this->fields['vln'] . ' and ' . $this->fields['vnum'];
                    $verified = false;
                } elseif ($status === 0) {
                    $this->msgs[] = 'Name is not the primary account holder for #' . $this->fields['vnum'];
                    $verified = false;
                } else {
                    $verified = true;
                    $card_no = $status;
                }
            } else {
                $card_no = $owner;
            }

            /**
              Validate the email address
            */
            $this->fields['ereceipt'] = isset($_REQUEST['ereceipt']) ? $_REQUEST['ereceipt'] : 0;
            if (empty($this->fields['email1']) || isset($_REQUEST['email1'])) {
                $this->fields['email1'] = $_REQUEST['email1'];
                if (!filter_var($this->fields['email1'], FILTER_VALIDATE_EMAIL)) {
                    $this->msgs[] = 'Invalid e-mail address: ' . $this->fields['email1'];

                    // error checking is done at this point
                    return true;
                }
            }

            /**
              All validation passed; send
            */
            if ($verified) {
                $to = 'andy@wholefoods.coop';   
                $subject = 'WebReq: Email Receipt';
                $msg = 'Email Receipt ' . ($this->fields['ereceipt'] ? 'ON' : 'OFF') . "\n"
                    . 'Owner #' . $card_no . "\n"
                    . 'Email address: ' . $this->fields['email1'] . "\n";
                if ($this->showVerifyFields) {
                    $msg .= "\n"
                        . 'Name given: ' . $this->fields['vln'] . "\n"
                        . 'Number given: ' . $this->fields['vnum'] . "\n";
                }
                $headers = 'From: website@wholefoods.coop' . "\r\n";
                mail($to, $subject, $msg, $headers);

                // redirect so refresh doesn't resubmit
                header('Location: EmailReceipt.php?sent=1');
                return false;
            }
        }

		return true;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new EmailReceipt();
}

