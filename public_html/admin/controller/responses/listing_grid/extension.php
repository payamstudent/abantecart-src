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
class ControllerResponsesListingGridExtension extends AController {
	private $error = array();
	public $data;
    public function main() {

	    //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

	    $this->loadLanguage('extension/extensions');

	    $page = $this->request->post['page']; // get the requested page
	    if ( (int)$page < 0 ) $page = 0;
		$limit = $this->request->post['rows']; // get how many rows we want to have into the grid
		$sidx = $this->request->post['sidx']; // get index row - i.e. user click to sort
		$sord = $this->request->post['sord']; // get the direction


	    $search_str = '';
	    if (isset($this->request->post['_search']) && $this->request->post['_search'] == 'true') {
		    $searchData = json_decode(htmlspecialchars_decode($this->request->post['filters']), true);
			$search_str = $searchData['rules'][0]['data'];
	    }

	    //sort
	    $allowedSort = array(1=>'id','name', 'category', 'update_date', 'status', 'store');
	    if ( !in_array($sidx, $allowedSort) ) $sidx = 'update_date';
	    $sidx = array_search($sidx, $allowedSort);
		$sidx = $sidx == 'update_date' ? 'update_date_int' : $sidx;

	    $allowedDirection = array(SORT_ASC => 'asc', SORT_DESC => 'desc');
	    if ( !in_array($sord, $allowedDirection) ) {
		    $sord = SORT_ASC;
	    } else {
		    $sord = array_search($sord, $allowedDirection);
	    }

	    //extensions that has record in DB but missing files
		$missing_extensions = $this->extensions->getMissingExtensions();

	    $data = array(
		    'search' => $search_str,
		    'filter' => $this->session->data['extension_filter'],
	    );
		if($this->config->get('config_store_id')){
			$data['store_id'] = (int)$this->config->get('config_store_id');
		}
	    //extensions list
	    $extensions = $this->extension_manager->getExtensionsList( $data ); 

	    $total = $extensions->total;
	    if( $total > 0 ) {
			$total_pages = ceil($total/$limit);
		} else {
			$total_pages = 0;
		}

	    $response = new stdClass();
		$response->rows = array();
		$response->page = $page;
		$response->total = $total_pages;
		$response->records = $total;

	    $i = 0;
	    $push = array();
	    foreach ($extensions->rows as $row) {
			$extension = $row['key'];
			$name = trim($this->extensions->getExtensionName($extension));

		    if (in_array($extension, $missing_extensions)) {

			 	$action = '<a class="btn_action" href="'.$this->html->getSecureURL('extension/extensions/delete', $this->data['url'].'&extension=' . $extension).'"
			 	onclick="return confirm(\''.$this->language->get('text_delete_confirm').'\')" title="'. $this->language->get('text_delete') . '">'.
				          '<img src="'.RDIR_TEMPLATE.'image/icons/icon_grid_delete.png" alt="'. $this->language->get('text_delete') . '" />'.
				          '</a>';

			    $icon = '<img src="'.RDIR_TEMPLATE.'image/default_extension.png'.'" alt="" border="0" />';
			    $name = str_replace('%EXT%', $extension, $this->language->get('text_missing_extension'));
			    $category = '';
			    $status = '';
				$row['update_date'] = date('Y-m-d H:i:s',time()); // change it for show it in list first by default sorting
			    $response->userdata->classes[$extension] = 'warning';

			} else {
				if ( !$this->config->has($extension . '_status')) {
					$action = '<a class="btn_action"
								onclick="show_popup(\'' . $extension.'\', \''.$this->html->getSecureURL('extension/extensions/install', $this->data['url'].'&extension=' . $extension).'\')"
					title="'. $this->language->get('text_install') . '">'.
				          '<img src="'.RDIR_TEMPLATE.'image/icons/icon_grid_install.png" alt="'. $this->language->get('text_install') . '" />'.
				          '</a>'.
						  '<a class="btn_action" href="'.$this->html->getSecureURL('extension/extensions/delete', $this->data['url'].'&extension=' . $extension).'"
						  onclick="return confirm(\''.$this->language->get('text_delete_confirm').'\')" title="'. $this->language->get('text_delete') . '">'.
				          '<img src="'.RDIR_TEMPLATE.'image/icons/icon_grid_delete.png" alt="'. $this->language->get('text_delete') . '" />'.
				          '</a>';
					$status = $this->language->get('text_not_installed');
				} else {

					$action = '<a id="action_edit_'.$extension.'" class="btn_action" href="'.$this->html->getSecureURL('extension/extensions/edit', $this->data['url'].'&store_id='.(int)$row['store_id'].'&extension=' . $extension).'"
						title="'. $this->language->get('text_edit') . '">'.
				          '<img src="'.RDIR_TEMPLATE.'image/icons/icon_grid_edit.png" alt="'. $this->language->get('text_edit') . '" />'.
				          '</a>'.
						  '<a class="btn_action" href="'.$this->html->getSecureURL('extension/extensions/uninstall', $this->data['url'].'&extension=' . $extension).'"
						  onclick="return confirm(\''.str_replace('%extension%',$name,$this->language->get('text_uninstall_confirm')).'\')"
						  title="'. $this->language->get('text_uninstall') . '">'.
				          '<img src="'.RDIR_TEMPLATE.'image/icons/icon_grid_uninstall.png" alt="'. $this->language->get('text_uninstall') . '" />'.
				          '</a>';
					$status = $this->html->buildCheckbox(array(
						'name' => $extension.'['.$extension.'_status]',
						'value' => $row['status'],//this->config->get($extension . '_status'),
						'style' => 'btn_switch',
					));
				}



				$icon_ext_img_url = HTTP_CATALOG . 'extensions/' . $extension . '/image/icon.png';
				$icon_ext_dir = DIR_EXT . $extension . '/image/icon.png';
				$icon = ( is_file ( $icon_ext_dir ) ? $icon_ext_img_url : RDIR_TEMPLATE . 'image/default_extension.png');
				if ( !$this->config->has($extension . '_status')) {
					$icon = '<img src="'.$icon.'" alt="" border="0" />';
				} else {
					$icon = '<a href="'.$this->html->getSecureURL('extension/extensions/edit', $this->data['url'].'&extension=' . $extension).'"><img src="'.$icon.'" alt="" border="0" /></a>';
				}

			    
			   
			    $category = $row['category'];

			    // if update available
			    if($this->session->data['extension_updates']){
				    if(in_array($extension,array_keys($this->session->data['extension_updates']))){
						$name = '<p class="warning">'.$name.'<br>'.str_replace('%NEWVERSION%',$this->session->data['extension_updates'][$extension]['new_version'], $this->language->get('text_update_it')).'</p>';
				        $push[] = $i;
				    }
			    }

		    }

			$response->rows[$i]['id'] = $extension.'_'.$row['store_id'];
			$response->rows[$i]['cell'] = array(
												$icon,
												$extension,
												$name,
												$category,
												(strtotime($row['update_date']) ? date('Y/m/d', strtotime($row['update_date'])) : ''));
			if(!$this->config->get('config_store_id')){
				$response->rows[$i]['cell'][] =	$row['store_name'] ? $row['store_name'] : $this->language->get('text_default');
			}
			$response->rows[$i]['cell'][] =	$status;
			$response->rows[$i]['cell'][] =	$action;
			if($push){
				if(in_array($i,$push)){
					$for_push[] = $response->rows[$i];
					unset($response->rows[$i]);
				}
			}

			$i++;
		}

	    $sort = array();
	    foreach ($response->rows as $item) {
		    $sort[] = $item['cell'][$sidx];
	    }

	    array_multisort($sort, $sord, $response->rows);

	    if($push){
		    foreach($for_push as $ext){
		        array_unshift($response->rows,$ext);
		    }
	    }

	    $response->rows = array_slice($response->rows,(int)($page-1)*$limit, $limit);

		//update controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
		$this->load->library('json');
		$this->response->setOutput(AJson::encode($response));

	}


	public function update() {

		//init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

		$this->loadLanguage('extension/extensions');

		if (!$this->user->hasPermission('modify', 'extension/extensions')) {
			$this->response->setOutput( sprintf($this->language->get('error_permission_modify'), 'extension/extensions') );
            return;
		}
		$store_id = isset($this->request->post['store_id']) ? (int)$this->request->post['store_id'] : (int)$this->request->get['store_id'];

		if ( empty($this->request->get['id']) ) {
			foreach ($this->request->post as $ext => $val){
				$val['store_id'] = $store_id;
				$this->extension_manager->editSetting($ext, $val);
			}
		} else {
			$this->request->post['store_id'] = $store_id;
			$this->extension_manager->editSetting($this->request->get['id'], $this->request->post);
		}

		//update controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
	}

	public function license(){
		//init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);
		$this->loadLanguage('extension/extensions');

		// first of all we need check dependencies
		$ext = new ExtensionUtils($this->request->get['extension']);
		$result = $ext->validateDependencies();
		if($result){
			// if all fine show license agreement
			if(file_exists(DIR_EXT.$this->request->get['extension']."/license.txt")){
				$this->data['license_text'] = file_get_contents(DIR_EXT.$this->request->get['extension']."/license.txt");
				$this->data['license_text'] = htmlentities($this->data['license_text'],ENT_QUOTES,'UTF-8');
				$this->data['license_text'] = nl2br($this->data['license_text']);
			}
		}else{
			$error_text = $ext->getError();
			end($error_text);
			$this->data['error_text'] = current($error_text);
		}
		//update controller data
		$this->extensions->hk_UpdateData($this,__FUNCTION__);
		$this->load->library('json');
		$this->response->setOutput(AJson::encode($this->data));
	}
}
?>