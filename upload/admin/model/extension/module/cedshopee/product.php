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

    public function getProducts($data = array()) 
    {
        $sql = "SELECT cup.*,csp.*,p.*,p.product_id as product_id, pd.* FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id) JOIN `" . DB_PREFIX . "cedshopee_profile_products` cpp ON (p.product_id = cpp.product_id) LEFT JOIN `".DB_PREFIX."cedshopee_profile` csp ON (csp.id = cpp.shopee_profile_id) LEFT JOIN `".DB_PREFIX."cedshopee_uploaded_products` cup ON (cup.product_id = p.product_id)";

        $sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape(trim($data['filter_name'])) . "%'";
        }

        if (!empty($data['filter_profile_name'])){
            $sql .= 'AND csp.id = '.(int)$data['filter_profile_name'];
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape(trim($data['filter_model'])) . "%'";
        }

        if (isset($data['filter_price']) && !is_null($data['filter_price'])) {
            $sql .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
        }

        if (isset($data['filter_quantity']) && !is_null($data['filter_quantity'])) {
            $sql .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
        }

        if (isset($data['filter_status'])) {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (isset($data['filter_shopee_status']) && !is_null($data['filter_shopee_status'])) {
            if( $data['filter_shopee_status'] == "NotUploaded"){
                $sql .= " AND cup.product_id is null ";
            }else{
            $sql .= " AND cup.shopee_status = '" . $data['filter_shopee_status'] . "'";
            }
        }

        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'pd.name',
            'p.model',
            'p.price',
            'p.quantity',
            'p.status',
            'p.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name";
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

    public function getAllProfiles()
    {
        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."cedshopee_profile`");
        if ($query->num_rows) {
            return $query->rows;
        } else {
            return array();
        }
    }

    public function getProductSpecials($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "' ORDER BY priority, price");

        return $query->rows;
    }

    public function getProductOptionValue($option_id,$product_id){
        $option_value_data = array();

        $option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE ov.option_id = '" . (int)$option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order, ovd.name");
        $sql="SELECT `option_value_id` FROM `" . DB_PREFIX . "product_option_value` where `product_id`='".$product_id."' and `option_id`='". (int)$option_id ."'";
        $selectedOptions=$this->db->query($sql);
        $val=array();
        if($selectedOptions->num_rows){
            foreach ($selectedOptions->rows as $key => $value) {
                $val[]=$value['option_value_id'];
            }
        }
        foreach ($option_value_query->rows as $option_value) {
            if(in_array($option_value['option_value_id'],$val)){
                $option_value_data[] = array(
                    'option_value_id' => $option_value['option_value_id'],
                    'name'            => $option_value['name'],
                    'image'           => $option_value['image'],
                    'sort_order'      => $option_value['sort_order']
                );
            }
        }

        return $option_value_data;
    }

    public function getTotalProducts($data = array()) {
        $sql = "SELECT count(DISTINCT cpp.product_id) as total FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id) JOIN `" . DB_PREFIX . "cedshopee_profile_products` cpp ON (p.product_id = cpp.product_id) LEFT JOIN `".DB_PREFIX."cedshopee_profile` csp ON (csp.id = cpp.shopee_profile_id) LEFT JOIN `".DB_PREFIX."cedshopee_uploaded_products` cup ON (cup.product_id = p.product_id)";
        
        $sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape(trim($data['filter_name'])) . "%'";
        }

        if (!empty($data['filter_profile_name'])){
            $sql .= 'AND csp.id = '.(int)$data['filter_profile_name'];
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape(trim($data['filter_model'])) . "%'";
        }

        if (isset($data['filter_price']) && !is_null($data['filter_price'])) {
            $sql .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
        }

        if (isset($data['filter_quantity']) && !is_null($data['filter_quantity'])) {
            $sql .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
        }

        if (isset($data['filter_status'])) {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (isset($data['filter_shopee_status']) && !is_null($data['filter_shopee_status'])) {
            if( $data['filter_shopee_status'] == "NotUploaded"){
                $sql .= " AND cup.product_id is null ";
            }else{
                $sql .= " AND cup.shopee_status = '" . $data['filter_shopee_status'] . "'";
            }
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getShopeeStatuses() {

        return array(
            array(
                'value' => 'NORMAL',
                'label' => 'NORMAL'
            ),
            array(
                'value' => 'DELETED',
                'label' => 'DELETED'
            ),
            array(
                'value' => 'BANNED',
                'label' => 'BANNED'
            ),
            array(
                'value' => 'NotUploaded',
                'label' => 'Not Uploaded'
            )
        );
    }

    public function getProduct($product_id) {
        $sql = "SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN `".DB_PREFIX."cedshopee_profile_products` cpp ON (p.product_id = cpp.product_id) LEFT JOIN `".DB_PREFIX."cedshopee_uploaded_products` cup ON (p.product_id = cup.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        $result_product = $this->db->query($sql);
        if($result_product && $result_product->num_rows ){
            return $result_product->row;
        } else {
            return array();
        }
    }

    public function addProduct($data)
    {
        $exist = $this->db->query("SELECT * FROM `".DB_PREFIX."cedshopee_uploaded_products` WHERE product_id = ".(int)$data['product_id']);
        if ($exist->num_rows) {
            $this->db->query("UPDATE `".DB_PREFIX."cedshopee_uploaded_products` SET logistics = '".$this->db->escape(json_encode($data['logistics']))."', wholesale = '".$this->db->escape(json_encode($data['wholesale']))."' WHERE product_id = ".(int)$data['product_id']);
        } else {
            $this->db->query("INSERT INTO `".DB_PREFIX."cedshopee_uploaded_products` SET product_id = ".(int)$data['product_id'].", logistics = '".$this->db->escape(json_encode($data['logistics']))."', wholesale = '".$this->db->escape(json_encode($data['wholesale']))."'");
        }
    }

    public function editProduct($product_id,$data) {

        $sql = "SELECT `id` FROM `".DB_PREFIX."cedshopee_product` where `product_id`='".$product_id."'";
        $exist = $this->db->query($sql);
        $attribute = '';
        if (isset($data['product']['attribute']) && count($data['product']['attribute'])) {
            $attribute = $this->db->escape(json_encode($data['product']['attribute']));
        }
        if(!isset($data['quantity'])) {
            $data['quantity'] = 0;
        }
        if(!isset($data['price'])) {
            $data['price'] = 0;
        }

        if ($exist && $exist->num_rows) {
            $sql = "UPDATE `".DB_PREFIX."cedshopee_product` SET `quantity`='".$this->db->escape($data['quantity'])."', `price`= '".$this->db->escape($data['price'])."', `override_sku`='".$this->db->escape($data['SkuUpdate'])."', `cedshopee_remove_free`='".$this->db->escape($data['cedshopee_remove_free'])."', `tax_code`= '".$this->db->escape($data['tax_code'])."', `alcohol_by_volume`='".$this->db->escape($data['alcohol_by_volume'])."', `food_form`= '".$this->db->escape($data['food_form'])."',`attribute`='".$attribute."' where `product_id`='".$product_id."'";
        } else {
            $sql = "INSERT INTO `".DB_PREFIX."cedshopee_product` (`id`, `quantity`, `price`,`override_sku`,`cedshopee_remove_free`, `tax_code`, `alcohol_by_volume`, `food_form`,`product_id`,`attribute`) VALUES ('', '".$this->db->escape($data['quantity'])."', '".$this->db->escape($data['price'])."','".$this->db->escape($data['SkuUpdate'])."', '".$this->db->escape($data['cedshopee_remove_free'])."', '".$this->db->escape($data['tax_code'])."', '".$this->db->escape($data['alcohol_by_volume'])."', '".$this->db->escape($data['food_form'])."','".$this->db->escape($product_id)."','".$attribute."');";
        }


        $this->db->query($sql);


        if (isset($data['shippingOverride']) && count($data['shippingOverride'])) {
            $sql = "DELETE FROM `".DB_PREFIX."cedshopee_product_shipping_exception` where `product_id`='".$product_id."'";
            $this->db->query($sql);
            foreach ($data['shippingOverride'] as $key => $value) {
                $sql = "INSERT INTO `".DB_PREFIX."cedshopee_product_shipping_exception` (`id`, `shipRegion`, `shipMethod`, `isShippingAllowed`, `shipPrice`, `product_id`) VALUES (NULL, '".$this->db->escape($value['ShippingOverrideShipRegion'])."', '".$this->db->escape($value['ShippingOverrideShipMethod'])."', '".$this->db->escape($value['ShippingOverrideIsShippingAllowed'])."', '".$this->db->escape($value['ShippingOverrideshipPrice'])."', '".$this->db->escape($product_id)."');";
                $this->db->query($sql);
            }
        }



        if (isset($data['product']['option']) && count($data['product']['option'])) {

            foreach ($data['product']['option'] as $key => $option) {

                $product_id=$key;

                $sql = "SELECT `merchant_sku` FROM `" . DB_PREFIX ."cedshopee_product_variations` where product_id=".$product_id;

                $priviousChilds=$this->db->query($sql);

                $previousChildSkus=array();

                if ($priviousChilds->num_rows) {
                    foreach ($priviousChilds->rows as $key => $priviousChild) {

                        $previousChildSkus[]=$priviousChild['merchant_sku'];
                    }

                    $previousChildSkus=array_filter($previousChildSkus);
                }

                $currentSkusArray=array();

                $sql = "DELETE FROM `" . DB_PREFIX ."cedshopee_product_variations` where product_id=".$product_id;

                $this->db->query($sql);

                $sql = "INSERT INTO `" . DB_PREFIX . "cedshopee_product_variations` (`product_id`, `option_combination`,`unique_choice` , `unique_value`,`merchant_sku`,`cedshopee_status`,`isPrimaryVariant`) VALUES ";

                foreach ($option as $key => $val) {
                    $sql .='('.$product_id.',';


                    if (!isset($val['isPrimaryVariant'])) {
                        $val['isPrimaryVariant'] = '';
                    } else if (isset($val['isPrimaryVariant']) && $val['isPrimaryVariant']) {
                        $primarySku = $val['isPrimaryVariant'];
                        unset($val['isPrimaryVariant']);
                        if($primarySku)
                            $val['isPrimaryVariant'] = 1;
                    }

                    foreach ($val as $k => $v) {

                        if (is_array($v)) {
                            $v=json_encode($v);
                        }
                        $sql .= "'".$this->db->escape($v)."'".",";
                    }
                    $sql = rtrim($sql,',').'),';
                    if(isset($val['merchant_sku']) && $val['merchant_sku']!='')
                        $currentSkusArray[]=$val['merchant_sku'];
                }
                $sql = rtrim($sql,',');
                $eligibleForArchieved=array_diff($previousChildSkus,$currentSkusArray);
                $result= $this->db->query($sql);
                if ($result) {
                    if(count($currentSkusArray)< count($previousChildSkus))
                        $this->retireByMerchantSku($eligibleForArchieved);
                }
            }
        }
    }
    

    public function getVariantProducts($product_id) {

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cedshopee_product_variations` where `product_id`='".$product_id."'");

        if ($query->num_rows) {

            return $query->rows;

        } else {

            return array();

        }
    }

    public function getShopeeItemId($product_id=0) {
        if ($product_id) {
            $shopee_item_id = '';
            $sql = "SELECT `shopee_item_id` FROM `".DB_PREFIX."cedshopee_uploaded_products` WHERE `product_id`='".$product_id."'";
            $result = $this->db->query($sql);
            if ($result && $result->num_rows && isset($result->row['shopee_item_id'])) {
                $shopee_item_id = $result->row['shopee_item_id'];
            }
            return $shopee_item_id;
        }
        return false;
    }

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

    public function editProductEvent ($product_id, $data) {

        $up_inv = $this->config->get('cedshopee_update_inventry');

        if ($up_inv) {
            $this->updateInvenetry($product_id, $data);
        }

        $up_pri = $this->config->get('cedshopee_update_price');

        if ($up_pri) {
            $this->updatePrice($product_id, $data);
        }

        $up_pro = $this->config->get('cedshopee_update_all');

        if ($up_pro) {
            $this->load->library('cedshopee');
            $cwal_lib = Cedshopee::getInstance($this->registry);
            $status = $cwal_lib->uploadProducts(array($product_id),false,'PARTIAL_UPDATE');
            $cwal_lib->log($status);
        }
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

    public function getItems($data = array()) 
    {
        $sql = "SELECT cup.*,csp.*,p.*,p.product_id as product_id, pd.* FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id) JOIN `" . DB_PREFIX . "cedshopee_profile_products` cpp ON (p.product_id = cpp.product_id) LEFT JOIN `".DB_PREFIX."cedshopee_profile` csp ON (csp.id = cpp.shopee_profile_id) LEFT JOIN `".DB_PREFIX."cedshopee_uploaded_products` cup ON (cup.product_id = p.product_id)";

        $sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cup.`shopee_item_id` > 0";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '" . $this->db->escape(trim($data['filter_name'])) . "%'";
        }

        if (!empty($data['filter_profile_name'])){
            $sql .= 'AND csp.id = '.(int)$data['filter_profile_name'];
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape(trim($data['filter_model'])) . "%'";
        }

        if (isset($data['filter_price']) && !is_null($data['filter_price'])) {
            $sql .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
        }

        if (isset($data['filter_quantity']) && !is_null($data['filter_quantity'])) {
            $sql .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
        }

        if (isset($data['filter_status'])) {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (isset($data['filter_shopee_status']) && !is_null($data['filter_shopee_status'])) {
            $sql .= " AND cup.shopee_status = '" . $data['filter_shopee_status'] . "'";
        }

        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'pd.name',
            'p.model',
            'p.price',
            'p.quantity',
            'p.status',
            'p.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name";
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
    
}