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
class ControllerExtensionModuleCedshopeeReturn extends Controller
{
    public function index()
    {
        $this->load->model('extension/module/cedshopee/return');
        $this->load->library('cedshopee');
        $cedshopee = Cedshopee::getInstance($this->registry);
        $status = $cedshopee->isEnabled();
        if ($status) {
            $url = 'returns/get';
            $cedshopee->log($url);
            $params = array('pagination_entries_per_page' => 100, 'pagination_offset' => 0);
            $return_data = $cedshopee->fetchReturn($url, $params);
            $cedshopee->log('Order Fetch: data');
            $cedshopee->log(json_encode($return_data));
            if ($return_data && isset($return_data['success']) && $return_data['success']) {
                $this->model_extension_module_cedshopee_return->addReturns($return_data['response']);
            }
            $this->response->setOutput(json_encode($return_data));
        } else {
            $this->response->setOutput(json_encode(array('success' => false, 'message' => 'Module status is disabled.')));
        }
    }
}