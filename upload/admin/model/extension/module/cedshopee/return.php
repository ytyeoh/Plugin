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
class ModelExtensionModuleCedshopeeReturn extends Model
{

    public function getTotalReturns()
    {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "cedshopee_return`";
        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getReturns($data = array())
    {
        $sql = "SELECT * FROM  `" . DB_PREFIX . "cedshopee_return` where return_data !=''";
        if (!empty($data['filter_returnsn'])) {
            $sql .= " AND returnsn LIKE '" . $this->db->escape($data['filter_returnsn']) . "%'";
        }

        if (!empty($data['filter_ordersn'])) {
            $sql .= " AND ordersn LIKE '" . $this->db->escape($data['filter_ordersn']) . "%'";
        }

        if (!empty($data['filter_reason'])) {
            $sql .= " AND reason = '" . $this->db->escape($data['filter_reason']) . "'";
        }

        $sort_data = array(
            'returnsn',
            'ordersn',
            'reason'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY returnsn";
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

    public function getReturn($return_id)
    {
        $sql = "SELECT * FROM  `" . DB_PREFIX . "cedshopee_return` where `id`=" . $return_id;
        $query = $this->db->query($sql);
        return $query->row;
    }

    public function addReturns($return_data)
    {
        if (isset($return_data['shopee_order_id']) && isset($return_data['orderLine']) && count($return_data['orderLine'])) {
            $shopee_order_id = $return_data['shopee_order_id'];
            $orderLines = $return_data['orderLine'];
            $this->load->library('cedshopee');
            $cwal_lib = Cedshopee::getInstance($this->registry);
            $response = array();
            foreach ($orderLines as $key => $orderLine) {
                $orderLine['orderLine'] = $key;
                $response = $cwal_lib->returnOrder($shopee_order_id, $orderLine);
                if (isset($response['success']) && $response['success']) {
                    if (isset($response['response']) && $response['response']['order']) {
                        if (isset($response['response']['orderLines']['orderLine'][$key]['return'])) {
                            $response = $response['response']['orderLines']['orderLine'][$key]['return'];
                            $this->db->query("INSERT INTO `" . DB_PREFIX . "cedshopee_return` (`id`, `feedback`, `shopee_order_id`, `returnId`, `returnStatus`, `return_data`) VALUES (NULL, '" . $this->db->escape($response['returnComments']) . "', '" . $this->db->escape($shopee_order_id) . "', '" . $this->db->escape($response['returnId']) . "', '" . $this->db->escape('Created') . "', '" . $this->db->escape($response) . "')");
                        }
                    }
                } else {
                    $cwal_lib->log(json_encode($response));
                }

            }
        }
    }
    public function saveReturnDispute($return_id, $reqest, $response) {
        $sql = "UPDATE `" . DB_PREFIX . "cedshopee_return` SET `dispute_request` = '".$this->db->escape(json_encode($reqest))."', `dispute_response` = '".$this->db->escape(json_encode($response))."' where `id`=" . $return_id;
        $this->db->query($sql);
    }
    public function getReturnReasons() {
        return array(
            'NON_RECEIPT'=> 'NON_RECEIPT',
            'OTHER'=> 'OTHER',
            'NOT_RECEIVED'=> 'NOT_RECEIVED',
            'UNKNOWN'=> 'UNKNOWN',
        );
    }

    public function getDisputeDetails($return_id)
    {
        $sql = "SELECT `dispute_request` FROM  `" . DB_PREFIX . "cedshopee_return` where `id`=" . $return_id;
        $query = $this->db->query($sql);
        $dispute_request = array();
        if($query->num_rows){
            $dispute_request = json_decode($query->row['dispute_request'], true);
        }
        return $dispute_request;
    }
}

?>
	
