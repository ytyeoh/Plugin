<?php
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End partner_id License Agreement (EULA)
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
class Cedshopee
{
    private $db;
    private $session;
    private $config;
    private $currency;
    private $request;
    private $weight;
    private $tax;
    protected $api_url = '';
    protected $partner_id = '';
    protected $shop_id = '';
    protected $timestamp;

    private static $instance;

    /**
     * @param  object $registry Registry Object
     */
    public static function getInstance($registry)
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($registry);
        }

        return static::$instance;
    }

    /**
     * @param  object $registry Registry Object
     */
    public function __construct($registry)
    {
        $this->db = $registry->get('db');
        $this->session = $registry->get('session');
        $this->config = $registry->get('config');
        $this->currency = $registry->get('currency');
        $this->request = $registry->get('request');
        $this->weight = $registry->get('weight');
        $this->tax = $registry->get('tax');
        $this->openbay = $registry->get('openbay');
        $this->timestamp = time();
    }

    public function _init()
    {
        $this->_api_url = $this->config->get('cedshopee_api_url');
        $this->partner_id = $this->config->get('cedshopee_partner_id');
        $this->shop_id = $this->config->get('cedshopee_shop_id');
        $this->signature = $this->config->get('cedshopee_shop_signature');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        $flag = false;
        if ($this->config->get('cedshopee_status')) {
            $flag = true;
            $this->_init();
        }
        return $flag;
    }

    /**
     * Post Request
     * $params = ['file' => "", 'data' => "" ]
     * @param string $url
     * @param array $params
     * @return string|array
     */
    public function postRequest($url, $params = array())
    {
        $request = null;
        $response = null;
        $enable = $this->isEnabled();
        if ($enable) {
            try {
                $host = str_replace('/api/v1/', '', $this->config->get('cedshopee_api_url'));
                $host = str_replace('https://', '', $host);
                $url = $this->config->get('cedshopee_api_url') . $url;
                $jsonBody = $this->createJsonBody($params);

                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: '.$this->signature($url, $jsonBody),
                    'Host: '.$host,
                    'Content-Length: '.strlen($jsonBody)
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST,       true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);

                $servererror = curl_error($ch);

                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $body = substr($response, $header_size);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $this->log('Headers');
                $this->log($headers);
                $this->log('Parameters');
                $this->log($params);
                $this->log('body');
                $this->log($body);
                $this->log('Responses');
                $this->log($response);
                if ($body) {
                    $body = json_decode($body, true);
                }

                if ($httpcode != 200) {
                    return $body;
                }
                if (!empty($servererror)) {
                    $request = curl_getinfo($ch);
                    curl_close($ch);
                    return array('error' => 'server_error', 'msg' => $servererror);
                }
                curl_close($ch);
                if ($body && ($httpcode == 200)) {
                    return $body;
                } else {
                    return '{}';
                }
            } catch (Exception $e) {
                $this->log(
                    "Shopee\\Shopee\\Request\\postRequest() : \n URL: " . $url .
                    "\n Request : \n" . var_export($request, true) .
                    "\n Response : \n " . var_export($body, true) .
                    "\n Errors : \n " . var_export($e->getMessage(), true)
                );
                return array('error' => $httpcode, 'msg' => $body);
            }
        }
    }

    /**
     * Generate an HMAC-SHA256 signature for a HTTP request
     *
     * @param UriInterface $uri
     * @param string $body
     * @return string
     */
    protected function signature($url, $body)
    {
        $data = $url . '|' . $body;
        return hash_hmac('sha256', $data, trim($this->signature));
    }

    protected function createJsonBody($data = array())
    {
        $data = array_merge(array(
            'shopid' => (int)$this->shop_id,
            'partner_id' => (int)$this->partner_id,
            'timestamp' => time(),
        ), $data);
        return json_encode($data);
    }

    public function getSku($product_id = 0)
    {
        if ($product_id) {
            $sku = '';
            $field = 'sku';
            // $sql = "SELECT `attribute_id` FROM `" . DB_PREFIX . "cedshopee_product_field_mapping` WHERE `cedshopee_id`='" . $field . "'";
            // $mappedField = $query = $this->db->query($sql);
            // if ($mappedField->num_rows) {
            //     $field = $mappedField->row['attribute_id'];
            // }
            if ($field && $product_id) {
                $result_product = $this->db->query("SELECT `" . $field . "` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . $product_id . "'");
                if ($result_product->num_rows) {
                    return $result_product->row[$field];
                }
            }
            $sql = "SELECT `sku` FROM `" . DB_PREFIX . "cedshopee_product` where `product_id`='" . $product_id . "'";
            $result = $this->db->query($sql);
            if ($result && $result->num_rows && isset($result->row['sku']) && $result->row['sku']) {
                $sku = $result->row['sku'];
            }
            if (strlen(trim($sku)) <= 4) {
                $sql = "SELECT `sku` FROM `" . DB_PREFIX . "product` where `product_id`='" . $product_id . "'";
                $result = $this->db->query($sql);
                if ($result && $result->num_rows && isset($result->row['sku'])) {
                    $sku = $result->row['sku'];
                }
            }
            return $sku;
        }
    }

    public function isCedshopeeInstalled()
    {
        if ($this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "cedshopee_install'")->num_rows) {
            return true;
        } else {
            return false;
        }
    }

    public function installCedshopee()
    {
        $cedshopee_discount = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_discount` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `discount_name` text COLLATE utf8_unicode_ci NOT NULL,
          `discount_id` int(11) NOT NULL,
          `start_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
          `items` longtext NOT NULL,
          `discount_item_price` float NOT NULL, 
          `discount_item_variation_price` float NOT NULL,
          `purchase_limit` int(11) NOT NULL,
          `price_type` int(11) NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_discount);
        if ($created)
            $this->log("cedshopee_discount table created", 6, true);

        $cedshopee_uploaded_products = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_uploaded_products` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `product_id` int(11) NOT NULL,
          `shopee_profile_id` int(11) NOT NULL,
          `shopee_status` text COLLATE utf8_unicode_ci NOT NULL,
          `error_message` longtext COLLATE utf8_unicode_ci NOT NULL,
          `shopee_item_id` bigint(20) NOT NULL,
          `logistics` text COLLATE utf8_unicode_ci NOT NULL,
          `wholesale` text COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_uploaded_products);
        if ($created)
            $this->log("cedshopee_uploaded_product table created", 6, true);

        $cedshopee_attribute = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_attribute` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `attribute_id` bigint(20) NOT NULL,
          `attribute_name` text COLLATE utf8_unicode_ci NOT NULL,
          `is_mandatory` tinyint(1) NOT NULL,
          `attribute_type` text COLLATE utf8_unicode_ci NOT NULL,
          `input_type` text COLLATE utf8_unicode_ci NOT NULL,
          `options` longtext COLLATE utf8_unicode_ci NOT NULL,
          `category_id` bigint(20) NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_attribute);
        if ($created)
            $this->log("cedshopee_attribute table created", 6, true);

        $cedshopee_category = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_category` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `category_id` bigint(11) NOT NULL,
          `category_name` text COLLATE utf8_unicode_ci NOT NULL,
          `parent_id` bigint(20) NOT NULL,
          `has_children` bigint(20) NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_category);
        if ($created)
            $this->log("cedshopee_category table created", 6, true);

        $cedshopee_logistics = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_logistics` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `logistic_id` text COLLATE utf8_unicode_ci NOT NULL,
          `logistic_name` text COLLATE utf8_unicode_ci NOT NULL,
          `has_cod` tinyint(1) NOT NULL,
          `enabled` tinyint(1) NOT NULL,
          `fee_type` text COLLATE utf8_unicode_ci NOT NULL,
          `sizes` longtext COLLATE utf8_unicode_ci NOT NULL,
          `weight_limits` longtext COLLATE utf8_unicode_ci NOT NULL,
          `item_max_dimension` longtext COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_logistics);
        if ($created)
            $this->log("cedshopee_logistics table created", 6, true);

        $cedshopee_order = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_order` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `order_place_date` datetime DEFAULT NULL COMMENT 'Order Place Date',
          `opencart_order_id` int(11) DEFAULT NULL COMMENT 'Opencart Order Id',
          `status` text COLLATE utf8_unicode_ci COMMENT 'status',
          `order_data` text COLLATE utf8_unicode_ci COMMENT 'Order Data',
          `shipment_data` text COLLATE utf8_unicode_ci COMMENT 'Shipping Data',
          `shopee_order_id` text COLLATE utf8_unicode_ci COMMENT 'Reference Order Id',
          `shipment_request_data` text COLLATE utf8_unicode_ci COMMENT 'Shipment Data send on shopee',
          `shipment_response_data` text COLLATE utf8_unicode_ci COMMENT 'Shipment Data get from shopee',
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_order);
        if ($created)
            $this->log("cedshopee_order table created", 6, true);

        $cedshopee_order_error = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_order_error` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `shopee_order_id` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Purchase Order Id',
          `merchant_sku` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT 'Reference_Number',
          `reason` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Reason',
          `order_data` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Order Data',
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_order_error);
        if ($created)
            $this->log("cedshopee_order_error table created", 6, true);

        $cedshopee_products = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_products` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `product_id` int(11) NOT NULL,
          `shopee_status` text COLLATE utf8_unicode_ci NOT NULL,
          `error_message` longtext COLLATE utf8_unicode_ci NOT NULL,
          `shopee_item_id` bigint(20) NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_products);
        if ($created)
            $this->log("cedshopee_products table created", 6, true);


        $cedshopee_product_variations = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_product_variations` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `product_id` int(11) NOT NULL,
          `name` text COLLATE utf8_unicode_ci NOT NULL,
          `variation_sku` text COLLATE utf8_unicode_ci NOT NULL,
          `status` text COLLATE utf8_unicode_ci NOT NULL,
          `is_removed` text COLLATE utf8_unicode_ci NOT NULL,
          `variation_id` int(11) NOT NULL,
          `stock` int(11) NOT NULL,
          `price` float NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_product_variations);
        if ($created)
            $this->log("cedshopee_product_variations table created", 6, true);

        $cedshopee_profile = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_profile` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `title` text COLLATE utf8_unicode_ci NOT NULL,
          `store_category` longtext COLLATE utf8_unicode_ci NOT NULL,
          `shopee_categories` longtext COLLATE utf8_unicode_ci NOT NULL,
          `shopee_category` longtext COLLATE utf8_unicode_ci NOT NULL,
          `profile_attribute_mapping` longtext COLLATE utf8_unicode_ci NOT NULL,
          `status` int(11) NOT NULL,
          `logistics` longtext COLLATE utf8_unicode_ci NOT NULL,
          `wholesale` longtext COLLATE utf8_unicode_ci NOT NULL,
          `default_mapping` text COLLATE utf8_unicode_ci NOT NULL,
          `profile_store` text COLLATE utf8_unicode_ci NOT NULL,
          `product_manufacturer` text COLLATE utf8_unicode_ci NOT NULL,
          `profile_language` int(11) NOT NULL,
          `shopee_category_name` text COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_profile);
        if ($created)
            $this->log("cedshopee_profile table created", 6, true);

        $cedshopee_profile_products = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_profile_products` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `product_id` int(11) NOT NULL,
          `shopee_profile_id` int(11) NOT NULL,
          `shopee_status` text COLLATE utf8_unicode_ci NOT NULL,
          `error_message` longtext COLLATE utf8_unicode_ci NOT NULL,
          `shopee_item_id` bigint(20) NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_profile_products);
        if ($created)
            $this->log("cedshopee_profile_products table created", 6, true);

        $cedshopee_return = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_return` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `reason` text COLLATE utf8_unicode_ci NOT NULL,
          `text_reason` longtext COLLATE utf8_unicode_ci NOT NULL,
          `returnsn` text COLLATE utf8_unicode_ci NOT NULL,
          `ordersn` text COLLATE utf8_unicode_ci NOT NULL,
          `return_data` longtext COLLATE utf8_unicode_ci NOT NULL,
          `status` text COLLATE utf8_unicode_ci NOT NULL,
          `dispute_request` longtext COLLATE utf8_unicode_ci NOT NULL,
          `dispute_response` longtext COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

        $created = $this->db->query($cedshopee_return);
        if ($created)
            $this->log("cedshopee_return table created", 6, true);

        $cedshopee_log = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cedshopee_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `method` text COLLATE utf8_unicode_ci NOT NULL,
        `message` text COLLATE utf8_unicode_ci NOT NULL,
        `response` text COLLATE utf8_unicode_ci NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) ;";

        $created = $this->db->query($cedshopee_log);
        if ($created)
            $this->log("cedshopee_log table created", 6, true);
    }

    public function log($data, $force_log = false, $step = 6)
    {
        if ($this->config->get('cedshopee_debug') || $force_log) {
            $backtrace = debug_backtrace();
            $log = new Log('cedshopee.log');
            if (is_array($data))
                $data = json_encode($data);
            if (isset($backtrace[$step]) && isset($backtrace[$step]['class']) && isset($backtrace[$step]['class'])) {
                $log->write('(' . $backtrace[$step]['class'] . '::' . $backtrace[$step]['function'] . ') - ' . $data);
            } else {
                $log->write($data);
            }
        }
    }

    public function getMultliplyNumber($weightClassId)
    {
        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."weight_class_description` WHERE weight_class_id = ".(int)$weightClassId." AND language_id = ".(int)$this->config->get('config_language_id'));
        if ($query->num_rows) {
            if ($query->row['unit'] == 'kg') {
                return '1';
            } elseif ($query->row['unit'] == 'g') {
                return '0.001';
            } elseif ($query->row['unit'] == 'lb') {
                return '0.453592';
            } elseif ($query->row['unit'] == 'oz') {
                return '0.0283495';
            }
        } else {
            return false;
        }
    }

    public function uploadProducts($product_ids)
    {
        $product_ids = array_filter($product_ids);
        $product_ids = array_unique($product_ids);
        $validation_error = array();
        if (!empty($product_ids)) {
            $productToUpload = array();
            $success_message = array();
            $error_message = array();
            $response = array();
            $itemCount = 0;
            foreach ($product_ids as $product_id) {
                if (is_numeric($product_id)) {

                    $profile_info = $this->getProfileByProductId($product_id);

                    $product = $this->getProduct($product_id);

                    if ($profile_info && !empty($product))
                    {
                        $product_info = $this->getCedShopeeMappedProductData($product_id, $profile_info, $product);

                        $category = $this->getCedShopeeCategory($product_id, $profile_info, $product);

                        $price = $this->getCedShopeePrice($product_id, $product);

                        $stock = $this->getCedShopeeQuantity($product_id, $product);

                        $attributes = $this->getCedShopeeAttribute($product_id, $profile_info, $product);

                        if(!empty($attributes)){
                          $productToUpload['attributes'] =  $attributes;
                        } else {
                          $productToUpload['attributes'] =  array(array('attributes_id' => 8794, 'value' => (string)'No Brand'));
                        }

                        $images = $this->productSecondaryImageURL($product_id, $product);

                        $productToUpload['category_id'] = (int) $category;

                        if(isset( $product_info['name']) &&  $product_info['name'])
                            $productToUpload['name'] = (string) $product_info['name'];
                        else
                            $validation_error[$itemCount] = 'Product ID '.$product_id.'Name is required Field';

                        // if(strlen($productToUpload['name']) > 20){
                        //   $productToUpload['name'] = substr($productToUpload['name'], 0, 20);
                        // }

                        if(isset($product_info['description']) &&  $product_info['description'])
                        {
                            $productToUpload['description'] = (string) (strip_tags(html_entity_decode(trim($product_info['description']),ENT_QUOTES, 'UTF-8')));
                        } else {
                            $validation_error[$itemCount] = 'Product ID '.$product_id.'Description is required Field';
                        }

                        if(strlen($productToUpload['description']) == 0)
                        {
                            $productToUpload['description'] = $productToUpload['name'];
                        } else {
                            if(strlen($productToUpload['description']) > 5000)
                            {
                                $productToUpload['description'] = substr($productToUpload['description'], 0, 5000);
                            }
                            if(strlen($productToUpload['description']) < 25)
                            {
                                $productToUpload['description'] = (string) $product_info['description'].'......';
                            }
                        }

                        $productToUpload['price'] = (float) $price;

                        $productToUpload['stock'] =  (int)$stock;

                        if(isset( $product_info['item_sku']) &&  $product_info['item_sku'])
                            $productToUpload['item_sku'] = (string) $product_info['item_sku'];

                        if(isset( $product_info['weight']) &&  $product_info['weight']) {
                            $productToUpload['weight'] = (float) $product_info['weight'];
                            if (isset($product['weight_class_id'])) {
                                $muliplyBy = $this->getMultliplyNumber($product['weight_class_id']);
                                if (!empty($muliplyBy)) {
                                    $productToUpload['weight'] = ((float)$productToUpload['weight'] * $muliplyBy) > 0.1 ? (float)$productToUpload['weight'] * $muliplyBy : 0.2;
                                }
                            }
                        }

                        if(isset( $product_info['package_length']) &&  (int)$product_info['package_length'])
                            $productToUpload['package_length'] = (int) $product_info['package_length'];

                        if(isset( $product_info['package_width']) &&  (int)$product_info['package_width'])
                            $productToUpload['package_width'] = (int) $product_info['package_width'];

                        if(isset( $product_info['package_height']) &&  (int)$product_info['package_height'])
                            $productToUpload['package_height'] =  (int)$product_info['package_height'];

                        if(isset( $product_info['days_to_ship']) &&  (int)$product_info['days_to_ship'])
                            $productToUpload['days_to_ship'] = (int) $product_info['days_to_ship'];

                        if(!empty($images))
                            $productToUpload['images'] = (array) $images;
                        else
                            $validation_error[$itemCount] = 'Product ID '.$product_id.'Image is required Field';

                        $logistics = $this->getLogistics($profile_info, $product_id);

                        if(!empty($logistics))
                            $productToUpload['logistics'] =  $logistics;
                        else
                            $validation_error[$itemCount] = 'Product ID '.$product_id.'Logistics is required Field';

                        $wholesales  = $this->getWholesales($profile_info, $product_id, $productToUpload['price']);

                        if(!empty($wholesales))
                            $productToUpload['wholesales'] = (array) array($wholesales);

                        $result = $this->db->query("SELECT shopee_item_id FROM `".DB_PREFIX."cedshopee_uploaded_products` WHERE product_id='".$product_id."'");
                        if($result->num_rows && isset($result->row['shopee_item_id'])) {
                            $productToUpload['item_id'] = (int)$result->row['shopee_item_id'];
                        } else {
                            $productToUpload['item_id'] = 0;
                        }

                        if ($variants = $this->isVariantProduct($product_id, $product_info)) {
                            $productToUpload['tier_variation'] = (array) $variants['tier_variations'];
                            $productToUpload['variations'] = (array) $variants['variations'];
                        }
                        
                        $valid = $this->validateProduct($productToUpload, $category);

                        if (isset($valid['success']) && $valid['success']) {
                            $itemCount++;
                            if (count($productToUpload) && (count($validation_error) == 0))
                            {
                                if(isset($productToUpload['item_id']) && !empty($productToUpload['item_id']))
                                {
                                    if (isset($productToUpload['variations']) && !empty($productToUpload['variations'])
                                        && isset($productToUpload['tier_variation']) && !empty($productToUpload['tier_variation']))
                                    {
                                        $variations = $productToUpload['variations'];
                                        unset($productToUpload['variations']);
                                        $tier_variation = $productToUpload['tier_variation'];
                                        unset($productToUpload['tier_variation']);
                                        $newVariationArray = array();

                                        $getTierVariations = $this->postRequest('item/tier_var/get', array('item_id' => (int)$productToUpload['item_id']));

                                        if (isset($getTierVariations['tier_variation']) && !empty($getTierVariations['tier_variation']))
                                        {
                                            foreach ($variations as $key => $variation) {
                                                if (!isset($variation['variation_id'])) {
                                                    $newVariationArray[] = $variation;
                                                }
                                            }

                                            if (!empty($newVariationArray))
                                            {
                                                $tierVariationParams = array(
                                                    'item_id' => (int)$productToUpload['item_id'],
                                                    'tier_variation' => (array)$tier_variation,
                                                );

                                                $res = $this->postRequest('item/tier_var/update_list', $tierVariationParams);
                                                if(isset($res['error']) && $res['error'])
                                                {
                                                //    return array('success' => false, 'message' => $res['msg']);
                                                    if(is_array($res['msg'])){
                                                        $res['msg'] = implode(', ', $res['msg']);
                                                    }
                                                    $error_message[] = 'Product ID - ' . $product_id . ' ' . $res['msg'];
                                                }

                                                $variationParams = array(
                                                    'item_id' => (int)$productToUpload['item_id'],
                                                    'variation' => array_values($newVariationArray),
                                                );

                                                $res = $this->postRequest('item/tier_var/add', $variationParams);
                                                if(isset($res['error']) && $res['error'])
                                                {
                                                //    return array('success' => false, 'message' => $res['msg']);
                                                    if(is_array($res['msg'])){
                                                        $res['msg'] = implode(', ', $res['msg']);
                                                    }
                                                    $error_message[] = 'Product ID - ' . $product_id . ' ' . $res['msg'];
                                                }
                                            }
                                        } else {
                                            foreach ($variations as $key => &$variation)
                                            {
                                                if (isset($variation['variation_id']) && !empty($variation['variation_id']))
                                                {
                                                    $this->postRequest('item/delete_variation', array('item_id' => (int)$productToUpload['item_id'], 'variation_id' => (int)$variation['variation_id']));
                                                    unset($variation['variation_id']);
                                                }
                                            }

                                            $variationParams = array(
                                                'item_id' => (int) $productToUpload['item_id'],
                                                'tier_variation' => (array) $tier_variation,
                                                'variation' => (array) $variations,
                                            );

                                            $variation_response = $this->postRequest('item/tier_var/init', $variationParams);
                                            if(isset($variation_response['error']) && $variation_response['error'])
                                            {
                                            //    return array('success' => false, 'message' => $variation_response['msg']);
                                                if(is_array($variation_response['msg'])){
                                                    $variation_response['msg'] = implode(', ', $variation_response['msg']);
                                                }
                                                $error_message[] = 'Product ID - ' . $product_id . ' ' . $variation_response['msg'];
                                            }
                                        }
                                    }
                                    $product_images = $productToUpload['images'];
                                    unset($productToUpload['images']);
                                    $response = $this->postRequest('item/update', $productToUpload);
                                    if (isset($response['item_id']) && !empty($response['item_id'])) {
                                        if (isset($product_images) && !empty($product_images)) {
                                            $this->updateitemimages($product_images, $response['item_id']);
                                        }
                                    }
                                } else {
                                    $variations = array();
                                    $tier_variation = array();
                                    if(isset($productToUpload['variations']) && !empty($productToUpload['variations'])
                                        && isset($productToUpload['tier_variation']) && !empty($productToUpload['tier_variation']))
                                    {
                                        $variations = $productToUpload['variations'];
                                        unset($productToUpload['variations']);
                                        $tier_variation = $productToUpload['tier_variation'];
                                        unset($productToUpload['tier_variation']);
                                    }
                                    $response = $this->postRequest('item/add', $productToUpload);
                                    
                                    if (isset($response['item_id']) && $response['item_id'])
                                    {
                                        if (isset($variations) && !empty($variations)
                                            && isset($tier_variation) && !empty($tier_variation))
                                        {
                                            $variationParams = array(
                                                'item_id' => (int) $response['item_id'],
                                                'tier_variation' => (array) $tier_variation,
                                                'variation' => (array) $variations,
                                            );
                                            $variation_response = $this->postRequest('item/tier_var/init', $variationParams);

                                            if(!isset($variation_response['variation_id_list']))
                                            {
                                                foreach($variations as $key => $variation)
                                                {
                                                    unset($variation['tier_index']);

                                                    if (isset($variation['variation_id']) && !empty($variation['variation_id'])) {
                                                        $this->postRequest('item/delete_variation', array('item_id' => (int)$response['item_id'], 'variation_id' => (int)$variation['variation_id']));
                                                        $variationParams = array(
                                                            'item_id' => (int)$response['item_id'],
                                                            'variations' => array($variation)
                                                        );
                                                        $this->postRequest('item/add_variations', $variationParams);

                                                    } else {
                                                        $variationParams = array(
                                                            'item_id' => (int)$response['item_id'],
                                                            'variations' => array($variation)
                                                        );
                                                        $this->postRequest('item/add_variations', $variationParams);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if (isset($response['item_id']) && $response['item_id'])
                                {
                                    if (isset($response['msg']) && $response['msg'])
                                    {
                                        $alreadyExist = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cedshopee_uploaded_products` WHERE product_id = '" . (int)$product_id . "'");
                                        if ($alreadyExist->num_rows)
                                        {
                                            if (!isset($response['item']['status']) && empty($response['item']['status']))
                                                $response['item']['status'] = 'NORMAL';

                                            $this->db->query("UPDATE `" . DB_PREFIX . "cedshopee_uploaded_products` SET shopee_item_id = '" . $response['item_id'] . "', shopee_status = '" . $response['item']['status'] . "' WHERE product_id = '" . (int)$product_id . "'");
                                        } else {
                                            if (!isset($response['item']['status']) && empty($response['item']['status']))
                                                $response['item']['status'] = 'NORMAL';

                                            $this->db->query("INSERT INTO `" . DB_PREFIX . "cedshopee_uploaded_products` SET product_id = " . (int)$product_id . ", shopee_item_id = '" . $response['item_id'] . "', shopee_status = '" . $response['item']['status'] . "'");
                                        }
                                        $variations = $this->postRequest('item/get', array('item_id' => (int)$response['item_id']));

                                        if (isset($variations['item']['variations']) && !empty($variations['item']['variations']))
                                        {
                                            foreach ($variations['item']['variations'] as $variation)
                                            {

                                                $name = $variation['name'];
                                                $qty = $variation['stock'];
                                                $price = $variation['price'];
                                                $variation_id = $variation['variation_id'];
                                                $sku = $variation['variation_sku'];

                                                $product_option_value_query = $this->db->query("SELECT id, variation_id FROM `" . DB_PREFIX . "cedshopee_product_variations` WHERE variation_sku = '" . $sku . "' AND product_id='" . $product_id . "'");

                                                if ($product_option_value_query && $product_option_value_query->num_rows) {
                                                    $this->db->query("UPDATE `" . DB_PREFIX . "cedshopee_product_variations` SET variation_id='" . (int)$variation_id . "', stock='" . (int)$qty . "',price='" . (float)$price . "',name='" . $name . "' WHERE variation_sku = '" . $this->db->escape($sku) . "' AND product_id='" . $product_id . "'");
                                                } else {
                                                    $this->db->query("INSERT INTO `" . DB_PREFIX . "cedshopee_product_variations` SET variation_id='" . (int)$variation_id . "',stock='" . (int)$qty . "',price='" . (float)$price . "',name='" . $name . "',variation_sku = '" . $this->db->escape($sku) . "',product_id='" . $product_id . "'");
                                                }
                                            }
                                        }
                                        //return array('success' => true, 'message' => $response['msg']);
                                        $success_message[] = 'Product ID - ' . $product_id . ' ' . $response['msg'];
                                    }
                                } else if (isset($response['error']) && isset($response['msg']) && $response['msg']) {
                                //    return array('success' => false, 'message' => $response['msg']);
                                    if(is_array($response['msg'])){
                                        $response['msg'] = implode(', ', $response['msg']);
                                    }
                                    $error_message[] = 'Product ID - ' . $product_id . ' ' . $response['msg'];
                                }
                            } else {
                            //    return array('success' => false, 'message' => $validation_error);
                                if(is_array($validation_error)){
                                    $validation_error = implode(', ', $validation_error);
                                }
                                $error_message[] = 'Product ID - ' . $product_id . ' ' . $validation_error;
                            }
                        } else {
                        //    return array('success' => false, 'message' => 'Required Attribute are Missing : -'.$valid['message']);
                            if(is_array($valid['message'])){
                                $valid['message'] = implode(', ', $valid['message']);
                            }
                            $error_message[] = 'Required Attribute are Missing for Product ID - ' . $product_id . ' ' . $valid['message'];
                        }
                    } else {
                        continue;
                    }
                }
            }
        }
        $response = array('error' => implode(', ', $error_message), 'success' => implode(', ', $success_message));

        return $response;
    }

    public function updateitemimages($product_images, $item_id){
        $images = array();
        foreach($product_images as $product_image){
            $images[] = $product_image['url'];
        }
        $params = array(
            'item_id' => (int)$item_id,
            'images' => (array)$images
        );
        $result = $this->postRequest('item/img/update', $params);
        return true;

    }

    public function validateProduct($productToUpload, $category){
        if(isset($productToUpload['attributes'])) {
            $required_attribute = array();
            $product_attribute = array();
            $Required_product_attribute = array();
            $result = $this->db->query("SELECT attribute_id,attribute_name FROM `".DB_PREFIX."cedshopee_attribute` where category_id='".$category."' AND is_mandatory='1'");
            if($result && $result->num_rows) {
                foreach ($result->rows as  $row) {
                    $required_attribute[] =  $row['attribute_id'];
                    $Required_product_attribute[$row['attribute_id']] = $row['attribute_name'];
                }
            }

            foreach($productToUpload['attributes'] as $attribute) {
                $product_attribute[] =  $attribute['attributes_id'];
            }
            $product_attribute = array_unique($product_attribute);
            $array_not_found = array_diff($required_attribute,$product_attribute);
            if(!empty($array_not_found)) {
                $name='';
                foreach ($array_not_found as $attribute_id) {
                    if(isset($Required_product_attribute[$attribute_id]))
                        $name .= $Required_product_attribute[$attribute_id];
                }
                return array('success' => false, 'message' =>$name);
            }
        }
        return array('success' => true, 'message' =>$productToUpload);
    }

    public function getLogistics($profile_info, $product_id = null)
    {
        $logistics = array();
        $profile_logistic = array();
        $fromProductUploadTable = $this->db->query("SELECT * FROM `".DB_PREFIX."cedshopee_uploaded_products` WHERE product_id = ".(int)$product_id);

        if(isset($profile_info['logistics']) && !empty($profile_info['logistics'])) {
            $profile_logistics = json_decode($profile_info['logistics'], true);

            if(isset($profile_logistics['0']['logistics']) && !empty($profile_logistics) && is_array($profile_logistics)){
                foreach ($profile_logistics as $key => $profile_logistic) {

                    if(isset($profile_logistic['selected']) && !empty($profile_logistic['selected']))
                    {
                        $result = $this->db->query("SELECT * FROM `".DB_PREFIX."cedshopee_logistics` WHERE logistic_id='".trim($profile_logistic['logistics'])."'");

                        if($result && $result->num_rows && $result->row && isset($result->row['fee_type']) && ($result->row['fee_type']='CUSTOM_PRICE'))
                        {
                            if(isset($profile_logistic['shipping_fee']) && !empty($profile_logistic['shipping_fee']))
                                $shippingFee = $profile_logistic['shipping_fee'];
                            else
                                $shippingFee = '0';
                            $logistics[] = array('logistic_id' => (int)$profile_logistic['logistics'],'enabled' =>  (bool) $result->row['enabled'], 'is_free' =>  (bool) $profile_logistic['is_free'],'shipping_fee' => (float)$shippingFee);
                        } else {
                            $logistics[] = array('logistic_id' => (int)$profile_logistic['logistics'], 'is_free' =>  (bool) $profile_logistic['is_free'], 'enabled' =>  (bool) $profile_logistic['is_free']);
                        }

                        if(isset($logistics[$key]['shipping_fee']) && $logistics[$key]['shipping_fee'] == '0')
                            unset($logistics[$key]['shipping_fee']);
                        if(isset($product_logistic['size_selection']) && !empty($product_logistic['size_selection']))
                            $logistics[$key]['size_id'] = (int)$profile_logistic['size_selection'];
                    }

                }
            }
        }

        $profile_logistic = $logistics;

        if ($fromProductUploadTable->num_rows)
        {
            if (isset($fromProductUploadTable->row['logistics']) && !empty($fromProductUploadTable->row['logistics'])) {
                $product_logistics = @json_decode($fromProductUploadTable->row['logistics'], true);

                if (isset($product_logistics['0']['logistics']) && !empty($product_logistics)) {
                    $logistics = array();
                    foreach ($product_logistics as $key => $product_logistic)
                    {

                        if(isset($product_logistic['selected']) && !empty($product_logistic['selected']))
                        {
                            $result = $this->db->query("SELECT * FROM `".DB_PREFIX."cedshopee_logistics` WHERE logistic_id='".trim($product_logistic['logistics'])."'");

                            if($result && $result->num_rows && $result->row && isset($result->row['fee_type']) && ($result->row['fee_type']='CUSTOM_PRICE') && $product_logistic){
                                if(isset($product_logistic['shipping_fee']) && !empty($product_logistic['shipping_fee']))
                                    $shippingFee = $product_logistic['shipping_fee'];
                                else
                                    $shippingFee = '0';
                                $logistics[] = array('logistic_id' => (int)$product_logistic['logistics'],'enabled' =>  (bool) $result->row['enabled'], 'is_free' =>  (bool) $product_logistic['is_free'],'shipping_fee' => (float)$shippingFee );
                            } else {
                                $logistics[] = array('logistic_id' => (int)$product_logistic['logistics'], 'is_free' =>  (bool) $product_logistic['is_free'], 'enabled' =>  (bool) $product_logistic['is_free']);
                            }

                            if(isset($logistics[$key]['shipping_fee']) && $logistics[$key]['shipping_fee'] == '0')
                                unset($logistics[$key]['shipping_fee']);
                            if(isset($product_logistic['size_selection']) && !empty($product_logistic['size_selection']))
                                $logistics[$key]['size_id'] = (int) $product_logistic['size_selection'];
                        }
                    }
                }
            }
        }

        if(!$logistics)
        {
            $logistics = $profile_logistic;
        }

        return $logistics;
    }

    public function getWholesales($profile_info, $product_id = null) {
        $wholesales = array();

        $uploadProductTable = $this->db->query("SELECT * FROM `".DB_PREFIX."cedshopee_uploaded_products` WHERE product_id = ".(int)$product_id);

        if(isset($profile_info['wholesale']) && !empty($profile_info['wholesale'])) {
            $profile_wholesale = json_decode($profile_info['wholesale'], true);
            if(!empty($profile_wholesale) && isset($profile_wholesale['wholesale_min'])){
                $wholesales['min'] =(int) $profile_wholesale['wholesale_min'];
            }
            if(!empty($profile_wholesale) && isset($profile_wholesale['wholesale_max'])){
                $wholesales['max'] = (int)$profile_wholesale['wholesale_max'];
            }
            if(!empty($profile_wholesale) && isset($profile_wholesale['wholesale_unit_price'])){
                $wholesales['unit_price'] = (float)$profile_wholesale['wholesale_unit_price'];
            }
        }

        if ($uploadProductTable->num_rows) {
            if (isset($uploadProductTable->row['wholesale']) && !empty($uploadProductTable->row['wholesale'])) {
                $product_wholesale = json_decode($uploadProductTable->row['wholesale'], true);
                $wholesales['min'] = (isset($product_wholesale['wholesale_min']) && !empty($product_wholesale['wholesale_min'])) ? (int)$product_wholesale['wholesale_min'] : 0;
                $wholesales['max'] = (isset($product_wholesale['wholesale_max']) && !empty($product_wholesale['wholesale_max'])) ? (int)$product_wholesale['wholesale_max'] : 0;
                $wholesales['unit_price'] = (isset($product_wholesale['wholesale_unit_price']) && !empty($product_wholesale['wholesale_unit_price'])) ? (int)$product_wholesale['wholesale_unit_price'] : 0;
            }
        }

        return array_filter($wholesales);
    }

    public function getProduct($product_id)
    {
        $product = false;
        $query = $this->db->query("SELECT DISTINCT p.*, pd.* FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        if ($query->num_rows)
            $product = $query->row;
        return $product;
    }

//    public function isVariantProduct($product_id, $product)
//    {
//      $product['price'] = $this->getCedShopeePrice($product_id, $product);
//        //echo '<pre>'; print_r($product); die;
//        $attirbute_combination = array();
//        $product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ");  // AND o.type IN ('select','radio','checkbox')
//        if ($product_option_query && $product_option_query->num_rows)
//        {
//            foreach ($product_option_query->rows as $option)
//            {
//                $product_option_value_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` pov LEFT JOIN `".DB_PREFIX."option_value_description` ovd ON (pov.option_value_id=ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
//                if($product_option_value_query && $product_option_value_query->num_rows)
//                {
//                    foreach ($product_option_value_query->rows as $option_value)
//                    {
//                        if(isset($option_value['product_option_value_id']) && isset($option_value['product_option_id']) && isset($option_value['option_id']) && isset($option_value['option_value_id']))
//                        {
//                            $variant_qty = 0;
//                            $attirbute_combination[$option_value['product_option_id']][$option_value['product_option_value_id']] = $option_value['product_option_value_id'];
//
//                            $variant_sku = $product_id.'-'.$option_value['product_option_id'].'-'.$option_value['option_id'].'-'.$option_value['product_option_value_id'];
//                            $variant_qty = $option_value['quantity'];
//                            $price = $option_value['price'];
//                            $price_prefix = $option_value['price_prefix'];
//                            $variant_price = $this->calculateValueByPrefix($product['price'], $price, $price_prefix);
//                            $name = substr($option_value['name'], 0, 20);
//                            $name = mb_convert_encoding($name, "UTF-8");
//                            $name = iconv(mb_detect_encoding($name), "UTF-8", $name);
//
//                            $attirbute_combination_variant[$option_value['product_option_value_id']] = array(
//                                'name' => (string)trim($name),
//                                'stock' => (int)$variant_qty,
//                                'price' => (float)$variant_price,
//                                'variation_sku' => (string)$variant_sku,
//                            );
//                        }
//                    }
//                }
//            }
//        }
//
//        $variations = array();
//        if(count(array_values($attirbute_combination))>1)
//            $attirbute_combination_options = $this->combinations(array_values($attirbute_combination));
//        elseif(isset($attirbute_combination_variant) && !empty($attirbute_combination_variant))
//            $attirbute_combination_options = array($attirbute_combination_variant);
//        else
//            $attirbute_combination_options = array();
//
//        if(!empty($attirbute_combination_options) && (count($attirbute_combination_options)>1)) {
//
//            foreach ($attirbute_combination_options as $attirbute_combination_option) {
//                $name = '';
//                $qty = array();
//                $price = array();
//                $sku = '';
//                foreach ($attirbute_combination_option as $attirbute_combination_opt) {
//
//                    if(isset($attirbute_combination_variant[$attirbute_combination_opt]) && $attirbute_combination_variant[$attirbute_combination_opt]){
//                        $attirbute_combination_variant[$attirbute_combination_opt];
//                        $name .= html_entity_decode($attirbute_combination_variant[$attirbute_combination_opt]['name'].' ');
//                        $qty[] = $attirbute_combination_variant[$attirbute_combination_opt]['stock'];
//                        $price[] = $attirbute_combination_variant[$attirbute_combination_opt]['price'];
//                        $sku .= $attirbute_combination_variant[$attirbute_combination_opt]['variation_sku'].'sku'.$attirbute_combination_opt;
//                    }
//
//                }
//                $product_option_value_query = $this->db->query("SELECT id, variation_id FROM `" . DB_PREFIX . "cedshopee_product_variations` WHERE variation_sku = '".$sku."' AND product_id='".$product_id."'");
//                if( $product_option_value_query  &&  $product_option_value_query ->num_rows && isset($product_option_value_query ->row['variation_id']) && $product_option_value_query ->row['variation_id']) {
//                    $variations[] = array(
//                        'name' => (string)html_entity_decode(trim($name)),
//                        'stock' => (int)min($qty),
//                        'price' => (float)max($price),
//                        'variation_sku' => (string)$sku,
//                        'variation_id' => (int) $product_option_value_query ->row['variation_id'],
//                    );
//                } else {
//                    $variations[] = array(
//                        'name' => (string)html_entity_decode(trim($name)),
//                        'stock' => (int)min($qty),
//                        'price' => (float)max($price),
//                        'variation_sku' => (string)$sku,
//                    );
//                }
//
//            }
//        } else {
//            if(isset($attirbute_combination_options[0])) {
//                foreach ($attirbute_combination_options[0] as $attirbute_combination_option) {
//                    $sku = $attirbute_combination_option['variation_sku'];
//                    $product_option_value_query = $this->db->query("SELECT id, variation_id FROM `" . DB_PREFIX . "cedshopee_product_variations` WHERE variation_sku = '".$sku."' AND product_id='".$product_id."'");
//                    if( $product_option_value_query  &&  $product_option_value_query ->num_rows && isset($product_option_value_query ->row['variation_id']) && $product_option_value_query ->row['variation_id']) {
//                        $variations[] = array(
//                            'name' => (string)trim($attirbute_combination_option['name']),
//                            'stock' => (int)$attirbute_combination_option['stock'],
//                            'price' => (float)$attirbute_combination_option['price'],
//                            'variation_sku' => (string)$attirbute_combination_option['variation_sku'],
//                            'variation_id' => (int) $product_option_value_query ->row['variation_id'],
//                        );
//                    } else {
//                        $variations[] = array(
//                            'name' => (string)trim($attirbute_combination_option['name']),
//                            'stock' => (int)$attirbute_combination_option['stock'],
//                            'price' => (float)$attirbute_combination_option['price'],
//                            'variation_sku' => (string)$attirbute_combination_option['variation_sku'],
//                        );
//                    }
//
//                }
//            }
//        }
//        return $variations;
//    }

    public function isVariantProduct($product_id, $product)
    {
        $product['price'] = $this->getCedShopeePrice($product_id, $product);
        $options_array = array();
        $attirbute_combination = array();
        $tier_variations = array();
        $product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' AND o.type IN ('select','radio','checkbox')");

        if ($product_option_query && $product_option_query->num_rows)
        {
            foreach ($product_option_query->rows as $option)
            {
                $product_option_value_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` pov LEFT JOIN `".DB_PREFIX."option_value_description` ovd ON (pov.option_value_id=ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
                if($product_option_value_query && $product_option_value_query->num_rows)
                {
                    foreach ($product_option_value_query->rows as $option_value)
                    {
                        if(isset($option_value['product_option_value_id']) && isset($option_value['product_option_id']) && isset($option_value['option_id']) && isset($option_value['option_value_id']))
                        {
                            $variant_qty = 0;
                            $attirbute_combination[$option_value['product_option_id']][$option_value['product_option_value_id']] = $option_value['product_option_value_id'];
                            $variant_sku = $product_id.'-'.$option_value['product_option_id'].'-'.$option_value['option_id'].'-'.$option_value['product_option_value_id'];
                            $variant_qty = $option_value['quantity'];
                            $price = $option_value['price'];
                            $price_prefix = $option_value['price_prefix'];
                            $variant_price = $this->calculateValueByPrefix($product['price'], $price, $price_prefix);
                            $name = $option_value['name'];
                            $options_array[] = (string)html_entity_decode($name);

                            $attirbute_combination_variant[$option_value['product_option_value_id']] = array(
                                'name' => (string)html_entity_decode($name),
                                'stock' => (int)$variant_qty,
                                'price' => (float)$variant_price,
                                'variation_sku' => (string)$variant_sku
                            );
                        }
                    }
                }
                $tier_variations[] = array(
                    'name' => $option['name'],
                    'options' => $options_array
                );
            }
        }

        $variations = array();
        if(count(array_values($attirbute_combination))>1)
            $attirbute_combination_options = $this->combinations(array_values($attirbute_combination));
        else
            $attirbute_combination_options = isset($attirbute_combination_variant) ? array($attirbute_combination_variant) : '';
        

        if(!empty($attirbute_combination_options) && (count($attirbute_combination_options)>1))
        {
            foreach ($attirbute_combination_options as $attirbute_combination_option)
            {
                $tier_index = array();
                foreach($tier_variations as $index => $tier_variation)
                {
                    $options = $tier_variation['options'];
                    foreach($options as $key => $val)
                    {
                        if(isset($attirbute_combination_option['name']) && !empty($attirbute_combination_option['name'])){
                            if($attirbute_combination_option['name'] == $val){
                                $tier_index[] = (int) $key;
                            }
                        }
                    }
                }
                $name = '';
                $qty = array();
                $price = array();
                $sku = '';
                foreach ($attirbute_combination_option as $attirbute_combination_opt)
                {
                    if(isset($attirbute_combination_variant[$attirbute_combination_opt]) && $attirbute_combination_variant[$attirbute_combination_opt])
                    {
                        $attirbute_combination_variant[$attirbute_combination_opt];
                        $name .= html_entity_decode($attirbute_combination_variant[$attirbute_combination_opt]['name'].' ');
                        $qty[] = $attirbute_combination_variant[$attirbute_combination_opt]['stock'];
                        $price[] = $attirbute_combination_variant[$attirbute_combination_opt]['price'];
                        $sku .= $attirbute_combination_variant[$attirbute_combination_opt]['variation_sku'].'sku'.$attirbute_combination_opt;

                        foreach($tier_variations as $index => $tier_variation)
                        {
                            $options = $tier_variation['options'];
                            foreach($options as $key => $val)
                            {
                                if($attirbute_combination_variant[$attirbute_combination_opt]['name'] == $val){
                                    $tier_index[] = (int) $key;
                                }
                            }
                        }
                    }

                }
                $product_option_value_query = $this->db->query("SELECT id, variation_id FROM `" . DB_PREFIX . "cedshopee_product_variations` WHERE variation_sku = '".$sku."' AND product_id='".$product_id."'");
                if( $product_option_value_query  &&  $product_option_value_query ->num_rows && isset($product_option_value_query ->row['variation_id']) && $product_option_value_query ->row['variation_id'])
                {
                    $variations[] = array(
                        'tier_index' => $tier_index,
                        'name' => (string)html_entity_decode(trim($name)),
                        'stock' => (int)min($qty),
                        'price' => (float)max($price),
                        'variation_sku' => (string)$sku,
                        'variation_id' => (int) $product_option_value_query->row['variation_id'],
                    );
                } else {
                    $variations[] = array(
                        'tier_index' => $tier_index,
                        'name' => (string)html_entity_decode(trim($name)),
                        'stock' => (int)min($qty),
                        'price' => (float)max($price),
                        'variation_sku' => (string)$sku,
                    );
                }
            }
        } else {
            if(isset($attirbute_combination_options[0])) {
                foreach ($attirbute_combination_options[0] as $attirbute_combination_option)
                {
                    $tier_index = array();
                    foreach($tier_variations as $index => $tier_variation)
                    {
                        $options = $tier_variation['options'];
                        foreach($options as $key => $val)
                        {
                            if($attirbute_combination_option['name'] == $val){
                                $tier_index = (int) $key;
                            }
                        }
                    }

                    $sku = $attirbute_combination_option['variation_sku'];
                    $product_option_value_query = $this->db->query("SELECT id, variation_id FROM `" . DB_PREFIX . "cedshopee_product_variations` where variation_sku = '".$sku."' AND product_id='".$product_id."'");
                    if( $product_option_value_query  &&  $product_option_value_query ->num_rows && isset($product_option_value_query ->row['variation_id']) && $product_option_value_query ->row['variation_id']) {
                        $variations[] = array(
                            'tier_index' => array($tier_index),
                            'name' => (string)html_entity_decode(trim($attirbute_combination_option['name'])),
                            'stock' => (int)$attirbute_combination_option['stock'],
                            'price' => (float)$attirbute_combination_option['price'],
                            'variation_sku' => (string)$attirbute_combination_option['variation_sku'],
                            'variation_id' => (int) $product_option_value_query ->row['variation_id'],
                        );
                    } else {
                        $variations[] = array(
                            'tier_index' => array($tier_index),
                            'name' => (string)html_entity_decode(trim($attirbute_combination_option['name'])),
                            'stock' => (int)$attirbute_combination_option['stock'],
                            'price' => (float)$attirbute_combination_option['price'],
                            'variation_sku' => (string)$attirbute_combination_option['variation_sku'],
                        );
                    }

                }
            }
        }
        $response = array(
            'tier_variations' => $tier_variations,
            'variations' => $variations
        );
        
        return $response;
    }

    public function calculateValueByPrefix($original_value, $value, $prefix) {
        switch ($prefix) {
            case '+' :
                return (float)$original_value + (float)$value;
                break;
            case '-' :
                return (float)$original_value - (float)$value;
                break;
            default :
                return $original_value;
                break;
        }
    }

    public function processVariantsProducts($product_id, $itemCount, $productToUpload, $variants, $cedshopee_parent_cat_id, $cedshopee_child_cat_id)
    {
        $variants_data = array();
        $parent_data = $productToUpload["MPItemFeed"]["MPItem"][$itemCount];
        if (is_array($variants) && count($variants)) {
            $variantId = $this->randomstring(20);
            foreach ($variants as $key => $variant) {
                if (isset($variant['option_combination']) && isset($variant['unique_choice']) && isset($variant['unique_value']) && isset($variant['merchant_sku']) && $variant['option_combination'] && $variant['unique_choice'] && $variant['unique_value'] && $variant['merchant_sku']) {
                    if (isset($parent_data['sku']) && $parent_data['sku']) {
                        $parent_data['sku'] = $variant['merchant_sku'];
                        $parent_data['productIdentifiers']['productIdentifier'] = array('productIdType' => $variant['unique_choice'], 'productId' => $variant['unique_value']);

                        if (isset($variant['option_combination']) && strlen($variant['option_combination']) > 0) {
                            $variantAttributeName = array();
                            $combination = json_decode($variant['option_combination'], true);
                            $options_value_array = array();
                            $options_array = array();
                            foreach ($combination as $key => $value) {

                                $result_product = $this->db->query("SELECT `cedshopee_id` FROM `" . DB_PREFIX . "cedshopee_option_mapping` where `wchild_category` = '" . $cedshopee_child_cat_id . "' AND `option_id` = '" . $key . "' AND `wparent_category` = '" . $cedshopee_parent_cat_id . "'");
                                if ($result_product && $result_product->num_rows) {
                                    $options_array[$key] = $result_product->row['cedshopee_id'];
                                    $variantAttributeName[] = $result_product->row['cedshopee_id'];
                                }

                                $result_product = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "product_option_value` pov LEFT JOIN `" . DB_PREFIX . "option_value_description` pod on (pov.option_value_id = pod.option_value_id) WHERE `product_id` = '" . $product_id . "' AND pov.option_id = '" . $key . "' AND pov.option_value_id = '" . $value . "'");
                                if ($result_product && $result_product->num_rows) {
                                    $options_value_array[$key] = $result_product->row['name'];
                                }
                            }
                            $variant_names = array();
                            if (is_array($options_array) && count($options_array)) {
                                foreach ($options_array as $key => $value) {
                                    $variant_names[$value] = $options_value_array[$key];
                                }
                            }
                            foreach ($variant_names as $key => $value) {
                                $parent_data[$key] = $value;
                            }
                            $parent_data['variantGroupId'] = $variantId;
                            $parent_data['variantAttributeNames'] = array('variantAttributeName' => $variantAttributeName);
                        }
                    }
                }
                $variants_data[$itemCount] = $parent_data;
                $itemCount++;
            }
        }
        return $variants_data;
    }

    public function getCedShopeeMappedProductData($product_id, $profile_info, $product)
    {
        if ($product_id && isset($profile_info['default_mapping']) && $profile_info['default_mapping']) {
            $default_mapping = json_decode($profile_info['default_mapping'], true);
            if (!empty($default_mapping)) {
                $mapped_data = array();
                foreach ($default_mapping as $key => $value) {
                    if (isset($product[$value]) && $product[$value]) {
                        $mapped_data[$key] = $product[$value];
                    } else if ($key == 'days_to_ship') {
                        $mapped_data[$key] = $value;
                    }
                }
                return $mapped_data;
            }
        } else {
            return false;
        }
    }

    public function getCedShopeePrice($product_id, $product = array())
    {
       $specialPrice = 0;
       $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_special` WHERE `product_id` = '" . (int)$product_id . "' ORDER BY priority, price");
       $product_specials = array();
       if ($query && $query->num_rows) {
           $product_specials = $query->rows;
       }

       foreach ($product_specials as $product_special) {
           if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
               $specialPrice = $product_special['price'];

               break;
           }
       }

        $product_price = 0;
        if (isset($product['price'])) {
            $product_price = $product['price'];
        } else {
            $query_price = $this->db->query("SELECT `price` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$product_id . "'");

            if ($query_price && $query_price->num_rows) {
                $product_price = $query_price->row['price'];
            }
        }

        $price = (float)$product_price;

       if (($specialPrice > 0) && ($specialPrice < $price)) {
           $price = $specialPrice;
       }

        $cedshopee_price_choice = trim($this->config->get(
            'cedshopee_price_choice'));

        switch ($cedshopee_price_choice) {
            case '2':
                $fixedIncement = trim($this->config->get('cedshopee_variable_price'));
                $price = $price + $fixedIncement;
                break;
            case '3':
                $fixedIncement = trim($this->config->get('cedshopee_variable_price'));
                $price = $price - $fixedIncement;
                break;
            case '4':
                $percentPrice = trim($this->config->get('cedshopee_variable_price'));
                $price = (float)($price + (($price / 100) * $percentPrice));
                break;
            case '5':
                $percentPrice = trim($this->config->get('cedshopee_variable_price'));
                $price = (float)($price - (($price / 100) * $percentPrice));
                break;
            case '6':
                $result = $this->db->query("SELECT `price` FROM `" . DB_PREFIX . "cedshopee_product` where `product_id`='" . $product_id . "'");
                if ($result && $result->num_rows && isset($result->row['price']))
                    $price = (isset($result->row['price']) && $result->row['price'] != 0) ? $result->row['price'] : $price;
                break;
            default:
                return (float)$price;
                break;
        }
        return (float)$price;
    }

    // public function getCedShopeePrice($product_id, $product = array())
    // {
    //     $specialPrice = 0;
    //     $product_specials = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_special` 
    //     WHERE `product_id` = '" . (int)$product_id . "' AND `customer_group_id` = '". (int) $this->config->get('config_customer_group_id') ."'
    //     ORDER BY priority, price");
    //     if ($product_specials->num_rows) {
    //         foreach ($product_specials->rows as $product_special) {
    //             if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
    //                 $specialPrice = $product_special['price'];
    //                 break;
    //             }
    //         }
    //     }

    //     if (($specialPrice > 0) && ($specialPrice < $product['price'])) {
    //         $price = $specialPrice;
    //     } else {
    //         $price = $product['price'];
    //     }

    //     if(!empty($product['tax_class_id'])) {
    //         $price = $this->tax->calculate($price, $product['tax_class_id'], $this->config->get('config_tax'));
    //     }

    //     $cedshopee_price_choice = trim($this->config->get('cedshopee_price_choice'));
    //     $cedshopee_price_amount = trim($this->config->get('cedshopee_variable_price'));

    //     switch ($cedshopee_price_choice) {
    //         case '1':
    //             $price = (float) $price;
    //             break;
    //         case '2':
    //             $price = $price + $cedshopee_price_amount;
    //             break;
    //         case '3':
    //             $price = $price - $cedshopee_price_amount;
    //             break;
    //         case '4':
    //             $price = (float)($price + (($price / 100) * $cedshopee_price_amount));
    //             break;
    //         case '5':
    //             $price = (float)($price - (($price / 100) * $cedshopee_price_amount));
    //             break;
    //         case '6':
    //             $result = $this->db->query("SELECT `price` FROM `" . DB_PREFIX . "cedshopee_product` WHERE `product_id`='" . $product_id . "'");
    //             if ($result && $result->num_rows && isset($result->row['price']))
    //                 $price = (isset($result->row['price']) && $result->row['price'] != 0) ? $result->row['price'] : $price;
    //             break;
    //         default:
    //             return (float)$price;
    //             break;
    //     }
    //     return (float)$price;
    // }

    public function getCedShopeeQuantity($product_id, $product = array())
    {
        $quantity = 0;
        if(isset($product['stock'])){
            $quantity = $product['stock'];
        } else if($product_id) {
            $result = $this->db->query("SELECT `quantity` FROM `" . DB_PREFIX . "product` where `product_id` = '" . $product_id . "'");
            if ($result->num_rows) {
                $quantity = $result->row['quantity'];
            } else {
                $quantity = 0;
            }
        }
        return $quantity;

    }

    public function getCedShopeeCategory($product_id, $profile_info, $product)
    {
        if ($product_id) {
            $shopee_category = false;
            if (isset($profile_info['shopee_category']) && $profile_info['shopee_category']) {
                $shopee_category = $profile_info['shopee_category'];
            }
            return $shopee_category;
        } else {
            return false;
        }
    }

        public function getCedShopeeAttribute($product_id, $profile_info, $product)
            {
            if ($product_id && isset($profile_info['profile_attribute_mapping']) && $profile_info['profile_attribute_mapping']) {
                $profile_attribute_mappings = json_decode($profile_info['profile_attribute_mapping'], true);

                $attribute_shopees = array();
                if ($profile_attribute_mappings) {
                    foreach ($profile_attribute_mappings as $profile_attribute_mapping)
                    {
                        $attribute_shopee = array();
                        if(isset($profile_attribute_mapping['store_attribute']) && $profile_attribute_mapping['store_attribute'])
                        {
                            $type_array = explode('-', $profile_attribute_mapping['store_attribute']);
                            if(isset($type_array['0']) && ($type_array['0']=='option'))
                            {
                                $options = array();
                                if(isset($profile_attribute_mapping['option']))
                                    $options = array_filter($profile_attribute_mapping['option']);
                                $option_value = $this->getProductOptions($product_id, $type_array['1'],$profile_info['profile_language'],$options);
                                $attribute_shopee = array('attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'], 'value' => $option_value);
                            } else if(isset($type_array['0']) && ($type_array['0']=='attribute')) {
                                $attribute_value = $this->getProductAttributes($product_id, $type_array['1'],$profile_info['profile_language']);
                                $attribute_shopee = array('attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'], 'value' => $attribute_value);
                            } else if(isset($type_array['0']) && ($type_array['0']=='product')) {
                                
                                if(isset($profile_attribute_mapping['store_attribute']) && ($profile_attribute_mapping['store_attribute'] =='product-manufacturer_id')) {
                                    $product_manufacturer = $product['manufacturer_id'];
                                    $shopee_val = '';
                                    
                                    foreach ($profile_attribute_mapping['option'] as $key => $value) {
                                        if($product_manufacturer==$value['store_attribute_id'])
                                            $shopee_val = $value['shopee_attribute'];
                                        $attribute_shopee = array('attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'], 'value' => $shopee_val);
                                    }
                                } else {
                                    $attribute_shopee = array('attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'], 'value' => $product[$type_array['1']]);
                                }

                            }
                        } else if(isset($profile_attribute_mapping['default_values']) && $profile_attribute_mapping['default_values']){
                            $attribute_shopee = array('attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'], 'value' => $profile_attribute_mapping['default_values']);
                        }
                        if(isset($attribute_shopee['value']) && !$attribute_shopee['value']){
                            if(isset($profile_attribute_mapping['default_values']) && $profile_attribute_mapping['default_values']){
                                $attribute_shopee = array('attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'], 'value' => $profile_attribute_mapping['default_values']);
                            }
                        }
                        $product_d = $this->db->query("SELECT * FROM `".DB_PREFIX."cedshopee_uploaded_products` WHERE product_id = ".(int)$product_id);
                        if($product_d->num_rows && isset($product_d->row['product_attribute']) && $product_d->row['product_attribute']){
                            $product_attribute_d = json_decode($product_d->row['product_attribute'], true);
                        }
                        $shoppee_selected_option = false;

                        if(isset($attribute_shopee['attributes_id']) && isset($attribute_shopee['attributes_id']) && isset($product_attribute_d[$attribute_shopee['attributes_id']]) && isset($product_attribute_d[$attribute_shopee['attributes_id']]['shopee_attribute']) && isset($product_attribute_d[$attribute_shopee['attributes_id']]['default_values']) && $product_attribute_d[$attribute_shopee['attributes_id']]['default_values'])
                            $attribute_shopee['value'] = $product_attribute_d[$attribute_shopee['attributes_id']]['default_values'];

                        $attribute_shopees[] = $attribute_shopee;
                    }
                    $attribute_shopees = array_filter($attribute_shopees);

                    return $attribute_shopees;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

    public function productSecondaryImageURL($product_id, $product)
    {
        if ($product_id) {
            $base_url = HTTP_CATALOG;
            if (strpos($base_url, 'admin') !== false) {
                $base_url = str_replace('admin/', '', $base_url);
            }

            $productImages = array();
            $additionalAssets = array();
            
            if (isset($product['image'])) {
                $productImages[] = array('image' => (string) $product['image']);
            }
            
            $query = $this->db->query("SELECT `image` FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");
            if ($query && $query->num_rows) {
                $addproductImages = $query->rows;
            }
            
            $i = 1;
            foreach($addproductImages as $addproimg){
                $productImages[$i]['image'] = $addproimg['image'];
                $i++;
            }
            
            // echo '<pre>'; print_r($productImages); die; 
            
            // $query = $this->db->query("SELECT `image` FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");
            // if ($query && $query->num_rows) {
            //     $productImages = $query->rows;
            // }
            
            // if (isset($product['image'])) {
            //     $productImages[] = array('image' => (string) $product['image']);
            // }
            
            // echo '<pre>'; print_r($productImages); die; 
            if (!empty($productImages)) {
                foreach ($productImages as $product_image) {
                    if (is_file(DIR_IMAGE . $product_image['image'])) {
                        $additionalAssets[] = array('url' => (string) $base_url.'image/'.$product_image['image']);
                        if (count($additionalAssets) == 9) {
                            break;
                        }
                    }
                }
            }
            
            return $additionalAssets;
        }
    }

    public function getProductAttributes($product_id, $store_attribute,$language_id)
    {
        $product_attribute_data = '';

        if($product_id && $store_attribute) {
            $product_attribute_query = $this->db->query("SELECT `text` FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$store_attribute . "' AND language_id = '" . (int)$language_id . "'");
            if($product_attribute_query && $product_attribute_query->num_rows && isset($product_attribute_query->row['text']) && $product_attribute_query->row['text']) {
                $product_attribute_data = $product_attribute_query->row['text'];

            }
        }

        return $product_attribute_data;
    }

    public function getProductOptions($product_id, $store_attribute, $language_id, $attribute_shopee) {
        $product_option_data = '';

        if($product_id && $store_attribute) {
            if (is_numeric($store_attribute) && !empty($store_attribute)) {
                $product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND  o.option_id = '".(int) $store_attribute."' AND  od.language_id = '" . (int)$language_id . "'");
                if ($product_option_query->num_rows && isset($attribute_shopee))   {
                    foreach ($attribute_shopee as $option_values) {
                        $product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_option_id = '" . (int)$product_option_query->row['product_option_id'] . "' AND option_value_id  = '" . (int)$option_values['store_attribute_id'] . "'");
                        if($product_option_value_query->num_rows && isset($option_values['shopee_attribute']) && $option_values['shopee_attribute']) {
                            $product_option_data = $option_values['shopee_attribute'];
                            break;
                        }
                    }
                }
            }
        }
        return $product_option_data;
    }

    public function fetchOrder($url, $params, $pagination_offset = 0)
    {
        // print_r($this->db->query("DELETE FROM ".DB_PREFIX."order WHERE order_id = 68 "));
        // print_r($this->db->query("DELETE FROM ".DB_PREFIX."cedshopee_order WHERE opencart_order_id = 68 ")); die;
        $pagination_entries_per_page = 50;
        if(!isset($params['pagination_entries_per_page']))
            $params['pagination_entries_per_page'] = $pagination_entries_per_page;

        if(!isset($params['pagination_offset']))
            $params['pagination_offset'] = $pagination_offset;

        $params['create_time_from'] = date('Y-m-d h:i:s a', strtotime("-14 days")); // , strtotime("-2 hours")
        $params['create_time_from'] = strtotime($params['create_time_from']);

        $response = $this->postRequest($url, $params);
        // $response['orders'][0] = array('ordersn' => '2011262UXWQ4TX');
        
        try {
            if (!empty($response))
            {
                if (!isset($response['error'])) {
                    if (is_array($response) && !empty($response)) {
                        $order_ids = array();
                        if (isset($response['orders']) && is_array($response) && !empty($response['orders'])) {
                            $totalOrderFetched = count($response['orders']);
                            $response['orders'] = array_chunk($response['orders'], '100');
                            foreach ($response['orders'] as $key => $orders)
                            {
                                $order_to_fetch = array();

                                foreach ($orders as $key => $order)
                                {
                                    $pagination_offset = $pagination_offset + $pagination_entries_per_page;
                                    $already_exist = $this->isPurchaseOrderIdExist($order['ordersn']);
                                    
                                    if ($already_exist) {
                                        continue;
                                    } else {
                                        $order_to_fetch[] = $order['ordersn'];
                                    }
                                }
                                if(isset($order_to_fetch) && is_array($order_to_fetch) && !empty($order_to_fetch))
                                {
                                    $orders_data = $this->fetchOrderDetails($order_to_fetch);
                                    // echo '<pre>'; print_r($orders_data); die; 
                                    $order_ids = array();
                                    $order_numbers = array();
                                    if(isset($orders_data['orders']) && is_array($orders_data['orders']) && !empty($orders_data['orders']))
                                    {
                                        foreach ($orders_data['orders'] as $key => $order_data)
                                        {
                                            if (isset($order_data['ordersn']) && $order_data['ordersn'])
                                            {
                                                $orderData = $this->prepareOrderData($order_data);
                                                $this->log(json_encode($orderData), '6', true);
                                                if(isset($orderData) && is_array($orderData) && !empty($orderData)) {
                                                    $order_ids[] = $this->createOrder($orderData);
                                                    $sql = $this->db->query("SELECT shipment_response_data FROM `" . DB_PREFIX . "cedshopee_order` WHERE `shopee_order_id` = '" . $order_data['ordersn'] . "'");
                                                    
                                                    if(empty($sql->row['shipment_response_data'])){
                                                        
                                                        $url = 'logistics/init_info/get';
                                                        $params = array('ordersn' => $order_data['ordersn']);
                                                        $logistics_init_info = $this->postRequest($url, $params);
                                                        
                                                        if(!empty($logistics_init_info['dropoff']) && !empty($logistics_init_info['pickup'])){
                                                            // foreach($logistics_init_info['pickup']['address_list'] as $key => $address_list){
                                                            //     foreach($address_list['time_slot_list'] as $time_slot_list){
                                                            //         $pickup = array(
                                                            //             'address_id' => $address_list['address_id'],
                                                            //             'pickup_time_id' => $time_slot_list['pickup_time_id'],
                                                            //         );
                                                            //     }
                                                            // }
                                                            // $url = 'logistics/init';
                                                            // $params = array('ordersn' => $shopee_order_id,
                                                            //                 'pickup' => $pickup,
                                                            //                 'dropoff'=> 
                                                            //             ); 
                                                            // $logistics_init = $this->postRequest($url, $params);
                                                        }elseif(empty($logistics_init_info['dropoff']) && !empty($logistics_init_info['pickup'])){
                                                            foreach($logistics_init_info['pickup']['address_list'] as $key => $address_list){
                                                                foreach($address_list['time_slot_list'] as $time_slot_list){
                                                                    $pickup = array(
                                                                        'address_id' => $address_list['address_id'],
                                                                        'pickup_time_id' => $time_slot_list['pickup_time_id'],
                                                                    );
                                                                }
                                                            }
                                                            $url = 'logistics/init';
                                                            $params = array('ordersn' => $order_data['ordersn'],
                                                                            'pickup' => $pickup,
                                                                        ); 
                                                            $logistics_init = $this->postRequest($url, $params);
                                                        }
                                    
                                                        $url = 'logistics/airway_bill/get_mass';
                                                        $params = array('ordersn_list' => (array)$order_data['ordersn']);
                                                        $bill_data = $this->postRequest($url, $params);
                                                        
                                                        if(isset($bill_data['result']['airway_bills'][0]) && !empty($bill_data['result']['airway_bills'][0])) {
                                                            if($bill_data['result']['airway_bills'][0]['ordersn'] == $order_data['ordersn']) {
                                                              
                                                              $this->db->query("UPDATE `" . DB_PREFIX . "cedshopee_order` SET shipment_data =  1, shipment_response_data =  '".$this->db->escape(json_encode($bill_data['result']['airway_bills'][0]['airway_bill']))."' WHERE `shopee_order_id` = '" . $order_data['ordersn'] . "'");
                                                            }else{
                                                            
                                                              $this->db->query("UPDATE `" . DB_PREFIX . "cedshopee_order` SET shipment_data = 1, shipment_response_data =  '".$this->db->escape(json_encode($bill_data['result']['airway_bills'][0]['airway_bill']))."' WHERE `shopee_order_id` = '" . $bill_data['result']['airway_bills'][0]['ordersn'] . "'");
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if (isset($response['more']) && $response['more'])
                                {
                                    $params['pagination_entries_per_page'] = $pagination_entries_per_page;
                                    $params['pagination_offset'] =  $pagination_offset;
                                    $createdTimeTo = date('Y-m-d h:i:s a');
                                    $params = array(
                                        'order_status' => 'READY_TO_SHIP',
                                        'create_time_to' => strtotime($createdTimeTo)
                                    );
                                    $params['create_time_from'] = date('Y-m-d h:i:s a', strtotime("-1 days"));
                                    $params['create_time_from'] = strtotime($params['create_time_from']);
                                    $this->fetchOrder($url, $params, $pagination_offset);
                                }

                                if (count($order_ids) == $totalOrderFetched) {
                                    return array('success' => true, 'message' => 'Order ID(s) - '. implode(' , ', $order_ids) . ' fetched successfully!');
                                } else if (count($order_ids) && ($totalOrderFetched > count($order_ids))) {
                                    return array('success' => true, 'message' => 'Order ID(s) - '. implode(' , ', $order_ids) . ' fetched successfully!', 'sub_message' => 'Please see Rejected List too.');
                                } else if ($totalOrderFetched == 0) {
                                    return array('success' => false, 'message' => 'No new Order Found.');
                                } else {
                                    return array('success' => false, 'message' => 'Order Send to Rejected List.');
                                }
                            }
                        } else {
                            return array('success' => false, 'message' => 'No New Order From Shopee.');
                        }
                    } else {
                        return array('success' => false, 'message' => 'No New Order From Shopee.');
                    }
                } else {
                    return array('success' => false, 'message' => $response['msg']);
                }
            } else {
                return array('success' => false, 'message' => 'No response from Shopee.');
            }
        } catch (Exception $e) {
            $this->log('Order Error:  ' . var_export($response, true));
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    public function fetchOrderDetails($order_ids)
    {
        $order_data = array();
        if (count($order_ids))
        {
            $url = 'orders/detail';
            $this->log($url);
            $params = array('ordersn_list' => $order_ids);
            $this->log($params);
            $order_data = $this->postRequest($url, $params);
            if(isset($order_data['orders']) && is_array($order_data['orders']) && !empty($order_data['orders']))
            {
                return $order_data;
            }
        }

        return $order_data;
    }

    public function isPurchaseOrderIdExist($shopee_order_id = 0)
    {
        $isExist = false;
        if ($shopee_order_id) {
            $sql = "SELECT `id` FROM `" . DB_PREFIX . "cedshopee_order` WHERE `shopee_order_id` = '" . $shopee_order_id . "'";
            $result = $this->db->query($sql);
            if ($result && $result->num_rows) {
                $isExist = true;
                $sql = $this->db->query("SELECT shipment_response_data FROM `" . DB_PREFIX . "cedshopee_order` WHERE `shopee_order_id` = '" . $shopee_order_id . "'");
                
                if(empty($sql->row['shipment_response_data'])){
                    
                    $url = 'logistics/init_info/get';
                    $params = array('ordersn' => $shopee_order_id);
                    $logistics_init_info = $this->postRequest($url, $params);
                    
                    if(!empty($logistics_init_info['dropoff']) && !empty($logistics_init_info['pickup'])){
                        // foreach($logistics_init_info['pickup']['address_list'] as $key => $address_list){
                        //     foreach($address_list['time_slot_list'] as $time_slot_list){
                        //         $pickup = array(
                        //             'address_id' => $address_list['address_id'],
                        //             'pickup_time_id' => $time_slot_list['pickup_time_id'],
                        //         );
                        //     }
                        // }
                        // $url = 'logistics/init';
                        // $params = array('ordersn' => $shopee_order_id,
                        //                 'pickup' => $pickup,
                        //                 'dropoff'=> 
                        //             ); 
                        // $logistics_init = $this->postRequest($url, $params);
                    }elseif(empty($logistics_init_info['dropoff']) && !empty($logistics_init_info['pickup'])){
                        foreach($logistics_init_info['pickup']['address_list'] as $key => $address_list){
                            foreach($address_list['time_slot_list'] as $time_slot_list){
                                $pickup = array(
                                    'address_id' => $address_list['address_id'],
                                    'pickup_time_id' => $time_slot_list['pickup_time_id'],
                                );
                            }
                        }
                        $url = 'logistics/init';
                        $params = array('ordersn' => $shopee_order_id,
                                        'pickup' => $pickup,
                                    ); 
                        $logistics_init = $this->postRequest($url, $params);
                    }

                    $url = 'logistics/airway_bill/get_mass';
                    $params = array('ordersn_list' => (array)$shopee_order_id);
                    $bill_data = $this->postRequest($url, $params);
                    
                    if(isset($bill_data['result']['airway_bills'][0]) && !empty($bill_data['result']['airway_bills'][0])) {
                        if($bill_data['result']['airway_bills'][0]['ordersn'] == $shopee_order_id) {
                          
                          $this->db->query("UPDATE `" . DB_PREFIX . "cedshopee_order` SET shipment_data =  1, shipment_response_data =  '".$this->db->escape(json_encode($bill_data['result']['airway_bills'][0]['airway_bill']))."' WHERE `shopee_order_id` = '" . $shopee_order_id . "'");
                        }else{
                        
                          $this->db->query("UPDATE `" . DB_PREFIX . "cedshopee_order` SET shipment_data = 1, shipment_response_data =  '".$this->db->escape(json_encode($bill_data['result']['airway_bills'][0]['airway_bill']))."' WHERE `shopee_order_id` = '" . $bill_data['result']['airway_bills'][0]['ordersn'] . "'");
                        }
                    }
                }
            }
        }
        return $isExist;
    }

    public function prepareOrderData($data = array())
    {
        if ($data && !$this->isPurchaseOrderIdExist($data['ordersn']))
        {
            return $this->formatOrderData($data);
        }
    }

    public function formatOrderData($data)
    {
        $order_data = array();
        $order_data['shopee_data'] = $data;

        if ($data['create_time'])
            $order_data['orderDate'] = date("Y-m-d h:i:s a", $data['create_time']);

        $order_data['shipment'] = $data['recipient_address'];
        $order_data['shopee_order_id'] = $data['ordersn'];
        $order_data['order_status'] = $data['order_status'];
        $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
        $order_data['store_id'] = $this->config->get('config_store_id');
        $order_data['store_name'] = $this->config->get('config_name');
        $base_url = HTTPS_SERVER;

        if (strpos($base_url, 'admin') !== false) {
            $base_url = str_replace('admin/', '', $base_url);
        }

        $order_data['store_url'] = $base_url;
        $order_data['customer_id'] = '';
        $order_data['customer_group_id'] = $this->config->get('config_customer_group_id');

        $firstname = '';
        $lastname = '';
        $email = '';
        $telephone = '';

        if (isset($data['recipient_address'])) {

            if (isset($data['recipient_address']['name'])) {
                $name = $data['recipient_address']['name'];
                $name = explode(' ', $name);
                $length = count($name);

                if ($length % 2 == 0) {
                    $length = $length / 2;
                } else {
                    $length = ($length + 1) / 2;
                }
            }
            $name = array_chunk($name, $length);

            if (isset($name['0'])) {
                $firstname = implode(' ', $name['0']);
            }

            if (isset($name['1'])) {
                $lastname = implode(' ', $name['1']);
            }

            $email = $this->config->get('cedshopee_order_email');

            if (isset($data['recipient_address']['phone'])) {
                $telephone = $data['recipient_address']['phone'];
            }
        }

        $order_data['firstname'] = $firstname;
        $order_data['lastname'] = $lastname;
        $order_data['email'] = $email;
        $order_data['telephone'] = $telephone;
        $order_data['fax'] = '';
        $order_data['custom_field'] = array();

        $order_data['payment_firstname'] = $firstname;
        $order_data['payment_lastname'] = $lastname;
        $order_data['payment_company'] = '';
        $order_data['payment_address_1'] = isset($data['recipient_address']['full_address']) ? $data['recipient_address']['full_address'] : '';
        $order_data['payment_address_2'] = '';
        $order_data['payment_city'] = isset($data['recipient_address']['city']) ? $data['recipient_address']['city'] : '';
        $order_data['payment_postcode'] = isset($data['recipient_address']['zipcode']) ? $data['recipient_address']['zipcode'] : '';
        $state = isset($data['recipient_address']['state']) ? $data['recipient_address']['state'] : '';
        $country = isset($data['recipient_address']['country']) ? $data['recipient_address']['country'] : '';
        $getLocalizationDeatails = $this->getLocalizationDeatails($state, $country);
        $order_data['payment_zone'] = $getLocalizationDeatails['name'];
        $order_data['payment_zone_id'] = $getLocalizationDeatails['zone_id'];
        $order_data['payment_country'] = $getLocalizationDeatails['country_name'];
        $order_data['payment_country_id'] = $getLocalizationDeatails['country_id'];
        $order_data['payment_address_format'] = '';
        $order_data['payment_custom_field'] = array();
        $payment_method_id = $this->config->get('cedshopee_order_payment');
        $paymentInfo = $this->getExtensions('payment', $payment_method_id);
        $order_data['payment_method'] = isset($paymentInfo['code']) ? $paymentInfo['code'] : 'Shopee Payment';
        $order_data['payment_code'] = 'Shopee Payment';

        $order_data['shipping_firstname'] = $firstname;
        $order_data['shipping_lastname'] = $lastname;
        $order_data['shipping_company'] = '';
        $order_data['shipping_address_1'] = isset($data['recipient_address']['full_address']) ? $data['recipient_address']['full_address'] : '';
        $order_data['shipping_address_2'] = '';
        $order_data['shipping_city'] = isset($data['recipient_address']['city']) ? $data['recipient_address']['city'] : '';
        $order_data['shipping_postcode'] = isset($data['recipient_address']['postalCode']) ? $data['recipient_address']['postalCode'] : '';
        $order_data['shipping_zone'] = $getLocalizationDeatails['name'];
        $order_data['shipping_zone_id'] = $getLocalizationDeatails['zone_id'];
        $order_data['shipping_country'] = $getLocalizationDeatails['country_name'];
        $order_data['shipping_country_id'] = $getLocalizationDeatails['country_id'];;
        $order_data['shipping_address_format'] = '';
        $order_data['shipping_custom_field'] = array();

        if(!empty($this->config->get('cedshopee_order_carrier'))){
            $res = $this->getExtensions('shipping', $this->config->get('cedshopee_order_carrier'));
            $shippingMethod      = $res['code'];
        } else {
            $shippingMethod      = $data['shipping_carrier'];
        }

        $order_data['shipping_method'] = isset($shippingMethod) ? $shippingMethod : 'Shopee Shipping';
        $order_data['shipping_code']   = isset($shippingMethod) ? $shippingMethod : 'ShopeeShipping.ShopeeShipping';

        // for products
        $shippingCost = (isset($data['actual_shipping_cost']) && !empty($data['actual_shipping_cost'])) ? $data['actual_shipping_cost'] : $data['estimated_shipping_fee'];

        if (isset($data['items']) && count($data['items'])) {
            foreach ($data['items'] as $orderLine => $item)
            {
                $sku = isset($item['item_sku']) ? $item['item_sku'] : '';
                if (!strlen($sku)) {
                    continue;
                }
                $product_title = $item['item_name'];
                $qty = isset($item['variation_quantity_purchased']) ? $item['variation_quantity_purchased'] : '0';
                $itemCost = isset($item['variation_original_price']) ? $item['variation_original_price'] : '0';
                $itemDiscountedCost = isset($item['variation_discounted_price']) ? $item['variation_discounted_price'] : '0';
                $variation_sku = (isset($item['variation_sku']) && !empty($item['variation_sku'])) ? $item['variation_sku'] : '';
                $product = $this->getProductBySKU($sku, $qty, $product_title, $itemCost, $itemDiscountedCost, $data['ordersn'], $data, $item, $variation_sku);
                if (isset($product) && is_array($product) && !empty($product))
                    $order_data['products'][] = $product;
                else
                    continue;
            }
        }

        $total = 0;
        $tax = 0;
        if (isset($order_data['products']) && count($order_data['products']) > 0) {
            foreach ($order_data['products'] as $key => $value) {
                $total = $total + (floatval($value['total']));
                if($value['tax'])
                    $tax = $tax + (floatval($value['tax']));
            }

            $order_data['comment'] = $data['message_to_seller'];
            $order_data['total'] = (float)$total; // $data['total_amount'];
            $order_data['affiliate_id'] = '0';
            $order_data['commission'] = '0';
            $order_data['marketing_id'] = '0';
            $order_data['tracking'] = isset($data['tracking_no']) ? $data['tracking_no'] : '';
            $order_data['language_id'] = $this->config->get('config_language_id');

            if (isset($this->session->data['currency']) && $this->session->data['currency']) {
                $order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
                $order_data['currency_code'] = $this->session->data['currency'];
                $order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
            } else {
                $order_data['currency_id'] = $this->currency->getId($this->config->get('config_currency'));
                $order_data['currency_code'] = $this->config->get('config_currency');
                $order_data['currency_value'] = $this->currency->getValue($this->config->get('config_currency'));
            }
            $order_data['vouchers'] = array();

            $order_data['totals'][] = array(
                'code' => 'escrow_amount',
                'title' => 'Escrow Amount',
                // 'text' => $this->currency->format($sub_total),
                'value' => $data['escrow_amount'],
                'sort_order' => 1
            );

            $order_data['totals'][] = array(
                'code' => 'shipping',
                'title' => 'Shopee Shipping',
                // 'text' => $this->currency->format((float)$shippingCost),
                'value' => (float) isset($shippingCost) ? $shippingCost : 0,
                'sort_order' => 2
            );

            $order_data['totals'][] = array(
                'code' => 'tax',
                'title' => 'Tax',
                // 'text' => $this->currency->format($tax),
                'value' => $tax,
                'sort_order' => 3
            );

            $order_data['totals'][] = array(
                'code' => 'total_amount',
                'title' => 'Total Amount by Shopee',
                // 'text' => $this->currency->format($tax),
                'value' => $data['total_amount'],
                'sort_order' => 4
            );

            $order_data['totals'][] = array(
                'code' => 'total',
                'title' => 'Total',
                // 'text' => $this->currency->format(max(0, $order_data['total'])),
                'value' => $total, //$data['total_amount'],
                'sort_order' => 5
            );

//            $order_data['ip'] = $this->request->server['REMOTE_ADDR'];
//
//            if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
//                $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
//            } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
//                $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
//            } else {
//                $order_data['forwarded_ip'] = '';
//            }
//
//            if (isset($this->request->server['HTTP_partner_id_AGENT'])) {
//                $order_data['partner_id_agent'] = $this->request->server['HTTP_partner_id_AGENT'];
//            } else {
//                $order_data['partner_id_agent'] = '';
//            }
//
//            if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
//                $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
//            } else {
//                $order_data['accept_language'] = '';
//            }
//
//            if (!empty($this->request->server['HTTP_CLIENT_IP'])) {
//                $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
//            } else {
//                $order_data['forwarded_ip'] = '';
//            }
//
//            if (isset($this->request->server['HTTP_partner_id_AGENT'])) {
//                $order_data['partner_id_agent'] = $this->request->server['HTTP_partner_id_AGENT'];
//            } else {
//                $order_data['partner_id_agent'] = '';
//            }
//
//            if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
//                $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
//            } else {
//                $order_data['accept_language'] = '';
//            }
            return $order_data;
        } else {
            return array();
        }
    }

    public function getLocalizationDeatails($Statecode, $countryCode)
    {
        $json = array();
        $query = $this->db->query("SELECT `country_id`,`name` FROM `" . DB_PREFIX . "country` WHERE `iso_code_3` LIKE '" . $countryCode . "%'");
        if ($query->num_rows) {
            $country_id = 0;
            $country_name = '';
            if (isset($query->row['country_id']) && $query->row['country_id']) {
                $country_id = $query->row['country_id'];
                $country_name = $query->row['name'];
            }
            if ($country_id) {
                $json = array(
                    'country_id' => $country_id,
                    'country_name' => $country_name
                );
                $query = $this->db->query("SELECT `zone_id`,`name` FROM " . DB_PREFIX . "zone WHERE country_id='" . $country_id . "' AND code='" . $Statecode . "'");
                if ($query->num_rows) {
                    if (isset($query->row['zone_id']) && isset($query->row['name'])) {
                        $json = array(
                            'country_id' => $country_id,
                            'country_name' => $country_name,
                            'zone_id' => $query->row['zone_id'],
                            'name' => $query->row['name']
                        );
                    };
                } else {
                    $json = array(
                        'country_id' => $country_id,
                        'country_name' => $country_name,
                        'zone_id' => '',
                        'name' => ''
                    );
                }
            } else {
                $json =  array(
                    'country_id' => '',
                    'zone_id' => '',
                    'name' => '',
                    'country_name' => ''
                );
            }
        } else {
            $json =  array(
                'country_id' => '',
                'zone_id' => '',
                'name' => '',
                'country_name' => ''
            );
        }
        return $json;
    }

    public function getExtensions($type, $payment_method_id)
    {
        $query = $this->db->query("SELECT code FROM " . DB_PREFIX . "extension WHERE `type` = '" . $this->db->escape($type) . "' AND extension_id = '". $payment_method_id ."' ");
        return $query->row;
    }

    public function getProductBySKU($sku, $q, $product_title, $cedshopee_price, $itemDiscountedCost, $shopee_order_id, $order_data, $orderLine, $variation_sku)
    {
        $product = array();
        $product_exist = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product` WHERE `model`='" . $sku . "' OR `sku`='" . $sku . "' ");

        if($product_exist->num_rows)
        {
            $product_data = $product_exist->row;
            $product_id = isset($product_data['product_id']) ? $product_data['product_id'] : null;
            if($product_id)
            {
                if (strlen($product_title) == '0') {
                    $product_desc = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "product_description` WHERE `product_id`='" . $product_id . "' AND `language_id`='" . $this->config->get('config_language_id') . "'");
                    if ($product_desc->num_rows)
                        $product_title = $product_desc->row['name'];
                }
                $quant =   $product_data['quantity'];
                $model =   $product_data['model'];
                $price =   $product_data['price'];

//                if($quant >= $q)
//                {
                    $product_options = $this->db->query("SELECT `name`, `stock` FROM `" . DB_PREFIX . "cedshopee_product_variations` WHERE `variation_sku`='" . $variation_sku . "'");

                    if($product_options->num_rows)
                    {
                        $option_combination['0'] = $product_options->row['name'];
                        $product['product_id'] = $product_id;
                        $product['quantity'] = $q;
                        $product['model'] = $model;
                        $product['subtract'] = $q;
                        $product['price'] = $cedshopee_price;
                        $product['total'] = ($cedshopee_price) ? ($q * $cedshopee_price) : $q * $price;
                        $product['tax'] = $this->config->get('cedshopee_tax');
                        $product['reward'] = 0;
                        $product['name'] = $product_title;
                        $product['option'] = $option_combination;
                        $product['download'] = array();
                        return $product;
                    } else {
                        $product['product_id'] = $product_id;
                        $product['quantity'] = $q;
                        $product['model'] = $model;
                        $product['subtract'] = $q;
                        $product['price'] = ($itemDiscountedCost) ? $itemDiscountedCost : $cedshopee_price;
                        $product['tax'] = $this->config->get('cedshopee_tax');
                        $product['total'] = ($product['price']) ? ($q * $product['price']) + $product['tax'] : $q * $price + $product['tax'];
                        $product['reward'] = 0;
                        $product['name'] = $product_title;
                        $product['option'] = array();
                        $product['download'] = array();
                        return $product;
                    }
//                } else {
//                    $this->orderErrorInformation($sku, $shopee_order_id, $order_data, "REQUESTED QUANTITY FOR PRODUCT ID " . $product_id . " IS NOT AVAILABLE", $orderLine);
//                    return array();
//                }
            } else {
                $this->orderErrorInformation($sku, $shopee_order_id, $order_data, "PRODUCT " . $product_id . " DOES NOT EXIST", $orderLine);
                return array();
            }
        } else {
            $this->orderErrorInformation($sku, $shopee_order_id, $order_data, "MERCHANT SKU DOES NOT EXIST", $orderLine);
            return array();
        }
    }

    public function orderErrorInformation($sku, $ordersn, $worder_data, $errormessage, $orderLine)
    {
        $sql_check_already_exists = "SELECT * FROM `" . DB_PREFIX . "cedshopee_order_error` WHERE `merchant_sku`='" . $sku . "' AND `shopee_order_id`='" . $ordersn . "'";
        $query_check_already_exists = $this->db->query($sql_check_already_exists);
        if (!$query_check_already_exists->num_rows) {
            $sql_delete = "DELETE  FROM `" . DB_PREFIX . "cedshopee_order_error` WHERE `merchant_sku`='" . $sku . "'";
            $this->db->query($sql_delete);
            $sql_insert = "INSERT INTO `" . DB_PREFIX . "cedshopee_order_error` (`merchant_sku`,`shopee_order_id`,`order_data`,`reason`)VALUES('" . $this->db->escape($sku) . "','" . $ordersn . "','" . $this->db->escape(json_encode($worder_data)) . "','" . $errormessage . "')";
            $result = $this->db->query($sql_insert);
            if ($result) {
                if ($this->config->get('cedshopee_auto_order') == '1') {
                    $this->cancelOrder($ordersn, $orderLine, 'v3/vorders');
                }
            }
        }
    }

//    public function getProductBySKU($sku, $q, $product_title, $cedshopee_price, $itemDiscountedCost, $shopee_order_id, $worder_data, $orderLine)
//    {
//        $product = array();
//        // for variation
//        //checking merchant sku in variants
//        $query = $this->db->query("SELECT `product_id`,`name` FROM `" . DB_PREFIX . "cedshopee_product_variations` WHERE `variation_sku`='" . $sku . "'");
//
//        if ($query->num_rows)
//        {
//          $option_combination['0'] = isset($query->row['name']) ? $query->row['name'] : '';
//          $product['product_id'] = isset($query->row['product_id']) ? $query->row['product_id'] : '';
//          $sql = "SELECT `status`,`minimum`,`quantity`,`model`,`price`FROM `" . DB_PREFIX . "product` WHERE `product_id`='" . $product['product_id'] . "'";
//          $query = $this->db->query($sql);
//          if (strlen($product_title) == '0')
//          {
//              $sql_name = "SELECT `name` FROM `" . DB_PREFIX . "product_description` WHERE `product_id`='" . $product['product_id'] . "' and `language_id`='" . $this->config->get('config_language_id') . "'";
//              $query_name = $this->db->query($sql_name);
//              if ($query_name->num_rows)
//                  $product_title = $query_name->row['name'];
//          }
//          if ($query->num_rows)
//          {
//            $status = $query->row['status'];
//            $quant = $query->row['quantity'];
//            $model = $query->row['model'];
//            $price = $query->row['price'];
//            if ($status)
//            {
//                if ($quant >= $q)
//                {
//                    $product['quantity'] = $q;
//                    $product['model'] = $model;
//                    $product['subtract'] = $q;
//                    $product['price'] = $cedshopee_price;
//                    $product['total'] = ($cedshopee_price) ? ($q * $cedshopee_price) : $q * $price;
//                    $product['tax'] = $this->config->get('cedshopee_tax');
//                    $product['reward'] = 0;
//                    $product['name'] = $product_title;
//                    $product['option'] = $option_combination;
//                    $product['download'] = array();
//                    return $product;
//                } else {
//                    $this->orderErrorInformation($sku, $shopee_order_id, $worder_data, "REQUESTED QUANTITY FOR PRODUCT ID " . $product['product_id'] . " IS NOT AVAILABLE", $orderLine);
//
//                    return array();
//                }
//            } else {
//              $this->orderErrorInformation($sku, $shopee_order_id, $worder_data, "PRODUCT STATUS IS DISABLED WITH ID " . $product['product_id'] . "", $orderLine);
//              return array();
//            }
//          } else {
//            $this->orderErrorInformation($sku, $shopee_order_id, $worder_data, "PRODUCT ID" . $product['product_id'] . " DOES NOT EXIST", $orderLine);
//            return array();
//          }
//        } else {
//            // checking merchant sku in products
//            $sql = "SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `sku` = '" . $sku . "'";
//            $productdata = $query = $this->db->query($sql);
//
//            $product['product_id'] = '';
//            if ($productdata->num_rows) {
//                $product['product_id'] = $productdata->row['product_id'];
//            }
//            if ($product['product_id'])
//            {
//                $sql = "SELECT `status`,`minimum`,`quantity`,`model`,`price`FROM `" . DB_PREFIX . "product` WHERE `product_id`='" . $product['product_id'] . "'";
//                $query = $this->db->query($sql);
//                if (strlen($product_title) == '0') {
//                    $sql_name = "SELECT `name` FROM `" . DB_PREFIX . "product_description` WHERE `product_id`='" . $product['product_id'] . "' and `language_id`='" . $this->config->get('config_language_id') . "'";
//                    $query_name = $this->db->query($sql_name);
//                    if ($query_name->num_rows)
//                        $product_title = $query_name->row['name'];
//                }
//                if ($query->num_rows) {
//
//                    $status = $query->row['status'];
//                    $quant = $query->row['quantity'];
//                    $model = $query->row['model'];
//                    $price = $query->row['price'];
//                    if ($status) {
//
//                        if (($quant >= $q) || $this->config->get('cedshopee_force_order_fetch')) {
//
//                            $product['quantity'] = $q;
//                            $product['model'] = $model;
//                            $product['subtract'] = $q;
//                            $product['price'] = ($itemDiscountedCost) ? $itemDiscountedCost : $cedshopee_price;
//                            $product['tax'] = $this->config->get('cedshopee_tax');
//                            $product['total'] = ($product['price']) ? ($q * $product['price']) + $product['tax'] : $q * $price + $product['tax'];
//                            $product['reward'] = 0;
//                            $product['name'] = $product_title;
//                            $product['option'] = array();
//                            $product['download'] = array();
//                            return $product;
//                        } else {
//                            $this->orderErrorInformation($sku, $shopee_order_id, $worder_data, "REQUESTED QUANTITY FOR PRODUCT ID " . $product['product_id'] . " IS NOT AVAILABLE", $orderLine);
//
//                            return array();
//                        }
//                    } else {
//                        $this->orderErrorInformation($sku, $shopee_order_id, $worder_data, "PRODUCT STATUS IS DISABLED WITH ID " . $product['product_id'] . "", $orderLine);
//                        return array();
//                    }
//
//
//                } else {
//                    $this->orderErrorInformation($sku, $shopee_order_id, $worder_data, "PRODUCT ID" . $product['product_id'] . " DOES NOT EXIST", $orderLine);
//                    return array();
//                }
//            } else {
//                $this->orderErrorInformation($sku, $shopee_order_id, $worder_data, "MERCHANT SKU DOES NOT EXIST", $orderLine);
//                return array();
//            }
//        }
//    }

    public function createOrder($data)
    {
        try{
            if (is_array($data) && count($data))
            {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "',store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "',payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', order_status_id = '". (int) $this->config->get("cedshopee_order_import") ."', affiliate_id  = '" . (int)$data['affiliate_id'] . "', language_id = '" . (int)$this->config->get('config_language_id') . "', currency_id = '" . (int)$data['currency_id'] . "', currency_code = '" . $this->db->escape($data['currency_code']) . "', currency_value = '" . (float)$data['currency_value'] . "', tracking = '". $this->db->escape($data['tracking']) ."', date_added = NOW(), date_modified = NOW()");
                $order_id = $this->db->getLastId();
                if ($order_id)
                {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "cedshopee_order` SET 
                    `opencart_order_id` = '" . $order_id . "',
                    `order_place_date` = '" . $this->db->escape($data['orderDate']) . "',
                    `order_data` = '" . $this->db->escape(json_encode($data['shopee_data'])) . "',
                    `status` = '". $data['order_status'] ."',
                    `shopee_order_id` = '" . $data['shopee_order_id'] . "',
                    `shipment_data` = '" . $this->db->escape(json_encode($data['shipment'])) . "'
                ");

                    // Products
                    foreach ($data['products'] as $product)
                    {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "'");

                        $order_product_id = $this->db->getLastId();

                        if (isset($product['option']) && count($product['option']) > 0)
                        {
                            foreach ($product['option'] as $option_value_id => $option_value)
                            {
                                $sql = "SELECT pov.product_option_value_id,po.product_option_id, od.name, ovd.name as `value`,o.type FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "product_option po ON (po.product_id=pov.product_id AND po.option_id=pov.option_id) LEFT JOIN " . DB_PREFIX . "option o ON (pov.option_id =o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (pov.option_id =od.option_id) JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_id =ovd.option_id AND pov.option_value_id=ovd.option_value_id) WHERE ovd.name ='" . $this->db->escape($option_value) . "' AND pov.product_id=" . (int)$product['product_id'];

                                $options_data = $this->db->query($sql);
                                if ($options_data->num_rows) {
                                    $option = $options_data->row;
                                    $this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
                                }
                            }
                        }
                    }
                    // Get the total
                    $total = 0;

                    if (isset($data['totals'])) {
                        foreach ($data['totals'] as $order_total) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($order_total['code']) . "', title = '" . $this->db->escape($order_total['title']) . "', `value` = '" . (float)$order_total['value'] . "', sort_order = '" . (int)$order_total['sort_order'] . "'");
                            if ($order_total['code'] == 'total') {
                                $total += $order_total['value'];
                            }
                        }
                    }
                    // Update order total

                    $this->db->query("UPDATE `" . DB_PREFIX . "order` SET total = '" . (float)$total . "' WHERE order_id = '" . (int)$order_id . "'");

                    $this->addOrderHistory($order_id, $this->getOrderStatusId($data['order_status']));
                    return $order_id;
                }
            } else {
                return false;
            }
        } catch (Exception $e) {
            echo '<pre>'; print_r($e->getMessage()); die;
        }
    }

    public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = true)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
        $data = array();
        $data['order_status_id'] = (int)$order_status_id;
        $data['order_id'] = (int)$order_id;
        $data['comment'] = 'A Shopee Order Imported Successfully';
        $data['notify'] = (int)$notify;
        // Stock subtraction
        $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

        $ids_products = array();
        foreach ($order_product_query->rows as $order_product) {
            $result = $this->db->query("SELECT `quantity` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$order_product['product_id'] . "'");
            $this->log(json_encode($result->rows), 6, true);
            // if (!$this->config->get('cedshopee_use_shipstation')) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

            $this->log('Product ID And QTY.' . $order_product['product_id'] . ' - ' . $order_product['quantity'], 6, true);
            $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product['order_product_id'] . "'");

            foreach ($order_option_query->rows as $option) {
                $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$option['product_option_value_id'] . "' AND subtract = '1'");
            }
            if (isset($order_product['product_id']) && (int)$order_product['product_id'])
                $ids_products[] = array('product_id' => (int)$order_product['product_id']);

            $this->updateInventory((int)$order_product['product_id']);
            //}

        }

        $order_info = $this->getOrder($order_id);

        if ($order_info) {

            // Update the DB with the new statuses
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");


            // If order status is 0 then becomes greater than 0 send main html email
            if ($order_status_id) {
                $language = new Language($order_info['language_directory']);
                $language->load($order_info['language_filename']);
                $language->load('mail/order');

                $subject = sprintf($language->get('text_subject'), $order_info['store_name'], $order_id);

                $message = $language->get('text_order') . ' ' . $order_id . "\n";
                $message .= $language->get('text_date_added') . ' ' . date($language->get('date_format_short'), strtotime($order_info['date_added'])) . "\n\n";

                $order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$data['order_status_id'] . "' AND language_id = '" . (int)$order_info['language_id'] . "'");

                if ($order_status_query->num_rows) {
                    $message .= $language->get('text_order_status') . "\n";
                    $message .= $order_status_query->row['name'] . "\n\n";
                }

                if ($order_info['customer_id']) {
                    $message .= $language->get('text_link') . "\n";
                    $message .= html_entity_decode($order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_id, ENT_QUOTES, 'UTF-8') . "\n\n";
                }

                if ($data['comment']) {
                    $message .= $language->get('text_comment') . "\n\n";
                    $message .= strip_tags(html_entity_decode($data['comment'], ENT_QUOTES, 'UTF-8')) . "\n\n";
                }

                $message .= $language->get('text_footer');

                $mail = new Mail();
                $mail->protocol = $this->config->get('config_mail_protocol');
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->hostname = $this->config->get('config_smtp_host');
                $mail->partner_idname = $this->config->get('config_smtp_partner_idname');
                $mail->shop_idword = $this->config->get('config_smtp_shop_idword');
                $mail->port = $this->config->get('config_smtp_port');
                $mail->timeout = $this->config->get('config_smtp_timeout');
                $mail->setTo($order_info['email']);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender($order_info['store_name']);
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();
            }
        }
    }

    public function getOrder($order_id)
    {
        $order_query = $this->db->query("SELECT *, (SELECT CONCAT(c.firstname, ' ', c.lastname) FROM " . DB_PREFIX . "customer c WHERE c.customer_id = o.customer_id) AS customer FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

        if ($order_query->num_rows) {
            $reward = 0;

            $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

            foreach ($order_product_query->rows as $product) {
                $reward += $product['reward'];
            }

            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

            if ($country_query->num_rows) {
                $payment_iso_code_2 = $country_query->row['iso_code_2'];
                $payment_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $payment_iso_code_2 = '';
                $payment_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $payment_zone_code = $zone_query->row['code'];
            } else {
                $payment_zone_code = '';
            }

            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

            if ($country_query->num_rows) {
                $shipping_iso_code_2 = $country_query->row['iso_code_2'];
                $shipping_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $shipping_iso_code_2 = '';
                $shipping_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $shipping_zone_code = $zone_query->row['code'];
            } else {
                $shipping_zone_code = '';
            }

            if ($order_query->row['affiliate_id']) {
                $affiliate_id = $order_query->row['affiliate_id'];
            } else {
                $affiliate_id = 0;
            }

            $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "language WHERE language_id = '" . (int)$order_query->row['language_id'] . "'");

            if ($query && $query->num_rows) {
                $language_info = $query->row;

                if ($language_info) {
                    $language_code = isset($language_info['code']) ? $language_info['code'] : '';
                    $language_filename = isset($language_info['filename']) ? $language_info['filename'] : '';
                    $language_directory = isset($language_info['directory']) ? $language_info['directory'] : '';
                } else {
                    $language_code = '';
                    $language_filename = '';
                    $language_directory = '';
                }
            }


            return array(
                'order_id' => $order_query->row['order_id'],
                'invoice_no' => $order_query->row['invoice_no'],
                'invoice_prefix' => $order_query->row['invoice_prefix'],
                'store_id' => $order_query->row['store_id'],
                'store_name' => $order_query->row['store_name'],
                'store_url' => $order_query->row['store_url'],
                'customer_id' => $order_query->row['customer_id'],
                'customer' => $order_query->row['customer'],
                'customer_group_id' => $order_query->row['customer_group_id'],
                'firstname' => $order_query->row['firstname'],
                'lastname' => $order_query->row['lastname'],
                'telephone' => $order_query->row['telephone'],
                'fax' => $order_query->row['fax'],
                'email' => $order_query->row['email'],
                'payment_firstname' => $order_query->row['payment_firstname'],
                'payment_lastname' => $order_query->row['payment_lastname'],
                'payment_company' => $order_query->row['payment_company'],
                'payment_company_id' => isset($order_query->row['payment_company_id']) ? $order_query->row['payment_company_id'] : '',
                'payment_tax_id' => isset($order_query->row['payment_tax_id']) ? $order_query->row['payment_tax_id'] : '',
                'payment_address_1' => $order_query->row['payment_address_1'],
                'payment_address_2' => $order_query->row['payment_address_2'],
                'payment_postcode' => $order_query->row['payment_postcode'],
                'payment_city' => $order_query->row['payment_city'],
                'payment_zone_id' => $order_query->row['payment_zone_id'],
                'payment_zone' => $order_query->row['payment_zone'],
                'payment_zone_code' => $payment_zone_code,
                'payment_country_id' => $order_query->row['payment_country_id'],
                'payment_country' => $order_query->row['payment_country'],
                'payment_iso_code_2' => $payment_iso_code_2,
                'payment_iso_code_3' => $payment_iso_code_3,
                'payment_address_format' => $order_query->row['payment_address_format'],
                'payment_method' => $order_query->row['payment_method'],
                'payment_code' => $order_query->row['payment_code'],
                'shipping_firstname' => $order_query->row['shipping_firstname'],
                'shipping_lastname' => $order_query->row['shipping_lastname'],
                'shipping_company' => $order_query->row['shipping_company'],
                'shipping_address_1' => $order_query->row['shipping_address_1'],
                'shipping_address_2' => $order_query->row['shipping_address_2'],
                'shipping_postcode' => $order_query->row['shipping_postcode'],
                'shipping_city' => $order_query->row['shipping_city'],
                'shipping_zone_id' => $order_query->row['shipping_zone_id'],
                'shipping_zone' => $order_query->row['shipping_zone'],
                'shipping_zone_code' => $shipping_zone_code,
                'shipping_country_id' => $order_query->row['shipping_country_id'],
                'shipping_country' => $order_query->row['shipping_country'],
                'shipping_iso_code_2' => $shipping_iso_code_2,
                'shipping_iso_code_3' => $shipping_iso_code_3,
                'shipping_address_format' => $order_query->row['shipping_address_format'],
                'shipping_method' => $order_query->row['shipping_method'],
                'shipping_code' => $order_query->row['shipping_code'],
                'comment' => $order_query->row['comment'],
                'total' => $order_query->row['total'],
                'reward' => $reward,
                'order_status_id' => $order_query->row['order_status_id'],
                'commission' => $order_query->row['commission'],
                'language_id' => $order_query->row['language_id'],
                'language_code' => $language_code,
                'language_filename' => $language_filename,
                'language_directory' => $language_directory,
                'currency_id' => $order_query->row['currency_id'],
                'currency_code' => $order_query->row['currency_code'],
                'currency_value' => $order_query->row['currency_value'],
                'ip' => $order_query->row['ip'],
                'forwarded_ip' => $order_query->row['forwarded_ip'],
                'partner_id_agent' => isset($order_query->row['partner_id_agent']) ? $order_query->row['partner_id_agent'] : '',
                'accept_language' => $order_query->row['accept_language'],
                'date_added' => $order_query->row['date_added'],
                'date_modified' => $order_query->row['date_modified']
            );
        } else {
            return false;
        }
    }

    public function updateInventory($product_id, $data = array())
    {
        $result =false;
        $shopee_item_id = $this->getShopeeItemId($product_id);

        if (isset($shopee_item_id) && !empty($shopee_item_id))
        {
            $quantity = $this->getCedShopeeQuantity($product_id);
            $variants = $this->isVariantProduct($product_id, $data);

            if (isset($variants['variations']) && !empty($variants['variations'])) {
                foreach ($variants['variations'] as $key => $value) {
                    if(isset($value['variation_id']) && !empty($value['variation_id']))
                    {
                        if($value['stock'] < '0')
                            $value['stock'] = '0';
                        $stock_data = array(
                            'stock' => (int)$value['stock'],
                            'variation_id' => (int)$value['variation_id'],
                            'item_id'=>(int)$shopee_item_id,
                        );
                        $this->log('items/update_variation_stock');
                        $result = $this->postRequest('items/update_variation_stock',$stock_data);
                        if(isset($result['item']) && $result['item'])
                        {
                            $this->db->query("UPDATE `". DB_PREFIX ."cedshopee_product_variations` 
                            SET `stock` = '". $value['stock'] ."'
                             WHERE `product_id` = '". (int) $product_id ."' 
                             AND `variation_id` = '". (int) $value['variation_id'] ."' ");
                        }

                        $this->log(json_encode($result));
                    } else {
                        if($quantity < '0')
                            $quantity = '0';
                        $this->log('items/update_stock');
                        $result = $this->postRequest('items/update_stock',array('stock'=> (int)$quantity, 'item_id'=>(int)$shopee_item_id));
                        $this->log(json_encode($result));
                    }
                }
            } else {
                if($quantity < '0')
                    $quantity = '0';
                $this->log('items/update_stock');
                $result = $this->postRequest('items/update_stock',array('stock'=> (int)$quantity, 'item_id'=>(int)$shopee_item_id));
                $this->log(json_encode($result));
            }
        }
        return $result ;
    }

    public function getOrderStatusId($name)
    {
        $query = $this->db->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order_status` WHERE `name`='" . $name . "'");
        if ($query && $query->num_rows)
            return $query->row['order_status_id'];
        else
            return '1';
    }

    public function getOrderStatusNameById($order_status_id)
    {
        $query  = $this->db->query("SELECT `name` FROM `".DB_PREFIX."order_status` WHERE `order_status_id`='". (int) $order_status_id."'");
        if($query && $query->num_rows)
            return $query->row['name'];
    }

    public function acknowledgeOrder($shopee_order_id, $url = 'v3/orders')
    {
        $response = $this->WPostRequest($url . '/' . $shopee_order_id . '/acknowledge'
        );
        try {
            if (isset($response['success']) && $response['success']) {
                $response = $response['response'];
            } else {
                return $response;
            }
            $response = json_decode($response, true);
            if (isset($response['error'])) {
                return array('success' => false, 'message' => $response['error']);
            }
            $order_status_name = $this->getOrderStatusNameById($this->config->get('cedshopee_order_import'));
            if(empty($order_status_name))
                $order_status_name = 'acknowledged';
            
            $this->updateOrderStatus($shopee_order_id, $order_status_name);
            return $response;
        } catch (Exception $e) {
            $this->log('acknowledgeOrder' . var_export($response, true));
            return false;
        }
    }

    public function shipOrder($ship_data = null)
    {
        $trackingNumber = '';
        if (isset($ship_data['tracking_number'])) {
            $trackingNumber = $ship_data['tracking_number'];
        }

        $ordersn = '';
        if (isset($ship_data['ordersn'])) {
            $ordersn = $ship_data['ordersn'];
        }
        if($trackingNumber && $ordersn){

            try {
                $params = array('info_list' => array(array('tracking_number' => $trackingNumber, 'ordersn' =>$ordersn)));
                $response = $this->postRequest('logistics/tracking_number/set_mass',
                    $params);
                if (isset($response['result']) && $response['result']) {
                    if (isset($response['result']['success_count']) && $response['result']['success_count']) {
                        $order_status_name = $this->getOrderStatusNameById($this->config->get('cedshopee_order_ship'));
                        if(empty($order_status_name))
                        {
                            $order_status_name = 'shipped';
                        }
                        $this->updateOrderStatus($ordersn, $order_status_name);
                        return array('success' => true, 'response' => json_encode($response));
                    } else {
                        return array('success' => false, 'message' => $response['result']['error_codes']);
                    }
                }
            } catch (Exception $e) {
                $this->log('Response: ' . var_export($response, true));
                return array('success' => false, 'message' => 'Response: ' . var_export($response, true));
            }
        }
    }

    public function cancelOrder($params)
    {
        $response = $this->postRequest('orders/cancel', $params);
        try {
            if (!isset($response['error']) && $response['error']) {
                if (isset($response['response']) && $response['response']) {
                    $order_status_name = $this->getOrderStatusNameById($this->config->get('cedshopee_order_cancel'));
                    if(empty($order_status_name))
                        $order_status_name = 'canceled';
                    
                    $this->updateOrderStatus($params['ordersn'], $order_status_name);
                    return array('success' => true, 'response' => $response['msg']);
                } else if(isset($response['msg'])) {
                    return array('success' => false, 'message' => $response['msg']);
                }
            }else if(isset($response['msg'])) {
                return array('success' => false, 'message' => $response['msg']);
            }
        } catch (Exception $e) {
            $this->log('cancelOrder: ' . var_export($response, true));
            return false;
        }
    }

    public function updateOrderStatus($shopee_order_id, $status = 'created')
    {
        $result = $this->db->query("SELECT `opencart_order_id` FROM `" . DB_PREFIX . "cedshopee_order` where `shopee_order_id` = '" . (int)$shopee_order_id . "'");
        $order_id = 0;
        if ($result && $result->num_rows) {
            $order_id = $result->row['opencart_order_id'];
        }
        $order_status_id = $this->getOrderStatusId($status);
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', date_added = NOW()");
        $this->db->query("UPDATE " . DB_PREFIX . "order SET `order_status_id` = '" . (int)$order_status_id . "' WHERE `order_id` = '" . (int)$order_id . "'");
    }

    public function getShopeeItemId($product_id=0) {
        if ($product_id) {
            $shopee_item_id = '';
            $sql = "SELECT `shopee_item_id` FROM `".DB_PREFIX."cedshopee_uploaded_products` where `product_id`='".$product_id."' AND shopee_item_id > 0";
            $result = $this->db->query($sql);
            if ($result && $result->num_rows && isset($result->row['shopee_item_id'])) {
                $shopee_item_id = $result->row['shopee_item_id'];
            }
            return $shopee_item_id;
        }
        return false;
    }

    public function getShipmentById($order_id)
    {
        $query = $this->db->query("SELECT `shipment_request_data` FROM `" . DB_PREFIX . "cedshopee_order` WHERE opencart_order_id='" . $order_id . "'");
        if ($query->num_rows) {
            return json_decode($query->row['shipment_request_data'], true);
        }
    }

    public function updateProductPriceOncedshopee($product_ids = array())
    {
        if (is_numeric($product_ids)) {
            $product_ids = array($product_ids);
        }

        if (count($product_ids) == 0) {
            $cedshopee_map_cat_all = $this->config->get('cedshopee_map_cat_all');

            if (is_array($cedshopee_map_cat_all) && isset($cedshopee_map_cat_all['children_category']) && isset($cedshopee_map_cat_all['parent_wcat']) && $cedshopee_map_cat_all['children_category'] && $cedshopee_map_cat_all['parent_wcat']) {
                $query = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product`");
                if ($query && $query->num_rows) {
                    foreach ($query->rows as $key => $value) {
                        if (isset($value['product_id']) && $value['product_id']) {
                            $ids[] = $value['product_id'];
                        }
                    }
                }
            } else {
                $query = $this->db->query("SELECT `category_id` FROM `" . DB_PREFIX . "cedshopee_category_mapping`");
                $ids = array();
                if ($query && $query->num_rows) {
                    foreach ($query->rows as $key => $value) {
                        if (isset($value['category_id']) && $value['category_id']) {
                            $query = $this->db->query("SELECT `product_id` FROM " . DB_PREFIX . "product_to_category WHERE  `category_id` = '" . (int)$value['category_id'] . "'");
                            if ($query && $query->num_rows) {
                                foreach ($query->rows as $key => $value) {
                                    if (isset($value['product_id']) && $value['product_id']) {
                                        $ids[] = $value['product_id'];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $product_ids = $ids;
        }
        if (is_array($product_ids) && count($product_ids)) {
            $product_idss = array_chunk($product_ids, 9900);
            foreach ($product_idss as $key => $product_ids) {
                $product_price_array = array();
                foreach ($product_ids as $key => $product_id) {
                    $variants = $this->getVariantProducts($product_id);
                    $product_price = 0;
                    $sku = '';
                    $sku = $this->getSku($product_id);
                    $result = $this->db->query("SELECT `price`,`sku` FROM `" . DB_PREFIX . "product` where `product_id` = '" . $product_id . "'");
                    if ($result->num_rows) {
                        $product_price = $result->row['price'];

                    }
                    if (is_array($variants) && count($variants)) {

                        foreach ($variants as $key => $value) {
                            $currentPriceArray = array();
                            $sku = $value['merchant_sku'];
                            if (isset($value['option_combination']) && count($value['option_combination']) > 0) {
                                $combination = json_decode($value['option_combination'], true);
                                foreach ($combination as $key => $value) {
                                    $result = $this->db->query("SELECT `product_option_id`,`option_value_id`,`quantity`,`price`,`price_prefix` FROM `" . DB_PREFIX . "product_option_value` where `product_id` = '" . $product_id . "' AND `option_id` = '" . $key . "' AND `option_value_id` = '" . $value . "'");

                                    if ($result->num_rows) {
                                        $options_array[] = $result->row;
                                    }
                                }
                            }

                            $result = $this->db->query("SELECT `price` FROM `" . DB_PREFIX . "cedshopee_product` where `product_id` = '" . $product_id . "'");
                            if ($result->num_rows) {
                                $product_price = $result->row['price'];
                            }
                            $optionsArray = array();
                            if (count($options_array) > 0) {

                                foreach ($options_array as $key => $value) {
                                    $product_price_temp = $product_price;
                                    $optionsArray[] = array('attribute_id' => (int)$value['product_option_id'], 'attribute_value' => $value['option_value_id']);
                                    if ($value['price_prefix'] == '+') {
                                        $product_price_temp = $product_price_temp + $value['price'];
                                    } else {
                                        $product_price_temp = $product_price_temp - $value['price'];
                                    }


                                    $specialPrice = 0;
                                    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_special` WHERE `product_id` = '" . (int)$product_id . "' ORDER BY priority, price");
                                    $product_specials = array();
                                    if ($query && $query->num_rows) {
                                        $product_specials = $query->rows;
                                    }

                                    foreach ($product_specials as $product_special) {
                                        if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
                                            $specialPrice = $product_special['price'];

                                            break;
                                        }
                                    }

                                    $price = (float)$product_price_temp;

                                    if ($specialPrice == 0) {
                                        $specialPrice = $price;
                                    }

                                    $cedshopee_price_choice = trim($this->config->get(
                                        'cedshopee_price_choice'));

                                    switch ($cedshopee_price_choice) {
                                        case '2':
                                            $fixedIncement = trim($this->config->get('cedshopee_variable_price'));
                                            $price = $price + $fixedIncement;
                                            $specialPrice = $specialPrice + $fixedIncement;
                                            break;

                                        case '3':
                                            $fixedIncement = trim($this->config->get('cedshopee_variable_price'));
                                            $price = $price - $fixedIncement;
                                            $specialPrice = $specialPrice + $fixedIncement;
                                            break;


                                        case '4':
                                            $percentPrice = trim($this->config->get('cedshopee_variable_price'));
                                            $price = (float)($price + (($price / 100) * $percentPrice));
                                            $specialPrice = (float)($specialPrice + (($specialPrice / 100) * $percentPrice));
                                            break;

                                        case '5':

                                            $percentPrice = trim($this->config->get('cedshopee_variable_price'));
                                            $price = (float)($price - (($price / 100) * $percentPrice));
                                            $specialPrice = (float)($specialPrice - (($specialPrice / 100) * $percentPrice));
                                            break;

                                        case '6':
                                            $result = $this->db->query("SELECT `price` FROM `" . DB_PREFIX . "cedshopee_product` where `product_id`='" . $product_id . "'");
                                            if ($result && $result->num_rows && isset($result->row['price']))
                                                $price = (isset($result->row['price']) && $result->row['price'] != 0) ? $result->row['price'] : $price;
                                            $specialPrice = $price;
                                            break;

                                        default:
                                            $price;
                                            $specialPrice;
                                    }

                                    $final_price = $price;
                                    $final_sprice = $specialPrice;
                                    $product_price_array[$sku] = array('price' => $final_price, 'specialPrice' => $final_sprice);
                                    $price = 0;
                                    $specialPrice = 0;
                                }
                            }
                        }
                    } else {
                        $price = $this->getcedshopeePrice($product_id);
                        $product_price_array[$sku] = $price;
                    }
                }
                $timeStamp = (string)$this->getMilliseconds();
                $priceArray = array(
                    'PriceFeed' => array(
                        '@xmlns' => "http://cedshopee.com/",
                        'PriceHeader' => array(
                            'version' => '1.5',
                        ),
                    ),
                );

                $currency = $this->config->get('config_currency');
                if (is_array($product_price_array) && count($product_price_array)) {
                    foreach ($product_price_array as $key => $product_prices) {
                        $priceArray['PriceFeed']['Price'][] = array(

                            'itemIdentifier' => array(
                                'sku' => $key
                            ),
                            'pricingList' => array(
                                'pricing' => array(
                                    'currentPrice' => array(
                                        'value' => array(

                                            '@currency' => $currency,
                                            '@amount' => $product_prices['specialPrice']


                                        )
                                    ),
                                    'currentPriceType' => 'BASE',
                                    'comparisonPrice' => array(
                                        'value' => array(

                                            '@currency' => $currency,
                                            '@amount' => $product_prices['price']

                                        )
                                    ),
                                )
                            )

                        );
                    }
                }
                $price = $this->feedRequest($priceArray, 'price');
            }
        }
    }

    public function getVariantProducts($product_id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cedshopee_product_variations` WHERE `product_id`='" . $product_id . "'");
        if ($query->num_rows) {
            return $query->rows;
        } else {
            return false;
        }
    }

    public function UpdateProductQtyOncedshopee($product_ids = array())
    {
        if (is_numeric($product_ids)) {
            $product_ids = array($product_ids);
        }
        if (count($product_ids) == 0) {
            $cedshopee_map_cat_all = $this->config->get('cedshopee_map_cat_all');

            if (is_array($cedshopee_map_cat_all) && isset($cedshopee_map_cat_all['children_category']) && isset($cedshopee_map_cat_all['parent_wcat']) && $cedshopee_map_cat_all['children_category'] && $cedshopee_map_cat_all['parent_wcat']) {
                $query = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product`");
                if ($query && $query->num_rows) {
                    foreach ($query->rows as $key => $value) {
                        if (isset($value['product_id']) && $value['product_id']) {
                            $ids[] = $value['product_id'];
                        }
                    }
                }
            } else {
                $query = $this->db->query("SELECT `category_id` FROM `" . DB_PREFIX . "cedshopee_category_mapping`");
                $ids = array();
                if ($query && $query->num_rows) {
                    foreach ($query->rows as $key => $value) {
                        if (isset($value['category_id']) && $value['category_id']) {
                            $query = $this->db->query("SELECT `product_id` FROM " . DB_PREFIX . "product_to_category WHERE  `category_id` = '" . (int)$value['category_id'] . "'");
                            if ($query && $query->num_rows) {
                                foreach ($query->rows as $key => $value) {
                                    if (isset($value['product_id']) && $value['product_id']) {
                                        $ids[] = $value['product_id'];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $product_ids = $ids;
        }
        if (is_array($product_ids) && count($product_ids)) {
            $product_idss = array_chunk($product_ids, 9900);
            foreach ($product_idss as $key => $product_ids) {
                $inventoryArray = array();
                if (is_array($product_ids) && count($product_ids)) {
                    $product_qantity_array = array();
                    foreach ($product_ids as $key => $product_id) {
                        $variants = $this->getVariantProducts($product_id);
                        $quantity = 0;
                        $sku = '';
                        $sku = $this->getSku($product_id);
                        $result = $this->db->query("SELECT `quantity` FROM `" . DB_PREFIX . "product` where `product_id` = '" . $product_id . "'");
                        if ($result->num_rows) {
                            $quantity = $result->row['quantity'];
                        }
                        if (is_array($variants) && count($variants)) {

                            foreach ($variants as $key => $value) {
                                $currentPriceArray = array();
                                $sku = $value['merchant_sku'];
                                if (isset($value['option_combination']) && count($value['option_combination']) > 0) {
                                    $combination = json_decode($value['option_combination'], true);
                                    foreach ($combination as $key => $value) {
                                        $result = $this->db->query("SELECT `product_option_id`,`option_value_id`,`quantity`,`price`,`price_prefix` FROM `" . DB_PREFIX . "product_option_value` where `product_id` = '" . $product_id . "' AND `option_id` = '" . $key . "' AND `option_value_id` = '" . $value . "'");

                                        if ($result->num_rows) {
                                            $options_array[] = $result->row;
                                        }
                                    }
                                }

                                $result = $this->db->query("SELECT `quantity` FROM `" . DB_PREFIX . "cedshopee_product` where `product_id` = '" . $product_id . "'");
                                if ($result->num_rows) {
                                    $quantity = $result->row['quantity'];
                                }
                                $optionsArray = array();
                                if (count($options_array) > 0) {

                                    foreach ($options_array as $key => $value) {
                                        $quantity_temp = $quantity;
                                        $optionsArray[$sku] = array('attribute_id' => (int)$value['product_option_id'], 'attribute_value' => $value['option_value_id']);
                                        $product_qantity_array[$sku] = $value['quantity'];
                                    }
                                }
                            }
                        } else {
                            $quantity = $this->getcedshopeeQuantity($product_id);
                            $product_qantity_array[$sku] = $quantity;
                        }
                    }
                    $inventoryArray = array(
                        'InventoryFeed' => array(
                            '@xmlns' => "http://cedshopee.com/",
                            'InventoryHeader' => array(
                                'version' => '1.4',
                            )
                        )
                    );
                    if (is_array($product_qantity_array) && count($product_qantity_array)) {

                        $fulfillmentLagTime = $this->config->get('cedshopee_fulfillmentLagTime');
                        foreach ($product_qantity_array as $key => $product_qantity) {
                            $inventoryArray['InventoryFeed']['inventory'][] = array(
                                'sku' => $key,
                                'quantity' => array(
                                    'unit' => 'EACH',
                                    'amount' => (string)$product_qantity,
                                ),
                                'fulfillmentLagTime' => $fulfillmentLagTime,
                            );
                        }
                    }

                }
                if (count($inventoryArray)) {
                    $inventry = $this->feedRequest($inventoryArray, 'inventry');
                }
            }
        }
    }

    public function getRejectedOrderJson($id)
    {
        $sql = "SELECT `order_data` FROM `" . DB_PREFIX . "cedshopee_order_error` WHERE `id`='" . $id . "'";
        $query = $this->db->query($sql);
        if ($query->num_rows)
            return $query->row['order_data'];
    }

    public function getProfileByProductId($product_id)
    {
        if ($product_id) {
            $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cedshopee_profile` cp LEFT JOIN `" . DB_PREFIX . "cedshopee_profile_products` cpp on (cp.id = cpp.shopee_profile_id) WHERE cpp.product_id='" . $product_id . "'");
            if ($result && $result->num_rows) {
                return $result->row;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getShopeeItem($shopee_item_id) {
        $url = 'item/get';
        $this->log($url);
        $params = array('item_id' => $shopee_item_id);
        $this->log($params);
        $item_data = $this->postRequest($url, $params);
        return $item_data;
    }

    public function fetchReturn($url, $params)
    {
        $response = $this->postRequest($url, $params);
        if(isset($response['returns']) && !empty($response['returns'])){
            return array('success' => true, 'response' => $response['returns']);
        } else if(isset($response['error']) && isset($response['msg'])){
            return array('success' => false, 'message' => $response['msg']);
        } else {
            return array('success' => false, 'message' => 'No Return From shopee.');
        }
    }

    public function combinations($arrays, $i = 0) {
        if (!isset($arrays[$i])) {
            return array();
        }
        if ($i == count($arrays) - 1) {
            return $arrays[$i];
        }
        $tmp = $this->combinations($arrays, $i + 1);

        $result = array();
        foreach ($arrays[$i] as $v) {
            foreach ($tmp as $t) {
                $result[] = is_array($t) ?
                    array_merge(array($v), $t) :
                    array($v, $t);
            }
        }
        return $result;
    }

    public function getProductIDByShopeeItemId($shopee_item_id)
    {
        $response = array();
        if($shopee_item_id)
        {
            $sql = $this->db->query("SELECT `product_id` FROM `". DB_PREFIX ."cedshopee_uploaded_products` WHERE `shopee_item_id` = '". $shopee_item_id ."' ");
            if(isset($sql->row['product_id']) && !empty($sql->row['product_id']))
            {
                $query = $this->db->query("SELECT `price` FROM `". DB_PREFIX ."product` WHERE `product_id` = '". $sql->row['product_id'] ."' ");
                $response = array(
                    'product_id' => $sql->row['product_id'],
                    'price' => $query->row['price']
                );
            }
        }
        return $response;
    }

    public function getVariationByID($product_id)
    {
        $sql = $this->db->query("SELECT * FROM `". DB_PREFIX ."cedshopee_product_variations` WHERE `product_id` = '". $product_id ."' ");
        return $sql->rows;
    }
}