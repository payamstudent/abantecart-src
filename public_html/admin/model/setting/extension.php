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
if (! defined ( 'DIR_CORE' ) || !IS_ADMIN) {
	header ( 'Location: static_pages/' );
}
class ModelSettingExtension extends Model {
	//???? i think this method not needed anymore
	public function getInstalled($type) {
		$extension_data = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extensions WHERE `type` = '" . $this->db->escape($type) . "'");
		
		foreach ($query->rows as $result) {
			$extension_data[] = $result['key'];
		}
		
		return $extension_data;
	}
	
	public function install($type, $key) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "extensions SET `type` = '" . $this->db->escape($type) . "', `key` = '" . $this->db->escape($key) . "'");
	}
	
	public function uninstall($type, $key) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "extensions WHERE `type` = '" . $this->db->escape($type) . "' AND `key` = '" . $this->db->escape($key) . "'");
	}
}
?>