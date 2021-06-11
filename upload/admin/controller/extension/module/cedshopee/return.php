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
    private $error = array();

    public function index()
    {
        $this->load->language('extension/module/cedshopee/return');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->getList();
    }

    public function add()
    {
        $this->load->language('extension/module/cedshopee/return');
        $this->load->model('extension/module/cedshopee/return');
        $this->load->library('cedshopee');
        $this->document->setTitle($this->language->get('heading_title'));
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
                $this->session->data['success'] = $this->language->get('text_success');
            } else if (isset($order_data['message'])) {
                $this->error['error'] = $order_data['message'];
            } else {
                $this->error['error'] = 'No Resposne From Shopee.';
            }
        } else {
            $this->error['error'] = $this->language->get('error_module');
        }
        $this->getList();
    }

    public function view()
    {
        $this->load->language('extension/module/cedshopee/return');
        $this->load->model('extension/module/cedshopee/return');
        $data['heading_title'] = $this->language->get('heading_title');
        $this->document->setTitle($this->language->get('heading_title'));
        $data['user_token'] = $this->session->data['user_token'];
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => "Refunds",
            'href' => $this->url->link('extension/module/cedshopee/return/add', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );


        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['button_dispute'] = $this->language->get('button_dispute');

        $data['return_data'] = array();

        $return_data = $this->model_extension_module_cedshopee_return->getReturn($this->request->get['return_id']);

        if (isset($return_data['return_data']))
            $data['return_data'] = json_decode($return_data['return_data'], true);

        $data['images'] = array();
        if(isset($data['return_data']['images']) && !empty($data['return_data']['images'])) {
            $data['images'] = $data['return_data']['images'];
            unset($data['return_data']['images']);
        }

        $data['user'] = array();
        if(isset($data['return_data']['user']) && !empty($data['return_data']['user'])) {
            $data['user'] = $data['return_data']['user'];
            unset($data['return_data']['user']);
        }

        $data['item'] = array();
        if(isset($data['return_data']['item']) && !empty($data['return_data']['item'])) {
            $data['item'] = $data['return_data']['item'];
            unset($data['return_data']['item']);
        }

        $data['returnsn'] =0;
        if(isset($return_data['returnsn']) && $return_data['returnsn']) {
            $data['returnsn'] = $return_data['returnsn'];
        }

        $data['cancel'] = $this->url->link('extension/module/cedshopee/return', 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['confirm'] = $this->url->link('extension/module/cedshopee/return/confirm', 'user_token=' . $this->session->data['user_token'].'&returnsn='.$data['returnsn'].'&return_id='.$this->request->get['return_id'], 'SSL');
        $data['dispute'] = $this->url->link('extension/module/cedshopee/return/dispute', 'user_token=' . $this->session->data['user_token'].'&returnsn='.$data['returnsn'].'&return_id='.$this->request->get['return_id'], 'SSL');

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

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/cedshopee/return_view', $data));

    }

    protected function getList()
    {
        if (isset($this->request->get['filter_returnsn'])) {
            $filter_returnsn = $this->request->get['filter_returnsn'];
        } else {
            $filter_returnsn = null;
        }

        if (isset($this->request->get['filter_ordersn'])) {
            $filter_ordersn = $this->request->get['filter_ordersn'];
        } else {
            $filter_ordersn = null;
        }

        if (isset($this->request->get['filter_reason'])) {
            $filter_reason = $this->request->get['filter_reason'];
        } else {
            $filter_reason = null;
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'r.returnsn';
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

        if (isset($this->request->get['filter_returnsn'])) {
            $url .= '&filter_returnsn=' . $this->request->get['filter_returnsn'];
        }

        if (isset($this->request->get['filter_ordersn'])) {
            $url .= '&filter_ordersn=' . $this->request->get['filter_ordersn'];
        }

        if (isset($this->request->get['filter_reason'])) {
            $url .= '&filter_reason=' . urlencode(html_entity_decode($this->request->get['filter_reason'], ENT_QUOTES, 'UTF-8'));
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
            'text' => "Refunds",
            'href' => $this->url->link('extension/module/cedshopee/return', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
        );

        $data['insert'] = $this->url->link('extension/module/cedshopee/return/add', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

        $data['returns'] = array();

        $filter_data = array(
            'filter_returnsn' => $filter_returnsn,
            'filter_ordersn' => $filter_ordersn,
            'filter_reason' => $filter_reason,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
        $this->load->model('extension/module/cedshopee/return');
        $return_total = $this->model_extension_module_cedshopee_return->getTotalReturns();

        $results = $this->model_extension_module_cedshopee_return->getReturns($filter_data);

        if ($results) {

            foreach ($results as $result) {

                $data['returns'][] = array(
                    'returnsn' => $result['returnsn'],
                    'ordersn' => $result['ordersn'],
                    'reason' => $result['reason'],
                    'view' => $this->url->link('extension/module/cedshopee/return/view', 'user_token=' . $this->session->data['user_token'] . $url . '&return_id=' . $result['id'], 'SSL'),

                );
            }
        }
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('heading_title');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');

        $data['column_returnsn'] = $this->language->get('column_returnsn');
        $data['column_ordersn'] = $this->language->get('column_ordersn');
        $data['column_reason'] = $this->language->get('column_reason');

        $data['column_action'] = $this->language->get('column_action');

        $data['entry_returnsn'] = $this->language->get('entry_returnsn');
        $data['entry_ordersn'] = $this->language->get('entry_ordersn');
        $data['entry_reason'] = $this->language->get('entry_reason');
        $data['entry_product'] = $this->language->get('entry_product');
        $data['entry_model'] = $this->language->get('entry_model');
        $data['entry_return_status'] = $this->language->get('entry_return_status');
        $data['entry_date_added'] = $this->language->get('entry_date_added');
        $data['entry_date_modified'] = $this->language->get('entry_date_modified');

        $data['button_insert'] = $this->language->get('button_insert');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_filter'] = $this->language->get('button_filter');

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['error'])) {
            $data['error_warning'] = $this->error['error'];
        } elseif (isset($this->error['warning'])) {
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

        if (isset($this->request->get['filter_returnsn'])) {
            $url .= '&filter_returnsn=' . $this->request->get['filter_returnsn'];
        }

        if (isset($this->request->get['filter_ordersn'])) {
            $url .= '&filter_ordersn=' . $this->request->get['filter_ordersn'];
        }

        if (isset($this->request->get['filter_reason'])) {
            $url .= '&filter_reason=' . urlencode(html_entity_decode($this->request->get['filter_reason'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_returnsn'] = $this->url->link('extension/module/cedshopee/return', 'user_token=' . $this->session->data['user_token'] . '&sort=returnsn' . $url, 'SSL');
        $data['sort_ordersn'] = $this->url->link('extension/module/cedshopee/return', 'user_token=' . $this->session->data['user_token'] . '&sort=ordersn' . $url, 'SSL');
        $data['sort_reason'] = $this->url->link('extension/module/cedshopee/return', 'user_token=' . $this->session->data['user_token'] . '&sort=reason' . $url, 'SSL');


        $pagination = new Pagination();
        $pagination->total = $return_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('sale/return', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($return_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($return_total - $this->config->get('config_limit_admin'))) ? $return_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $return_total, ceil($return_total / $this->config->get('config_limit_admin')));

        $data['filter_returnsn'] = $filter_returnsn;
        $data['filter_ordersn'] = $filter_ordersn;
        $data['filter_reason'] = $filter_reason;


        $this->load->model('localisation/return_status');

        $data['return_statuses'] = $this->model_localisation_return_status->getReturnStatuses();

        $data['sort'] = $sort;
        $data['order'] = $order;


        $this->template = 'extension/module/cedshopee/return';
        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/cedshopee/return', $data));
    }

    public function confirm() {
        if (isset($this->request->get['return_id']) && $this->request->get['return_id'] && isset($this->request->get['returnsn']) && $this->request->get['returnsn']) {
            $returnsn = $this->request->get['returnsn'];
            $this->load->library('cedshopee');
            $this->document->setTitle($this->language->get('heading_title'));
            $cedshopee = Cedshopee::getInstance($this->registry);
            $status = $cedshopee->isEnabled();
            if ($status) {
                $url = 'returns/get';
                $cedshopee->log($url);
                $params = array('returnsn' => $returnsn);
                $return_data = $cedshopee->fetchReturn($url, $params);
                $cedshopee->log('Order Fetch: data');
                $cedshopee->log(json_encode($return_data));
                if ($return_data && isset($return_data['success']) && $return_data['success']) {
                    $this->model_extension_module_cedshopee_return->addReturns($return_data['response']);
                    $this->session->data['success'] = $this->language->get('text_success');
                } else if (isset($order_data['message'])) {
                    $this->error['warning'] = $order_data['message'];
                } else {
                    $this->error['warning'] =  'No Resposne From Shopee.';
                }
            }
            $this->view();
        } else {
            $this->error['error'] = 'Return Id Not Found';
            $this->getList();
        }
    }

    public function dispute() {
        $this->language->load('extension/module/cedshopee/return');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/cedshopee/return');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->library('cedshopee');
            $cedshopee = Cedshopee::getInstance($this->registry);
            $status = $cedshopee->isEnabled();
            if ($status) {
                $url = 'returns/get';
                $cedshopee->log($url);
                $return_id = $this->request->get['return_id'];
                $return_data = $cedshopee->postRequest($url, $this->request->post);
                $cedshopee->log('Return Dispute: data');
                $cedshopee->log(json_encode($return_data));
                if ($return_data && isset($return_data['success']) && $return_data['success']) {
                    $this->model_extension_module_cedshopee_return->saveReturnDispute($return_id, $this->request->post, $return_data);
                    $this->session->data['success'] = $this->language->get('text_success');
                } else if (isset($order_data['message'])) {
                    $this->error['warning'] = $order_data['message'];
                } else {
                    $this->error['warning'] =  'No Resposne From Shopee.';
                }
            }
        }

        $this->getForm();
    }
    public function getForm()
    {
        $this->load->language('extension/module/cedshopee/return');
        $data['heading_title'] = $this->language->get('heading_title');
        $this->document->setTitle($this->language->get('heading_title'));
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => "Refunds",
            'href' => $this->url->link('extension/module/cedshopee/return/add', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['text_retrunsn'] = $this->language->get('text_retrunsn');
        $data['text_email'] = $this->language->get('text_email');
        $data['text_dispute_reason'] = $this->language->get('text_dispute_reason');
        $data['text_dispute_text_reason'] = $this->language->get('text_dispute_text_reason');

        $dispute_info =array();

        if(isset($this->request->get['return_id']) && $this->request->get['return_id']) {
            $dispute_info = $this->model_extension_module_cedshopee_return->getDisputeDetails($this->request->get['return_id']);
        }

        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_dispute'] = $this->language->get('button_dispute');

        $data['returnsn'] =0;
        if(isset($this->request->get['returnsn']) && $this->request->get['returnsn']) {
            $data['returnsn'] = $this->request->get['returnsn'];
        }

        $data['cancel'] = $this->url->link('extension/module/cedshopee/return/view', 'user_token=' . $this->session->data['user_token'].'&return_id='. $this->request->get['return_id'].'&returnsn='. $this->request->get['returnsn'], 'SSL');
        $data['dispute'] = $this->url->link('extension/module/cedshopee/return/dispute', 'user_token=' . $this->session->data['user_token'].'&return_id='. $this->request->get['return_id'].'&returnsn='. $this->request->get['returnsn'], 'SSL');

        if (isset($this->request->post['dispute_text_reason'])) {
            $data['dispute_text_reason'] = $this->request->post['dispute_text_reason'];
        } elseif (!empty($dispute_info)) {
            $data['dispute_text_reason'] = $dispute_info['dispute_text_reason'];
        } else {
            $data['dispute_text_reason'] = '';
        }

        if (isset($this->request->post['email'])) {
            $data['email'] = $this->request->post['email'];
        } elseif (!empty($dispute_info)) {
            $data['email'] = $dispute_info['email'];
        } else {
            $data['email'] = '';
        }

        if (isset($this->request->post['dispute_reason'])) {
            $data['dispute_reason'] = $this->request->post['dispute_reason'];
        } elseif (!empty($dispute_info)) {
            $data['dispute_reason'] = $dispute_info['dispute_reason'];
        } else {
            $data['dispute_reason'] = '';
        }

        $data['dispute_reasons'] = $this->model_extension_module_cedshopee_return->getReturnReasons();

        if (isset($this->error['email'])) {
            $data['error_email'] = $this->error['email'];
        } else {
            $data['error_email'] = '';
        }

        if (isset($this->error['dispute_text_reason'])) {
            $data['error_dispute_text_reason'] = $this->error['dispute_text_reason'];
        } else {
            $data['error_dispute_text_reason'] = '';
        }

        if (isset($this->error['returnsn'])) {
            $data['error_returnsn'] = $this->error['returnsn'];
        } else {
            $data['error_returnsn'] = '';
        }

        if (isset($this->error['images'])) {
            $data['error_images'] = $this->error['images'];
        } else {
            $data['error_images'] = '';
        }

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

        $$data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/cedshopee/return_dispute', $data));

    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/return')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['dispute_text_reason']) && utf8_strlen($this->request->post['dispute_text_reason']) < 3) {
           $this->error['dispute_text_reason'] = $this->language->get('error_dispute_text_reason');
        }

        $pattern='/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';

        if (isset($this->request->post['email']) && !preg_match($pattern, $this->request->post['email'])) {
            $this->error['email'] = $this->language->get('error_email');
        }

        if (isset($this->request->post['images']) && empty($this->request->post['images'])) {
           // $this->error['images'] = $this->language->get('error_images');
        }

        if (!isset($this->request->post['returnsn']) || !$this->request->post['returnsn']) {
            $this->error['returnsn'] = $this->language->get('error_returnsn');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}

?>