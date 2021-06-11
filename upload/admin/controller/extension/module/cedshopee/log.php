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
class ControllerExtensionModuleCedshopeeLog extends Controller
{
    private $error = array();

    private $data = array();

    public function index()
    {
        $this->language->load('extension/module/cedshopee/log');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/log');

        $this->getList();
    }

    protected function getList()
    {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'method';
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
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/cedshopee/log', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'),
            'separator' => ' :: '
        );

        $data['delete'] = $this->url->link('extension/module/cedshopee/log/delete', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        $data['clear'] = $this->url->link('extension/module/cedshopee/log/deleteAll', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $data['logs'] = array();

        $filter_data = array(
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_admin_limit'),
            'limit' => $this->config->get('config_admin_limit')
        );
        
        $logs_total = $this->model_extension_module_cedshopee_log->getTotalLogs();

        $results = $this->model_extension_module_cedshopee_log->getLogs($filter_data);

        foreach ($results as $result) {

            $data['logs'][] = array(
                'id' => $result['id'],
                'method' => $result['method'],
                'message' => $result['message'],
                'response' => $result['response'],
                'created_at' => $result['created_at'],
                'selected' => isset($this->request->post['selected']) && in_array($result['id'], $this->request->post['selected']),
            );
        }

        $this->language->load('extension/module/cedshopee/log');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['heading_title'] = $this->language->get('heading_title');

        $data['button_clear'] = $this->language->get('button_clear');

        $data['column_id'] = $this->language->get('column_id');
        $data['column_method'] = $this->language->get('column_method');
        $data['column_message'] = $this->language->get('column_message');
        $data['column_response'] = $this->language->get('column_response');
        $data['column_created_at'] = $this->language->get('column_created_at');
        $data['column_action'] = $this->language->get('column_action');
        $data['button_clear'] = $this->language->get('button_clear');
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

        $data['sort_id'] = $this->url->link('extension/module/cedshopee/log', 'user_token=' . $this->session->data['user_token'] . '&sort=id' . $url, 'SSL');
        $data['sort_method'] = $this->url->link('extension/module/cedshopee/log', 'user_token=' . $this->session->data['user_token'] . '&sort=method' . $url, 'SSL');
        $data['sort_created_at'] = $this->url->link('extension/module/cedshopee/log', 'user_token=' . $this->session->data['user_token'] . '&sort=created_at' . $url, 'SSL');

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $logs_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_admin_limit');
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/module/cedshopee/log', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', 'SSL');

        $data['pagination'] = $pagination->render();

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/cedshopee/log' , $data));
    }

//    public function getList()
//    {
//        $this->language->load('extension/module/cedshopee/log');
//
//        $this->document->setTitle($this->language->get('heading_title'));
//
//        $data['heading_title'] = $this->language->get('heading_title');
//
//        $data['button_clear'] = $this->language->get('button_clear');
//
//        $data['column_id'] = $this->language->get('column_id');
//        $data['column_method'] = $this->language->get('column_method');
//        $data['column_message'] = $this->language->get('column_message');
//        $data['column_response'] = $this->language->get('column_response');
//        $data['column_created_at'] = $this->language->get('column_created_at');
//        $data['column_action'] = $this->language->get('column_action');
//
//        if (isset($this->session->data['success'])) {
//            $data['success'] = $this->session->data['success'];
//
//            unset($this->session->data['success']);
//        } else {
//            $data['success'] = '';
//        }
//
//        $data['breadcrumbs'] = array();
//
//        $data['breadcrumbs'][] = array(
//            'text' => $this->language->get('text_home'),
//            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
//            'separator' => false
//        );
//
//        $data['breadcrumbs'][] = array(
//            'text' => $this->language->get('heading_title'),
//            'href' => $this->url->link('extension/module/cedshopee/log', 'user_token=' . $this->session->data['user_token'], 'SSL'),
//            'separator' => ' :: '
//        );
//
//        $data['clear'] = $this->url->link('extension/module/cedshopee/log/clear', 'user_token=' . $this->session->data['user_token'], 'SSL');
//
//        $file = DIR_LOGS . 'Cedshopee.log';
//
//        if (file_exists($file)) {
//            $data['log'] = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
//        } else {
//            $data['log'] = '';
//        }
//
//        $this->template = 'extension/module/cedshopee/log.tpl';
//        $this->children = array(
//            'common/header',
//            'common/footer'
//        );
//
//        $this->response->setOutput($this->render());
//    }

//    public function clear()
//    {
//        $this->language->load('cedshopee/log');
//
//        $file = DIR_LOGS . 'Cedshopee.log';
//
//        $handle = fopen($file, 'w+');
//
//        fclose($handle);
//
//        $this->session->data['success'] = $this->language->get('text_success');
//
//        $this->redirect($this->url->link('extension/module/cedshopee/log', 'user_token=' . $this->session->data['user_token'], 'SSL'));
//    }

    public function delete() {
        $this->language->load('extension/module/cedshopee/log');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/log');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $log_id) {
                $this->model_extension_module_cedshopee_log->deleteLog($log_id);
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

            $this->response->redirect($this->url->link('extension/module/cedshopee/log', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getList();
    }

    public function deleteAll() {
        $this->language->load('extension/module/cedshopee/log');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/log');

        if ($this->validateDelete()) {
            $this->model_extension_module_cedshopee_log->deleteAllLog();

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

            $this->response->redirect($this->url->link('extension/module/cedshopee/log', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getList();
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/log')) {
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