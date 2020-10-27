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
    public function addReturns($return_data)
    {
        if (isset($return_data['shopee_order_id']) && isset($return_data['orderLine']) && count($return_data['orderLine'])) {
            $shopee_order_id = $return_data['shopee_order_id'];
            $orderLines = $return_data['orderLine'];
            $this->load->library('cedshopee');
            $cedshopee = Cedshopee::getInstance($this->registry);
            $response = array();
            foreach ($orderLines as $key => $orderLine) {
                $orderLine['orderLine'] = $key;
                $response = $cedshopee->returnOrder($shopee_order_id, $orderLine);
                if (isset($response['success']) && $response['success']) {
                    if (isset($response['response']) && $response['response']['order']) {
                        if (isset($response['response']['orderLines']['orderLine'][$key]['return'])) {
                            $response = $response['response']['orderLines']['orderLine'][$key]['return'];
                            $this->db->query("INSERT INTO `" . DB_PREFIX . "cedshopee_return` (`id`, `feedback`, `shopee_order_id`, `returnId`, `returnStatus`, `return_data`) VALUES (NULL, '" . $this->db->escape($response['returnComments']) . "', '" . $this->db->escape($shopee_order_id) . "', '" . $this->db->escape($response['returnId']) . "', '" . $this->db->escape('Created') . "', '" . $this->db->escape($response) . "')");
                        }
                    }
                } else {
                    $cedshopee->log(json_encode($response));
                }

            }
        }
    }
}

?>
    
                                        