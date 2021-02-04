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
    private $error = array();

    public function index()
    {
        $this->load->language('extension/module/cedshopee/product');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/product');

        $this->getList();
    }

    protected function getList()
    {
        if (isset($this->request->get['profile_filter'])) {
            $profile_filter = $this->request->get['profile_filter'];
        } else {
            $profile_filter = null;
        }

        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = null;
        }

        if (isset($this->request->get['filter_model'])) {
            $filter_model = $this->request->get['filter_model'];
        } else {
            $filter_model = null;
        }

        if (isset($this->request->get['filter_price'])) {
            $filter_price = $this->request->get['filter_price'];
        } else {
            $filter_price = null;
        }

        if (isset($this->request->get['filter_quantity'])) {
            $filter_quantity = $this->request->get['filter_quantity'];
        } else {
            $filter_quantity = null;
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = null;
        }

        if (isset($this->request->get['filter_shopee_status'])) {
            $filter_shopee_status = $this->request->get['filter_shopee_status'];
        } else {
            $filter_shopee_status = null;
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'pd.name';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_price'])) {
            $url .= '&filter_price=' . $this->request->get['filter_price'];
        }

        if (isset($this->request->get['filter_quantity'])) {
            $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }
        if (isset($this->request->get['profile_filter'])) {
            $url .= '&profile_filter=' . $this->request->get['profile_filter'];
        }

        if (isset($this->request->get['filter_shopee_status'])) {
            $url .= '&filter_shopee_status=' . $this->request->get['filter_shopee_status'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['insert'] = $this->url->link('extension/module/cedshopee/product/insert', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        $data['stock_update'] = $this->url->link('extension/module/cedshopee/product/updateStock', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        $data['price_update'] = $this->url->link('extension/module/cedshopee/product/updatePrice', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        $data['delete'] = $this->url->link('extension/module/cedshopee/product/retire', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

        $data['products'] = array();

        $filter_data = array(
            'filter_name' => $filter_name,
            'filter_model' => $filter_model,
            'filter_price' => $filter_price,
            'filter_quantity' => $filter_quantity,
            'filter_status' => $filter_status,
            'filter_shopee_status' => $filter_shopee_status,
            'profile_filter' => $profile_filter,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $this->load->model('tool/image');
        $this->load->model('extension/module/cedshopee/product');

        $product_total = $this->model_extension_module_cedshopee_product->getTotalProducts($filter_data);

        $results = $this->model_extension_module_cedshopee_product->getProducts($filter_data);
        // echo '<pre>'; print_r($results); die;
        foreach ($results as $result) {
            $action = array();

            $action[] = array(
                'text' => $this->language->get('text_edit'),
                'href' => $this->url->link('extension/module/cedshopee/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, 'SSL')
            );

            $data['view'] = $this->language->get('text_view');

            $action[] = array(
                'text' => $this->language->get('text_view'),
                'href' => 'false'
            );

            if ($result['image'] && file_exists(DIR_IMAGE . $result['image'])) {
                $image = $this->model_tool_image->resize($result['image'], 40, 40);
            } else {
                $image = $this->model_tool_image->resize('no_image.jpg', 40, 40);
            }

            $special = false;
            $product_specials = $this->model_extension_module_cedshopee_product->getProductSpecials($result['product_id']);

            foreach ($product_specials as $product_special) {
                if (($product_special['date_start'] == '0000-00-00' || $product_special['date_start'] < date('Y-m-d')) && ($product_special['date_end'] == '0000-00-00' || $product_special['date_end'] > date('Y-m-d'))) {
                    $special = $product_special['price'];

                    break;
                }
            }

            $cedshopee_inventry_choice  = $this->config->get('cedshopee_inventry_choice');
            $cedshopee_price_choice     = $this->config->get('cedshopee_price_choice');

            $data['products'][] = array(
                'product_id' => $result['product_id'],
                'name' => $result['name'],
                'model' => $result['model'],
                'price' => ($cedshopee_price_choice == 6) ? $result['wprice'] : $result['price'],
                'special' => $special,
                'image' => $image,
                'profile_name' => $result['title'],
                'shopee_id' => isset($result['shopee_item_id']) ? $result['shopee_item_id'] : 0,
                'quantity' => ($cedshopee_inventry_choice == 2) ? $result['wquantity'] : $result['quantity'],
                'status' => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'cedshopee_status' => ($result['shopee_status']) ? $result['shopee_status'] : 'Not Uploaded',
                'selected' => isset($this->request->post['selected']) && in_array($result['product_id'], $this->request->post['selected']),
                'action' => $action
            );
        }
        $data['profiles'] = $this->model_extension_module_cedshopee_product->getAllProfiles();

        $data['all_url'] = $this->url->link('extension/module/cedshopee/product/uploadall', 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['fetchstatus'] = $this->url->link('extension/module/cedshopee/product/fetchstatus', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $data['filter_shopee_status'] = $filter_shopee_status;
        $data['profile_filter'] = $profile_filter;
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_list'] = $this->language->get('text_list');
        $data['button_fetchstatus'] = $this->language->get('button_fetchstatus');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_image_manager'] = $this->language->get('text_image_manager');

        $data['column_image'] = $this->language->get('column_image');
        $data['column_name'] = $this->language->get('column_name');
        $data['column_model'] = $this->language->get('column_model');
        $data['column_price'] = $this->language->get('column_price');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_wstatus'] = $this->language->get('column_wstatus');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_stock_update'] = $this->language->get('button_stock_update');
        $data['button_price_update'] = $this->language->get('button_price_update');
        $data['button_insert'] = $this->language->get('button_insert');
        $data['button_all'] = $this->language->get('button_all');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_filter'] = $this->language->get('button_filter');
        $data['column_profile_name'] = $this->language->get('column_profile_name');

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_price'])) {
            $url .= '&filter_price=' . $this->request->get['filter_price'];
        }

        if (isset($this->request->get['filter_quantity'])) {
            $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }
        if (isset($this->request->get['profile_filter'])) {
            $url .= '&profile_filter=' . $this->request->get['profile_filter'];
        }

        if (isset($this->request->get['filter_shopee_status'])) {
            $url .= '&filter_shopee_status=' . $this->request->get['filter_shopee_status'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        $data['cedshopee_status'] = $this->model_extension_module_cedshopee_product->getShopeeStatuses();

        $data['sort_name'] = $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, 'SSL');
        $data['sort_model'] = $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url, 'SSL');
        $data['sort_price'] = $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url, 'SSL');
        $data['sort_quantity'] = $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity' . $url, 'SSL');
        $data['sort_status'] = $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, 'SSL');
        $data['sort_wstatus'] = $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.cedshopee_status' . $url, 'SSL');
        $data['sort_order'] = $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, 'SSL');

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_price'])) {
            $url .= '&filter_price=' . $this->request->get['filter_price'];
        }

        if (isset($this->request->get['filter_quantity'])) {
            $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }
        
        if (isset($this->request->get['profile_filter'])) {
            $url .= '&profile_filter=' . $this->request->get['profile_filter'];
        }

        if (isset($this->request->get['filter_shopee_status'])) {
            $url .= '&filter_shopee_status=' . $this->request->get['filter_shopee_status'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $product_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

        $data['filter_name'] = $filter_name;
        $data['filter_model'] = $filter_model;
        $data['filter_price'] = $filter_price;
        $data['filter_quantity'] = $filter_quantity;
        $data['filter_status'] = $filter_status;
        $data['filter_shopee_status'] = $filter_shopee_status;
        $data['profile_filter'] = $profile_filter;
        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/cedshopee/product_list', $data));
    }

    public function autocomplete()
    {
        $json = array();

        if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_model'])) {
            $this->load->model('extension/module/cedshopee/product');
            $this->load->model('extension/module/cedshopee/option');

            if (isset($this->request->get['filter_name'])) {
                $filter_name = $this->request->get['filter_name'];
            } else {
                $filter_name = '';
            }

            if (isset($this->request->get['filter_model'])) {
                $filter_model = $this->request->get['filter_model'];
            } else {
                $filter_model = '';
            }

            if (isset($this->request->get['limit'])) {
                $limit = $this->request->get['limit'];
            } else {
                $limit = 5;
            }

            $filter_data = array(
                'filter_name' => $filter_name,
                'filter_model' => $filter_model,
                'start' => 0,
                'limit' => $limit
            );

            $results = $this->model_extension_module_cedshopee_product->getProducts($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'product_id' => $result['product_id'],
                    'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'model' => $result['model'],
                    'price' => $result['price']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function items()
    {
        $json = array();

        if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_model'])) {
            $this->load->model('extension/module/cedshopee/product');
            $this->load->model('extension/module/cedshopee/option');

            if (isset($this->request->get['filter_name'])) {
                $filter_name = $this->request->get['filter_name'];
            } else {
                $filter_name = '';
            }

            if (isset($this->request->get['filter_model'])) {
                $filter_model = $this->request->get['filter_model'];
            } else {
                $filter_model = '';
            }

            if (isset($this->request->get['limit'])) {
                $limit = $this->request->get['limit'];
            } else {
                $limit = 5;
            }

            $filter_data = array(
                'filter_name' => $filter_name,
                'filter_model' => $filter_model,
                'start' => 0,
                'limit' => $limit
            );

            $results = $this->model_extension_module_cedshopee_product->getItems($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'product_id' => $result['product_id'],
                    'shopee_item_id' => $result['shopee_item_id'],
                    'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'model' => $result['model'],
                    'price' => $result['price']
                );
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function edit()
    {
        $this->load->language('extension/module/cedshopee/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/product');
        $this->getForm();
    }


    protected function getForm()
    {
        $data['heading_title']            = $this->language->get('heading_title');
        $data['text_form']                = $this->language->get('text_edit');
        $data['text_none']                = $this->language->get('text_none');
        $data['text_default']             = $this->language->get('text_default');
        $data['text_enabled']             = $this->language->get('text_enabled');
        $data['text_disabled']            = $this->language->get('text_disabled');
        $data['entry_quantity']           = $this->language->get('entry_quantity');
        $data['entry_price']              = $this->language->get('entry_price');
        $data['button_save']              = $this->language->get('button_save');
        $data['button_cancel']            = $this->language->get('button_cancel');
        $data['tab_product']              = $this->language->get('tab_product');
        $data['tab_cedshopee']            = $this->language->get('tab_cedshopee');
        $data['tab_shipping']             = $this->language->get('tab_shipping');
        $data['tab_attribute']            = $this->language->get('tab_attribute');
        $data['tab_required_attribute']   = $this->language->get('tab_required_attribute');
        $data['tab_variant']              = $this->language->get('tab_variant');
        $data['entry_logistics'] = $this->language->get('entry_logistics');
        $data['entry_wholesale_unit_price'] = $this->language->get('entry_wholesale_unit_price');
        $data['entry_wholesale_max'] = $this->language->get('entry_wholesale_max');
        $data['entry_wholesale_min'] = $this->language->get('entry_wholesale_min');
        $data['entry_is_free'] = $this->language->get('entry_is_free');
        $data['entry_manufacturer'] = $this->language->get('entry_manufacturer');
        $data['entry_category'] = $this->language->get('entry_category');
        $data['entry_store_attribute'] = $this->language->get('entry_store_attribute');
        $data['entry_shopee_attribute'] = $this->language->get('entry_shopee_attribute');
        $data['entry_language'] = $this->language->get('entry_language');
        $data['entry_shopee_category'] = $this->language->get('entry_shopee_category');
        $data['entry_shipping_fee'] = $this->language->get('entry_shipping_fee');

        if (isset($this->error['error_price'])) {
            $data['error_price'] = $this->error['error_price'];
        } else {
            $data['error_price'] = '';
        }

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        $this->load->model('extension/module/cedshopee/logistics');
        $data['logistics_list'] = $this->model_extension_module_cedshopee_logistics->getLogistics();

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $this->load->model('extension/module/cedshopee/product');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['shopee_edit_action'] = $this->url->link('extension/module/cedshopee/product/shopeeEdit',"user_token={$this->session->data['user_token']}".$url, true);

        if (isset($this->request->get['product_id'])) {
            $data['action'] = $this->url->link('extension/module/cedshopee/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $this->request->get['product_id'] . $url, true);
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['cancel'] = $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $product_info = array();

        if (isset($this->request->get['product_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $product_info = $this->model_extension_module_cedshopee_product->getProduct($this->request->get['product_id']);
        }

        if (isset($this->request->post['logistics'])) {
            $data['logistics'] = $this->request->post['logistics'];
        } elseif (isset($product_info['logistics']) && !empty($product_info['logistics'])) {
            $data['logistics'] = json_decode($product_info['logistics'], true);
        } else {
            $data['logistics'] = '';
        }

        if (isset($this->request->post['wholesale'])) {
            $data['wholesale'] = $this->request->post['wholesale'];
        } elseif (isset($product_info['wholesale']) && !empty($product_info['wholesale'])) {
            $data['wholesale'] = json_decode($product_info['wholesale'], true);
        } else {
            $data['wholesale'] = '';
        }

        $data['product_id'] = $this->request->get['product_id'];

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/cedshopee/product_form', $data));
    }

    public function shopeeEdit()
    {
        //die('etste');
        $this->language->load('extension/module/cedshopee/product');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/product');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_extension_module_cedshopee_product->addProduct($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getForm();
    }


    protected function validateForm()
    {
        $this->session->data['form_data'] = $this->request->post;
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/product')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }
        return !$this->error;
    }

    public function retire()
    {
        $this->load->language('extension/module/cedshopee/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/cedshopee/product');
        if (isset($this->request->post) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $product_id) {
                $shopee_item_id = $this->model_extension_module_cedshopee_product->getShopeeItemId($product_id);
                if ($shopee_item_id) {
                    $this->load->library('cedshopee');
                    $cedshopee = Cedshopee::getInstance($this->registry);
                    $requestSent = $cedshopee->postRequest('item/delete', array('item_id'=> (int)$shopee_item_id));
                    //print_r($requestSent);die;
                    if (isset($requestSent['item_id'])) {
                        $requestSent['message'] = $requestSent['msg'];
                        $this->db->query("UPDATE `".DB_PREFIX."cedshopee_uploaded_products` SET shopee_item_id='', shopee_status='Deleted' where product_id='".$product_id."'");
                        $this->session->data['success'] = $this->language->get('text_success_retire');
                    } else {
                        $this->error['warning'] = $requestSent['msg'];
                    }
                } else {
                    $this->error['warning'] = 'Product Delete failed Sku not Found.';
                }
            }
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_price'])) {
            $url .= '&filter_price=' . $this->request->get['filter_price'];
        }

        if (isset($this->request->get['filter_quantity'])) {
            $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_shopee_status'])) {
            $url .= '&filter_shopee_status=' . $this->request->get['filter_shopee_status'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        $this->getList();
    }

    protected function validateDelete()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/product')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!isset($this->request->post['selected']) || (isset($this->request->post['selected']) && (count($this->request->post['selected']) == 0))) {
            $this->error['warning'] = $this->language->get('error_selected');
        }

        return !$this->error;
    }

    public function massupload()
    {
        $json = array();
        $params = $this->request->post;
        if (isset($params['selected']) && count($params['selected'])) {
            if (is_array($params['selected']) && count($params['selected'])) {

                $this->load->library('cedshopee');
                $cedshopee = Cedshopee::getInstance($this->registry);

                $status = $cedshopee->uploadProducts($params['selected']);
                if (isset($status['success']) && !empty($status['success'])) {
                    $json['success'] = true;
                    $json['message'] = $status['success'];
                } else {
                    $json['success'] = false;
                    $json['message'] = $status['error'];
                }
            }
        } else {
            $message = '';
            $json['success'] = false;
            $json['message'] = $message;
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function uploadall()
    {
        $this->load->language('extension/module/cedshopee/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/cedshopee/product');
        $productIds = $this->model_extension_module_cedshopee_product->getAllShopeeProductIds();
        if ($productIds && count($productIds)) {
            $total_product = count($productIds);
            $array_chunk_count = ceil($total_product / 10);
            $productIds = array_chunk($productIds, $array_chunk_count);
            $data['product_ids'] = json_encode($productIds);

            $data['heading_title'] = $this->language->get('heading_title');

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );
            $data['user_token'] = $this->session->data['user_token'];

            $data['cancel'] = $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'], true);


            $data['header']  = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            $this->response->setOutput($this->load->view('extension/module/cedshopee/uploadallstatus', $data));
        } else {
            $this->error['warning'] = 'No Category Mapped in profile or profile does not have any product yet';
            $this->getList();
        }
    }

    public function uploadallProcess()
    {
        $this->load->library('cedshopee');
        $productIds = $this->request->post;
        $json = array();
        if (!empty($productIds) && isset($productIds['selected']) && !empty($productIds['selected'])) {
            $cedshopee = Cedshopee::getInstance($this->registry);
            $status = $cedshopee->uploadProducts($productIds['selected']);

            if (isset($status['success']) && !empty($status['success'])) {
                $json['success'] = true;
                $json['message'] = $status['success'];
            } else {
                $json['success'] = false;
                $json['message'] = $status['error'];
            }
        } else {
            $json['success'] = false;
            $json['message'] = 'No Profile Create or not product in Profile yet.';
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewDetails()
    {
        $json = array();
        if (isset($this->request->post['product_id']) && $this->request->post['product_id']) {
            $this->load->model('extension/module/cedshopee/product');
            $shopee_item_id = $this->model_extension_module_cedshopee_product->getShopeeItemId($this->request->post['product_id']);
            if($shopee_item_id) {
                $this->load->library('cedshopee');
                $cedshopee = Cedshopee::getInstance($this->registry);
                $response = $cedshopee->getShopeeItem((int)$shopee_item_id);

                if (isset($response['item'])) {
                    $response = $response['item'];
                    $json['success'] = true;
                    $json['message'] = $response;

                } else if (isset($response['error']) && isset($response['msg'])) {
                    $json['success'] = false;
                    $json['message'] = $response['msg'];
                } else {
                    $json['success'] = false;
                    $json['message'] = 'Item Not Found On Shopee.';
                }
            } else {
                $json['success'] = false;
                $json['message'] = 'Item Not Found On Shopee.';
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }

    public function updateStock()
    {
        $this->load->language('extension/module/cedshopee/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/cedshopee/product');

        $post_data = $this->request->post;
        if (is_array($post_data) && isset($post_data['selected']) && count($post_data['selected'])) {
            $product_ids = $post_data['selected'];
            $updated = 0;
            $fail = 0;
            if (is_array($product_ids) && count($product_ids))
            {
                $final_response = array();
                foreach ($product_ids as $product_id)
                {
                    $result = $this->model_extension_module_cedshopee_product->updateInvenetry($product_id, array());

                    if(isset($result['item'])){
                        $updated++;
                        $final_response['success'][] = $result['item'];
                    } else if(isset($result['error']))
                    {
                        $fail++;
                        $final_response['error'][] = $result['error'];
                    }
                }
            }
            if ($updated) {
                if($fail)
                    $this->error['warning'] = implode("<br/>",  $final_response['error']); //$updated . ' Product() Updated and '.$fail . 'are failed to update ';
                else
                    $this->session->data['success'] = implode("<br/>",  $final_response['success']); //$updated . ' Product(s) Updated';
            } else if($fail)
            {
                $this->error['warning'] = implode("<br/>",  $final_response['error']);
            } else {
                $this->error['warning'] = 'Unable to update data.';
            }
        }
        $this->getList();
    }

    public function updatePrice()
    {
        $this->load->language('extension/module/cedshopee/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/cedshopee/product');

        $post_data = $this->request->post;
        if (is_array($post_data) && isset($post_data['selected']) && count($post_data['selected'])) {
            $product_ids = $post_data['selected'];
            $updated = 0;
            $fail = 0;
            if (is_array($product_ids) && count($product_ids)) {
                $final_response = array();
                foreach ($product_ids as $product_id)
                {
                    $result =  $this->model_extension_module_cedshopee_product->updatePrice($product_id, array());
                    if(isset($result['item']))
                    {
                        $updated++;
                        $final_response['success'][] = $result['item'];
                    } else if(isset($result['error']))
                    {
                        $fail++;
                        $final_response['error'][] = $result['error'];
                    }
                }
            }
            if ($updated) {
                if($fail)
                    $this->error['warning'] = implode("<br/>",  $final_response['error']); //$updated . ' Product() Updated and '.$fail . 'are failed to update ';
                else
                    $this->session->data['success'] = implode("<br/>",  $final_response['success']); //$updated . ' Product(s) Updated';
            } else if($fail)
            {
                $this->error['warning'] = implode("<br/>",  $final_response['error']);
            } else {
                $this->error['warning'] = 'Unable to update price.';
            }
        }
        $this->getList();
    }

    public function updatestatus()
    {
        $this->load->library('cedshopee');
        $cedshopee = Cedshopee::getInstance($this->registry);

        $pagination_offset = isset($this->request->post['pagination_offset']) ? $this->request->post['pagination_offset'] : 0;
        $pagination_entries_per_page = isset($this->request->post['pagination_entries_per_page']) ? $this->request->post['pagination_entries_per_page'] : 100;
        $response = $cedshopee->postRequest('items/get', array('pagination_offset' => (int)$pagination_offset, 'pagination_entries_per_page' => (int)$pagination_entries_per_page));

        if (!isset($response['error']) && isset($response['items']) && !empty($response['items'])) {
            foreach ($response['items'] as $items) {
                $sql = "UPDATE`" . DB_PREFIX . "cedshopee_uploaded_products` SET shopee_status='" . $items['status'] . "' WHERE `shopee_item_id`='" . $items['item_id'] . "'";
                $this->db->query($sql);
            }
            if(isset($response['more']) && $response['more']) {
                $this->response->setOutput(json_encode(array('success' => true, 'pagination_offset' => (int)$pagination_entries_per_page, 'pagination_entries_per_page' =>(int) $pagination_entries_per_page )));
            } else {
                $this->response->setOutput(json_encode(array('success' => true, 'message' => 'Status Updated Successfully.')));
            }
        } else {
            if (isset($response['msg']))
                $this->response->setOutput(json_encode(array('success' => false, 'message' => $response['msg'])));
            else
                $this->response->setOutput(json_encode(array('success' => false, 'message' => ' No Response Found in store.')));
        }
    }

    public function eventadd($product_id)
    {
        if(isset($product_data['0']) && !empty($product_data['0']))
        {
            $product_id = $product_data['0'];
            $productData = $product_data['1'];
            
            $this->load->model('extension/module/cedshopee/profile');
            $result =  $this->model_extension_module_cedshopee_profile->addNewProduct($product_id, $productData);
        }
    }
    
    public function eventedit($eventUrl, $product_data)
    {
        if(isset($product_data['0']) && !empty($product_data['0']))
        {
            $product_id = $product_data['0'];
            $this->load->library('cedshopee');
            $cedshopee = Cedshopee::getInstance($this->registry);
            $cedshopee->uploadProducts(array($product_id));
        }
    }
    
    public function eventdelete($product_id)
    {
        if(isset($product_id) && !empty($product_id))
        {
            $this->load->model('extension/module/cedshopee/product');
            $shopee_item_id = $this->model_extension_module_cedshopee_product->getShopeeItemId($product_id);
            if ($shopee_item_id) 
            {
                $this->load->library('cedshopee');
                $cedshopee = Cedshopee::getInstance($this->registry);
                $result = $cedshopee->postRequest('item/delete', array('item_id'=> (int)$shopee_item_id));
                
                if (isset($result['item_id'])) 
                {
                    $this->db->query("DELETE FROM `". DB_PREFIX ."cedshopee_uploaded_products` WHERE `product_id` = '". $product_id ."' ");
                    $this->db->query("DELETE FROM `". DB_PREFIX ."cedshopee_profile_products` WHERE `product_id` = '". $product_id ."' ");
                    $this->db->query("DELETE FROM `". DB_PREFIX ."cedshopee_products` WHERE `product_id` = '". $product_id ."' ");
                    $this->db->query("DELETE FROM `". DB_PREFIX ."cedshopee_product_variations` WHERE `product_id` = '". $product_id ."' ");
                }
            }
        }
    }

    public function updateCedshopeeInventory(){
        $product_id = $this->request->get['product_id'];
        $this->load->model('extension/module/cedshopee/product');

        $this->load->library('cedshopee');
        $cedshopee = Cedshopee::getInstance($this->registry);
        $cedshopee->updateInventory($product_id, array());

        $this->model_extension_module_cedshopee_product->updatePrice($product_id, array());

    }

}