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
class ControllerExtensionModuleCedshopeeOrder extends Controller
{
    public function index()
    {
        $this->load->library('cedshopee');
        $cedshopee = Cedshopee::getInstance($this->registry);
        $status = $cedshopee->isEnabled();
        if ($status) {
            $url = 'orders/get';
            $cedshopee->log($url);
            $createdTimeTo = date('Y-m-d h:i:s a', strtotime("-1 days"));
            $params = array(
                'order_status' => 'ALL',  
                'create_time_to' => strtotime($createdTimeTo)
                );      
            $order_data = $cedshopee->fetchOrder($url, $params);
            $cedshopee->log('Order Fetch: data');
            $cedshopee->log(json_encode($order_data));
            $this->response->setOutput(json_encode($order_data));
        } else {
            $this->response->setOutput(json_encode(array('success' => false, 'message' => 'Module status is disabled.')));
        }
    }
}