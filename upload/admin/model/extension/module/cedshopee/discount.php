<?php 
class ModelExtensionModuleCedshopeeDiscount extends Model {
	public function addDiscount($data) {

		$timezone_name = timezone_name_from_abbr("", $data['timezone_offset_minutes']*60, false);

		$datetimeFormat = 'Y-m-d H:i:s A';
        $startDate = new \DateTime($data['start_date'], new \DateTimeZone($timezone_name));
        $startDate->setTimestamp($data['start_time']);
        $start_date = $startDate->format($datetimeFormat);

        $endDate = new \DateTime($data['end_date'], new \DateTimeZone($timezone_name));
        $endDate->setTimestamp($data['end_time']);
        $end_date = $endDate->format($datetimeFormat);

        if(!isset($data['shopee_items']))
        	$data['shopee_items'] = '';
        elseif(isset($data['shopee_items']) && !empty($data['shopee_items']))
        	$data['shopee_items'] = $data['shopee_items'];

		$this->db->query("INSERT INTO " . DB_PREFIX . "cedshopee_discount SET 
			discount_id = '" . (int)$data['discount_id'] . "', 
			discount_name = '" . $this->db->escape($data['discount_name']) . "', 
			items = '" . $this->db->escape(json_encode($data['shopee_items'])) . "' , 
			start_date = '" . $this->db->escape($start_date) . "', 
			end_date = '" . $this->db->escape($end_date) . "', 
			`discount_item_price` = '". (float) $data['discount_item_price'] ."', 
			`discount_item_variation_price` = '". (float) $data['discount_item_variation_price'] ."',
			`purchase_limit` = '". (int) $data['purchase_limit'] ."',
			`price_type` = '". (int) $data['price_type'] ."'
		");
	}

	public function editDiscount($discount_id, $data) 
	{
		// $timezone_name = timezone_name_from_abbr("", $data['timezone_offset_minutes']*60, false);
  //       $datetimeFormat = 'Y-m-d H:i:s A';
  //       $startDate = new \DateTime($data['start_date'], new \DateTimeZone($timezone_name));
  //       $startDate->setTimestamp($data['start_time']);
  //       $start_date = $startDate->format($datetimeFormat);

  //       $endDate = new \DateTime($data['end_date'], new \DateTimeZone($timezone_name));
  //       $endDate->setTimestamp($data['end_time']);
  //       $end_date = $endDate->format($datetimeFormat);

		$this->db->query("UPDATE " . DB_PREFIX . "cedshopee_discount SET 
			discount_name = '" . $this->db->escape($data['discount_name']) . "', 
			items = '" . $this->db->escape(json_encode($data['shopee_items'])) . "' ,
			`discount_item_price` = '". (float) $data['discount_item_price'] ."', 
			`discount_item_variation_price` = '". (float) $data['discount_item_variation_price'] ."',
			`purchase_limit` = '". (int) $data['purchase_limit'] ."',
			`price_type` = '". (int) $data['price_type'] ."'
			WHERE discount_id = '" . (int)$discount_id. "'
		");
	}

	public function deleteDiscountByDiscountId($discount_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "cedshopee_discount WHERE discount_id = '" . (int)$discount_id. "'");
	}

	public function deleteLogistic($discount_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "cedshopee_discount WHERE id = '" . (int)$discount_id. "'");
	}

	public function getDiscount($discount_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cedshopee_discount WHERE id = '" . (int)$discount_id. "'");

		return $query->row;
	}

	public function getDiscounts($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "cedshopee_discount WHERE discount_id >= '0'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND discount_name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_logistic_id'])) {
			$sql .= " AND discount_id = '" . $this->db->escape($data['filter_logistic_id']) . "'";
		}

		$sort_data = array(
			'discount_name',
			'discount_id',
			'status'
		);	

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];	
		} else {
			$sql .= " ORDER BY discount_name";
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

	public function getTotalDiscounts() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cedshopee_discount");

		return $query->row['total'];
	}	

}
?>