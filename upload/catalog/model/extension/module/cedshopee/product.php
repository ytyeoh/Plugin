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
class ModelExtensionModuleCedshopeeProduct extends Model {

    public function getAllShopeeProductIds() {
        $product_ids = array();
        $query = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "cedshopee_profile_products`");
        if ($query && $query->num_rows) {
            foreach ($query->rows as $key => $value) {
                if (isset($value['product_id']) && $value['product_id']) {
                    $product_ids[] = $value['product_id'];
                }
            }
        }
        return $product_ids;
    }

	public function getShopeeItemId($product_id=0) {
        if ($product_id) {
            $shopee_item_id = '';
            $sql = "SELECT `shopee_item_id` FROM `".DB_PREFIX."cedshopee_uploaded_products` where `product_id`='".$product_id."'";
            $result = $this->db->query($sql);
            if ($result && $result->num_rows && isset($result->row['shopee_item_id'])) {
                $shopee_item_id = $result->row['shopee_item_id'];
            }
            return $shopee_item_id;
        }
        return false;
    }

    public function updateInvenetry($product_id, $data)
    {
        try {
            $result = array();
            $shopee_item_id = $this->getShopeeItemId($product_id);
    
            if (isset($shopee_item_id) && !empty($shopee_item_id))
            {
                $this->load->library('cedshopee');
                $cedshopee = Cedshopee::getInstance($this->registry);
                $quantity = $cedshopee->getCedShopeeQuantity($product_id);
                $variants = $cedshopee->isVariantProduct($product_id, $data);
               
                if (isset($variants['variations']) && !empty($variants['variations']))
                {
                     $success_message = array();
                     $error_message = array();
                     $response = array();
                    foreach ($variants['variations'] as $key => $value)
                    {
                        if(isset($value['variation_id']) && !empty($value['variation_id']))
                        {
                            if($value['stock'] < '0')
                                $value['stock'] = '0';
                            $stock_data = array(
                                'stock' => (int)$value['stock'],
                                'variation_id' => (int)$value['variation_id'],
                                'item_id'=>(int)$shopee_item_id,
                            );
                            $cedshopee->log('items/update_variation_stock');
                            $result = $cedshopee->postRequest('items/update_variation_stock', $stock_data);
    
                            if(isset($result['item']) && $result['item'])
                            {
                                $this->db->query("UPDATE `". DB_PREFIX ."cedshopee_product_variations` 
                                SET `stock` = '". $value['stock'] ."'
                                 WHERE `product_id` = '". (int) $product_id ."' 
                                 AND `variation_id` = '". (int) $value['variation_id'] ."' ");
                                $success_message[] = 'Variation ID - ' . $value['variation_id'] . ' Quantity Updated Successfully!';
                            } else {
                                $error_message[] = $result['msg'] . ' - ' . $value['variation_id'];
                            }
                            $cedshopee->log(json_encode($result));
    
                        } else {
                            if($quantity < '0')
                                $quantity = '0';
                            $cedshopee->log('items/update_stock');
                            $result = $cedshopee->postRequest('items/update_stock',array('stock'=> (int)$quantity, 'item_id'=>(int)$shopee_item_id));
                            if(isset($result['msg']) && $result['msg'])
                            {
                                $error_message[] = $result['msg'];
                            } else {
                                $success_message[] = 'Quantity Updated Successfully!';
                            }
                            $cedshopee->log(json_encode($result));
    
                        }
                    }
                } else {
                    if($quantity < '0')
                        $quantity = '0';
                    $cedshopee->log('items/update_stock');
                    $result = $cedshopee->postRequest('items/update_stock',array('stock'=> (int)$quantity, 'item_id'=>(int)$shopee_item_id));
                    if(isset($result['msg']) && $result['msg'])
                    {
                        $error_message[] = $result['msg'];
                    } else {
                        $success_message[] = 'Quantity Updated Successfully!';
                    }
                    $cedshopee->log(json_encode($result));
    
                }
            }
    
            if(isset($error_message) && is_array($error_message) && $error_message)
            {
                $result['error'] = 'Product ID - ' . $product_id . ' : ' . implode(" , ", $error_message);
            } else if(isset($success_message) && is_array($success_message) && $success_message) {
                $result['item'] = 'Product ID - ' . $product_id . ' : ' . implode(" , ", $success_message);
            }
        } catch (Exception $e) {
            $result['error'] = 'Product ID - ' . $product_id . ' : ' . $e->getMessage();
        }

        return $result ;
    }

    public function updatePrice($product_id, $data)
    {
        try {
            $result = array();
            $price_data = array();
            $shopee_item_id = $this->getShopeeItemId($product_id);
           
            if (isset($shopee_item_id) && !empty($shopee_item_id))
            {
                $this->load->library('cedshopee');
                $cedshopee = Cedshopee::getInstance($this->registry);
                $price = $cedshopee->getCedShopeePrice($product_id);
                $variants = $cedshopee->isVariantProduct($product_id, $data);
                
                if (isset($variants['variations']) && !empty($variants['variations']))
                {
                    $success_message = array();
                    $error_message = array();
                    $response = array();
                    foreach ($variants['variations'] as $key => $value)
                    {
                        if(isset($value['variation_id']) && !empty($value['variation_id']))
                        {
                            $price_data = array(
                                'item_id'=>(int)$shopee_item_id,
                                'variation_id' => (int)$value['variation_id'],
                                'price' => (float)$value['price']
                            );
                            $cedshopee->log('items/update_variation_price');
                            $result = $cedshopee->postRequest('items/update_variation_price',$price_data);
    //                        echo '<pre>'; print_r($result);
                            if(isset($result['item']) && $result['item'])
                            {
                                $this->db->query("UPDATE `". DB_PREFIX ."cedshopee_product_variations` 
                                SET `price` = '". $value['price'] ."'
                                 WHERE `product_id` = '". (int) $product_id ."' 
                                 AND `variation_id` = '". (int) $value['variation_id'] ."' ");
                                $success_message[] = 'Variation ID - ' . $value['variation_id'] . ' Price Updated Successfully!';
                            } else {
                                $error_message[] = $result['msg'] . ' - ' . $value['variation_id'];
                            }
                            $cedshopee->log(json_encode($result));
    
                        } else {
                            $cedshopee->log('items/update_price');
                            $result = $cedshopee->postRequest('items/update_price',array('price'=> (float)$price, 'item_id'=>(int)$shopee_item_id));
    
                            if(isset($result['msg']) && $result['msg'])
                            {
                                $error_message[] = $result['msg'];
                            } else {
                                $success_message[] = 'Price Updated Successfully!';
                            }
                            $cedshopee->log(json_encode($result));
    
                        }
                    }
                } else {
    
                    $cedshopee->log('items/update_price');
                    $result = $cedshopee->postRequest('items/update_price',array('price'=> (float)$price, 'item_id'=>(int)$shopee_item_id));
                    if(isset($result['msg']) && $result['msg'])
                    {
                        $error_message[] = $result['msg'];
                    } else {
                        $success_message[] = 'Price Updated Successfully!';
                    }
                    $cedshopee->log(json_encode($result));
    
                }
            }
            if(isset($error_message) && is_array($error_message) && $error_message)
            {
                $result['error'] = 'Product ID - ' . $product_id . ' : ' . implode(" , ", $error_message);
            } else if(isset($success_message) && is_array($success_message) && $success_message) {
                $result['item'] = 'Product ID - ' . $product_id . ' : ' . implode(" , ", $success_message);
            }
        } catch (Exception $e) {
            $result['error'] = 'Product ID - ' . $product_id . ' : ' . $e->getMessage();
        }
        
        return $result ;
    }
}