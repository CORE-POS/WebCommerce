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
 // session_start();
 
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

/**
  Class for remote payment processors where the user
  is redirected to a page on the processor's site to
  enter card information then redirected back to
  our site to finalize purchase
*/
class RemoteProcessor
{
    public $tender_name = '';
    public $tender_code = '';

    public $cancelable = true;

    const LIVE_MODE = false;
    const CURRENT_PROCESSOR = 'PayPalMod';

    /**
      Start payment process. Usually involves a request to the
      processor entity.
      @param $amount [numeric] total payment amount
      @param $tax [numeric] tax amount (optional)
      @param $email [string] user email address (optional)
      @return [string] payment identifier or [boolean] false
    */
    public function initializePayment($amount, $tax=0, $email="")
    {
        return false;
    }

    /**
      Send user to the payment processor's site
      @param $identifier [string] payment identifier
    */
    public function redirectToProcess($identifier)
    {

    }

    /**
      Finalize the payment
      @param $identifier [string] payment identifier
      @return [boolean] success or failure
    */
    public function finalizePayment($identifier) 
    {
        return false;
    }

    /**
      Display processor-specfic checkout button
      and any other icons/bling. The checkout button
      should have name=checkoutButton
      @return [string] html
    */
    public function checkoutButton() {
        return '';
    }
}

