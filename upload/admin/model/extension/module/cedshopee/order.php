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
class ModelExtensionModuleCedshopeeOrder extends Model
{
	protected $error=array();

	public function getOrders($data = array()) {
		$sql = "SELECT wo.shopee_order_id ,wo.status as `wstatus`, o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o JOIN `" . DB_PREFIX . "cedshopee_order` wo on (o.order_id = wo.opencart_order_id)";

		if (isset($data['filter_order_status'])) {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id) {
				$implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} else {
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total'])) {
			$sql .= " AND o.total = '" . (float)$data['filter_total'] . "'";
		}

		$sort_data = array(
			'o.order_id',
			'customer',
			'order_status',
			'o.date_added',
			'o.date_modified',
			'o.total'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY o.order_id";
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
	public function getTotalOrders($data = array())
	{
		$sql = "SELECT wo.shopee_order_id ,wo.status as `wstatus`, o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o JOIN `" . DB_PREFIX . "cedshopee_order` wo on (o.order_id = wo.opencart_order_id)";

		if (isset($data['filter_order_status'])) {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id) {
				$implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} else {
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total'])) {
			$sql .= " AND o.total = '" . (float)$data['filter_total'] . "'";
		}

		$sort_data = array(
			'o.order_id',
			'customer',
			'order_status',
			'o.date_added',
			'o.date_modified',
			'o.total'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY o.order_id";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		$query = $this->db->query($sql);

		return $query->num_rows;
	}
	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT `order_data` FROM `" . DB_PREFIX . "cedshopee_order` WHERE opencart_order_id = '" . (int)$order_id . "'");

		if ($order_query->num_rows) {
			if (isset($order_query->row['order_data']) && $order_query->row['order_data']) {
				$order = json_decode($order_query->row['order_data'], true);
				if ($order) {
					return $order;
				} else {
					return array();
				} 
			}
			
		} else {
			return array();
		}
	}
	public function getopencartstatecode($Statecode)
	{
		$query=$this->db->query("SELECT `zone_id` FROM ".DB_PREFIX."zone WHERE country_id='223' AND code='".$Statecode."'");
		if($query->num_rows)
		{
			
			return $query->rows[0]['zone_id'];
		}
	}
	public function getopencartstatename($Statecode)
	{
		$query=$this->db->query("SELECT `name` FROM `".DB_PREFIX."zone` WHERE `code`='".$Statecode."' AND `country_id`='223'");
		if($query->num_rows)
		{
			
			return $query->rows[0]['name'];
		}
	}
	public function getrejectedOrders()
  	{
    	$sql="SELECT * FROM `".DB_PREFIX."cedshopee_order_error`";
    	$query=$this->db->query($sql);
	    return $query->rows;
  	}
  	public function getRejectedTotals()
  	{
    	$sql="SELECT * FROM `".DB_PREFIX."cedshopee_order_error`";
    	$query=$this->db->query($sql);
    	return count($query->rows);
  	}
  	public function getRejectedOrder($id)
  	{
    	$sql="SELECT `order_data` FROM `".DB_PREFIX."cedshopee_order_error` where `id` = '".$id."'";
    	$query=$this->db->query($sql);
    	if ($query && $query->num_rows) {
    		return json_decode($query->row['order_data'], true);
    	} else {
    		return array();
    	}
    	
  	}
  	public function feedbackOptionArray()
    {
        return array(
                'item damaged' => 'item damaged',
                'not shipped in original packaging' => 'not shipped in original packaging',
                'customer opened item' => 'customer opened item'
        );
    }

	public function getCancelReasons() {
		return array(
			'OUT_OF_STOCK'=> 'OUT_OF_STOCK',
			'CUSTOMER_REQUEST'=> 'CUSTOMER_REQUEST',
			'UNDELIVERABLE_AREA'=> 'UNDELIVERABLE_AREA',
			'COD_NOT_SUPPORTED'=> 'COD_NOT_SUPPORTED',
		);
	}




    public function methodCodeArrray() {
    	return array(
    			'Standard' 		=> 'Standard',
               	'Express' 		=> 'Express',
				'Oneday' 		=> 'Oneday',
				'Freigh' 		=> 'Freigh',
				'WhiteGlove' 	=> 'WhiteGlove',
				'Value'         => 'Value' 
				);
    }

    public function carrierNameArray() {
    	return array(
    		'UPS' 		=> 'UPS',
            'USPS' 		=> 'USPS',
			'FedEx' 	=> 'FedEx',
			'Airborne' 	=> 'Airborne',
			'OnTrac' 	=> 'OnTrac'
			);
    }

    public function saveShipping($ship_data) {
    	$this->load->library('cedshopee');
    	$cedshopee = Cedshopee::getInstance($this->registry);
		$status = $cedshopee->shipOrder($ship_data);
    	if (isset($status['success']) && $status['success']) {
    		$this->db->query("UPDATE `".DB_PREFIX."cedshopee_order` SET `shipment_response_data` = '".$this->db->escape($status['response'])."' , `shipment_request_data` = '".$this->db->escape(json_encode($ship_data))."' , `status`='shiped' where `shopee_order_id` = '".$ship_data['shopee_order_id']."'");
    		return array('success' => true, 'response' => 'Shipment Created Successfully.');
    	} else {
    		return array('success' => false, 'response' => $status['message']);;
    	}
    }

    public function getOrderByPurchaseOrderId($shopee_order_id) {
    	$result = $this->db->query("SELECT `order_data` FROM `".DB_PREFIX."cedshopee_order` where `shopee_order_id`='".$shopee_order_id."'");
    	if ($result && $result->num_rows) {
    		return json_decode($result->row['order_data'], true);
    	} else {
    		return array();
    	}
    	
    }
}