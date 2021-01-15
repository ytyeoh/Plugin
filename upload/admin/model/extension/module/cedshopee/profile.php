<?php
class ModelExtensionModuleCedshopeeProfile extends Model {
	public function addProfile($data) {
		//echo '<pre>'; print_r($data); die;
		 if(isset($data['product_manufacturer']) && $data['product_manufacturer'])
		 {
		 	$data['product_manufacturer'] = $data['product_manufacturer'];
		 } else {
		 	$data['product_manufacturer'] = array();
		 }

		 if(isset($data['profile_attribute_mapping']) && $data['profile_attribute_mapping'])
		 {
		 	$data['profile_attribute_mapping'] = $data['profile_attribute_mapping'];
		 } else {
		 	$data['profile_attribute_mapping'] = array();
		 }

		$this->db->query("INSERT INTO " . DB_PREFIX . "cedshopee_profile SET title = '" . $data['title'] . "',
		   status = '" .(int)$data['status']  . "',
		  product_manufacturer = '" . $this->db->escape(json_encode($data['product_manufacturer'])) . "',
		   store_category = '" . $this->db->escape(json_encode($data['product_category'])) . "',
			shopee_category = '" . $this->db->escape($data['shopee_category_id']) . "',  
			shopee_category_name = '" . $this->db->escape($data['shopee_category']) . "',
			shopee_categories = '" . $this->db->escape(json_encode($data['shopee_category_id'])) . "',
			profile_store = '" . $this->db->escape(json_encode($data['profile_store'])) . "',
			default_mapping = '" . $this->db->escape(json_encode($data['default_mapping'])) . "',
			profile_attribute_mapping = '" . $this->db->escape(json_encode($data['profile_attribute_mapping'])) . "',
			
			logistics = '" . $this->db->escape(json_encode($data['logistics'])) . "',
			wholesale = '" . $this->db->escape(json_encode($data['wholesale'])) . "',
			profile_language = '" . $this->db->escape($data['profile_language']) . "'");
		$profile_id = $this->db->getLastId();
		if($profile_id){
			$this->removeProductFromProfile($profile_id);
			$this->addProductInProfile($profile_id, $data['product_category'], $data['profile_store'], $data['product_manufacturer']);	
		}
		
		$this->cache->delete('profile');
	}

	public function editProfile($profile_id, $data) {
//        echo '<pre>'; print_r($data); die;
		if(isset($data['product_manufacturer']) && $data['product_manufacturer'])
		 {
		 	$data['product_manufacturer'] = $data['product_manufacturer'];
		 } else {
		 	$data['product_manufacturer'] = array();
		 }
		 
		if(isset($data['profile_attribute_mapping']) && $data['profile_attribute_mapping'])
		 {
		 	$data['profile_attribute_mapping'] = $data['profile_attribute_mapping'];
		 } else {
		 	$data['profile_attribute_mapping'] = array();
		 }
		 
		$this->db->query("UPDATE " . DB_PREFIX . "cedshopee_profile SET title = '" . $data['title'] . "',
		 status = '" .(int)$data['status']  . "', 
		 product_manufacturer = '" . $this->db->escape(json_encode($data['product_manufacturer'])) . "', 
		 store_category = '" . $this->db->escape(json_encode($data['product_category'])) . "',
			shopee_categories = '" . $this->db->escape(json_encode($data['shopee_category_id'])) . "',
			shopee_category = '" . $this->db->escape($data['shopee_category_id']) . "',  
			shopee_category_name = '" . $this->db->escape($data['shopee_category']) . "',
			profile_store = '" . $this->db->escape(json_encode($data['profile_store'])) . "',
			default_mapping = '" . $this->db->escape(json_encode($data['default_mapping'])) . "',
			profile_attribute_mapping = '" . $this->db->escape(json_encode($data['profile_attribute_mapping'])) . "',
			logistics = '" . $this->db->escape(json_encode($data['logistics'])) . "',
			wholesale = '" . $this->db->escape(json_encode($data['wholesale'])) . "',
			profile_language = '" . $this->db->escape($data['profile_language']) . "'  WHERE id = '" . (int)$profile_id . "'");

		
		$this->updateProductInProfile($profile_id, $data['product_category'], $data['profile_store'], $data['product_manufacturer']);
		$this->cache->delete('profile');
	}

	public function deleteProfile($profile_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "cedshopee_profile WHERE id = '" . (int)$profile_id . "'");

		$sql = $this->db->query("DELETE FROM `".DB_PREFIX."cedshopee_profile_products` WHERE `shopee_profile_id` = '". $profile_id ."' ");
		
		$this->cache->delete('profile');
	}	

	public function getProfile($profile_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cedshopee_profile WHERE id = '" . (int)$profile_id . "'");

		return $query->row;
	}

	public function getProfiles($data = array()) {

			$sql = "SELECT * FROM " . DB_PREFIX . "cedshopee_profile cp WHERE id >= '0'";

			$sort_data = array(
				'title',
				'status',
				'id'
			);		

			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				$sql .= " ORDER BY " . $data['sort'];	
			} else {
				$sql .= " ORDER BY cp.title";	
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

	public function getTotalProfiles() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cedshopee_profile");

		return $query->row['total'];
	}	
	
	public function getDefaultAttributesMapping()
	{
		return array(
			'name' 			=> 'name',
			'description' 	=> 'description',
			'price' 		=> 'price',
			'stock' 		=> 'quantity',
			'item_sku' 		=> 'sku',
			'weight' 		=> 'weight',
			'package_length'=> 'length',
			'package_width' => 'width',
			'package_height'=> 'height',
			'days_to_ship' 	=> '',
		);
	}	
	public function getDefaultAttributes()
	{
		return array(
			'name' 			=> 'Name',
			'description' 	=> 'Description',
			'price' 		=> 'Price',
			'quantity' 		=> 'Quantity',
			'sku' 			=> 'SKU',
			'weight' 		=> 'Weight',
			'length'		=> 'Length',
			'width' 		=> 'Width',
			'height'		=> 'Height',
			'days_to_ship' 	=> '',
		);
	}	
	public function addProductInProfile($profile_id, $categories, $store, $manufacturer)
	{
		$sql = "SELECT DISTINCT (p.product_id) FROM `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."product_to_store` pts on (pts.product_id = p.product_id) LEFT JOIN `".DB_PREFIX."product_to_category` ptc on (ptc.product_id = p.product_id) where p.quantity >0 ";

		if(!empty($categories)){
			$sql .= "AND ptc.category_id IN(".implode(',', $categories).") ";
		}

		if(!empty($store)){
			$sql .= "AND pts.store_id IN(".implode(',', $store).") ";
		}

		if(!empty($manufacturer)){
			$sql .= "AND p.manufacturer_id IN(".implode(',', $manufacturer).") ";
		}
		
		$result = $this->db->query( $sql);

		if($result && $result->num_rows) {
			
			$products =  array_chunk( $result->rows, 1000);
			foreach ($products as $key => $value) {
				$sql = "INSERT INTO `".DB_PREFIX."cedshopee_profile_products` (`product_id`,`shopee_profile_id`) values ";
				foreach ( $result->rows as $key => $product) {
					$sql .= "('".$product['product_id']."','".$profile_id."'), ";
				}
				$sql = rtrim($sql, ', ');
				$this->db->query($sql);		
			}	
		}
	}

	public function updateProductInProfile($profile_id, $categories, $store, $manufacturer)
	{
		$sql = "SELECT DISTINCT (p.product_id) FROM `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."product_to_store` pts on (pts.product_id = p.product_id) LEFT JOIN `".DB_PREFIX."product_to_category` ptc on (ptc.product_id = p.product_id) where p.quantity >0 ";

		if(!empty($categories)){
			$sql .= "AND ptc.category_id IN(".implode(',', $categories).") ";
		}

		if(!empty($store)){
			$sql .= "AND pts.store_id IN(".implode(',', $store).") ";
		}

		if(!empty($manufacturer)){
			$sql .= "AND p.manufacturer_id IN(".implode(',', $manufacturer).") ";
		}

		$result = $this->db->query($sql);
//echo '<pre>'; print_r($result); die;
		if($result && $result->num_rows) {
			$products =  array_chunk( $result->rows, 1000);
			$delete = $this->db->query("DELETE * FROM `".DB_PREFIX."cedshopee_profile_products` WHERE `product_id` NOT IN '". $products ."' ");
			foreach ($products as $key => $value) {
				foreach ($result->rows as $key => $product)
				{
					$sql = $this->db->query("SELECT * FROM `".DB_PREFIX."cedshopee_profile_products` WHERE `product_id` = '". $product['product_id'] ."' ");

					if($sql->num_rows == 0)
					{
						$this->db->query("INSERT INTO `".DB_PREFIX."cedshopee_profile_products` SET `product_id` = '". $product['product_id'] ."' ,`shopee_profile_id` = '". $profile_id ."' ");
					}
				}
			}
		}
	}
	public function removeProductFromProfile($profile_id)
	{
		$this->db->query("UPDATE `".DB_PREFIX."cedshopee_profile_products` SET shopee_profile_id='0' where `shopee_profile_id`='".$profile_id."'");
	}
	public function getMappedAttributes($profile_id) {
		$query = $this->db->query("SELECT `profile_attribute_mapping` FROM " . DB_PREFIX . "cedshopee_profile WHERE id = '" . (int)$profile_id . "'");
		if($query->num_rows){
			return json_decode($query->row['profile_attribute_mapping'], true);
		}
	}
	
	
	public function UpdateProfileCategory($profile_id){
	    $sql = "select DISTINCT(a.category_id) from  oc_product_to_category as  a left outer join  oc_category as b on a.category_id =b.category_id where b.status =1 order by a.category_id asc";
	    $query = $this->db->query($sql);
	    $categorys="[";
	    $i=0;
	 
	    foreach ($query->rows as $result) {
	        if($i == count($query->rows)-1){
	            $categorys.='"'.$result["category_id"].'"'; 
	        }else{
	            $categorys.='"'.$result["category_id"].'",'; 
	        }
	        $i++;
	    }
	    $categorys.="]";

	    $this->db->query("UPDATE `".DB_PREFIX."cedshopee_profile` SET store_category='".$categorys."' where id='".$profile_id."'");
	}

	public function addNewProduct($product_id = '', $product_data = array())
	{
	    $response = array();
	    
	    if($product_id && !empty($product_data))
	    {
            $mapped_details = $this->db->query("SELECT `id`, `store_category` FROM `". DB_PREFIX ."cedshopee_profile` ");
            
            if($mapped_details->num_rows)
            {
                foreach($mapped_details->rows as $mapped_details)
                {
                    $profile_id = $mapped_details['id'];
                    $store_categories = json_decode($mapped_details['store_category'], true);
                    
                    $existingProducts = $this->db->query("SELECT DISTINCT `product_id` FROM `". DB_PREFIX ."cedshopee_profile_products` WHERE `shopee_profile_id` = '". $profile_id ."' ");
                    $productExist = array();
                    if($existingProducts->num_rows) {
                        foreach($existingProducts->rows as $val) {
                            $productExist[] = $val['product_id'];
                        }
                    }
                    
                    foreach($store_categories as $category_id)
                    {
                        $newProducts = $this->db->query("SELECT DISTINCT `product_id` FROM `" . DB_PREFIX . "product_to_category` WHERE `category_id` = '" . $category_id . "' ");
                        
                        foreach ($newProducts->rows as  $product_array)
                        {
                            if(!in_array($product_array['product_id'], $productExist))
                            {
                                $product_already_exist = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "cedshopee_profile_products` WHERE `product_id` = '" . $product_array['product_id'] . "' ");
                                if($product_already_exist->num_rows == 0)
                                {
                                    $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "cedshopee_profile_products` (`shopee_profile_id` , `product_id`) VALUES ('" . $profile_id . "', '" . $product_array['product_id'] . "')");
                                }
                            }
                        }
                    }
                }
            }
	    }
	}
}
?>
