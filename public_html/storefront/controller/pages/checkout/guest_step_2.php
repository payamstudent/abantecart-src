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
if (! defined ( 'DIR_CORE' )) {
	header ( 'Location: static_pages/' );
}
class ControllerPagesCheckoutGuestStep2 extends AController {
	private $error = array();
	public $data = array();
  	public function main() {

        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

    	if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
	  		$this->redirect($this->html->getSecureURL('checkout/cart'));
    	}
		
		if ($this->customer->isLogged()) {
	  		$this->redirect($this->html->getSecureURL('checkout/shipping'));
    	} 

		if (!$this->config->get('config_guest_checkout') || $this->cart->hasDownload()) {
			$this->session->data['redirect'] = $this->html->getSecureURL('checkout/shipping');

	  		$this->redirect($this->html->getSecureURL('account/login'));
    	} 
		
		if (!isset($this->session->data['guest'])) {
	  		$this->redirect($this->html->getSecureURL('checkout/guest_step_1'));
    	} 
		
    	if (!$this->cart->hasShipping()) {
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);

			//$this->tax->setZone($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		    $this->tax->setZone($this->session->data['country_id'], $this->session->data['zone_id']);
    	}		

		$total_data = array();
		$total = 0;
		$taxes = $this->cart->getTaxes();		 
		$this->loadModel('checkout/extension');		
		$sort_order = array(); 		
		$results = $this->model_checkout_extension->getExtensions('total');		
		foreach ($results as $key => $value) {
			$sort_order[$key] = $this->config->get($value['key'] . '_sort_order');
		}
		
		array_multisort($sort_order, SORT_ASC, $results);		
		foreach ($results as $result) {
			$this->loadModel('total/' . $result['key']);
			$this->{'model_total_' . $result['key']}->getTotal($total_data, $total, $taxes);
		}
		
		$sort_order = array(); 	  
		foreach ($total_data as $key => $value) {
      		$sort_order[$key] = $value['sort_order'];
    	}

    	array_multisort($sort_order, SORT_ASC, $total_data);

        $this->document->setTitle( $this->language->get('heading_title') );
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['coupon']) && $this->_validateCoupon()) {
			$this->session->data['coupon'] = $this->request->post['coupon'];
			$this->session->data['success'] = $this->language->get('text_success');
			$this->redirect($this->html->getSecureURL('checkout/guest_step_3'));
		}
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && !isset($this->request->post['coupon']) && $this->_validate()) {
			if (isset($this->request->post['shipping_method'])) {
				$shipping = explode('.', $this->request->post['shipping_method']);
				$this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
			}
			
			$this->session->data['payment_method'] = $this->session->data['payment_methods'][$this->request->post['payment_method']];
			$this->session->data['comment'] = $this->request->post['comment'];
	  		$this->redirect($this->html->getSecureURL('checkout/guest_step_3'));
    	}		
		
		$this->loadModel('checkout/extension');
		// Shipping Methods
		if ($this->cart->hasShipping() && (!isset($this->session->data['shipping_methods']) || !$this->config->get('config_shipping_session'))) {
			$quote_data = array();
			
			$results = $this->model_checkout_extension->getExtensions('shipping');
			foreach ($results as $result) {
				$this->loadModel('extension/' . $result['key']);
				if (isset($this->session->data['guest']['shipping'])){
					$quote = $this->{'model_extension_' . $result['key']}->getQuote($this->session->data['guest']['shipping']);
				} else {
					$quote = $this->{'model_extension_' . $result['key']}->getQuote($this->session->data['guest']);
				}
	
				if ($quote) {
					$quote_data[$result['key']] = array(
						'title'      => $quote['title'],
						'quote'      => $quote['quote'], 
						'sort_order' => $quote['sort_order'],
						'error'      => $quote['error']
					);
				}
			}
	
			$sort_order = array();
			foreach ($quote_data as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}
			array_multisort($sort_order, SORT_ASC, $quote_data);
			$this->session->data['shipping_methods'] = $quote_data;
		}
		
		// Payment Methods
		$method_data = array();
		$results = $this->model_checkout_extension->getExtensions('payment');
		foreach ($results as $result) {
			$this->loadModel('extension/' . $result['key']);
			$method = $this->{'model_extension_' . $result['key']}->getMethod($this->session->data['guest']);
			if ($method) {
				$method_data[$result['key']] = $method;
			}
		}
					 
		$sort_order = array();
		foreach ($method_data as $key => $value) {
      		$sort_order[$key] = $value['sort_order'];
    	}
    	array_multisort($sort_order, SORT_ASC, $method_data);
		$this->session->data['payment_methods'] = $method_data;

      	
		$this->document->resetBreadcrumbs();
      	$this->document->addBreadcrumb( array ( 
        	'href'      => $this->html->getURL('index/home'),
        	'text'      => $this->language->get('text_home'),
        	'separator' => FALSE
      	 ));
      	$this->document->addBreadcrumb( array ( 
        	'href'      => $this->html->getURL('checkout/cart'),
        	'text'      => $this->language->get('text_cart'),
        	'separator' => $this->language->get('text_separator')
      	 ));
      	$this->document->addBreadcrumb( array ( 
        	'href'      => $this->html->getSecureURL('checkout/guest_step_1'),
        	'text'      => $this->language->get('text_guest_step_1'),
        	'separator' => $this->language->get('text_separator')
      	 ));
      	$this->document->addBreadcrumb( array ( 
        	'href'      => $this->html->getSecureURL('checkout/guest_step_2'),
        	'text'      => $this->language->get('text_guest_step_2'),
        	'separator' => $this->language->get('text_separator')
      	 ));

        $this->data['text_payment_methods'] = $this->language->get('text_payment_methods');
		$this->data['text_coupon'] = $this->language->get('text_coupon');
		$this->data['entry_coupon'] = $this->language->get('entry_coupon');

		if (isset($this->session->data['error'])) {
			$this->view->assign('error_warning', $this->session->data['error']);
			unset($this->session->data['error']);
		} elseif (isset($this->error['warning'])) {
    		$this->view->assign('error_warning', $this->error['warning']);
		} else {
			$this->view->assign('error_warning', '');
		}

        $this->view->assign('success', $this->session->data['success']);
		if (isset($this->session->data['success'])) {
			unset($this->session->data['success']);
		}

		$action = $this->html->getSecureURL('checkout/guest_step_2');

		$this->data['coupon_status'] = $this->config->get('coupon_status');

		$item = HtmlElementFactory::create( array('type' => 'button',
			                                      'name' => 'change_address',
			                                      'style' => 'button',
		                                          'text' => $this->language->get('button_change_address')
		                                    ));
		$this->data['change_address'] = $item->getHTML();


		$form = new AForm();
		$form->setForm(array( 'form_name' => 'coupon' ));
		$this->data['form0'][ 'form_open' ] = $form->getFieldHtml(
                                                                array( 'type' => 'form',
                                                                       'name' => 'coupon',
                                                                       'action' => $action ));
		$this->data['form0'][ 'coupon' ] = $form->getFieldHtml( array(
                                                                       'type' => 'input',
		                                                               'name' => 'coupon',
		                                                               'value' => ( isset($this->request->post[ 'coupon' ]) ? $this->request->post[ 'coupon' ] : $this->session->data[ 'coupon' ] )
		                                                       ));
		$this->data['form0'][ 'submit' ] = $form->getFieldHtml( array(
                                                                       'type' => 'submit',
		                                                               'name' => $this->language->get('button_coupon') ));


		
		if (isset($this->session->data['shipping_methods']) && !$this->session->data['shipping_methods']) {
			$this->view->assign('error_warning', $this->language->get('error_no_shipping'));
		}

		$form = new AForm();
		$form->setForm(array( 'form_name' => 'guest' ));
		$this->data['form'][ 'form_open' ] = $form->getFieldHtml(
                                                                array( 'type' => 'form',
                                                                       'name' => 'guest',
                                                                       'action' => $action ));

		$this->data[ 'shipping_methods' ] = $this->session->data[ 'shipping_methods' ] 	? $this->session->data[ 'shipping_methods' ] : array();
		$shipping = isset($this->request->post['shipping_method']) ? $this->request->post['shipping_method'] : $this->session->data[ 'shipping_method' ][ 'id' ];
		if ($this->data[ 'shipping_methods' ]) {
			foreach ($this->data[ 'shipping_methods' ] as $k => $v) {
				foreach($v['quote'] as $key => $val){
					$this->data[ 'shipping_methods' ][ $k ]['quote'][$key][ 'radio' ] = $form->getFieldHtml(array(
																								  'type' => 'radio',
																								  'id' => $val[ 'id' ],
																								  'name' => 'shipping_method',
																								  'options' => array( $val[ 'id' ] => '' ),
																								  'value' => ($shipping == $val[ 'id' ] ? TRUE : FALSE)
																							 ));
				}
			}
		} else {
			$this->data[ 'shipping_methods' ] = array();
		}


		$this->data['payment_methods'] = $this->session->data[ 'payment_methods' ];
		$payment = isset($this->request->post[ 'payment_method' ]) ? $this->request->post[ 'payment_method' ] : $this->session->data[ 'payment_method' ][ 'id' ];

		if($this->data['payment_methods']){
			foreach($this->data['payment_methods'] as $k=>$v){
				$this->data['payment_methods'][$k]['radio'] = $form->getFieldHtml( array(
					                                                                   'type' => 'radio',
					                                                                   'name' => 'payment_method',
					                                                                   'options' => array($v['id']=>''),
					                                                                   'value' => ( $payment == $v['id'] ? TRUE : FALSE )
				                                                                  ));
			}
		}else{
			$this->data['payment_methods'] = array();
		}
		

		$this->data['comment'] = isset($this->request->post[ 'comment' ]) ? $this->request->post[ 'comment' ] : $this->session->data[ 'comment' ];
		$this->data['form']['comment'] =  $form->getFieldHtml( array(
																	'type' => 'textarea',
																	'name' => 'comment',
																	'value' => $this->data['comment'],
																	'attr' => ' rows="8" style="width: 99%" ' ));

		if ($this->config->get('config_checkout_id')) {
			$this->loadModel('catalog/content');
			$content_info = $this->model_catalog_content->getContent($this->config->get('config_checkout_id'));
			if ($content_info) {
				$this->data['text_agree'] = $this->language->get('text_agree');
				$this->data['text_agree_href'] = $this->html->getURL('r/content/content/loadInfo', '&content_id=' . $this->config->get('config_checkout_id'));
				$this->data['text_agree_href_text'] = $content_info['title'];
			} else {
				$this->data['text_agree'] ='';
			}
		} else {
			$this->data['text_agree'] ='';
		}

		if($this->data['text_agree']){
			$this->data['form']['agree'] = $form->getFieldHtml( array(
					                                                  'type' => 'checkbox',
					                                                  'name' => 'agree',
				                                                      'value' => '1',
					                                                  'checked' => ( $this->request->post[ 'agree' ] ? TRUE : FALSE )
				                                                      ));
		}

		$this->data['agree'] = $this->request->post[ 'agree' ];
		$this->data[ 'back' ] = $this->html->getSecureURL('checkout/guest_step_1');
		$this->data['form'][ 'back' ] = $form->getFieldHtml( array( 'type' => 'button',
		                                                            'name' => 'back',
			                                                        'style' => 'button',
		                                                            'text' => $this->language->get('button_back') ));
		$this->data['form'][ 'continue' ] = $form->getFieldHtml( array(
                                                                       'type' => 'submit',
		                                                               'name' => $this->language->get('button_continue') ));

		$this->view->batchAssign($this->data);
		$this->processTemplate('pages/checkout/guest_step_2.tpl' );

        //init controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
  	}
	
  	private function _validate() {
		if ($this->cart->hasShipping()) {
    		if (!isset($this->request->post['shipping_method']) || !$this->request->post['shipping_method']) {
		  		$this->error['warning'] = $this->language->get('error_shipping');
			} else {
				$shipping = explode('.', $this->request->post['shipping_method']);
				
				if (!isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {			
					$this->error['warning'] = $this->language->get('error_shipping');
				}
			}
		}
		
    	if (!isset($this->request->post['payment_method'])) {
	  		$this->error['warning'] = $this->language->get('error_payment');
		} else {
			if (!isset($this->session->data['payment_methods'][$this->request->post['payment_method']])) {
				$this->error['warning'] = $this->language->get('error_payment');
			}
		}
		
		if ($this->config->get('config_checkout_id')) {
			$this->loadModel('catalog/content');
			
			$content_info = $this->model_catalog_content->getContent($this->config->get('config_checkout_id'));
			
			if ($content_info) {
    			if (!isset($this->request->post['agree'])) {
      				$this->error['warning'] = sprintf($this->language->get('error_agree'), $content_info['title']);
    			}
			}
		}
		
    	if (!$this->error) {
      		return TRUE;
    	} else {
      		return FALSE;
    	}
  	}
  	
  	private function _validateCoupon() {
  	
		$this->loadLanguage('checkout/payment');
		$promotion = new APromotion();
		$coupon = $promotion->getCouponData($this->request->post['coupon']);
		if (!$coupon) {
			$this->error['warning'] = $this->language->get('error_coupon');
		}
  		
  		if (!$this->error) {
	  		return TRUE;
		} else {
	  		return FALSE;
		}
  	}
}
?>