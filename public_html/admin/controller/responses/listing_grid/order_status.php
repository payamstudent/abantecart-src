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
class ControllerResponsesListingGridOrderStatus extends AController {
	private $error = array();

    public function main() {

	    //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

        $this->loadLanguage('localisation/order_status');
	    $this->loadModel('localisation/order_status');

		$page = $this->request->post['page']; // get the requested page
		$limit = $this->request->post['rows']; // get how many rows we want to have into the grid
		$sidx = $this->request->post['sidx']; // get index row - i.e. user click to sort
		$sord = $this->request->post['sord']; // get the direction

	    // process jGrid search parameter
	    $allowedDirection = array('asc', 'desc');

	    if ( !in_array($sord, $allowedDirection) ) $sord = $allowedDirection[0];

	    $data = array(
			'order' => strtoupper($sord),
			'start' => ($page - 1) * $limit,
			'limit' => $limit,
		    'content_language_id' => $this->session->data['content_language_id'],
		);

		$total = $this->model_localisation_order_status->getTotalOrderStatuses();
	    if( $total > 0 ) {
			$total_pages = ceil($total/$limit);
		} else {
			$total_pages = 0;
		}

	    $response = new stdClass();
		$response->page = $page;
		$response->total = $total_pages;
		$response->records = $total;

	    $results = $this->model_localisation_order_status->getOrderStatuses($data);
	    $i = 0;
		foreach ($results as $result) {
            $response->rows[$i]['id'] = $result['order_status_id'];
			$response->rows[$i]['cell'] = array(
				$this->html->buildInput(array(
                    'name'  => 'order_status['.$result['order_status_id'].']['.$this->session->data['content_language_id'].'][name]',
                    'value' => $result['name'],
                )),
			);
			$i++;
		}

		//update controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);

		$this->load->library('json');
		$this->response->setOutput(AJson::encode($response));
	}

	public function update() {

		//init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

	    $this->loadModel('localisation/order_status');
		$this->loadModel('setting/store');
		$this->loadModel('sale/order');
        $this->loadLanguage('localisation/order_status');
        if (!$this->user->hasPermission('modify', 'localisation/order_status')) {
			$this->response->setOutput( sprintf($this->language->get('error_permission_modify'), 'localisation/order_status') );
            return;
		}

		switch ($this->request->post['oper']) {
			case 'del':
				$ids = explode(',', $this->request->post['id']);
				if ( !empty($ids) )
				foreach( $ids as $id ) {
					$err = $this->_validateDelete($id);
					if (!empty($err)) {
						$this->response->setOutput($err);
						return;
					}
					$this->model_localisation_order_status->deleteOrderStatus($id);
				}
				break;
			case 'save':
				$ids = explode(',', $this->request->post['id']);
				if ( !empty($ids) )
				foreach( $ids as $id ) {
					if ( isset($this->request->post['order_status'][$id]) ) {
						foreach ($this->request->post['order_status'][$id] as $value) {
							if ((strlen(utf8_decode($value['name'])) < 3) || (strlen(utf8_decode($value['name'])) > 32)) {
								$this->response->setOutput( $this->language->get('error_name'));
								return;
							}
						}
						$this->model_localisation_order_status->editOrderStatus($id, array( 'order_status' => $this->request->post['order_status'][$id]) );
					}
				}

				break;

			default:
				//print_r($this->request->post);

		}

		//update controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
	}

    /**
     * update only one field
     *
     * @return void
     */
    public function update_field() {

		//init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

        $this->loadLanguage('localisation/order_status');
        if (!$this->user->hasPermission('modify', 'localisation/order_status')) {
			$this->response->setOutput( sprintf($this->language->get('error_permission_modify'), 'localisation/order_status') );
            return;
		}

        $this->loadModel('localisation/order_status');
		if ( isset($this->request->get['id']) && !empty($this->request->post['order_status']) ) {
		    //request sent from edit form. ID in url

			foreach ($this->request->post['order_status'] as $value) {
				if ((strlen(utf8_decode($value['name'])) < 3) || (strlen(utf8_decode($value['name'])) > 32)) {
					$this->response->setOutput(  $this->language->get('error_name'));
					return;
				}
			}

		    $this->model_localisation_order_status->editOrderStatus($this->request->get['id'], $this->request->post);
	        return;
	    }

	    //request sent from jGrid. ID is key of array
	    if ( isset($this->request->post['order_status']) ) {
			foreach ( $this->request->post['order_status'] as $id => $v ) {
				foreach ($v as $value) {
					if ((strlen(utf8_decode($value['name'])) < 3) || (strlen(utf8_decode($value['name'])) > 32)) {
						$this->response->setOutput(  $this->language->get('error_name'));
						return;
					}
				}
				$this->model_localisation_order_status->editOrderStatus($id, array('order_status' => $v) );
			}
	    }

		//update controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
	}

	private function _validateDelete( $order_status_id ) {
		if ($this->config->get('config_order_status_id') == $order_status_id) {
			return $this->language->get('error_default');
		}

		if ($this->config->get('config_download_status') == $order_status_id) {
			return $this->language->get('error_download');
		}

		$store_total = $this->model_setting_store->getTotalStoresByOrderStatusId($order_status_id);
		if ($store_total) {
			return sprintf($this->language->get('error_store'), $store_total);
		}

		$order_total = $this->model_sale_order->getOrderHistoryTotalByOrderStatusId($order_status_id);
		if ($order_total) {
			return sprintf($this->language->get('error_order'), $order_total);
		}
	}

}
?>