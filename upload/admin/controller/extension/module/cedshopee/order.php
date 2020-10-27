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
    private $error = array();

    public function index()
    {
        $this->load->language('extension/module/cedshopee/order');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->getList();
    }

    public function add()
    {
        $this->load->language('extension/module/cedshopee/order');
        $this->load->model('extension/module/cedshopee/order');
        $this->load->library('cedshopee');
        $this->document->setTitle($this->language->get('heading_title'));
        $cedshopee = Cedshopee::getInstance($this->registry);
        $status = $cedshopee->isEnabled();
        if ($status) {
            $url = 'orders/get';
            $cedshopee->log($url);
            $createdTimeTo = date('Y-m-d h:i:s a');
            $params = array(
                'order_status' => 'READY_TO_SHIP',  
                'create_time_to' => strtotime($createdTimeTo)
                );       
            $order_data = $cedshopee->fetchOrder($url, $params);
//            echo '<pre>'; print_r($order_data); die;
            $cedshopee->log('Order Fetch: data');
            $cedshopee->log(json_encode($order_data));
            if (isset($order_data['success']) && $order_data['success'] == true) 
            {
                if (isset($order_data['message'])) 
                {
                    if(is_array($order_data['message']))
                    {
                        $this->session->data['success'] = implode(", ", $order_data['message']);
                    } else {
                        $this->session->data['success'] = $order_data['message'];
                    }
                } else {
                    $this->session->data['success'] = $this->language->get('text_success');
                }
                
            } else {
                if (isset($order_data['message']))
                {
                    if(is_array($order_data['message']))
                    {
                        $this->error['warning'] = implode(", ", $order_data['message']);
                    } else {
                        $this->error['warning'] = $order_data['message'];
                    }
                } else {
                    $this->error['warning'] = 'No Resposne From Shopee.';
                }
            }
        } else {
            $this->error['warning'] = 'Shopee Module is disabled';
        }
        $this->getList();
    }

    protected function getList()
    {
        if (isset($this->request->get['filter_order_id'])) {
            $filter_order_id = $this->request->get['filter_order_id'];
        } else {
            $filter_order_id = null;
        }

        if (isset($this->request->get['filter_customer'])) {
            $filter_customer = $this->request->get['filter_customer'];
        } else {
            $filter_customer = null;
        }

        if (isset($this->request->get['filter_total'])) {
            $filter_total = $this->request->get['filter_total'];
        } else {
            $filter_total = null;
        }

        if (isset($this->request->get['filter_date_added'])) {
            $filter_date_added = $this->request->get['filter_date_added'];
        } else {
            $filter_date_added = null;
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $filter_date_modified = $this->request->get['filter_date_modified'];
        } else {
            $filter_date_modified = null;
        }

        if (isset($this->request->get['filter_shopee_order_status_id'])) {
            $filter_shopee_order_status_id = $this->request->get['filter_shopee_order_status_id'];
        } else {
            $filter_shopee_order_status_id = null;
        }

        if (isset($this->request->get['filter_shopee_order_id'])) {
            $filter_shopee_order_id = $this->request->get['filter_shopee_order_id'];
        } else {
            $filter_shopee_order_id = null;
        }
        if (isset($this->request->get['filter_order_status_id'])) {
            $filter_order_status_id = $this->request->get['filter_order_status_id'];
        } else {
            $filter_order_status_id = null;
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'o.order_id';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_order_id'])) {
            $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        }

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_total'])) {
            $url .= '&filter_total=' . $this->request->get['filter_total'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
        }

        if (isset($this->request->get['filter_shopee_order_status_id'])) {
            $url .= '&filter_shopee_order_status_id=' . $this->request->get['filter_shopee_order_status_id'];
        }

        if (isset($this->request->get['filter_shopee_order_id'])) {
            $url .= '&filter_shopee_order_id=' . $this->request->get['filter_shopee_order_id'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
        );

        $data['shipping'] = $this->url->link('extension/module/cedshopee/order/shipping', 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['add'] = $this->url->link('extension/module/cedshopee/order/add', 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['multi'] = $this->url->link('extension/module/cedshopee/order/acknowledge', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        $data['rejected'] = $this->url->link('extension/module/cedshopee/order/rejection', 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['orders'] = array();

        $filter_data = array(
            'filter_order_id' => $filter_order_id,
            'filter_customer' => $filter_customer,
            'filter_total' => $filter_total,
            'filter_date_added' => $filter_date_added,
            'filter_date_modified' => $filter_date_modified,
            'filter_shopee_order_id' => $filter_shopee_order_id,
            'filter_shopee_order_status_id' => $filter_shopee_order_status_id,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
        $this->load->model('extension/module/cedshopee/order');
        $order_total = $this->model_extension_module_cedshopee_order->getTotalOrders($filter_data);

        $results = $this->model_extension_module_cedshopee_order->getOrders($filter_data);

        if ($results) {
            foreach ($results as $result) {
                $data['orders'][] = array(
                    'order_id' => $result['order_id'],
                    'shopee_order_id' => $result['shopee_order_id'],
                    'customer' => $result['customer'],
                    'status' => $result['order_status'],
                    'wstatus' => $result['wstatus'],
                    'total' => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
                    'shipping_code' => $result['shipping_code'],
                    'view' => $this->url->link('extension/module/cedshopee/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] . $url, 'SSL'),
                    'ship' => $this->url->link('extension/module/cedshopee/order/ship', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] . $url, 'SSL'),
                    'selected' => isset($this->request->post['selected']) && in_array($result['order_id'], $this->request->post['selected']),
                );
            }
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');
        $data['text_missing'] = $this->language->get('text_missing');

        $data['column_order_id'] = $this->language->get('column_order_id');
        $data['column_shopee_order_id'] = $this->language->get('column_shopee_order_id');
        $data['column_customer'] = $this->language->get('column_customer');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_wstatus'] = $this->language->get('column_wstatus');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_date_modified'] = $this->language->get('column_date_modified');
        $data['column_action'] = $this->language->get('column_action');

        $data['entry_return_id'] = $this->language->get('entry_return_id');
        $data['entry_order_id'] = $this->language->get('entry_order_id');
        $data['entry_customer'] = $this->language->get('entry_customer');
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_total'] = $this->language->get('entry_total');
        $data['entry_date_added'] = $this->language->get('entry_date_added');
        $data['entry_date_modified'] = $this->language->get('entry_date_modified');

        $data['button_invoice_print'] = $this->language->get('button_invoice_print');
        $data['button_shipping_print'] = $this->language->get('button_shipping_print');
        $data['button_insert'] = $this->language->get('button_insert');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_rejection'] = $this->language->get('button_rejection');
        $data['button_shipment'] = $this->language->get('button_ship');
        $data['button_filter'] = $this->language->get('button_filter');
        $data['button_view'] = $this->language->get('button_view');

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['error_module'])) {
            $data['error_warning'] = $this->error['error_module'];
        } else if (isset($this->error['warning'])) {
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

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }

        $url = '';

        if (isset($this->request->get['filter_order_id'])) {
            $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        }

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_total'])) {
            $url .= '&filter_total=' . $this->request->get['filter_total'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
        }

        if (isset($this->request->get['filter_shopee_order_status_id'])) {
            $url .= '&filter_shopee_order_status_id=' . $this->request->get['filter_shopee_order_status_id'];
        }

        if (isset($this->request->get['filter_shopee_order_id'])) {
            $url .= '&filter_shopee_order_id=' . $this->request->get['filter_shopee_order_id'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_order'] = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . '&sort=o.order_id' . $url, 'SSL');
        $data['sort_customer'] = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . '&sort=customer' . $url, 'SSL');
        $data['sort_status'] = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, 'SSL');
        $data['sort_total'] = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . '&sort=o.total' . $url, 'SSL');
        $data['sort_date_added'] = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_added' . $url, 'SSL');
        $data['sort_date_modified'] = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_modified' . $url, 'SSL');
        $data['sort_wstatus'] = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . '&sort=wo.status' . $url, 'SSL');
        $data['sort_shopee_order_id'] = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . '&sort=wo.shopee_order_id' . $url, 'SSL');

        $url = '';

        if (isset($this->request->get['filter_order_id'])) {
            $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        }

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_order_status'])) {
            $url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
        }

        if (isset($this->request->get['filter_total'])) {
            $url .= '&filter_total=' . $this->request->get['filter_total'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        $pagination = new Pagination();
        $pagination->total = $order_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

        $data['filter_order_id'] = $filter_order_id;
        $data['filter_customer'] = $filter_customer;
        $data['filter_total'] = $filter_total;
        $data['filter_date_added'] = $filter_date_added;
        $data['filter_date_modified'] = $filter_date_modified;
        $data['filter_shopee_order_status_id'] = $filter_shopee_order_status_id;
        $data['filter_shopee_order_id'] = $filter_shopee_order_id;
        $data['filter_order_status_id'] = $filter_order_status_id;

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/cedshopee/order_list', $data));
    }

    public function info()
    {
        $this->load->model('sale/order');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $order_info = $this->model_sale_order->getOrder($order_id);

        if ($order_info) {

            $this->load->language('extension/module/cedshopee/order');

            $this->document->setTitle($this->language->get('heading_title'));

            $data['heading_title'] = $this->language->get('heading_title');

            $data['text_order_id'] = $this->language->get('text_order_id');
            $data['text_invoice_no'] = $this->language->get('text_invoice_no');
            $data['text_invoice_date'] = $this->language->get('text_invoice_date');
            $data['text_store_name'] = $this->language->get('text_store_name');
            $data['text_store_url'] = $this->language->get('text_store_url');
            $data['text_customer'] = $this->language->get('text_customer');
            $data['text_customer_group'] = $this->language->get('text_customer_group');
            $data['text_email'] = $this->language->get('text_email');
            $data['text_telephone'] = $this->language->get('text_telephone');
            $data['text_total'] = $this->language->get('text_total');
            $data['text_order_status'] = $this->language->get('text_order_status');
            $data['text_date_added'] = $this->language->get('text_date_added');
            $data['text_date_modified'] = $this->language->get('text_date_modified');

            $data['text_firstname'] = $this->language->get('text_firstname');
            $data['text_lastname'] = $this->language->get('text_lastname');
            $data['text_company'] = $this->language->get('text_company');
            $data['text_address_1'] = $this->language->get('text_address_1');
            $data['text_address_2'] = $this->language->get('text_address_2');
            $data['text_city'] = $this->language->get('text_city');
            $data['text_postcode'] = $this->language->get('text_postcode');
            $data['text_zone'] = $this->language->get('text_zone');
            $data['text_zone_code'] = $this->language->get('text_zone_code');
            $data['text_country'] = $this->language->get('text_country');
            $data['text_shipping_method'] = $this->language->get('text_shipping_method');
            $data['text_payment_method'] = $this->language->get('text_payment_method');
            $data['text_country_code'] = $this->language->get('text_country_code');

            $data['column_product'] = $this->language->get('column_product');
            $data['column_model'] = $this->language->get('column_model');
            $data['column_quantity'] = $this->language->get('column_quantity');
            $data['column_price'] = $this->language->get('column_price');
            $data['column_total'] = $this->language->get('column_total');

            $data['entry_order_status'] = $this->language->get('entry_order_status');
            $data['entry_notify'] = $this->language->get('entry_notify');
            $data['entry_comment'] = $this->language->get('entry_comment');

            $data['button_invoice_print'] = $this->language->get('button_invoice_print');
            $data['button_shipping_print'] = $this->language->get('button_shipping_print');
            $data['button_acknowledge'] = $this->language->get('button_acknowledge');
            $data['button_rejection'] = $this->language->get('button_rejection');
            $data['button_back'] = $this->language->get('button_back');
            $data['button_shipment'] = $this->language->get('button_shipment');
            $data['button_generate'] = $this->language->get('button_generate');

            $data['tab_order'] = $this->language->get('tab_order');
            $data['tab_payment'] = $this->language->get('tab_payment');
            $data['tab_shipping'] = $this->language->get('tab_shipping');
            $data['tab_product'] = $this->language->get('tab_product');
            $data['tab_wshipment'] = $this->language->get('tab_wshipment');
            $data['tab_worder'] = $this->language->get('tab_worder');
            $data['tab_cancel'] = $this->language->get('tab_cancel');

            $data['user_token'] = $this->session->data['user_token'];

            $url = '';

            if (isset($this->request->get['filter_order_id'])) {
                $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
            }

            if (isset($this->request->get['filter_customer'])) {
                $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_order_status'])) {
                $url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
            }

            if (isset($this->request->get['filter_total'])) {
                $url .= '&filter_total=' . $this->request->get['filter_total'];
            }

            if (isset($this->request->get['filter_date_added'])) {
                $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
            }

            if (isset($this->request->get['filter_date_modified'])) {
                $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
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
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
            );

            $data['acknowledge'] = $this->url->link('extension/module/cedshopee/order/acknowledge', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'], 'SSL');

            $data['rejection'] = $this->url->link('extension/module/cedshopee/order/rejection', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'], 'SSL');

            $data['shipment'] = $this->url->link('extension/module/cedshopee/order/ship', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'], 'SSL');
            $data['rejection'] = $this->url->link('extension/module/cedshopee/rejection', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'] . $url, 'SSL');
            $data['back'] = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

            $data['order_id'] = $this->request->get['order_id'];

            if ($order_info['invoice_no']) {
                $data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
            } else {
                $data['invoice_no'] = '';
            }

            $data['store_name'] = $order_info['store_name'];
            $data['store_url'] = $order_info['store_url'];
            $data['firstname'] = $order_info['firstname'];
            $data['lastname'] = $order_info['lastname'];

            if ($order_info['customer_id']) {
                $data['customer'] = $this->url->link('sale/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $order_info['customer_id'], 'SSL');
            } else {
                $data['customer'] = '';
            }

            $data['customer_group'] = '';
            $data['email'] = $order_info['email'];
            $data['telephone'] = $order_info['telephone'];
            $data['shipping_method'] = $order_info['shipping_method'];
            $data['payment_method'] = $order_info['payment_method'];
            $data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']);

            $this->load->model('localisation/order_status');

            $order_status_info = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

            if ($order_status_info) {
                $data['order_status'] = $order_status_info['name'];
            } else {
                $data['order_status'] = '';
            }

            $data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));
            $data['date_modified'] = date($this->language->get('date_format_short'), strtotime($order_info['date_modified']));

            $data['payment_firstname'] = $order_info['payment_firstname'];
            $data['payment_lastname'] = $order_info['payment_lastname'];
            $data['payment_company'] = $order_info['payment_company'];
            $data['payment_address_1'] = $order_info['payment_address_1'];
            $data['payment_address_2'] = $order_info['payment_address_2'];
            $data['payment_city'] = $order_info['payment_city'];
            $data['payment_postcode'] = $order_info['payment_postcode'];
            $data['payment_zone'] = $order_info['payment_zone'];
            $data['payment_zone_code'] = $order_info['payment_zone_code'];
            $data['payment_country'] = $order_info['payment_country'];
            $data['shipping_firstname'] = $order_info['shipping_firstname'];
            $data['shipping_lastname'] = $order_info['shipping_lastname'];
            $data['shipping_company'] = $order_info['shipping_company'];
            $data['shipping_address_1'] = $order_info['shipping_address_1'];
            $data['shipping_address_2'] = $order_info['shipping_address_2'];
            $data['shipping_city'] = $order_info['shipping_city'];
            $data['shipping_postcode'] = $order_info['shipping_postcode'];
            $data['shipping_zone'] = $order_info['shipping_zone'];
            $data['shipping_zone_code'] = $order_info['shipping_zone_code'];
            $data['shipping_country'] = $order_info['shipping_country'];

            $data['text_trackingNumber'] = $this->language->get('entry_trackingNumber');
            $this->load->model('extension/module/cedshopee/order');
            $data['cancel_reasons'] = $this->model_extension_module_cedshopee_order->getCancelReasons();
            $data['products'] = array();

            $products = $this->getOrderProducts($this->request->get['order_id']);

            foreach ($products as $product) {
                $option_data = array();

                $options = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);
// echo '<pre>'; print_r($options); die;
                foreach ($options as $option) {
                    if ($option['type'] != 'file') {
                        $option_data[] = array(
                            'name' => $option['name'],
                            'value' => $option['value'],
                            'type' => $option['type']
                        );
                    } else {
                        $option_data[] = array(
                            'name' => $option['name'],
                            'value' => utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.')),
                            'type' => $option['type'],
                            'href' => $this->url->link('sale/order/download', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $this->request->get['order_id'] . '&order_option_id=' . $option['order_option_id'], 'SSL')
                        );
                    }
                }

                $data['products'][] = array(
                    'order_product_id' => $product['order_product_id'],
                    'product_id' => $product['product_id'],
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'option' => $option_data,
                    'quantity' => $product['quantity'],
                    'price' => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                    'total' => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
                    'href' => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id'], 'SSL')
                );
            }

            $this->load->model('extension/module/cedshopee/order');
            $data['cedshopee_shipment'] = array();
            $this->load->library('cedshopee');
            $cedshopee = Cedshopee::getInstance($this->registry);
            $cedshopee_shipment = $cedshopee->getShipmentById($this->request->get['order_id']);
            if ($cedshopee_shipment)
                $data['cedshopee_shipment'] = $cedshopee_shipment;

            $totals = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);

            foreach ($totals as $total) {
                $data['totals'][] = array(
                    'title' => $total['title'],
                    'text' => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']),
                );
            }
//            echo '<pre>'; print_r($data); die;
            $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

            $data['order_status_id'] = $order_info['order_status_id'];

            $shopee_order_info = $this->model_extension_module_cedshopee_order->getOrder($order_id);
// echo '<pre>'; print_r($shopee_order_info); die;
            if (isset($this->request->post['trackingNumber'])) {
                $data['trackingNumber'] = $this->request->post['trackingNumber'];
            } elseif(isset($shopee_order_info['tracking_no']) && !empty($shopee_order_info['tracking_no'])) {
                $data['trackingNumber'] = $shopee_order_info['tracking_no'];
            }else {
                $data['trackingNumber'] = '';
            }

            // Additional Tabs
            $data['is_cedshopee_order'] = false;
            $data['order_items'] = array();

            if (isset($shopee_order_info) && is_array($shopee_order_info) && !empty($shopee_order_info)) 
            {
                $results = $shopee_order_info;
                $data['is_cedshopee_order'] = true;
                
                $rejectedskus = "";
                $order_details = array();
                if ($results && isset($results['ordersn'])) {
                    $order_details['ordersn'] = $results['ordersn'];
                }
                if ($results && isset($results['days_to_ship'])) {
                    $order_details['days_to_ship'] = $results['days_to_ship'];
                }
                if ($results && isset($results['escrow_amount'])) {
                    $order_details['escrow_amount'] = $results['escrow_amount'];
                }
                if ($results && isset($results['tracking_no'])) {
                    $order_details['tracking_no'] = $results['tracking_no'];
                }
                if ($results && isset($results['order_status'])) {
                    $order_details['order_status'] = $results['order_status'];
                }
                if (isset($order_info['date_added'])) {
                    $order_details['orderDate'] = date('d-m-Y H:i:s A', strtotime($order_info['date_added']));
                }

                $data['shippingInfo'] = array();
                if ($results && isset($results['recipient_address']['name'])) {
                    $data['shippingInfo']['name'] = $results['recipient_address']['name'];
                }
                if ($results && isset($results['recipient_address']['phone'])) {

                    $data['shippingInfo']['phone'] = $results['recipient_address']['phone'];
                }
                if ($results && isset($results['shippingInfo']['estimatedDeliveryDate'])) {
                    $date = new DateTime();
                    $value = $date->setTimestamp($results['shippingInfo']['estimatedDeliveryDate']);
                    $value = $date->format('y-m-d H:i:s');
                    $data['shippingInfo']['estimatedDeliveryDate'] = $value;
                }

                if ($results && isset($results['shippingInfo']['estimatedShipDate'])) {
                    $date = new DateTime();
                    $value = $date->setTimestamp($results['shippingInfo']['estimatedShipDate']);
                    $value = $date->format('y-m-d H:i:s');
                    $data['shippingInfo']['estimatedShipDate'] = $value;
                }

                if ($results && isset($results['shipping_carrier'])) {
                    $data['shippingInfo']['methodCode'] = $results['shipping_carrier'];
                }

                if ($results && isset($results['recipient_address'])) {
                    $data['shippingInfo']['zipcode'] = $results['recipient_address']['zipcode'];
                }

                if ($results && isset($results['recipient_address']['city'])) {
                    $data['shippingInfo']['city'] = $results['recipient_address']['city'];
                }

                if ($results && isset($results['recipient_address']['state'])) {
                    $data['shippingInfo']['state'] = $results['recipient_address']['state'];
                }

                if ($results && isset($results['recipient_address']['town'])) {
                    $data['shippingInfo']['town'] = $results['recipient_address']['town'];
                }

                if ($results && isset($results['recipient_address']['district'])) {
                    $data['shippingInfo']['district'] = $results['recipient_address']['district'];
                }

                if ($results && isset($results['recipient_address']['country'])) {
                    $data['shippingInfo']['country'] = $results['recipient_address']['country'];
                }

                $data['wproducts'] = array();
                if ($results && isset($results['items'])) {
                    $data['wproducts'] = $results['items'];
                }
                
                $data['orders'] = $order_details;
            }
            // echo '<pre>'; print_r($shopee_order_info); die;
            $data['header']  = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/cedshopee/order_info', $data));
        } else {
            $this->language->load('error/not_found');

            $this->document->setTitle($this->language->get('heading_title'));

            $data['heading_title'] = $this->language->get('heading_title');

            $data['text_not_found'] = $this->language->get('text_not_found');

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );

            $data['header']  = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
        
            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    public function getOrderProducts($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/order')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function fail()
    {
        $this->load->language('extension/module/cedshopee/failorder');
        $this->document->setTitle($this->language->get('heading_title'));
        
        if (isset($this->request->get['filter_shopee_order_id'])) {
            $filter_shopee_order_id = $this->request->get['filter_shopee_order_id'];
        } else {
            $filter_shopee_order_id = null;
        }

        if (isset($this->request->get['filter_merchant_sku'])) {
            $filter_merchant_sku = $this->request->get['filter_merchant_sku'];
        } else {
            $filter_merchant_sku = null;
        }

        if (isset($this->request->get['filter_reason'])) {
            $filter_reason = $this->request->get['filter_reason'];
        } else {
            $filter_reason = null;
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'shopee_order_id';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_shopee_order_id'])) {
            $url .= '&filter_shopee_order_id=' . $this->request->get['filter_shopee_order_id'];
        }

        if (isset($this->request->get['filter_merchant_sku'])) {
            $url .= '&filter_merchant_sku=' . urlencode(html_entity_decode($this->request->get['filter_merchant_sku'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_reason'])) {
            $url .= '&filter_reason=' . $this->request->get['filter_reason'];
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
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/cedshopee/order/rejected', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
        );

        $filter_data = array(
            'filter_order_id' => $filter_shopee_order_id,
            'filter_customer' => $filter_merchant_sku,
            'filter_order_status' => $filter_reason,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
        $this->load->model('extension/module/cedshopee/order');
        $results = $this->model_extension_module_cedshopee_order->getrejectedOrders($filter_data);
        $order_total = $this->model_extension_module_cedshopee_order->getRejectedTotals($filter_data);
        
        if ($results) {
            foreach ($results as $result) {
                $data['orders'][] = array(
                    'id' => $result['shopee_order_id'],
                    'merchantsku' => $result['merchant_sku'],
                    'reason' => $result['reason'],

                    'view' => $this->url->link('extension/module/cedshopee/order/rejectview', 'user_token=' . $this->session->data['user_token'] . '&id=' . $result['id'] . $url, 'SSL')

                );
            }
        }


        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['column_shopee_order_id'] = $this->language->get('column_shopee_order_id');
        $data['column_merchant_sku'] = $this->language->get('column_merchant_sku');
        $data['column_reason'] = $this->language->get('column_reason');
        $data['column_action'] = $this->language->get('column_action');

        $data['entry_shopee_order_id'] = $this->language->get('entry_shopee_order_id');
        $data['entry_merchant_sku'] = $this->language->get('entry_merchant_sku');
        $data['entry_reason'] = $this->language->get('entry_reason');

        $data['button_edit'] = $this->language->get('viewrejected');
        $data['button_cancel'] = $this->language->get('button_cancel');

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

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }

        $url = '';

        if (isset($this->request->get['filter_shopee_order_id'])) {
            $url .= '&filter_shopee_order_id=' . $this->request->get['filter_shopee_order_id'];
        }

        if (isset($this->request->get['filter_merchant_sku'])) {
            $url .= '&filter_merchant_sku=' . urlencode(html_entity_decode($this->request->get['filter_merchant_sku'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_reason'])) {
            $url .= '&filter_reason=' . $this->request->get['filter_reason'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_shopee_order_id'] = $this->url->link('extension/module/cedshopee/order/fail', 'user_token=' . $this->session->data['user_token'] . '&sort=shopee_order_id' . $url, 'SSL');
        $data['sort_merchant_sku'] = $this->url->link('extension/module/cedshopee/order/fail', 'user_token=' . $this->session->data['user_token'] . '&sort=merchant_sku' . $url, 'SSL');
        $data['sort_reason'] = $this->url->link('extension/module/cedshopee/order/fail', 'user_token=' . $this->session->data['user_token'] . '&sort=reason' . $url, 'SSL');

        $data['cancel'] = $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

        $data['filter_shopee_order_id'] = $filter_shopee_order_id;
        $data['filter_customermerchant_sku'] = $filter_merchant_sku;
        $data['filter_reason'] = $filter_reason;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/cedshopee/rejectedorder', $data));
    }

    public function rejectview()
    {
        $errorlist = '';
        if (isset($this->request->get['id'])) {
            $this->load->language('extension/module/cedshopee/orderrejectview');
            $this->document->setTitle($this->language->get('heading_title'));

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/cedshopee/order/rejectview', 'user_token=' . $this->session->data['user_token'] . '&id=' . $this->request->get['id'], 'SSL')
            );
            if (isset($this->error['warning'])) {
                $data['error_warning'] = $this->error['warning'];
            } else {
                $data['error_warning'] = '';
            }

            $id = 0;

            if (isset($this->request->get['id'])) {
                $id = $this->request->get['id'];
            }

            $this->load->model('extension/module/cedshopee/order');
            $results = $this->model_extension_module_cedshopee_order->getRejectedOrder($id);
            if (count($results)) {
                $order_details = array();

                if ($results && isset($results['ordersn'])) {
                    $order_details['ordersn'] = $results['ordersn'];
                }
                if ($results && isset($results['tracking_no'])) {
                    $order_details['tracking_no'] = $results['tracking_no'];
                }
                if ($results && isset($results['payment_method'])) {
                    $order_details['payment_method'] = $results['payment_method'];
                }
                if ($results && isset($results['order_status'])) {
                    $order_details['order_status'] = $results['order_status'];
                }
                if ($results && isset($results['shipping_carrier'])) {
                    $order_details['shipping_carrier'] = $results['shipping_carrier'];
                }
                if ($results && isset($results['shipping_amount'])) {
                    $order_details['shipping_amount'] = $results['shipping_amount'];
                }
                if ($results && isset($results['days_to_ship'])) {
                    $order_details['days_to_ship'] = $results['days_to_ship'];
                }
                if ($results && isset($results['create_time'])) {
                    $date = new DateTime();
                    $value = $date->format('y-m-d H:i:s');
                    $order_details['create_time'] = $value;
                }

                $data['shippingInfo'] = array();
                if ($results && isset($results['recipient_address']['name'])) {

                    $data['shippingInfo']['name'] = $results['recipient_address']['name'];
                }
                if ($results && isset($results['recipient_address']['phone'])) {

                    $data['shippingInfo']['phone'] = $results['recipient_address']['phone'];
                }

                if ($results && isset($results['recipient_address']['full_address'])) {
                    $data['shippingInfo']['full_address'] = $results['recipient_address']['full_address'];
                }

                if ($results && isset($results['recipient_address']['city'])) {
                    $data['shippingInfo']['city'] = $results['recipient_address']['city'];
                }
                if ($results && isset($results['recipient_address']['town'])) {
                    $data['shippingInfo']['town'] = $results['recipient_address']['town'];
                }
                if ($results && isset($results['recipient_address']['district'])) {
                    $data['shippingInfo']['district'] = $results['recipient_address']['district'];
                }
                if ($results && isset($results['recipient_address']['state'])) {
                    $data['shippingInfo']['state'] = $results['recipient_address']['state'];
                }
                if ($results && isset($results['recipient_address']['country'])) {
                    $data['shippingInfo']['country'] = $results['recipient_address']['country'];
                }
                if ($results && isset($results['recipient_address']['zipcode'])) {
                    $data['shippingInfo']['zipcode'] = $results['recipient_address']['zipcode'];
                }

                $data['products'] = array();
                if ($results && isset($results['items'])) {
                    $data['products'] = $results['items'];
                }

                $data['orders'] = $order_details;

            } else {
                $errorlist = "Rejected Order Can not be Viewed.";
                $this->response->redirect($this->url->link('extension/module/cedshopee/order/fail', 'user_token=' . $this->session->data['user_token'] . '&errorlist=' . (string)$errorlist, 'SSL'));
            }

            $data['heading_title'] = $this->language->get('heading_title');

            $data['text_list'] = $this->language->get('text_list');
            $data['text_no_results'] = $this->language->get('text_no_results');
            $data['text_confirm'] = $this->language->get('text_confirm');
            $data['text_missing'] = $this->language->get('text_missing');

            $data['column_order_id'] = $this->language->get('column_order_id');
            $data['column_customer'] = $this->language->get('column_customer');
            $data['column_status'] = $this->language->get('column_status');
            $data['column_total'] = $this->language->get('column_total');
            $data['column_product'] = $this->language->get('column_product');
            $data['column_date_added'] = $this->language->get('column_date_added');
            $data['column_date_modified'] = $this->language->get('column_date_modified');
            $data['column_action'] = $this->language->get('column_action');

            $data['entry_return_id'] = $this->language->get('entry_return_id');
            $data['entry_order_id'] = $this->language->get('entry_order_id');
            $data['entry_customer'] = $this->language->get('entry_customer');
            $data['entry_order_status'] = $this->language->get('entry_order_status');
            $data['entry_total'] = $this->language->get('entry_total');
            $data['entry_date_added'] = $this->language->get('entry_date_added');
            $data['entry_date_modified'] = $this->language->get('entry_date_modified');

            $data['button_invoice_print'] = $this->language->get('button_invoice_print');
            $data['button_shipping_print'] = $this->language->get('button_shipping_print');
            $data['button_insert'] = $this->language->get('button_insert');
            $data['button_edit'] = $this->language->get('viewrejected');
            $data['button_delete'] = $this->language->get('button_delete');
            $data['button_filter'] = $this->language->get('button_filter');
            $data['button_view'] = $this->language->get('button_view');
            $data['Items'] = $this->language->get('Items');
            $data['order_id'] = $this->language->get('order_id');
            $data['customer_reference_order_id'] = $this->language->get('customer_reference_order_id');
            $data['fulfillment_node'] = $this->language->get('fulfillment_node');
            $data['order_placed_date'] = $this->language->get('order_placed_date');
            $data['order_transmission_date'] = $this->language->get('order_transmission_date');
            $data['phone_number'] = $this->language->get('phone_number');
            $data['recipient'] = $this->language->get('recipient');
            $data['recipient_phone_number'] = $this->language->get('recipient_phone_number');
            $data['ship_to_address1'] = $this->language->get('ship_to_address1');
            $data['ship_to_address2'] = $this->language->get('ship_to_address2');
            $data['ship_to_city'] = $this->language->get('ship_to_city');
            $data['ship_to_state'] = $this->language->get('ship_to_state');
            $data['ship_to_zip_code'] = $this->language->get('ship_to_zip_code');
            $data['ship_to_state'] = $this->language->get('ship_to_state');
            $data['rejected_merchant_sku'] = $this->language->get('rejected_merchant_sku');
            $data['button_Back'] = $this->language->get('button_Back');
            $data['button_cancel'] = $this->language->get('button_cancel');
            $data['back'] = $this->url->link('extension/module/cedshopee/order/fail', 'user_token=' . $this->session->data['user_token'], 'SSL');
            $data['cancel'] = $this->url->link('extension/module/cedshopee/order/cancel', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $id, 'SSL');
            $data['user_token'] = $this->session->data['user_token'];

            $data['header']  = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
        
            $this->response->setOutput($this->load->view('extension/module/cedshopee/rejectedorderview', $data));
        } else {
            $this->error['warning'] = 'No Error Order Id Found.';
            $this->rejected();
        }
    }

    public function acknowledge()
    {
        $this->load->language('extension/module/cedshopee/order');
        $post_data = $this->request->post;
        if (isset($post_data['selected']) && count($post_data['selected'])) {
            $this->load->library('cedshopee');
            $cedshopee_lib = Cedshopee::getInstance($this->registry);
            $status = $cedshopee_lib->isEnabled();
            if ($status) {
                foreach ($post_data['selected'] as $key => $value) {
                    $response = $cedshopee_lib->acknowledgeOrder($value);
                    if (isset($response['order'])) {
                        $this->session->data['success'] = 'Acknowledged Successfully.';
                    }
                }
            }
        } else {
            $this->error['warning'] = 'Please Select Order to acknowledge';
        }
        $this->getList();
    }

    public function rejection()
    {
        $this->load->language('extension/module/cedshopee/order');
        $post_data = $this->request->post;
        $url = 'v3/orders';
        if (isset($post_data['selected']) && count($post_data['selected'])) {
            $this->load->library('cedshopee');
            $cedshopee_lib = Cedshopee::getInstance($this->registry);
            $status = $cedshopee_lib->isEnabled();
            if ($status) {
                foreach ($post_data['selected'] as $key => $value) {
                    $orderLine = '1';
                    $cedshopee_lib->cancelOrder($value, $orderLine, $url);
                }
            }
        } else {
            $this->error['warning'] = 'Please Select Order to reject';
        }
        $this->getList();
    }

    public function ship()
    {
        $this->load->language('extension/module/cedshopee/order');
        $this->load->model('extension/module/cedshopee/order');
            if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
                $response = $this->model_extension_module_cedshopee_order->saveShipping($this->request->post);
                if (isset($response['success']) && $response['success']) {
                    $json['success'] = $response['response'];
                } else {
                    $json['error'] = $response['response'];
                }
            }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function cancel()
    {
        $this->load->language('extension/module/cedshopee/order');
        $this->load->model('extension/module/cedshopee/order');
        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $this->load->library('cedshopee');
            $cedshopee_lib = Cedshopee::getInstance($this->registry);
            $status = $cedshopee_lib->isEnabled();
            if ($status) {
                $response = $cedshopee_lib->cancelOrder($this->request->post);
                if (isset($response['success']) && $response['success']) {
                    $json['success'] = $response['response'];
                } else {
                    $json['error'] = $response['message'];
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}