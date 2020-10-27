<?php

class ControllerExtensionModuleCedshopeeLogistics extends Controller
{
    private $error = array();

    public function index()
    {
        $this->language->load('extension/module/cedshopee/logistics');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/logistics');

        $this->getList();
    }

    public function insert()
    {
        $this->language->load('extension/module/cedshopee/logistics');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/logistics');
        $this->load->library('cedshopee');
        $cedshopee = Cedshopee::getInstance($this->registry);
        $response = $cedshopee->postRequest('logistics/channel/get', array());
        if (!isset($response['error']) && isset($response['logistics'])) {
            $this->session->data['success'] = $this->language->get('text_success');
            $this->model_extension_module_cedshopee_logistics->addLogistic($response['logistics']);
        } else if (isset($response['msg'])) {
            $this->error['warning'] = $response['msg'];
        } else {
            $this->error['warning'] = 'No response from by shopee.com';
        }

        $this->getList();
    }

    protected function getList()
    {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'ad.name';
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
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/cedshopee/logistics', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
        );

        $data['insert'] = $this->url->link('extension/module/cedshopee/logistics/insert', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        $data['delete'] = $this->url->link('extension/module/cedshopee/logistics/delete', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

        $data['logistics'] = array();

        $filter_data = array(
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $logistics_total = $this->model_extension_module_cedshopee_logistics->getTotalLogistics();

        $results = $this->model_extension_module_cedshopee_logistics->getLogistics($filter_data);

        foreach ($results as $result) {
            $action = array();

            $action[] = array(
                'text' => $this->language->get('text_edit'),
                'href' => $this->url->link('extension/module/cedshopee/logistics/update', 'user_token=' . $this->session->data['user_token'] . '&logistic_id=' . $result['logistic_id'] . $url, 'SSL')
            );

            $data['logistics'][] = array(
                'logistic_id' => $result['logistic_id'],
                'name' => $result['logistic_name'],
                'status' => ($result['enabled']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'selected' => isset($this->request->post['selected']) && in_array($result['logistic_id'], $this->request->post['selected']),
                'action' => $action
            );
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');

        $data['text_no_results'] = $this->language->get('text_no_results');

        $data['column_name'] = $this->language->get('column_name');
        $data['column_logistics_id'] = $this->language->get('column_logistics_id');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_insert'] = $this->language->get('button_insert');
        $data['button_delete'] = $this->language->get('button_delete');
        
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

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link('extension/module/cedshopee/logistics', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, 'SSL');
        $data['sort_logistics_id'] = $this->url->link('extension/module/cedshopee/logistics', 'user_token=' . $this->session->data['user_token'] . '&sort=logistics_id' . $url, 'SSL');
        $data['sort_status'] = $this->url->link('extension/module/cedshopee/logistics', 'user_token=' . $this->session->data['user_token'] . '&sort=enabled' . $url, 'SSL');

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $logistics_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/module/cedshopee/logistics', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($logistics_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($logistics_total - $this->config->get('config_limit_admin'))) ? $logistics_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $logistics_total, ceil($logistics_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/cedshopee/logistics_list', $data));
    }
    public function delete() {
        $this->language->load('extension/module/cedshopee/logistics');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/logistics');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $filter_group_id) {
                $this->model_extension_module_cedshopee_logistics->deleteLogistic($filter_group_id);
            }

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

            $this->response->redirect($this->url->link('extension/module/cedshopee/logistics', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getList();
    }
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/logistics')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}

?>