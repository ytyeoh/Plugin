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
class ModelExtensionModuleCedshopeeCategory extends Model {

    public function getCategories($data = array()) {

        $sql = "SELECT * FROM " . DB_PREFIX . "cedshopee_category WHERE category_id > 0  AND has_children = 0";

        if (!empty($data['filter_category_name'])) {
            $sql .= " AND category_name LIKE '%" . strip_tags(html_entity_decode($this->db->escape($data['filter_category_name']), ENT_QUOTES, 'UTF-8')) . "%'";
        }
        if (!empty($data['filter_name'])) {
            $sql .= " AND category_name LIKE '%" . strip_tags(html_entity_decode($this->db->escape($data['filter_name']), ENT_QUOTES, 'UTF-8')) . "%'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND category_id = '" . $this->db->escape(html_entity_decode($data['filter_category_id'])) . "%'";
        }

        if (!empty($data['filter_parent_id'])) {
            $sql .= " AND parent_id LIKE '" . $this->db->escape($data['filter_parent_id']) . "%'";
        }

        $sql .= " GROUP BY category_id";

        $sort_data = array(
            'category_name',
            'category_id',
            'parent_id'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY category_id";
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
    public function deleteCategory($category_id) {
        $this->db->query("DELETE FROM `".DB_PREFIX."cedshopee_category` where id = '".$category_id."'");
    }

    public function getTotalCategories($data = array())
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "cedshopee_category WHERE category_id > 0  AND has_children = 0 ";

        if (!empty($data['filter_category_name'])) {
            $sql .= " AND category_name LIKE '" . strip_tags(html_entity_decode($this->db->escape($data['filter_category_name']), ENT_QUOTES, 'UTF-8')). "%'";
        }
        if (!empty($data['filter_name'])) {
            $sql .= " AND category_name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND category_id = '" . $this->db->escape($data['filter_category_id']) . "%'";
        }

        if (!empty($data['filter_parent_id'])) {
            $sql .= " AND parent_id = '" . $this->db->escape($data['parent_id']) . "%'";
        }

        $sql .= " GROUP BY category_id ORDER BY category_name";

        $query = $this->db->query($sql);

        return $query->num_rows;
    }

    public function getAttributes($category_id) {
        if ($category_id) {
            $results = $this->db->query("SELECT * FROM `".DB_PREFIX."cedshopee_attribute` WHERE category_id='".$category_id."'");
            if ($results && $results->num_rows) {
                return $results->rows;
            } else {
                $this->load->model('extension/module/cedshopee/logistics');
                $this->load->library('cedshopee');
                $cedshopee = Cedshopee::getInstance($this->registry);
                $response = $cedshopee->postRequest('item/attributes/get', array('category_id' => (int)$category_id));
                if (!isset($response['error']) && isset($response['attributes'])) {
                    $this->model_extension_module_cedshopee_category->addAttributes($category_id, $response['attributes']);
                    return $response['attributes'];
                } else {
                    return array();
                }
            }
        } else {
            return array();
        }
    }

    public function getBrands($catId, $attribute_id, $brandName)
    {
        $brandArray = array();
        $results = $this->db->query("SELECT * FROM `".DB_PREFIX."cedshopee_attribute` WHERE category_id = '" . $catId . "' AND attribute_id = '" . $attribute_id . "' ");
        
        if($results->num_rows)
        {
            foreach ($results->rows as $res) {
                $brandArray = array_merge_recursive($brandArray, json_decode($res['options'],true));
            }
            $input = preg_quote($brandName, '~');
            $result = preg_grep('~' . $input . '~', $brandArray);
        }
        // echo '<pre>'; print_r($result); die;
        if(empty($result))
            return $brandArray;
        return $result;
    }

    public function getStoreOptions($catId, $attribute_id, $brandName)
    {
        $option_value_data = array();
        $option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE ov.option_id = '" . (int)$attribute_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND ovd.name LIKE '%".$brandName."%' ORDER BY ov.sort_order ASC");
                
        foreach ($option_value_query->rows as $option_value) {
            $option_value_data[] = array(
                'option_value_id' => $option_value['option_value_id'],
                'name'            => $option_value['name'],
                'image'           => $option_value['image'],
                'sort_order'      => $option_value['sort_order']
            );
        }
        return $option_value_data;
    }
    
    public function getAttributeOptions($category_id) {
        if ($category_id) {
            $results = $this->db->query("SELECT `attribute_id`,`attribute_name`,`options`, `is_mandatory` FROM `".DB_PREFIX."cedshopee_attribute` where category_id='".$category_id."'");
            if ($results && $results->num_rows) {
                return $results->rows;
            }
        } else {
            return array();
        }
    }
    public function addShopeeCategories($data) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cedshopee_category");
        foreach ($data as $category) {
            if(isset($category['category_id']) && $category['category_id']) {
                $query = $this->db->query("SELECT `category_name` FROM " . DB_PREFIX . "cedshopee_category WHERE category_id = '" . (int)$category['parent_id'] . "'");
                if($query->num_rows) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "cedshopee_category SET category_id = '" . (int)$category['category_id'] . "', parent_id = '" . (int)$category['parent_id'] . "', has_children = '" . (int)$category['has_children'] . "', category_name = '" . strip_tags(html_entity_decode($this->db->escape($query->row['category_name'].' > '.$category['category_name']), ENT_QUOTES, 'UTF-8')) ."'");
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "cedshopee_category SET category_id = '" . (int)$category['category_id'] . "', parent_id = '" . (int)$category['parent_id'] . "', has_children = '" . (int)$category['has_children'] . "', category_name = '" . strip_tags(html_entity_decode($this->db->escape($category['category_name']), ENT_QUOTES, 'UTF-8')). "'");
                }
            }
        }
    }
    public function addAttributes($category_id, $data) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cedshopee_attribute WHERE category_id = '" . (int)$category_id . "'");
        foreach ($data as $attribute) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "cedshopee_attribute SET attribute_id = '" . (int)$attribute['attribute_id'] . "', category_id = '" . (int)$category_id . "', is_mandatory = '" . (int)$attribute['is_mandatory'] . "', attribute_name = '" . strip_tags(html_entity_decode($this->db->escape($attribute['attribute_name']), ENT_QUOTES, 'UTF-8')) . "', attribute_type = '" . $this->db->escape($attribute['attribute_type']) . "', input_type = '" . $this->db->escape($attribute['input_type']) . "', options = '" . $this->db->escape(json_encode($attribute['options'])) . "'");
        }
    }
    public function getCategory() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cedshopee_category ORDER BY category_name");

        return $query->rows;
    }
}