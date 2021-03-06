<?php
/*------------------------------------------------------------------------------
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
------------------------------------------------------------------------------*/

class ControllerPagesSettings extends AController {
	private $error = array();
	
	public function main() {
        $template_data = array();
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->redirect(HTTP_SERVER . 'index.php?rt=install');
		}

		if (isset($this->error['warning'])) {
			$template_data['error_warning'] = $this->error['warning'];
		} else {
			$template_data['error_warning'] = '';	
		}
		
		$template_data['action'] = HTTP_SERVER . 'index.php?rt=settings';

		$template_data['config_catalog'] = DIR_ABANTECART . 'system/config.php';

		$template_data['system'] = DIR_SYSTEM;		
		$template_data['cache'] = DIR_SYSTEM . 'cache';
		$template_data['logs'] = DIR_SYSTEM . 'logs';
		$template_data['image'] = DIR_ABANTECART . 'image';
		$template_data['image_thumbnails'] = DIR_ABANTECART . 'image/thumbnails';
		$template_data['download'] = DIR_ABANTECART . 'download';
		$template_data['extensions'] = DIR_ABANTECART . 'extensions';
		$template_data['resources'] = DIR_ABANTECART . 'resources';
		$template_data['backup'] = DIR_ABANTECART . 'admin/system/backup';
		$template_data['button_continue'] = $this->html->buildButton(array(
			'name' => 'continue',
			'text' => 'Continue >>',
			'style' => 'button1' )
		);

		$this->addChild('common/header', 'header', 'common/header.tpl');
		$this->addChild('common/footer', 'footer', 'common/footer.tpl');

        $this->view->batchAssign( $template_data );
		$this->processTemplate('pages/settings.tpl');
	}
	
	private function validate() {
		if (phpversion() < '5.2') {
			$this->error['warning'] = 'Warning: You need to use PHP5.2 or above for AbanteCart to work!';
		}

		if (!ini_get('file_uploads')) {
			$this->error['warning'] = 'Warning: file_uploads needs to be enabled!';
		}
	
		if (ini_get('session.auto_start')) {
			$this->error['warning'] = 'Warning: AbanteCart will not work with session.auto_start enabled!';
		}

		if (!extension_loaded('mysql')) {
			$this->error['warning'] = 'Warning: MySQL extension needs to be loaded for AbanteCart to work!';
		}

		if (!extension_loaded('gd')) {
			$this->error['warning'] = 'Warning: GD extension needs to be loaded for AbanteCart to work!';
		}

		if (!extension_loaded('zlib')) {
			$this->error['warning'] = 'Warning: ZLIB extension needs to be loaded for AbanteCart to work!';
		}
	
		if (!is_writable(DIR_ABANTECART . 'system/config.php')) {
			$this->error['warning'] = 'Warning: config.php needs to be writable for AbanteCart to be installed!';
		}


		if (!is_writable(DIR_SYSTEM)) {
			$this->error['warning'] = 'Warning: System directory and all its children files/directories need to be writable for AbanteCart to work!';
		}
				
		if (!is_writable(DIR_SYSTEM . 'cache')) {
			$this->error['warning'] = 'Warning: Cache directory needs to be writable for AbanteCart to work!';
		}
		
		if (!is_writable(DIR_SYSTEM . 'logs')) {
			$this->error['warning'] = 'Warning: Logs directory needs to be writable for AbanteCart to work!';
		}
		
		if (!is_writable(DIR_ABANTECART . 'image')) {
			$this->error['warning'] = 'Warning: Image directory and all its children files/directories need to be writable for AbanteCart to work!';
		}

		if (!is_writable(DIR_ABANTECART . 'image/thumbnails')) {
			$this->error['warning'] = 'Warning: image/thumbnails directory needs to be writable for AbanteCart to work!';
		}
		
		if (!is_writable(DIR_ABANTECART . 'download')) {
			$this->error['warning'] = 'Warning: Download directory needs to be writable for AbanteCart to work!';
		}

        if (!is_writable(DIR_ABANTECART . 'extensions')) {
			$this->error['warning'] = 'Warning: Extensions directory needs to be writable for AbanteCart to work!';
		}

        if (!is_writable(DIR_ABANTECART . 'resources')) {
			$this->error['warning'] = 'Warning: Resources directory needs to be writable for AbanteCart to work!';
		}

		if (!is_writable(DIR_ABANTECART . 'admin/system/backup')) {
			$this->error['warning'] = 'Warning: Backup directory needs to be writable for AbanteCart to work!';
		}

    	if (!$this->error) {
      		return TRUE;
    	} else {
      		return FALSE;
    	}
	}
}
?>