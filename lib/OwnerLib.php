<?php

class OwnerLib
{

/**
  Validate information against custdata and/or memberCards
  @param $num CardNo or upc value
  @param $lastname [string] last name
  @return
    [int] CardNo if found and primary owner
    [int] 0 if found and not primary owner
    [boolean] false if not found
*/
static public function verifyOwner($num, $lastname)
{
    $num = str_replace(' ','',$num);
    $lastname = trim($lastname);
    if (strlen($num)>=10) { // likely a card

        if ($num[0] == '2') { // add lead digit
            $num = '4' . $num;
        }
        if (strlen($num) >= 12) { // remove check digit
            $num = substr($num,0,11);
        }
        $num = str_pad($num,13,'0',STR_PAD_LEFT);
    }
    $dbc = Database::pDataConnect();
    $query = 'SELECT c.CardNo, c.personNum FROM custdata AS c
                LEFT JOIN membercards AS m ON c.CardNo=m.card_no
                WHERE (c.CardNo=? OR m.upc=?) AND c.LastName=?
                AND Type=\'PC\' ORDER BY personNum';
    $prep = $dbc->prepare_statement($query);
    $result = $dbc->exec_statement($prep, array($num, $num, $lastname));
    if ($result === false || $dbc->num_rows($result) == 0) {
        return false;
    }
    $row = $dbc->fetch_row($result);

    return $row['personNum'] == 1 ? $row['CardNo'] : 0;
}

}
