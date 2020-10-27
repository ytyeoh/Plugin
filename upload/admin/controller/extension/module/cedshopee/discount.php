<?php

class ControllerExtensionModuleCedshopeeDiscount extends Controller
{
    private $error = array();

    public function index() {
        $this->language->load('extension/module/cedshopee/discount');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/discount');

        $this->getList();
    }

    public function insert() {
        $this->language->load('extension/module/cedshopee/discount');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/cedshopee/discount');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) 
        {
            $this->load->library('cedshopee');
            $cedshopee = Cedshopee::getInstance($this->registry);

            $params = array();
            $params['discount_name'] = $this->request->post['discount_name'];
            $params['start_time'] =  $this->request->post['start_time'] = strtotime($this->request->post['start_date']);
            $params['end_time'] = $this->request->post['end_time'] = strtotime($this->request->post['end_date']);
            $item_discount_percentage = $this->request->post['discount_item_price'];
            $item_variation_discount_percentage = $this->request->post['discount_item_variation_price'];
            $purchase_limit = $this->request->post['purchase_limit'];

            $items = array();
            if(isset($this->request->post['shopee_items']) && is_array($this->request->post['shopee_items']) && !empty($this->request->post['shopee_items']))
            {
                foreach($this->request->post['shopee_items'] as $shopee_item_id)
                {
                    if($shopee_item_id > 0)
                    {
                        $result = $cedshopee->getProductIDByShopeeItemId($shopee_item_id);
                        if(isset($result['product_id']) && $result['product_id'])
                        {
                            $product_id = $result['product_id'];
                            $price = $result['price'];

                            if($this->request->post['price_type'] == '1')
                            {
                                $item_price = $price;
                            } else {
                                $item_price = $price - ($price * $item_discount_percentage) / 100 ;
                            }

                            $variations = $cedshopee->getVariationByID($product_id);

                            if(isset($variations) && is_array($variations) && !empty($variations))
                            {
                                $variation_array = array();
                                foreach($variations as $key => $variation)
                                {
                                    if($this->request->post['price_type'] == '1')
                                    {
                                        $variation_price = $variation['price'];
                                    } else {
                                        $variation_price = $variation['price'] - ($variation['price'] * $item_variation_discount_percentage) / 100 ;
                                    }
                                    
                                    $variation_array[] = array(
                                        'variation_id' => (int) $variation['variation_id'],
                                        'variation_promotion_price' => (float) $variation_price
                                        );
                                }
                                $items[] = array(
                                    'item_id' => (int) $shopee_item_id,
                                    'item_promotion_price' => (float) $item_price,
                                    'purchase_limit' => (int) $purchase_limit,
                                    'variations' => $variation_array
                                    );
                            } else {
                                $items[] = array(
                                    'item_id' => (int) $shopee_item_id,
                                    'item_promotion_price' => (float) $item_price,
                                    'purchase_limit' => (int) $purchase_limit,
                                    );
                            }
                        }
                    }
                }

                if(isset($items) && is_array($items) && !empty($items))
                {
                    $params['items'] = $items;
                }
            }

            $response = $cedshopee->postRequest('discount/add', $params);

            if (!isset($response['error']) && isset($response['discount_id'])) {
                $this->request->post['discount_id'] = $response['discount_id'];
                $this->model_extension_module_cedshopee_discount->addDiscount($this->request->post);
                $this->session->data['success'] = $this->language->get('text_success');
            } else {
                $this->error['warning'] = $response['msg'];
                $this->getForm();
                return;
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

            $this->response->redirect($this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getForm();
    }

    public function update() {
        $this->language->load('extension/module/cedshopee/discount');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/discount');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->library('cedshopee');
            $cedshopee = Cedshopee::getInstance($this->registry);
            $params = array();
            // $params['start_time'] =  $this->request->post['start_time'] = strtotime($this->request->post['start_date']);
            // $params['end_time'] = $this->request->post['end_time'] = strtotime($this->request->post['end_date']);
            $params['discount_id'] = (int)$this->request->post['discount_id'];
            $item_discount_percentage = $this->request->post['discount_item_price'];
            $item_variation_discount_percentage = $this->request->post['discount_item_variation_price'];
            $purchase_limit = $this->request->post['purchase_limit'];
            if(isset($this->request->post['shopee_item']))
            {
                $this->request->post['shopee_items'] = $this->request->post['shopee_item'];
            }
            $items = array();
            if(isset($this->request->post['shopee_items']) && is_array($this->request->post['shopee_items']) && !empty($this->request->post['shopee_items']))
            {
                foreach($this->request->post['shopee_items'] as $shopee_item_id)
                {
                    if($shopee_item_id > 0)
                    {
                       $result = $cedshopee->getProductIDByShopeeItemId($shopee_item_id);
                        if(isset($result['product_id']) && $result['product_id'])
                        {
                            $product_id = $result['product_id'];
                            $price = $result['price'];

                            if($this->request->post['price_type'] == '1')
                            {
                                $item_price = $price;
                            } else {
                                $item_price = $price - ($price * $item_discount_percentage) / 100 ;
                            }

                            $variations = $cedshopee->getVariationByID($product_id);

                            if(isset($variations) && is_array($variations) && !empty($variations))
                            {
                                $variation_array = array();
                                foreach($variations as $key => $variation)
                                {
                                    if($this->request->post['price_type'] == '1')
                                    {
                                        $variation_price = $variation['price'];
                                    } else {
                                        $variation_price = $variation['price'] - ($variation['price'] * $item_variation_discount_percentage) / 100 ;
                                    }
                                    
                                    $variation_array[] = array(
                                        'variation_id' => (int) $variation['variation_id'],
                                        'variation_promotion_price' => (float) $variation_price
                                        );
                                }
                                $items[] = array(
                                    'item_id' => (int) $shopee_item_id,
                                    'item_promotion_price' => (float) $item_price,
                                    'purchase_limit' => (int) $purchase_limit,
                                    'variations' => $variation_array
                                    );
                            } else {
                                $items[] = array(
                                    'item_id' => (int) $shopee_item_id,
                                    'item_promotion_price' => (float) $item_price,
                                    'purchase_limit' => (int) $purchase_limit,
                                    );
                            }
                        } 
                    }
                    
                }
            }

            if(isset($items) && is_array($items) && !empty($items))
            {
                $params['discount_id'] = (int)$this->request->post['discount_id'];
                $params['items'] = $items;

                $response = $cedshopee->postRequest('discount/items/update', $params);
                if(isset($response['error']) && $response['msg'])
                {
                    $this->error['warning'] = $response['msg'];
                    $this->getForm();
                    return;
                } else {
                    $this->request->post['discount_id'] = $response['discount_id'];
                    $this->model_extension_module_cedshopee_discount->editDiscount($this->request->post['discount_id'], $this->request->post);
                    $this->session->data['success'] = $this->language->get('text_success');
                }
            }
            
            // $response = $cedshopee->postRequest('discount/update', $params);

            // if (!isset($response['error']) && isset($response['discount_id'])) 
            // {
            //     $this->request->post['discount_id'] = $response['discount_id'];
            //     $this->model_extension_module_cedshopee_discount->editDiscount($this->request->post['discount_id'], $this->request->post);
            //     $this->session->data['success'] = $this->language->get('text_success');
            // } else {
            //     $this->error['warning'] = $response['msg'];
            //     $this->getForm();
            //     return;
            // }
            
            // $this->session->data['success'] = $this->language->get('text_success');

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

            $this->response->redirect($this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getForm();
    }

    public function delete() {
        $this->language->load('extension/module/cedshopee/discount');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/discount');
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            $this->load->library('cedshopee');
            $cedshopee = Cedshopee::getInstance($this->registry);

            foreach ($this->request->post['selected'] as $discount_id) {
                $response = $cedshopee->postRequest('discount/delete', array('discount_id'=> (int)$discount_id));
                
                if (isset($response['discount_id']) && $response['discount_id']) {
                    $this->request->post['discount_id'] = $response['discount_id'];
                    $this->model_extension_module_cedshopee_discount->deleteDiscountByDiscountId($this->request->post['discount_id']);
                    $this->session->data['success'] = $this->language->get('text_success');
                } else {
                    $this->error['warning'] = $response['msg'];
                }
            }

            //$this->session->data['success'] = $this->language->get('text_success');

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

            $this->response->redirect($this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getList();
    }

    protected function getList() {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'r.date_added';
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
            'href'      => $this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
        );

        $data['insert'] = $this->url->link('extension/module/cedshopee/discount/insert', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        $data['delete'] = $this->url->link('extension/module/cedshopee/discount/delete', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

        $data['discounts'] = array();

        $filter_data = array(
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $discount_total = $this->model_extension_module_cedshopee_discount->getTotalDiscounts();

        $results = $this->model_extension_module_cedshopee_discount->getDiscounts($filter_data);

        foreach ($results as $result) {
            $action = array();

            $action[] = array(
                'text' => $this->language->get('text_edit'),
                'href' => $this->url->link('extension/module/cedshopee/discount/update', 'user_token=' . $this->session->data['user_token'] . '&id=' . $result['id'] . $url, 'SSL')
            );

            $data['discounts'][] = array(
                'id'  => $result['id'],
                'discount_id'  => $result['discount_id'],
                'discount_name'       => $result['discount_name'],
                'end_date' => date($this->language->get('date_format_short'), strtotime($result['end_date'])),
                'start_date' => date($this->language->get('date_format_short'), strtotime($result['start_date'])),
                'selected'   => isset($this->request->post['selected']) && in_array($result['id'], $this->request->post['selected']),
                'action'     => $action
            );
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');

        $data['text_no_results'] = $this->language->get('text_no_results');

        $data['column_discount_id'] = $this->language->get('column_discount_id');
        $data['column_discount_name'] = $this->language->get('column_discount_name');
        $data['column_start_date'] = $this->language->get('column_start_date');
        $data['column_end_date'] = $this->language->get('column_end_date');
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

        $data['sort_product'] = $this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, 'SSL');
        $data['sort_author'] = $this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . '&sort=r.author' . $url, 'SSL');
        $data['sort_rating'] = $this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . '&sort=r.rating' . $url, 'SSL');
        $data['sort_status'] = $this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . '&sort=r.status' . $url, 'SSL');
        $data['sort_date_added'] = $this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . '&sort=r.date_added' . $url, 'SSL');

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $discount_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($discount_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($discount_total - $this->config->get('config_limit_admin'))) ? $discount_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $discount_total, ceil($discount_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/cedshopee/discount_list', $data));
    }

    protected function getForm() {
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_form'] = !isset($this->request->get['id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_none'] = $this->language->get('text_none');
        $data['text_select'] = $this->language->get('text_select');

        $data['entry_items'] = $this->language->get('entry_items');
        $data['entry_discount_name'] = $this->language->get('entry_discount_name');
        $data['entry_rating'] = $this->language->get('entry_rating');
        $data['entry_start_date'] = $this->language->get('entry_start_date');
        $data['entry_end_date'] = $this->language->get('entry_end_date');
        $data['entry_discount_item_price'] = $this->language->get('entry_discount_item_price');
        $data['entry_discount_item_variation_price'] = $this->language->get('entry_discount_item_variation_price');
        $data['entry_purchase_limit'] = $this->language->get('entry_purchase_limit');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['items'])) {
            $data['error_items'] = $this->error['items'];
        } else {
            $data['error_items'] = '';
        }

        if (isset($this->error['discount_name'])) {
            $data['error_discount_name'] = $this->error['discount_name'];
        } else {
            $data['error_discount_name'] = '';
        }

        if (isset($this->error['start_date'])) {
            $data['error_start_date'] = $this->error['start_date'];
        } else {
            $data['error_start_date'] = '';
        }

        if (isset($this->error['end_date'])) {
            $data['error_end_date'] = $this->error['end_date'];
        } else {
            $data['error_end_date'] = '';
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
            'href'      => $this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
        );

        if (!isset($this->request->get['id'])) {
            $data['action'] = $this->url->link('extension/module/cedshopee/discount/insert', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        } else {
            $data['action'] = $this->url->link('extension/module/cedshopee/discount/update', 'user_token=' . $this->session->data['user_token'] . '&id=' . $this->request->get['id'] . $url, 'SSL');
        }

        $data['cancel'] = $this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

        if (isset($this->request->get['id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $discount_info = $this->model_extension_module_cedshopee_discount->getDiscount($this->request->get['id']);
        }
// echo '<pre>'; print_r($discount_info); die;
        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('catalog/product');

        if (isset($this->request->post['discount_id'])) {
            $data['discount_id'] = $this->request->post['discount_id'];
        } elseif (!empty($discount_info)) {
            $data['discount_id'] = $discount_info['discount_id'];
        } else {
            $data['discount_id'] = '';
        }
        $data['shopee_items'] = array();
        
        if (isset($this->request->post['shopee_items']) || isset($this->request->post['shopee_item'])) 
        {
            if(isset($this->request->post['shopee_item']))
            {
                $this->request->post['shopee_items'] = $this->request->post['shopee_item'];
            }
            foreach ($this->request->post['shopee_items'] as $shop_item) {
                $results =  $this->db->query("SELECT pd.name, cpp.shopee_item_id FROM `".DB_PREFIX."cedshopee_uploaded_products` cpp LEFT JOIN `".DB_PREFIX."product_description` pd ON (cpp.product_id=pd.product_id) where cpp.shopee_item_id = '".$shop_item."'");
                if(!empty($results->row))
                $data['shopee_items'][]= $results->row;
            }
        } elseif (!empty($discount_info)) {
            $shop_items = (json_decode($discount_info['items'],true))?json_decode($discount_info['items'],true):array();
            // 
            foreach ($shop_items as $shop_item) {
               $results =  $this->db->query("SELECT pd.name, cpp.shopee_item_id FROM `".DB_PREFIX."cedshopee_uploaded_products` cpp LEFT JOIN `".DB_PREFIX."product_description` pd ON (cpp.product_id=pd.product_id) WHERE cpp.shopee_item_id = '".$shop_item."'");
               //echo '<pre>'; print_r($results); die;
                $data['shopee_items'][]= $results->row;
            }
        } else {
            $data['shopee_items'] = array();
        }

        if (isset($this->request->post['discount_name'])) {
            $data['discount_name'] = $this->request->post['discount_name'];
        } elseif (!empty($discount_info)) {
            $data['discount_name'] = $discount_info['discount_name'];
        } else {
            $data['discount_name'] = '';
        }
/*[start_date] => 06/22/2019 4:00 PM
    [end_date] => 06/30/2019 4:00 AM*/

        if (isset($this->request->post['start_date'])) {
            $data['start_date'] = $this->request->post['start_date'];
        } elseif (!empty($discount_info)) {
            $data['start_date'] = date('m/d/Y h:i A', strtotime($discount_info['start_date']));
        } else {
            $data['start_date'] = '';
        }

        if (isset($this->request->post['end_date'])) {
            $data['end_date'] = $this->request->post['end_date'];
        } elseif (!empty($discount_info)) {
            $data['end_date'] = date('m/d/Y h:i A', strtotime($discount_info['end_date']));;
        } else {
            $data['end_date'] = '';
        }

        if (isset($this->request->post['price_type'])) {
            $data['price_type'] = $this->request->post['price_type'];
        } elseif (!empty($discount_info)) {
            $data['price_type'] = $discount_info['price_type'];
        } else {
            $data['price_type'] = '';
        }

        if (isset($this->request->post['discount_item_price'])) {
            $data['discount_item_price'] = $this->request->post['discount_item_price'];
        } elseif (!empty($discount_info)) {
            $data['discount_item_price'] = $discount_info['discount_item_price'];
        } else {
            $data['discount_item_price'] = '';
        }

        if (isset($this->request->post['discount_item_variation_price'])) {
            $data['discount_item_variation_price'] = $this->request->post['discount_item_variation_price'];
        } elseif (!empty($discount_info)) {
            $data['discount_item_variation_price'] = $discount_info['discount_item_variation_price'];
        } else {
            $data['discount_item_variation_price'] = '';
        }

        if (isset($this->request->post['purchase_limit'])) {
            $data['purchase_limit'] = $this->request->post['purchase_limit'];
        } elseif (!empty($discount_info)) {
            $data['purchase_limit'] = $discount_info['purchase_limit'];
        } else {
            $data['purchase_limit'] = '';
        }

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/cedshopee/discount_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/discount')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['discount_name']) {
            $this->error['discount_name'] = $this->language->get('error_discount_name');
        }

        if ((utf8_strlen($this->request->post['discount_name']) < 3) || (utf8_strlen($this->request->post['discount_name']) > 64)) {
            $this->error['discount_name'] = $this->language->get('error_discount_name');
        }

       if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/discount')) {
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