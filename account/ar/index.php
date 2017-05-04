<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

class ArHistoryPage extends BasicPage
{
    private $transactions = array();
    private $details = array();
    private $uuid = '';
    private $all = false;
    private $cardNo = 0;

    public function preprocess()
    {
        // get token
        $uuid = isset($_GET['id']) ? $_GET['id'] : '';
        if ($uuid === '' && isset($_POST['id'])) {
            $uuid = $_POST['id'];
        }
        $uuid = str_replace('-', '', $uuid);
        $all = isset($_POST['all']) ? $_POST['all'] : false;

        // validate token format
        if (strlen($uuid) != 32 || !preg_match('/^[0-9a-fA-f]+$/', $uuid)) {
            include('error.php');
            exit;
        }

        // validate token is active
        $dbc = Database::pDataConnect();
        $prep = $dbc->prepare("SELECT cardNo FROM CustomerTokens WHERE uuid=?");
        $res = $dbc->execute($prep, array($uuid));
        if ($res && $dbc->numRows($res) !== 1) {
            include('error.php');
            exit;
        }

        $row = $dbc->fetchRow($res);
        $cardNo = $row['cardNo'];
        $this->uuid = $uuid;
        $this->all = $all;
        $this->cardNo = $cardNo;

        $query = "
            SELECT a.tdate,
                a.trans_num,
                a.charges,
                a.payments,
                d.description,
                d.amount,
                d.arHistoryID
            FROM ar_history AS a
                LEFT JOIN ArHistoryDetails AS d ON a.ar_history_id=d.arHistoryID
            WHERE a.card_no=?
            ORDER BY a.tdate DESC,
                d.arHistoryDetailID";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($cardNo));
        while ($row = $dbc->fetchRow($res)) {
            $tid = $row['arHistoryID'];
            if (!isset($this->transactions[$tid])) {
                if (!$all & count($this->transactions) > 49) {
                    break;
                }
                $this->transactions[$tid] = array(
                    'tdate' => $row['tdate'],
                    'trans_num' => $row['trans_num'],
                    'charges' => $row['charges'],
                    'payments' => $row['payments'],
                );
            }
            if (!isset($this->details[$tid])) {
                $this->details[$tid] = array();
            }
            $this->details[$tid][] = array('description' => $row['description'], 'amount' => $row['amount']);
        }

        return true;
    }

    public function main_content()
    {
        echo '<h3>Account #' . $this->cardNo . ' History</h3>';
        if (count($this->transactions) == 0) {
            echo 'No activity found';
            return;
        }
        echo '<table class="table table-bordered">';
        echo '<tbody>';
        foreach ($this->transactions as $tid => $tinfo) {
            $net = $tinfo['charges'] - $tinfo['payments'];
            $type = 'Charge';
            if ($net < 0) {
                $type = 'Payment';
                $net *= -1;
            }
            printf('<tr><td>Date %s</td><td>Receipt #%s</td><td>%s</td><td>$%.2f</td></tr>',
                $tinfo['tdate'], $tinfo['trans_num'], $type, $net);
            foreach ($this->details[$tid] as $detail) {
                printf('<tr><td>&nbsp;</td><td colspan="2">%s</td><td>$%.2f</td></tr>',
                    $detail['description'], $detail['amount']);
            }
            echo '<tr><td colspan="4">&nbsp;</td></tr>';
        }
        echo '</tbody>';
        echo '</table>';
        if (!$this->all) {
            echo '<form method="post" action="index.php">';
            echo '<input type="hidden" name="id" value="' . $this->uuid . '" />';
            echo '<input type="hidden" name="all" value="1" />';
            echo '<button type="submit" class="btn">View All</button>';
            echo '</form>';
        }
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new ArHistoryPage();
}

