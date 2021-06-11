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

class ControllerExtensionModuleCedshopee extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/cedshopee');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('cedshopee', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] , true));
        }

        $data['url_live'] = 'https://partner.shopeemobile.com/api/v1/';
        $data['url_sandbox'] = 'https://partner.uat.shopeemobile.com/api/v1/';

        $data['heading_title'] = $this->language->get('heading_title');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_validate'] = $this->language->get('button_validate');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['tab_general'] = $this->language->get('tab_general');
        $data['tab_product'] = $this->language->get('tab_product');
        $data['tab_order'] = $this->language->get('tab_order');
        $data['tab_cron'] = $this->language->get('tab_cron');

        // Text Tab
        $text_tabs = array('edit', 'enabled', 'disabled', 'sandbox', 'live');
        foreach ($text_tabs as $text_tab)
        {
            $data['text_' . $text_tab] = $this->language->get('text_' . $text_tab);
        }

        // General Tab
        $general_tabs = array('status', 'debug');
        foreach ($general_tabs as $general_tab)
        {
            $data['entry_' . $general_tab] = $this->language->get('entry_' . $general_tab);
        }

        // API Tab
        $api_tabs = array('api_mode', 'api_url', 'partner_id', 'shop_id', 'shop_signature', 'redirect_url', 'validate');
        foreach ($api_tabs as $api_tab)
        {
            $data['entry_' . $api_tab] = $this->language->get('entry_' . $api_tab);
        }

        // Product Tab
        $product_tabs = array('auto_sync', 'update_all', 'update_price', 'update_inventry', 'inventry_choice', 'price_choice', 'variable_price');
        foreach ($product_tabs as $product_tab)
        {
            $data['entry_' . $product_tab] = $this->language->get('entry_' . $product_tab);
        }

        // Order Tab
        $order_tabs = array('order_email', 'order_shipping_label', 'auto_order', 'order_import', 'order_cancel', 'order_ship', 'order_carrier', 'order_payment');
        foreach ($order_tabs as $order_tab)
        {
            $data['entry_' . $order_tab] = $this->language->get('entry_' . $order_tab);
        }

        $shipping_tabs = array('seller_name', 'seller_add', 'seller_contact');
        foreach ($shipping_tabs as $shipping_tab)
        {
            $data['entry_' . $shipping_tab] = $this->language->get('entry_' . $shipping_tab);
        }

        // Help Tab
        $help_tabs = array('debug', 'order_email', 'validate', 'update_all', 'update_price', 'update_inventry', 'inventry_choice', 'price_choice', 'variable_price');
        foreach ($help_tabs as $help_tab)
        {
            $data['help_' . $help_tab] = $this->language->get('help_' . $help_tab);
        }

        // Error list
        $error_lists = array('warning', 'api_mode', 'api_url', 'shop_id', 'shop_signature', 'partner_id');
        foreach ($error_lists as $error_list)
        {
            if($error_list == 'warning')
            {
                if (isset($this->error['warning'])) {
                    $data['error_' . $error_list] = $this->error['warning'];
                } else {
                    $data['error_' . $error_list] = '';
                }
            } else {
                if (isset($this->error['error_' . $error_list])) {
                    $data['error_' . $error_list] = $this->error['error_' . $error_list];
                } else {
                    $data['error_' . $error_list] = '';
                }
            }
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/cedshopee', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['user_token']= $this->session->data['user_token'];
        $data['action'] = $this->url->link('extension/module/cedshopee', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);


        // General Settings
        foreach ($general_tabs as $general_tab)
        {
            if (isset($this->request->post['cedshopee_' . $general_tab])) {
                $data['cedshopee_' . $general_tab] = $this->request->post['cedshopee_' . $general_tab];
            } else if ($this->config->get('cedshopee_' . $general_tab)) {
                $data['cedshopee_' . $general_tab] = $this->config->get('cedshopee_' . $general_tab);
            } else {
                $data['cedshopee_' . $general_tab] = '';
            }
        }

        // API Settings
        foreach ($api_tabs as $api_tab)
        {
            if (isset($this->request->post['cedshopee_' . $api_tab])) {
                $data['cedshopee_' . $api_tab] = $this->request->post['cedshopee_' . $api_tab];
            } else if ($this->config->get('cedshopee_' . $api_tab)) {
                $data['cedshopee_' . $api_tab] = $this->config->get('cedshopee_' . $api_tab);
            } else {
                if($api_tab == 'api_url')
                {
                    $data['cedshopee_' . $api_tab] = 'https://partner.uat.shopeemobile.com/api/v1/';
                }
                if($api_tab == 'redirect_url')
                {
                    $data['cedshopee_' . $api_tab] = HTTPS_CATALOG . 'index.php?route=extension/module/cedshopee/success/index';
                } else {
                    $data['cedshopee_' . $api_tab] = '';
                }
            }
        }

        // Product Settings
        foreach ($product_tabs as $product_tab)
        {
            if (isset($this->request->post['cedshopee_' . $product_tab])) {
                $data['cedshopee_' . $product_tab] = $this->request->post['cedshopee_' . $product_tab];
            } else if ($this->config->get('cedshopee_' . $product_tab)) {
                $data['cedshopee_' . $product_tab] = $this->config->get('cedshopee_' . $product_tab);
            } else {
                $data['cedshopee_' . $product_tab] = '';
            }
        }

        // Order Settings
        foreach ($order_tabs as $order_tab)
        {
            if (isset($this->request->post['cedshopee_' . $order_tab])) {
                $data['cedshopee_' . $order_tab] = $this->request->post['cedshopee_' . $order_tab];
            } else if ($this->config->get('cedshopee_' . $order_tab)) {
                $data['cedshopee_' . $order_tab] = $this->config->get('cedshopee_' . $order_tab);
            } else {
                $data['cedshopee_' . $order_tab] = '';
            }
        }

        // Shipping Setting
        foreach ($shipping_tabs as $shipping_tab)
        {
            if (isset($this->request->post['cedshopee_' . $shipping_tab])) {
                $data['cedshopee_' . $shipping_tab] = $this->request->post['cedshopee_' . $shipping_tab];
            } else if ($this->config->get('cedshopee_' . $shipping_tab)) {
                $data['cedshopee_' . $shipping_tab] = $this->config->get('cedshopee_' . $shipping_tab);
            } else {
                $data['cedshopee_' . $shipping_tab] = '';
            }
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['order_carriers'] = $this->getExtensions('shipping');

        $data['order_pay'] = $this->getExtensions('payment');

        $data['price_choices'] = array(
            '1'=>'Default Price',
            '2'=>'Increase By Fix Amount',
            '3'=>'Decrease By Fix Amount',
            '4'=>'Increase By Fix Percent',
            '5'=>'Decrease By Fix Percent'
        );

        $cron_array = array(
            'Product Upload'=> HTTP_CATALOG.'index.php?route=extension/module/cedshopee/product/uploadProduct',
            'Sync Quantity'=> HTTP_CATALOG.'index.php?route=extension/module/cedshopee/product/updateStock',
            'Sync Price'=> HTTP_CATALOG.'index.php?route=extension/module/cedshopee/product/updatePrice',
            'Fetch Order'=> HTTP_CATALOG.'index.php?route=extension/module/cedshopee/order/index',
            'Fetch Return'=> HTTP_CATALOG.'index.php?route=extension/module/cedshopee/return/index'
        );
        $data['crons'] = $cron_array;

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/module/cedshopee', $data));
    }

    public function getExtensions($type) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = '" . $this->db->escape($type) . "'");

        return $query->rows;
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['cedshopee_api_url']) < 3) || (utf8_strlen($this->request->post['cedshopee_api_url']) > 64)) {
            $this->error['error_api_url'] = $this->language->get('error_api_url');
        }

        if (!$this->request->post['cedshopee_partner_id']) {
            $this->error['error_partner_id'] = $this->language->get('error_partner_id');
        }

        if (!$this->request->post['cedshopee_shop_signature']) {
            $this->error['error_shop_signature'] = $this->language->get('error_shop_signature');
        }

        if (!$this->request->post['cedshopee_shop_id']) {
            $this->error['error_shop_id'] = $this->language->get('error_shop_id');
        }

        if (!$this->request->post['cedshopee_api_mode']) {
            $this->error['error_api_mode'] = $this->language->get('error_api_mode');
        }

        return !$this->error;
    }

    public function install()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent('add_shopee_menu', 'admin/view/common/column_left/before', 'extension/module/cedshopee/column_left/eventMenu');
        $this->model_setting_event->addEvent('add_cedshopee_product', 'admin/model/catalog/product/addProduct/after', 'extension/module/cedshopee/product/eventadd');
        $this->model_setting_event->addEvent('update_cedshopee_product', 'admin/model/catalog/product/editProduct/after', 'extension/module/cedshopee/product/eventedit');
        $this->model_setting_event->addEvent('delete_cedshopee_product', 'admin/model/catalog/product/deleteProduct/after', 'extension/module/cedshopee/product/eventdelete');
        $this->model_setting_event->addEvent('update_cedshopee_inventory_price', 'admin/model/catalog/product/editProduct/after', 'extension/module/cedshopee/product/updateCedshopeeInventory');



        $this->load->model('user/user_group');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/category');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/category');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/attribute');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/attribute');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/product');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/product');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/order');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/order');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/profile');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/profile');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/option');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/option');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/logistics');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/logistics');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/return');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/return');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/discount');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/discount');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/shipping_label');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/shipping_label');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/cedshopee/column_left');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/cedshopee/column_left');

        $this->load->library('cedshopee');

        $cedshopee = Cedshopee::getInstance($this->registry);

        if (!$cedshopee->isCedshopeeInstalled())
            $cedshopee->installCedshopee();
    }

    public function uninstall() {
        $this->db->query("DELETE  FROM `".DB_PREFIX."setting` WHERE `key` LIKE '%cedshopee%'");
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('add_shopee_menu');
        $this->model_setting_event->deleteEventByCode('add_cedshopee_product');
        $this->model_setting_event->deleteEventByCode('update_cedshopee_product');
        $this->model_setting_event->deleteEventByCode('delete_cedshopee_product');
    }

    public function generateToken()
    {
        $json = array('success' => false, 'message' => '');
        $post = $this->request->post;
        if(!empty($post['shop_signature']))
        {
            $merge_key = $post['shop_signature'] . $post['redirect_url'];
            $token = hash('sha256', $merge_key);
            $json = array('success' => true, 'message' => $token);
        }
        $this->response->setOutput(json_encode($json));
    }

}
