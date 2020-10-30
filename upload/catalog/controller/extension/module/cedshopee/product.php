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
class ControllerExtensionModuleCedshopeeProduct extends Controller
{
    public function updateStock()
    {
        $success_msg = array();
        $error_msg = array();
        $this->load->model('extension/module/cedshopee/product');
        $post_data = $this->model_extension_module_cedshopee_product->getAllShopeeProductIds();
        if (is_array($post_data) && !empty($post_data)) {
            $product_ids = $post_data;
            $updated = 0;
            $fail = 0;
            if (is_array($product_ids) && count($product_ids)) 
            {
                $final_response = array();
                foreach ($product_ids as $product_id) 
                {
                    $result = $this->model_extension_module_cedshopee_product->updateInvenetry($product_id, array());

                    if(isset($result['item'])) {
                        $updated++;
                        $final_response['success'][] = $result['item'];
                    } else if(isset($result['error'])) {
                        $fail++;
                        $final_response['error'][] = $result['error'];
                    }
                }
                if ($updated) 
                {
                    if($fail) {
                        $error_msg = implode("<br/>",  $final_response['error']);
                        $this->response->setOutput(json_encode(array('success' => false, 'message' => $error_msg)));
                    }
                    else {
                        $success_msg = implode("<br/>",  $final_response['success']);
                        $this->response->setOutput(json_encode(array('success' => true, 'message' => $success_msg)));
                    }
                } else if($fail) {
                    $error_msg = implode("<br/>",  $final_response['error']);
                    $this->response->setOutput(json_encode(array('success' => false, 'message' => $error_msg)));
                } else {
                    $error_msg = 'Unable to update data.';
                    $this->response->setOutput(json_encode(array('success' => false, 'message' => $error_msg)));
                }
            }
        } else {
            $this->response->setOutput(json_encode(array('success' => false, 'message' => 'No ids to update data')));
        }
    }
    
    public function updatePrice()
    {
        $success_msg = array();
        $error_msg = array();
        $this->load->model('extension/module/cedshopee/product');
        $post_data = $this->model_extension_module_cedshopee_product->getAllShopeeProductIds();
        if (is_array($post_data) && !empty($post_data)) {
            $product_ids = $post_data;
            $updated = 0;
            $fail = 0;
            if (is_array($product_ids) && count($product_ids)) 
            {
                $final_response = array();
                foreach ($product_ids as $product_id) 
                {
                    $result = $this->model_extension_module_cedshopee_product->updatePrice($product_id, array());
                    if(isset($result['item'])) {
                        $updated++;
                        $final_response['success'][] = $result['item'];
                    } else if(isset($result['error'])) {
                        $fail++;
                        $final_response['error'][] = $result['error'];
                    }
                }

                if ($updated) 
                {
                    if($fail) {
                        $error_msg = implode("<br/>",  $final_response['error']);
                        $this->response->setOutput(json_encode(array('success' => false, 'message' => $error_msg)));
                    }
                    else {
                        $success_msg = implode("<br/>",  $final_response['success']);
                        $this->response->setOutput(json_encode(array('success' => true, 'message' => $success_msg)));
                    }
                } else if($fail) {
                    $error_msg = implode("<br/>",  $final_response['error']);
                    $this->response->setOutput(json_encode(array('success' => false, 'message' => $error_msg)));
                } else {
                    $error_msg = 'Unable to update data.';
                    $this->response->setOutput(json_encode(array('success' => false, 'message' => $error_msg)));
                }
            }
        } else {
            $this->response->setOutput(json_encode(array('success' => false, 'message' => 'No ids to update data')));
        }
    }

    public function uploadProduct()
    {
        $this->load->model('extension/module/cedshopee/product');
        $product_ids = $this->model_extension_module_cedshopee_product->getAllShopeeProductIds();
        if (is_array($product_ids) && !empty($product_ids)) {
            
            $this->load->library('cedshopee');
            $cedshopee = Cedshopee::getInstance($this->registry);

            $status = $cedshopee->uploadProducts($product_ids);
            if (isset($status['success']) && !empty($status['success'])) {
                $json['success'] = true;
                $json['message'] = $status['success'];
            } else {
                $json['success'] = false;
                $json['message'] = $status['error'];
            }
        } else {
            $this->response->setOutput(json_encode(array('success' => false, 'message' => 'No ids to update data')));
        }
    }
}