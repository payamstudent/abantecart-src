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
class ControllerPagesTotalHandling extends AController {
	public $data = array();
	private $error = array();
	private $fields = array('handling_total', 'handling_fee', 'handling_tax_class_id', 'handling_status', 'handling_sort_order');
	 
	public function main() {

        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

		$this->document->setTitle( $this->language->get('heading_title') );
		$this->loadModel('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->_validate())) {
			$this->model_setting_setting->editSetting('handling', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->redirect($this->html->getSecureURL('total/handling'));
		}
 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		$this->data['success'] = $this->session->data['success'];
		if (isset($this->session->data['success'])) {
			unset($this->session->data['success']);
		}

   		$this->document->initBreadcrumb( array (
       		'href'      => $this->html->getSecureURL('index/home'),
       		'text'      => $this->language->get('text_home'),
      		'separator' => FALSE
   		 ));
   		$this->document->addBreadcrumb( array ( 
       		'href'      => $this->html->getSecureURL('extension/total'),
       		'text'      => $this->language->get('text_total'),
      		'separator' => ' :: '
   		 ));
   		$this->document->addBreadcrumb( array ( 
       		'href'      => $this->html->getSecureURL('total/handling'),
       		'text'      => $this->language->get('heading_title'),
      		'separator' => ' :: '
   		 ));
		
		$this->loadModel('localisation/tax_class');
		$_tax_classes = $this->model_localisation_tax_class->getTaxClasses();
		$tax_classes = array( 0 => $this->language->get ( 'text_none' ));
		foreach ( $_tax_classes as $k => $v ) {
			$tax_classes[ $v['tax_class_id'] ] = $v['title'];
		}

		foreach ( $this->fields as $f ) {
				$this->data [$f] = $this->config->get($f);
		}

		$this->data ['action'] = $this->html->getSecureURL ( 'total/handling' );
		$this->data['cancel'] = $this->html->getSecureURL('extension/total');
		$this->data ['heading_title'] = $this->language->get ( 'text_edit' ) . $this->language->get ( 'text_total' );
		$this->data ['form_title'] = $this->language->get ( 'heading_title' );
		$this->data ['update'] = $this->html->getSecureURL ( 'listing_grid/total/update_field', '&id=handling' );

		$form = new AForm ( 'HS' );
		$form->setForm ( array ('form_name' => 'editFrm', 'update' => $this->data ['update'] ) );

		$this->data['form']['form_open'] = $form->getFieldHtml ( array ('type' => 'form', 'name' => 'editFrm', 'action' => $this->data ['action'] ) );
		$this->data['form']['submit'] = $form->getFieldHtml ( array ('type' => 'button', 'name' => 'submit', 'text' => $this->language->get ( 'button_go' ), 'style' => 'button1' ) );
		$this->data['form']['cancel'] = $form->getFieldHtml ( array ('type' => 'button', 'name' => 'cancel', 'text' => $this->language->get ( 'button_cancel' ), 'style' => 'button2' ) );

		$this->data['form']['fields']['total'] = $form->getFieldHtml(array(
		    'type' => 'input',
		    'name' => 'handling_total',
		    'value' => $this->data['handling_total'],
	    ));
		$this->data['form']['fields']['fee'] = $form->getFieldHtml(array(
		    'type' => 'input',
		    'name' => 'handling_fee',
		    'value' => $this->data['handling_fee'],
	    ));
		$this->data['form']['fields']['tax'] = $form->getFieldHtml(array(
		    'type' => 'selectbox',
		    'name' => 'handling_tax_class_id',
			'options' => $tax_classes,
		    'value' => $this->data['handling_tax_class_id'],
	    ));
		$this->data['form']['fields']['status'] = $form->getFieldHtml(array(
		    'type' => 'checkbox',
		    'name' => 'handling_status',
		    'value' => $this->data['handling_status'],
			'style'  => 'btn_switch',
	    ));
		$this->data['form']['fields']['sort_order'] = $form->getFieldHtml(array(
		    'type' => 'input',
		    'name' => 'handling_sort_order',
		    'value' => $this->data['handling_sort_order'],
	    ));
		$this->view->assign('help_url', $this->gen_help_url('edit_handling') );
		$this->view->batchAssign( $this->data );
		$this->processTemplate('pages/total/form.tpl' );

        //update controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
	}

	private function _validate() {
		if (!$this->user->hasPermission('modify', 'total/handling')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if (!(int)$this->request->post['handling_total']) {
			$this->error['warning'] = $this->language->get('error_number');
		}
		if (!(float)$this->request->post['handling_fee']) {
			$this->error['warning'] = $this->language->get('error_number');
		}

		
		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}	
	}
}
?>