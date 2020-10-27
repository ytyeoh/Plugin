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
class ControllerExtensionModuleCedshopeeSuccess extends Controller
{
    public function index()
    {
        $json = array();
        if(isset($this->request->get['shop_id']) && !empty($this->request->get['shop_id']))
        {
            $sql = $this->db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE `code` = 'cedshopee' AND `key` = 'cedshopee_shop_id' AND store_id = '0'");
            if($sql->num_rows)
            {
                $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($this->request->get['shop_id']) . "' WHERE `code` = 'cedshopee' AND `key` = 'cedshopee_shop_id' AND store_id = '0'");
            } else {
                $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($this->request->get['shop_id']) . "', `code` = 'cedshopee', `key` = 'cedshopee_shop_id', store_id = '0'");
            }
            $json['success'] = true;
            $json['message'] = 'Shop ID - '. $this->request->get['shop_id'] .' successfully fetched';
        } else {
            $json['success'] = false;
            $json['message'] = 'Shop ID not found';
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}

?>