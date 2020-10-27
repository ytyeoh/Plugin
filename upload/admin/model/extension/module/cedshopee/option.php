<?php
/**
* CedCommerce
*
* NOTICE OF LICENSE
*
* This source file is subject to the End User License Agreement (EULA)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://cedcommerce.com/license-agreement.txt
*
* @category  modules
* @package   cedshopee
* @author    CedCommerce Core Team 
* @copyright Copyright CEDCOMMERCE (http://cedcommerce.com/)
* @license   http://cedcommerce.com/license-agreement.txt
*/
class ModelExtensionModuleCedShopeeOption extends Model {

	public function getOptions($data = array()) {
		$sql = "SELECT * FROM `".DB_PREFIX."cedshopee_option` wm_attr";

		if (!empty($data['filter_name'])) {
			$sql .= " Where cedshopee_attribute_name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = array(
			'cedshopee_attribute_name',
			'cedshopee_attribute_level',
			'cedshopee_attribute_type',
			'is_mapped'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY cedshopee_attribute_name";
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

	public function getTotalOptions() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cedshopee_option");

		return $query->row['total'];
	}
	public function addMapping($data) {
		//cedshopee_attribute_mapping
		if (isset($data['mapped_option']) && count($data['mapped_option'])) {
			if (is_array($data['mapped_option']) && count($data['mapped_option'])) {
				$sql="DELETE FROM `".DB_PREFIX."cedshopee_option_mapping`";
				$result=$this->db->query($sql);
				foreach ($data['mapped_option'] as $key => $value) {
					if (isset($value['name']) && isset($value['option_id']) && isset($value['wallmart_attribute']) && isset($value['cedshopee_id'])) {
				        
				        $sql="SELECT `id` FROM `".DB_PREFIX."cedshopee_option_mapping` where `option_id`='".$value['option_id']."'";
						$result=$this->db->query($sql);
                        if($result && $result->num_rows)
                        	continue;
						$sql="INSERT INTO `".DB_PREFIX."cedshopee_option_mapping` (`id`, `name`, `option_id`, `cedshopee_id`, `wallmart_attribute`,`wparent_category`,`wchild_category`) VALUES (NULL, '".$this->db->escape($value['name'])."', '".$this->db->escape($value['option_id'])."', '".$this->db->escape($value['cedshopee_id'])."', '".$this->db->escape($value['wallmart_attribute'])."', '".$this->db->escape($data['parent_wcat'])."', '".$this->db->escape($data['children_category'])."');";
						$this->db->query($sql);
					}
				}
			}
		}
	}
	public function getOptionMappings($parent_wcat,$children_category){
		$sql="SELECT * FROM `".DB_PREFIX."cedshopee_option_mapping` where `wparent_category`='".$this->db->escape($parent_wcat)."' AND `wchild_category`='".$this->db->escape($children_category)."'";
		$result = $this->db->query($sql);
		if ($result && $result->num_rows) {
			return $result->rows;
		} else {
			return array();
		}
	}
	public function getMappedOptionIdsByCategory($cat_id , $parent_cat_id) {
		$sql="SELECT `option_id` FROM `".DB_PREFIX."cedshopee_option_mapping` where `wparent_category`='".$this->db->escape($parent_cat_id)."' AND `wchild_category`='".$this->db->escape($cat_id)."'";
		$result = $this->db->query($sql);
		if ($result && $result->num_rows) {
			$option_ids = array();
			foreach ($result->rows as $key => $value) {
				$option_ids[] = $value['option_id'];
			}
			return $option_ids;
		} else {
			return array();
		}
	}

	public function getStoreOptions()
	{
		$sql = "SELECT * FROM `" . DB_PREFIX . "option` o LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE od.language_id = '" . (int)$this->config->get('config_language_id') . "' AND `type` IN ('checkbox','select','radio') ORDER BY od.name";
		$result = $this->db->query($sql);
		$options = $result->rows;
		$option_value_data = array();
		if (!empty($options)) {
			foreach ($options as $option) {
				$option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE ov.option_id = '" . (int)$option['option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order ASC");
				foreach ($option_value_query->rows as $option_value) {
					$option_value_data[$option['option_id']][] = array(
						'option_value_id' => $option_value['option_value_id'],
						'name' => $option_value['name'],
					);
				}
			}
			return array('options' => $options, 'option_values' => $option_value_data);
		}
		return array('options' => $options, 'option_values' => $option_value_data);
	}

}
