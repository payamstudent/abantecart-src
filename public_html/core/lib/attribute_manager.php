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

/**
 * Class to handle access to global attributes
 * 
 */
 
class AAttribute_Manager extends AAttribute {
	
	public function __construct($attribute_type = '', $language_id = 0 ) {
		parent::__construct($attribute_type, $language_id );
	}

    public function clearCache() {
        $this->cache->delete('attribute.types');
        $this->cache->delete('attribute.groups');
        $this->cache->delete('attributes');
        $this->cache->delete('attribute.values');
    }

	public function deleteAttribute($attribute_id) {

        $this->db->query("DELETE FROM `".DB_PREFIX."global_attributes`
            WHERE attribute_id = '" . $this->db->escape($attribute_id) . "' ");
        $this->db->query("DELETE FROM `".DB_PREFIX."global_attributes_descriptions`
            WHERE attribute_id = '" . $this->db->escape($attribute_id) . "' ");
		$this->db->query("DELETE FROM `".DB_PREFIX."global_attributes_values`
            WHERE attribute_id = '" . $this->db->escape($attribute_id) . "' ");
		$this->db->query("DELETE FROM `".DB_PREFIX."global_attributes_value_descriptions`
            WHERE attribute_id = '" . $this->db->escape($attribute_id) . "' ");

        $this->clearCache();
    }

    public function addAttribute($data) {
		if(!$data['name']) return false;
        $this->db->query(
							"INSERT INTO `".DB_PREFIX."global_attributes`
							 SET attribute_type_id = '" . $this->db->escape($data['attribute_type_id']) . "',
								attribute_group_id = '" . $this->db->escape($data['attribute_group_id']) . "',
								attribute_parent_id = '" . $this->db->escape($data['attribute_parent_id']) . "',
								element_type = '" . $this->db->escape($data['element_type']) . "',
								sort_order = '" . $this->db->escape($data['sort_order']) . "',
								required = '" . $this->db->escape($data['required']) . "',
								status = '" . $this->db->escape($data['status']) . "' ");

	    $attribute_id = $this->db->getLastId();
		// insert descriptions for all languages
		$result = $this->db->query(	"SELECT language_id FROM`".DB_PREFIX."languages`;");
	    $languages = $result->rows;
		foreach ($languages as $l) {
			$this->db->query( "INSERT INTO `".DB_PREFIX."global_attributes_descriptions`
							   SET attribute_id = '" . (int)$attribute_id . "',
							       language_id = '" . (int)$l['language_id'] . "',
					               name = '" . $this->db->escape($data['name']) . "' ");
		}

		if ( !empty($data['values']) ) {
			$data['values'] = array_unique($data['values']);
			foreach ( $data['values'] as $id=>$value  ) {
				$attribute_value_id = $this->addAttributeValue($attribute_id, $data['sort_orders'][$id]);
				foreach ($languages as $l) {
        			$this->addAttributeValueDescritpion($attribute_id, $attribute_value_id, $l['language_id'], $value);
        		}
			}
		}

        $this->clearCache();
        return $attribute_id;
    }

    public function updateAttribute($attribute_id, $data) {
		//Note: update is done per 1 language 
	    $language_id = $this->session->data['content_language_id'];
        $fields = array( 'attribute_type_id',
                         'attribute_group_id',
                         'attribute_parent_id',
                         'element_type',
                         'required',
                         'sort_order',
                         'status');
		$elements_with_options = HtmlElementFactory::getElementsWithOptions();
	    $attribute = $this->getAttribute($attribute_id, $language_id);

	    //check if we change element type and clean options if it does not require it
	    if ( isset($data['element_type']) && $data['element_type'] != $attribute['element_type'] ) {
		    if ( !in_array($data['element_type'], $elements_with_options) ) {
			    $sql = "DELETE FROM `".DB_PREFIX."global_attributes_values`
						WHERE attribute_id = '" . (int)$attribute_id . "'";
			    $this->db->query( $sql );
			    $sql = "DELETE FROM `".DB_PREFIX."global_attributes_value_descriptions`
						WHERE attribute_id = '" . (int)$attribute_id . "'";
			    $this->db->query( $sql );
		    }
	    }

        $update = array();
        foreach ( $fields as $f ) {
            if ( isset($data[$f]) )
                $update[] = "$f = '".$this->db->escape($data[$f])."'";
        }
        if ( !empty($update) ) {
	        $sql = "UPDATE " . DB_PREFIX . "global_attributes
                SET ". implode(',', $update) ."
                WHERE attribute_id = '" . (int)$attribute_id . "'";
            $this->db->query( $sql );
        }

        if ( !empty($data['name']) ) {
			$sql = "SELECT *
                    FROM " . DB_PREFIX . "global_attributes_descriptions
                    WHERE attribute_id = '" . (int)$attribute_id . "'
	                    AND language_id = '" . (int)$language_id . "' ";
            $exist = $this->db->query( $sql );

            if ($exist->num_rows) {
	            $sql = "UPDATE " . DB_PREFIX . "global_attributes_descriptions
						SET name = '" . $this->db->escape($data['name']) ."'
						WHERE attribute_id = '" . (int)$attribute_id . "'
							AND language_id = '" . (int)$language_id . "' ";
                $this->db->query( $sql );
            } else {
	            $sql = "INSERT INTO `".DB_PREFIX."global_attributes_descriptions`
                     SET attribute_id = '" . (int)$attribute_id . "',
                         language_id = '" . (int)$language_id . "',
                         name = '" . $this->db->escape($data['name']) . "' ";
                $this->db->query( $sql );
            }
        }

		//Update Attribute Values
	    if ( !empty($data['values']) && in_array($data['element_type'], $elements_with_options) ) {
			foreach ( $data['values'] as $atr_val_id=>$value ) {
	    	//Check if new or update			
		    	if ( $data['attribute_value_ids'][$atr_val_id] == 'delete' ) {
		    		//delete the description
		    		$this->deleteAttributeValueDescritpion($atr_val_id, $language_id);
		    		//delete value if no other language
		    		$this->deleteAttributeValues($atr_val_id);
		    	}
		    	else if ( $data['attribute_value_ids'][$atr_val_id] == 'new') {
		    		// New need to create
		    		$attribute_value_id = $this->addAttributeValue($attribute_id, $data['sort_orders'][$atr_val_id]);
		    		if($attribute_value_id){
		    			$this->addAttributeValueDescritpion($attribute_id, $attribute_value_id, $language_id, $value);
		    		}
		    	} else {
		    		//Existing need to update
		    		$this->updateAttributeValue($atr_val_id, $data['sort_orders'][$atr_val_id]);
		    		$this->updateAttributeValueDescritpion($attribute_id, $atr_val_id, $language_id, $value); 		
		    	}
	    	}
        }

        $this->clearCache();

    }

    public function addAttributeValue($attribute_id, $sort_order) {
		if ( empty($attribute_id) ) {
			return;
		}		
		$sql = "INSERT INTO `".DB_PREFIX."global_attributes_values`
		  		SET attribute_id = '" . (int)$attribute_id . "',
		  			sort_order = '" . $this->db->escape($sort_order) . "'";	
		$this->db->query( $sql );
		return $this->db->getLastId();		
    	
	}

    public function deleteAttributeValues($attribute_value_id) {
		if ( empty($attribute_value_id) ) {
			return;
		}
		//Delete global_attributes_values that have no values left
    	$sql = "DELETE FROM `".DB_PREFIX."global_attributes_values`
    	    	WHERE attribute_value_id = '" . (int)$attribute_value_id . "' AND attribute_value_id not in 
    	    	( SELECT attribute_value_id FROM `".DB_PREFIX."global_attributes_value_descriptions` 
    	    			 WHERE attribute_value_id = '" . $attribute_value_id . "' )";
    	$this->db->query( $sql );
    	$this->clearCache();
	}

    public function updateAttributeValue($attribute_value_id, $sort_order) {
		if ( empty($attribute_value_id) ) {
			return;
		}
		
		$sql = "UPDATE " . DB_PREFIX . "global_attributes_values
						SET sort_order = '" . $this->db->escape( $sort_order ) ."'
						WHERE attribute_value_id = '" . (int)$attribute_value_id . "'";
        $this->db->query( $sql );
    	$this->clearCache();
	}

	public function addAttributeValueDescritpion ($attribute_id, $attribute_value_id, $language_id, $value){
		if ( empty($attribute_id) || empty($attribute_value_id) || empty($language_id) ) {
			return;
		}
 	    $sql = "INSERT INTO `".DB_PREFIX."global_attributes_value_descriptions`
		    	SET attribute_id = '" . (int)$attribute_id . "',
		    	    attribute_value_id = '" . (int)$attribute_value_id . "',
		    	    language_id = '" . (int)$language_id . "',
		    		`value` = '" . $this->db->escape($value) . "' ";
        $this->db->query( $sql );
        $this->clearCache();       
	}

    public function updateAttributeValueDescritpion($attribute_id, $attribute_value_id, $language_id, $value) {
		if ( empty($attribute_id) || empty($attribute_value_id) || empty($language_id) ) {
			return;
		}
		//Delete and add operation 
		$this->deleteAttributeValueDescritpion($attribute_value_id, $language_id);
		$this->addAttributeValueDescritpion($attribute_id, $attribute_value_id, $language_id, $value);
		
		$this->clearCache(); 
	}

    public function deleteAttributeValueDescritpion($attribute_value_id, $language_id) {
		if ( empty($attribute_value_id) || empty($language_id) ) {
			return;
		}

		$sql = "DELETE FROM `".DB_PREFIX."global_attributes_value_descriptions`
						WHERE attribute_value_id = '" . (int)$attribute_value_id . "'
						   AND language_id = '" . (int)$language_id . "'";
		$this->db->query( $sql );    
        $this->clearCache();
	}

    public function deleteAttributeGroup($group_id) {

        $this->db->query("DELETE FROM `".DB_PREFIX."global_attributes_groups`
                          WHERE attribute_group_id = '" . $this->db->escape($group_id) . "' ");
        $this->db->query( "DELETE FROM `".DB_PREFIX."global_attributes_groups_descriptions`
                           WHERE attribute_group_id = '" . $this->db->escape($group_id) . "' ");

        $this->db->query(
            "UPDATE `".DB_PREFIX."global_attributes`
             SET attribute_group_id = ''
             WHERE attribute_group_id = '" . $this->db->escape($group_id) . "' ");

        $this->clearCache();
        return $group_id;
    }

    public function addAttributeGroup($data) {

        $this->db->query(
            "INSERT INTO `".DB_PREFIX."global_attributes_groups`
             SET sort_order = '" . $this->db->escape($data['sort_order']) . "',
                 status = '" . $this->db->escape($data['status']) . "' ");

        $group_id = $this->db->getLastId();
        $language_id = $this->session->data['content_language_id'];
        $this->db->query(
            "INSERT INTO `".DB_PREFIX."global_attributes_groups_descriptions`
             SET attribute_group_id = '" . $group_id . "',
                 language_id = '" . $this->db->escape($language_id) . "',
                 name = '" . $this->db->escape($data['name']) . "' ");

        $this->clearCache();
        return $group_id;
    }

    public function updateAttributeGroup($group_id, $data) {

        $fields = array('sort_order', 'status');
        $update = array();
        foreach ( $fields as $f ) {
            if ( isset($data[$f]) )
                $update[] = "$f = '".$this->db->escape($data[$f])."'";
        }
        if ( !empty($update) ) {
            $this->db->query(
                "UPDATE " . DB_PREFIX . "global_attributes_groups
                SET ". implode(',', $update) ."
                WHERE attribute_group_id = '" . (int)$group_id . "'");
        }

        if ( !empty($data['name']) ) {
            $language_id = $this->session->data['content_language_id'];
            $exist = $this->db->query(
                "SELECT *
                FROM " . DB_PREFIX . "global_attributes_groups_descriptions
                WHERE attribute_group_id = '" . (int)$group_id . "'
                    AND language_id = '" . (int)$language_id . "' ");

            if ($exist->num_rows) {
                $this->db->query(
                    "UPDATE " . DB_PREFIX . "global_attributes_groups_descriptions
                    SET name = '" . $this->db->escape($data['name']) ."'
                    WHERE attribute_group_id = '" . (int)$group_id . "'
                        AND language_id = '" . (int)$language_id . "' ");
            } else {
                $this->db->query(
                    "INSERT INTO `".DB_PREFIX."global_attributes_groups_descriptions`
                     SET attribute_group_id = '" . (int)$group_id . "',
                         language_id = '" . (int)$language_id . "',
                         name = '" . $this->db->escape($data['name']) . "' ");
            }
        }

        $this->clearCache();

    }

    /**
     * @param  $group_id, $language_id = 0
     * Get details about given group for attributes
     */
    public function getAttributeGroup( $group_id, $language_id = 0 ) {

        if ( !$language_id ) {
            $language_id = $this->config->get('storefront_language_id');
        }

        $query = $this->db->query("
            SELECT gag.*, gagd.name
            FROM `".DB_PREFIX."global_attributes_groups` gag
                LEFT JOIN `".DB_PREFIX."global_attributes_groups_descriptions` gagd ON ( gag.attribute_group_id = gagd.attribute_group_id AND gagd.language_id = '" . (int)$language_id . "' )
            WHERE gag.attribute_group_id = '" . $this->db->escape( $group_id ) . "'"
        );

	    if ( $query->num_rows ) {
            return $query->row;
        } else {
            return null;
        }
	}

    public function getAttributeGroups( $data = array() ) {

        if ( !$data['language_id'] ) {
            $data['language_id'] = $this->config->get('storefront_language_id');
        }

        $sql = "SELECT gag.*, gagd.name
            FROM `".DB_PREFIX."global_attributes_groups` gag
                LEFT JOIN `".DB_PREFIX."global_attributes_groups_descriptions` gagd
                ON ( gag.attribute_group_id = gagd.attribute_group_id AND gagd.language_id = '" . (int)$data['language_id'] . "' )";


        $sort_data = array(
            'gagd.name',
            'gag.sort_order',
            'gag.status',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY gag.sort_order, gagd.name ";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalAttributeGroups( $data = array() ) {

        if ( !$data['language_id'] ) {
            $data['language_id'] = $this->config->get('storefront_language_id');
        }

        $sql = "SELECT gag.*, gagd.name
            FROM `".DB_PREFIX."global_attributes_groups` gag
                LEFT JOIN `".DB_PREFIX."global_attributes_groups_descriptions` gagd ON ( gag.attribute_group_id = gagd.attribute_group_id AND gagd.language_id = '" . (int)$data['language_id'] . "' )";

        $query = $this->db->query($sql);
        return $query->num_rows;
    }

	public function getAttribute( $attribute_id, $language_id = 0 ) {

        if ( !$language_id ) {
            $language_id = $this->session->data['content_language_id'];
        }

        $query = $this->db->query("
            SELECT ga.*, gad.name
            FROM `".DB_PREFIX."global_attributes` ga
                LEFT JOIN `".DB_PREFIX."global_attributes_descriptions` gad
                ON ( ga.attribute_id = gad.attribute_id AND gad.language_id = '" . (int)$language_id . "' )
            WHERE ga.attribute_id = '" . $this->db->escape( $attribute_id ) . "'"
        );

	    if ( $query->num_rows ) {
            return $query->row;
        } else {
            return null;
        }
	}

    public function getAttributeDescriptions($attribute_id) {
        $query = $this->db->query("
            SELECT *
            FROM `".DB_PREFIX."global_attributes_descriptions`
            WHERE attribute_id = '" . $this->db->escape( $attribute_id ) . "'"
        );
        $result = array();
        foreach ( $query->rows as $row ) {
            $result[ $row['language_id'] ] = $row['name'];
        }
	    return $result;
	}

	public function getAttributeValues($attribute_id, $language_id = 0) {
        if ( !$language_id ) {
            $language_id = $this->session->data['content_language_id'];
        }
        $query = $this->db->query("SELECT ga.*, gad.value
            FROM `".DB_PREFIX."global_attributes_values` ga
                LEFT JOIN `".DB_PREFIX."global_attributes_value_descriptions` gad
                ON ( ga.attribute_value_id = gad.attribute_value_id AND gad.language_id = '" . (int)$language_id . "' )
            WHERE ga.attribute_id = '" . $this->db->escape( $attribute_id ) . "'
            ORDER BY sort_order"                
        );	
	    return $query->rows;
	}

	public function getAttributeValueDescriptions($attribute_value_id) {
        $query = $this->db->query("
            SELECT *
            FROM `".DB_PREFIX."global_attributes_value_descriptions`
            WHERE attribute_value_id = '" . $this->db->escape( $attribute_value_id ) . "'"
        );
        $result = array();
        foreach ( $query->rows as $row ) {
            $result[ $row['language_id'] ] = $row['value'];
        }
	    return $result;
	}

	public function getAttributes( $data = array(), $language_id = 0, $attribute_parent_id = null ) {

        if ( !$language_id ) {
            $language_id = $this->session->data['content_language_id'];
        }

        $sql = "SELECT ga.*, gad.name
            FROM `".DB_PREFIX."global_attributes` ga
                LEFT JOIN `".DB_PREFIX."global_attributes_descriptions` gad
                ON ( ga.attribute_id = gad.attribute_id AND gad.language_id = '" . (int)$language_id . "' )";
        if ( !empty($data['search']) ) {
            $sql .= " WHERE ".$data['search'];
        }
		if (empty($data['search']) && !is_null($attribute_parent_id) ) {
            $sql .= " WHERE ga.attribute_parent_id = '".(int)$attribute_parent_id."' ";
        }

        $sort_data = array(
            'gad.name',
            'ga.sort_order',
            'ga.status',
            'ga.attribute_type_id',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY ga.sort_order, gad.name ";
        }

        if (isset($data['order']) && (strtoupper($data['order']) == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }


	public function getTotalAttributes( $data = array() ) {

        if ( !$data['language_id'] ) {
            $data['language_id'] = $this->config->get('storefront_language_id');
        }

        $sql = "SELECT ga.*, gad.name
            FROM `".DB_PREFIX."global_attributes` ga
                LEFT JOIN `".DB_PREFIX."global_attributes_descriptions` gad
                ON ( ga.attribute_id = gad.attribute_id AND gad.language_id = '" . (int)$data['language_id'] . "' )";
        if ( !empty($data['search']) ) {
            $sql .= " WHERE ".$data['search'];
        }

        $query = $this->db->query($sql);
        return $query->num_rows;
    }

}