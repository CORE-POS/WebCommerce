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

/* BasicPage
 *
 * This is the base class for all display scripts
 * When instantiated, it calls the following functions
 * in this order:
 *
 * preprocess()
 * if preprocess() returns True 
 *   js_content()
 *   main_content()
 *
 * Any of these functions may be overriden by subclasses
 * is4c.css and jquery.js are automatically included
 * TODO: templating
 */

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

class BasicPage {

	protected $title;
    protected $scripts = array();

	function __construct($arg="CORE Online"){
		$this->title = $arg;
		if ($this->preprocess()){
			ob_start();
			$this->print_page();
			while (ob_get_level() > 0)
				ob_end_flush();
		}
	}

	function js_content(){

	}

	function css_content(){

	}

    /**
      Add a script to the page using <script> tags
      @param $file_url the script URL
      @param $type the script type
    */
    public function addScript($file_url, $type='text/javascript')
    {
        $this->scripts[$file_url] = $type;
    }

	function main_content(){

	}

	function preprocess(){
		return True;
	}

	function print_page(){
		global $IS4C_PATH;
		$this->streamTemplateFile('doctype.html');
		echo '<title>'.$this->title.'</title>';	
		echo "<link rel=\"stylesheet\" type=\"text/css\"
		    href=\"{$IS4C_PATH}is4c.css\">";
		echo "<script type=\"text/javascript\"
            src=\"{$IS4C_PATH}js/jquery.js\"></script>";
		echo "<script type=\"text/javascript\"
			src=\"{$IS4C_PATH}js/angular.js\"></script>";
		echo "<script type=\"text/javascript\"
			src=\"{$IS4C_PATH}js/angular-sanitize.min.js\"></script>";
        foreach($this->scripts as $s_url => $s_type) {
            printf('<script type="%s" src="%s"></script>',
                $s_type, $s_url);
            echo "\n";
        }
		echo "<script type=\"text/javascript\">";
		$this->js_content();
		echo "</script>";
        echo '<style type="text/css">';
        echo $this->css_content();
        echo '</style>';
		$this->streamTemplateFile('header.html');
		$this->top_menu();
		echo "<div id=\"boundingBox\">\n";
		$this->main_content();	
		echo "\n</div>\n";
		$this->streamTemplateFile('footer.html');
	}

	function top_menu(){
		global $IS4C_PATH;
		echo '<div id="topMenuRunner">';
        echo '<div class="alert-danger" style="text-align: center; padding: 3px;">
Pre-order WFC Deli sides and plates by Nov 16th and pies by Nov 19th.
            </div>';
        echo '<div class="row">';
        echo '<div class="col-sm-6">';
		$user = AuthLogin::checkLogin();
		if (!$user){
			printf('<ul>
				<li><a href="%sitems/">Browse Store</a>
				<li><a href="%sgui-modules/loginPage.php">Login</a></li>
				</ul>',
				$IS4C_PATH,$IS4C_PATH);
		}
		else {
			printf('<ul>
				<li><a href="%sitems/">Browse Store</a>
				<li><a href="%sgui-modules/manageAccount.php">%s</a></li>
				<li><a href="%sgui-modules/cart.php">Shopping Cart</a></li>
				<li><a href="%sgui-modules/loginPage.php?logout=yes">Logout</a></li>
				</ul>',
				$IS4C_PATH,$IS4C_PATH,$user,$IS4C_PATH,$IS4C_PATH);
		}
        echo '</div>';
        echo '<div class="col-sm-6">';
        echo '<form action="/gui-modules/search.php" method="get">
                <div class="form-inline">
                <input type="text" class="form-control" name="search" />
                <button type="submit" class="btn btn-custom">Search</button>
                </div>
            </form>';
        echo '</div>';
        echo '</div>';
		echo '</div>';
	}

	function streamTemplateFile($fn){
		global $IS4C_LOCAL, $IS4C_PATH;
		$full_name = $IS4C_PATH.'src/template/';
		$full_name .= $IS4C_LOCAL->get("Template").'/';
		$full_name .= $fn;
		$str = file_get_contents($full_name);
		echo str_replace('"/','"'.$IS4C_PATH,$str);
	}
}

?>
