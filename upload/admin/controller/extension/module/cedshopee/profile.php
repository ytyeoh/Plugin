<?php
class ControllerExtensionModuleCedshopeeProfile extends Controller { 
    private $error = array();

    public function index() {
        $this->language->load('extension/module/cedshopee/profile');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/profile');

        $this->getList();
    }

    public function insert() {
        $this->language->load('extension/module/cedshopee/profile');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/profile');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_extension_module_cedshopee_profile->addProfile($this->request->post);

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

            $this->response->redirect($this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getForm();
    }

    public function update() {
        $this->language->load('extension/module/cedshopee/profile');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/profile');
         //echo '<pre>'; print_r($this->request->post); die;
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {

            $this->model_extension_module_cedshopee_profile->editProfile($this->request->get['id'], $this->request->post);

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

            $this->response->redirect($this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getForm();
    }

    public function delete() {
        $this->language->load('extension/module/cedshopee/profile');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/profile');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $id) {

                $this->model_extension_module_cedshopee_profile->deleteProfile($id);
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

            $this->response->redirect($this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getList();
    }
    
    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'title';
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
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
        );

        $data['insert'] = $this->url->link('extension/module/cedshopee/profile/insert', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        $data['delete'] = $this->url->link('extension/module/cedshopee/profile/delete', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');   

        $data['profiles'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $profile_total = $this->model_extension_module_cedshopee_profile->getTotalProfiles();

        $results = $this->model_extension_module_cedshopee_profile->getProfiles($filter_data);

        foreach ($results as $result) {
            $action = array();

            $action[] = array(
                'text' => $this->language->get('text_edit'),
                'href' => $this->url->link('extension/module/cedshopee/profile/update', 'user_token=' . $this->session->data['user_token'] . '&id=' . $result['id'] . $url, 'SSL')
            );

            $data['profiles'][] = array(
                'id' => $result['id'],
                'title'      => $result['title'],
                'status'     => ($result['status']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'selected'       => isset($this->request->post['selected']) && in_array($result['id'], $this->request->post['selected']),
                'action'         => $action
            );
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

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_title'] = $this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'] . '&sort=title' . $url, 'SSL');
        $data['sort_id'] = $this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'] . '&sort=id' . $url, 'SSL');
        $data['sort_status'] = $this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, 'SSL');

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $profile_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($profile_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($profile_total - $this->config->get('config_limit_admin'))) ? $profile_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $profile_total, ceil($profile_total / $this->config->get('config_limit_admin')));


        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/cedshopee/profile_list', $data));
    }

    protected function getForm() {

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_form'] = !isset($this->request->get['id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['title'])) {
            $data['error_title'] = $this->error['title'];
        } else {
            $data['error_title'] = array();
        }

        if (isset($this->error['store'])) {
            $data['error_store'] = $this->error['store'];
        } else {
            $data['error_store'] = array();
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
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
        );

        if (!isset($this->request->get['id'])) {
            $data['action'] = $this->url->link('extension/module/cedshopee/profile/insert', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        } else {
            $data['profile_id'] = $this->request->get['id'];
            $data['action'] = $this->url->link('extension/module/cedshopee/profile/update', 'user_token=' . $this->session->data['user_token'] . '&id=' . $this->request->get['id'] . $url, 'SSL');
        }

        $data['cancel'] = $this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        $profile_info = array();
        if (isset($this->request->get['id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $profile_info = $this->model_extension_module_cedshopee_profile->getProfile($this->request->get['id']);

            $logistics = json_decode($profile_info['logistics'], true);
            $profile_store = json_decode($profile_info['profile_store'], true);
            $categories = json_decode($profile_info['store_category'], true);
            $product_manufacturer = json_decode($profile_info['product_manufacturer'], true);
            $wholesale =json_decode($profile_info['wholesale'], true);
            $default_mapping = json_decode($profile_info['default_mapping'], true);

            if(!is_array($logistics) || empty($logistics))
                $logistics = array();
            if(!is_array($profile_store) || empty($profile_store))
                $profile_store = array();
            if(!is_array($categories) || empty($categories))
                $categories = array();
            if(!is_array($product_manufacturer) || empty($product_manufacturer))
                $product_manufacturer = array();
            if(!is_array($wholesale) || empty($wholesale))
                $wholesale = array();
            if(!is_array($default_mapping) || empty($default_mapping))
                $default_mapping = array();
        }

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('extension/module/cedshopee/logistics');

        $data['logistics_list'] = $this->model_extension_module_cedshopee_logistics->getLogistics();

        if (isset($this->request->post['logistics'])) {
            $data['logistics'] = $this->request->post['logistics'];
        } elseif (!empty($logistics)) {
            $data['logistics'] = $logistics;
        } else {
            $data['logistics'] = array(0);
        }
//        echo '<pre>'; print_r($data); die;
        $this->load->model('setting/store');

        $data['stores'] = $this->model_setting_store->getStores();

        if (isset($this->request->post['profile_store'])) {
            $data['profile_store'] = $this->request->post['profile_store'];
        } elseif (!empty($profile_store)) {
            $data['profile_store'] = $profile_store;
        } else {
            $data['profile_store'] = array(0);
        }

        // Store Categories
        $this->load->model('catalog/category');

        if (isset($this->request->post['product_category'])) {
            $categories = $this->request->post['product_category'];
        } elseif (!empty($categories)) {
            $categories = $categories;
        } else {
            $categories = array();
        }

        $data['product_categories'] = array();

        foreach ($categories as $category_id) {
            $category_info = $this->model_catalog_category->getCategory($category_id);

            if ($category_info) {
                $data['product_categories'][] = array(
                    'category_id' => $category_info['category_id'],
                    'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
                );
            }
        }

        // Manufacturer
        $this->load->model('catalog/manufacturer');

        if (isset($this->request->post['manufacturer_id'])) {
            $product_manufacturer = $this->request->post['manufacturer_id'];
        } elseif (!empty($product_manufacturer)) {
            $product_manufacturer = $product_manufacturer;
        } else {
            $product_manufacturer = array();
        }

        $data['product_manufacturers'] = array();

        foreach ($product_manufacturer as $manufacturer) {
            $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($manufacturer);

            if ($manufacturer_info) {
                $data['product_manufacturers'][] = array(
                    'manufacturer_id' => $manufacturer_info['manufacturer_id'],
                    'name' => $manufacturer_info['name']
                );
            }
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
    
        if (isset($this->request->post['profile_language'])) {
            $data['profile_language'] = $this->request->post['profile_language'];
        } elseif (isset($profile_info['profile_language'])) {
            $data['profile_language'] = $profile_info['profile_language'];
        } else {
            $data['profile_language'] = 1;
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($profile_info)) {
            $data['status'] = $profile_info['status'];
        } else {
            $data['status'] = 1;
        }

        if (isset($this->request->post['title'])) {
            $data['title'] = $this->request->post['title'];
        } elseif (!empty($profile_info)) {
            $data['title'] = $profile_info['title'];
        } else {
            $data['title'] = '';
        }

        // Shopee Category
        $this->load->model('extension/module/cedshopee/category');

        $data['categories'] = $this->model_extension_module_cedshopee_category->getCategory();

        if (isset($this->request->post['shopee_category'])) {
            $data['shopee_category'] = $this->request->post['shopee_category'];
        } elseif (!empty($profile_info['shopee_category'])) {
            $data['shopee_category'] = $profile_info['shopee_category'];
        } else {
            $data['shopee_category'] = '';
        }

        if (isset($this->request->post['shopee_category_name'])) {
            $data['shopee_category_name'] = $this->request->post['shopee_category_name'];
        } else {
            $data['shopee_category_name'] = '';
        }

        if(!empty($profile_info['shopee_category']) && empty($profile_info['shopee_category_name']))
        {
            $sql = $this->db->query("SELECT `category_name` FROM `". DB_PREFIX ."cedshopee_category` WHERE category_id = '". $profile_info['shopee_category'] ."' ");
            $data['shopee_category_name'] = $sql->row['category_name'];
        } else {
            if (!empty($profile_info['shopee_category_name'])) {
                $data['shopee_category_name'] = $profile_info['shopee_category_name'];
            }
        }

        if (isset($this->request->post['wholesale'])) {
            $data['wholesale'] = $this->request->post['wholesale'];
        } elseif (!empty($wholesale)) {
            $data['wholesale'] = $wholesale;
        } else {
            $data['wholesale'] = '';
        }

        if(isset($default_mapping) && !empty($default_mapping)) {
            $data['default_map_attributes'] = $default_mapping;
        } else {
            $data['default_map_attributes'] = $this->model_extension_module_cedshopee_profile->getDefaultAttributesMapping();
        }

        $data['default_attributes'] = $this->model_extension_module_cedshopee_profile->getDefaultAttributes();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/cedshopee/profile_form', $data));
    }

    protected function validateForm() {
        return true;
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/profile')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->request->post['profile_description'] as $language_id => $value) {
            if ((utf8_strlen($value['title']) < 3) || (utf8_strlen($value['title']) > 64)) {
                $this->error['title'][$language_id] = $this->language->get('error_title');
            }

            if (utf8_strlen($value['description']) < 3) {
                $this->error['description'][$language_id] = $this->language->get('error_description');
            }
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/profile')) {
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