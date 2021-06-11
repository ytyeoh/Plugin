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
class ModelExtensionModuleCedshopeeAttribute extends Model {

	public function getAttributes($data = array()) {
		$sql = "SELECT * FROM `".DB_PREFIX."cedshopee_attributes`";

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

	public function getTotalAttributes() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cedshopee_attributes");

		return $query->row['total'];
	}
	public function addMapping($data) {
		//cedshopee_attribute_mapping
		if (isset($data['mapped_attribute']) && count($data['mapped_attribute'])) {
			if (is_array($data['mapped_attribute']) && count($data['mapped_attribute'])) {
				$sql="DELETE FROM `".DB_PREFIX."cedshopee_attribute_mapping`";
				$result=$this->db->query($sql);
				foreach ($data['mapped_attribute'] as $key => $value) {
					if (isset($value['name']) && isset($value['attribute_id']) && isset($value['wallmart_attribute']) && isset($value['cedshopee_id'])) {
				        
				        $sql="SELECT `id` FROM `".DB_PREFIX."cedshopee_attribute_mapping` where `attribute_id`='".$value['attribute_id']."'";
						$result=$this->db->query($sql);
						if($result && $result->num_rows)
                        continue;
						$sql="INSERT INTO `".DB_PREFIX."cedshopee_attribute_mapping` (`id`, `name`, `attribute_id`, `cedshopee_id`, `wallmart_attribute`,`wparent_category`,`wchild_category`) VALUES (NULL, '".$this->db->escape($value['name'])."', '".$this->db->escape($value['attribute_id'])."', '".$this->db->escape($value['cedshopee_id'])."', '".$this->db->escape($value['wallmart_attribute'])."', '".$this->db->escape($data['parent_wcat'])."', '".$this->db->escape($data['children_category'])."');";
						$this->db->query($sql);
					}
				}
			}
		}
	}
	public function getAttributeMappings($parent_wcat,$children_category){
		$sql="SELECT * FROM `".DB_PREFIX."cedshopee_attribute_mapping` where `wparent_category`='".$this->db->escape($parent_wcat)."' AND `wchild_category`='".$this->db->escape($children_category)."'";
		$result = $this->db->query($sql);
		if ($result && $result->num_rows) {
			return $result->rows;
		} else {
			return array();
		}
	}
	
}
