<?php 
class ModelExtensionModuleCedshopeeLogistics extends Model {
	public function addLogistic($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "cedshopee_logistics");
		foreach ($data as $logistic) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "cedshopee_logistics SET logistic_id = '" . (int)$logistic['logistic_id'] . "', logistic_name = '" . strip_tags(html_entity_decode($this->db->escape($logistic['logistic_name']), ENT_QUOTES, 'UTF-8')) . "', sizes = '" . $this->db->escape(json_encode($logistic['sizes'])) . "' , item_max_dimension = '" . $this->db->escape(json_encode($logistic['item_max_dimension'])) . "', weight_limits = '" . $this->db->escape(json_encode($logistic['weight_limits'])) . "', fee_type = '" . $this->db->escape($logistic['fee_type']) . "', enabled = '" . (int)$logistic['enabled'] . "', has_cod = '" . (int)$logistic['has_cod'] . "'");
		}
	}

	public function editLogistic($attribute_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "cedshopee_logistics SET logistic_id = '" . (int)$data['logistic_id'] . "', logistic_name = '" . strip_tags(html_entity_decode($this->db->escape($logistic['logistic_name']), ENT_QUOTES, 'UTF-8')) . "', sizes = '" . $this->db->escape(json_encode($data['sizes'])) . "' , item_max_dimension = '" . $this->db->escape(json_encode($data['item_max_dimension'])) . "', weight_limits = '" . $this->db->escape(json_encode($data['weight_limits'])) . "', fee_type = '" . $this->db->escape($data['fee_type']) . "', enabled = '" . (int)$data['enabled'] . "', has_cod = '" . (int)$data['has_cod'] . "' where id = '" . (int)$attribute_id. "'");
	}

	public function deleteLogistic($logistic_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "cedshopee_logistics where logistic_id = '" . (int)$logistic_id. "'");
	}

	public function getLogistic($attribute_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cedshopee_logistics where id = '" . (int)$attribute_id. "'");

		return $query->row;
	}

	public function getLogistics($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "cedshopee_logistics WHERE logistic_id >= '0'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND ad.logistic_name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_logistic_id'])) {
			$sql .= " AND logistic_id = '" . $this->db->escape($data['filter_logistic_id']) . "'";
		}

		$sort_data = array(
			'logistic_name',
			'logistic_id',
			'status'
		);	

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];	
		} else {
			$sql .= " ORDER BY logistic_name";	
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

	public function getTotalLogistics() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cedshopee_logistics");

		return $query->row['total'];
	}	
	
}
?>