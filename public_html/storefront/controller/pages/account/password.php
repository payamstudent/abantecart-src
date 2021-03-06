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
class ControllerPagesAccountPassword extends AController {
	private $error = array();
	     
  	public function main() {

        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

    	if (!$this->customer->isLogged()) {
      		$this->session->data['redirect'] = $this->html->getSecureURL('account/password');
      		$this->redirect($this->html->getSecureURL('account/login'));
    	}

    	$this->document->setTitle( $this->language->get('heading_title') );
			  
    	if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->_validate()) {
			$this->loadModel('account/customer');
			
			$this->model_account_customer->editPassword($this->customer->getEmail(), $this->request->post['password']);
 
      		$this->session->data['success'] = $this->language->get('text_success');
	  
	  		$this->redirect($this->html->getSecureURL('account/account'));
    	}

      	$this->document->resetBreadcrumbs();

      	$this->document->addBreadcrumb( array ( 
        	'href'      => $this->html->getURL('index/home'),
        	'text'      => $this->language->get('text_home'),
        	'separator' => FALSE
      	 )); 

      	$this->document->addBreadcrumb( array ( 
        	'href'      => $this->html->getURL('account/account'),
        	'text'      => $this->language->get('text_account'),
        	'separator' => $this->language->get('text_separator')
      	 ));
		
      	$this->document->addBreadcrumb( array ( 
        	'href'      => $this->html->getURL('account/password'),
        	'text'      => $this->language->get('heading_title'),
        	'separator' => $this->language->get('text_separator')
      	 ));
			
        $this->view->assign('error_password', $this->error['password'] );
        $this->view->assign('error_confirm', $this->error['confirm'] );

	    $form = new AForm();
        $form->setForm(array('form_name' => 'PasswordFrm'));
        $form_open = $form->getFieldHtml(
            array(
                 'type' => 'form',
                 'name' => 'PasswordFrm',
                 'action' => $this->html->getSecureURL('account/password')));
    	$this->view->assign('form_open', $form_open);

        $password = $form->getFieldHtml( array(
                                               'type' => 'password',
		                                       'name' => 'password',
		                                       'value' => '',
		                                       'required' => true ));
		$confirm = $form->getFieldHtml( array(
                                               'type' => 'password',
		                                       'name' => 'confirm',
		                                       'value' => '',
		                                       'required' => true ));
		$submit = $form->getFieldHtml( array(
                                               'type' => 'submit',
		                                       'name' => $this->language->get('button_continue')
		                                        ));

		$this->view->assign('password', $password );
		$this->view->assign('submit', $submit );
		$this->view->assign('confirm', $confirm );
		$this->view->assign('back', $this->html->getSecureURL('account/account') );

		$back = HtmlElementFactory::create( array ('type' => 'button',
		                                           'name' => 'back',
			                                       'text'=> $this->language->get('button_back'),
			                                       'style' => 'button'));
		$this->view->assign('button_back', $back->getHtml());

        $this->processTemplate('pages/account/password.tpl');

        //init controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
  	}
  
  	private function _validate() {
    	if ((strlen(utf8_decode($this->request->post['password'])) < 4) || (strlen(utf8_decode($this->request->post['password'])) > 20)) {
      		$this->error['password'] = $this->language->get('error_password');
    	}

    	if ($this->request->post['confirm'] != $this->request->post['password']) {
      		$this->error['confirm'] = $this->language->get('error_confirm');
    	}  
	
		if (!$this->error) {
	  		return TRUE;
		} else {
	  		return FALSE;
		}
  	}
}
?>
