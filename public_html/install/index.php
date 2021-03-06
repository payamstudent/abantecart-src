<?php
/*
------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>
  
 UPGRADE NOTE: 
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.  
------------------------------------------------------------------------------  
*/

// Real path (operating system web root) to the directory where abantecart is installed
$root_path = dirname(__FILE__);

if (defined('IS_WINDOWS')) {
		$root_path = str_replace('\\', '/', $root_path);
}
define('DIR_ROOT', $root_path); 

// HTTP
define('HTTP_SERVER', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/');
define('HTTP_ABANTECART', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(rtrim(dirname($_SERVER['PHP_SELF']), 'install'), '/.\\'). '/');

// DIR
define('DIR_APP_SECTION', str_replace('\'', '/', realpath(dirname(__FILE__))) . '/');
define('DIR_CORE', str_replace('\'', '/', realpath(dirname(__FILE__) . '/../')) . '/core/');
define('DIR_SYSTEM', str_replace('\'', '/', realpath(dirname(__FILE__) . '/../')) . '/system/');
define('DIR_CACHE', str_replace('\'', '/', realpath(dirname(__FILE__) . '/../')) . '/system/cache/');
define('DIR_ABANTECART', str_replace('\'', '/', realpath(DIR_APP_SECTION . '../')) . '/');
define('DIR_DATABASE', DIR_CORE . 'database/');
define('DIR_TEMPLATE', DIR_APP_SECTION . 'view/template/');
define('INSTALL', 'true');
// Relative paths and directories
define('RDIR_TEMPLATE',  'view/');

// Startup with local init
require_once('init.php');

//Check if cart is already installed
if (file_exists(DIR_SYSTEM . 'config.php')){
	require_once(DIR_SYSTEM . 'config.php');
}

if(isset($_SESSION['SALT']) && strlen($_SESSION['SALT'])==4){
	define('SALT',$_SESSION['SALT']);
}

//generate salt
if(!defined('SALT')){
	function randomWord(){
			$codelenght = 4;
			$newcode_length=0;
			$newcode='';
			while($newcode_length < $codelenght) {
				$x=1;
				$y=3;
				$part = rand($x,$y);
				if($part==1){// Numbers
					$a=48;
					$b=57;
				}  
				if($part==2){// UpperCase
					$a=65;
					$b=90;
				}  
				if($part==3){// LowerCase
					$a=97;
					$b=122;
				} 
				$code_part=chr(rand($a,$b));
				$newcode_length = $newcode_length + 1;
				$newcode = $newcode.$code_part;
			}
			return $newcode;
	}
	DEFINE('SALT',randomWord());
	$_SESSION['SALT'] = SALT;
}

$data_exist = false;
if ( defined('DB_HOSTNAME') ) {
    $connection = mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);
    mysql_select_db(DB_DATABASE, $connection);
    $r = mysql_query("SELECT product_id FROM ".DB_PREFIX."products", $connection);
    $data_exist = mysql_num_rows($r);
} else {
    unset($session->data['finish']);
}

if ( $data_exist && empty($session->data['finish']) ) {
    header('Location: ../');
}

if ( !empty($session->data['finish']) ) {
    $request->get['rt'] = 'finish';
}

try {

// Document
$document = new ADocument();
$document->setBase( HTTP_SERVER );
$registry->set('document', $document);

// Page Controller 
$page_controller = new APage($registry);

// Router
if (!empty($request->get['rt'])) {
	$dispatch = $request->get['rt'];
} else {
	$dispatch = 'license';
}

$page_controller->build('pages/'.$dispatch);

// Output
$response->output();
}
catch (AException $e) {
    ac_exception_handler($e);
}

//display debug info
ADebug::display();
