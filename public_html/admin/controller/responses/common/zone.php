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
class ControllerResponsesCommonZone extends AController {
	private $error = array(); 
	    
  	public function main() {

          //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

       	$this->loadModel('localisation/zone');

		if(isset( $this->request->get['country_id'] )){
			$results = $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']);
		}elseif( $this->request->get['location_id'] ){
			$results = $this->model_localisation_zone->getZonesByLocationId( $this->request->get['location_id'] );
		}

		$json = array('options' => array());

        $selected_name = '';
        if ( !empty($this->request->get['type']) ) {
            $json['type'] = $this->request->get['type'];
        } else {
	        if(!$results){
		       $json['options']['0']['value'] = array($this->language->get('text_none'));
	        }
        }

		if (!$this->request->get['zone_id']) {
		  		$json['options'][0] = array( 'value' => $this->language->get('text_none'), 'selected' => TRUE );
		} else {
				$json['options'][0]['value'] = $this->language->get('text_none');
		}

		// options for zones
      	foreach ($results as $result) {
        	$selected = FALSE;
	    	if (isset($this->request->get['zone_id']) && ($this->request->get['zone_id'] == $result['zone_id'])) {
	      		$selected = TRUE;
                $selected_name = $result['name'];
	    	}

	    	$json['options'][$result['zone_id']]['value'] = $result['name'];
	    	if ( $selected )
			{
	    		$json['options'][$result['zone_id']]['selected'] = $selected;
			}
    	}

		if (!$results) {
			if (!$this->request->get['zone_id']) {
		  		$json['options'][0] = array('value' => $this->language->get('text_none'), 'selected' => TRUE);
			} else {
				$json['options'][0]['value'] = $this->language->get('text_none');
			}
		}

        if ( !empty($this->request->get['type']) ) {
		    $json['type'] = $this->request->get['type'];
		    $json['selected_name'] = $selected_name;
        }

		$this->response->setOutput(json_encode($json), $this->config->get('config_compression'));

        //update controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
  	}

}