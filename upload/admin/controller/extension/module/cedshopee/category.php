<?php

class ControllerExtensionModuleCedshopeeCategory extends Controller
{
    private $error = array();

    public function index()
    {
        $this->language->load('extension/module/cedshopee/category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/category');

        $this->getList();
    }

    protected function getList()
    {
        if (isset($this->request->get['filter_category_name'])) {
            $filter_category_name = $this->request->get['filter_category_name'];
        } else {
            $filter_category_name = null;
        }

        if (isset($this->request->get['filter_category_id'])) {
            $filter_category_id = $this->request->get['filter_category_id'];
        } else {
            $filter_category_id = null;
        }

        if (isset($this->request->get['filter_parent_id'])) {
            $filter_parent_id = $this->request->get['filter_parent_id'];
        } else {
            $filter_parent_id = null;
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'category_name';
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

        if (isset($this->request->get['filter_category_name'])) {
            $url .= '&filter_category_name=' . urlencode(html_entity_decode($this->request->get['filter_category_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_category_id'])) {
            $url .= '&filter_category_id=' . urlencode(html_entity_decode($this->request->get['filter_category_id'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_parent_id'])) {
            $url .= '&filter_parent_id=' . $this->request->get['filter_parent_id'];
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
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/cedshopee/category', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
        );

        $data['fetch'] = $this->url->link('extension/module/cedshopee/category/fetch', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
        $data['delete'] = $this->url->link('extension/module/cedshopee/category/delete', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

        $data['categorys'] = array();

        $filter_data = array(
            'filter_category_name' => $filter_category_name,
            'filter_category_id' => $filter_category_id,
            'filter_parent_id' => $filter_parent_id,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );


        $category_total = $this->model_extension_module_cedshopee_category->getTotalCategories($filter_data);

        $results = $this->model_extension_module_cedshopee_category->getCategories($filter_data);

        foreach ($results as $result) {

            $action = array();

            $action[] = array(
                'text' => $this->language->get('text_edit'),
                'href' => $this->url->link('extension/module/cedshopee/category/update', 'user_token=' . $this->session->data['user_token'] . '&category_id=' . $result['category_id'] . $url, 'SSL')
            );

            $data['categorys'][] = array(
                'id' => $result['id'],
                'category_id' => $result['category_id'],
                'category_name' => $result['category_name'],
                'parent_id' => $result['parent_id'],
                'selected' => isset($this->request->post['selected']) && in_array($result['category_id'], $this->request->post['selected']),
                'action' => $action
            );
        }

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

        if (isset($this->request->get['filter_category_id'])) {
            $url .= '&filter_category_id=' . urlencode(html_entity_decode($this->request->get['filter_category_id'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_parent_id'])) {
            $url .= '&filter_parent_id=' . $this->request->get['filter_parent_id'];
        }


        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_category_name'] = $this->url->link('extension/module/cedshopee/category', 'user_token=' . $this->session->data['user_token'] . '&sort=category_name' . $url, 'SSL');
        $data['sort_category_id'] = $this->url->link('extension/module/cedshopee/category', 'user_token=' . $this->session->data['user_token'] . '&sort=category_id' . $url, 'SSL');
        $data['sort_parent_id'] = $this->url->link('extension/module/cedshopee/category', 'user_token=' . $this->session->data['user_token'] . '&sort=parent_id' . $url, 'SSL');

        $url = '';

        if (isset($this->request->get['filter_category_name'])) {
            $url .= '&filter_category_name=' . urlencode(html_entity_decode($this->request->get['filter_category_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_category_id'])) {
            $url .= '&filter_category_id=' . urlencode(html_entity_decode($this->request->get['filter_category_id'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_parent_id'])) {
            $url .= '&filter_parent_id=' . $this->request->get['filter_parent_id'];
        }


        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $category_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/module/cedshopee/category', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($category_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($category_total - $this->config->get('config_limit_admin'))) ? $category_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $category_total, ceil($category_total / $this->config->get('config_limit_admin')));

        $data['filter_category_name'] = $filter_category_name;
        $data['filter_category_id'] = $filter_category_id;
        $data['filter_parent_id'] = $filter_parent_id;


        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header']  = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/cedshopee/category_list', $data));
    }

    public function autocomplete()
    {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('extension/module/cedshopee/category');

            if (isset($this->request->get['filter_name'])) {
                $filter_name = $this->request->get['filter_name'];
            } else {
                $filter_name = '';
            }

            if (isset($this->request->get['limit'])) {
                $limit = $this->request->get['limit'];
            } else {
                $limit = 20;
            }

            $data = array(
                'filter_name' => $filter_name,
                'start' => 0,
                'limit' => $limit
            );

            $results = $this->model_extension_module_cedshopee_category->getCategories($data);
            
            foreach ($results as $category) {
                $json[] = array(
                    'category_id' => $category['category_id'],
                    'name' => strip_tags(html_entity_decode($category['category_name'], ENT_QUOTES, 'UTF-8')),
                );
            }

        }
        $this->response->setOutput(json_encode($json));
    }

    public function attributesByCategory(){

        $category_id = isset($this->request->get['category_id'])?$this->request->get['category_id']:0;
        $profile_id = isset($this->request->get['profile_id'])?$this->request->get['profile_id']:0;
        $html ='No Attribute Found , Please checkCategory.';
        if ($category_id) {
            ini_set('memory_limit','512M');
            $this->load->model('extension/module/cedshopee/category');
            $this->load->model('catalog/attribute');
            $this->load->model('extension/module/cedshopee/option');
            $this->load->model('extension/module/cedshopee/profile');
            $attributes_options = $this->model_extension_module_cedshopee_category->getAttributes($category_id);

            $mapped_attributes_options = array();
            if($profile_id)
            $mapped_attributes_options = $this->model_extension_module_cedshopee_profile->getMappedAttributes($profile_id);

            $results = $this->model_catalog_attribute->getAttributes();
            $store_options = $this->model_extension_module_cedshopee_option->getStoreOptions();
            $options = $store_options['options'];
            $attributes = $this->model_extension_module_cedshopee_category->getAttributes($category_id);

            $html ='';
            $required = array();
            if(isset($attributes) && is_array($attributes) && $attributes)
            {
                foreach ($attributes as $attribute) 
                {
                    $key = $attribute['attribute_id'];
                    $html .= '<tr>';
                    $html .= '<td class="text-">';

                    if(isset($attribute['is_mandatory']) && $attribute['is_mandatory']){
                        $required[] = $attribute['attribute_id'];
                        $html .= '<div class="required" style="color: #F00; font-weight: bold;">*</div>';
                        $html .= '<input type="hidden" name="profile_attribute_mapping['.$key.'][is_mandatory]" value="1"/>';
                    } else {
                        $html .= '<div class="required" style="color: #F00; font-weight: bold;">*</div>';
                        $html .= '<input type="hidden" name="profile_attribute_mapping['.$key.'][is_mandatory]" value="0"/>';
                    }
                    $html .= '<input type="hidden" name="profile_attribute_mapping['.$key.'][attribute_type]" value="'.$attribute['attribute_type'].'"/>';
                    $html .= '<input type="hidden" name="profile_attribute_mapping['.$key.'][input_type]" value="'.$attribute['input_type'].'"/>';
                    $html .= '<select name="profile_attribute_mapping['.$key.'][shopee_attribute]" class="form-control">';

                    $mapped_options = false;
                    $store_selected_option = false;
                    $default_values_selected = false;
                    $default_values_id_selected = false;
                    $shoppee_selected_option = false;
                    if(isset($mapped_attributes_options[$attribute['attribute_id']]) && isset($mapped_attributes_options[$attribute['attribute_id']]['option']) && isset($mapped_attributes_options[$attribute['attribute_id']]['option'])) {
                        $mapped_options = $mapped_attributes_options[$attribute['attribute_id']]['option'];

                        if(is_array($mapped_options) && !empty($mapped_options)){
                            $mapped_options = array_filter($mapped_options);
                            $mapped_options = array_values($mapped_options);
                        }
                        
                        if(isset($mapped_attributes_options[$attribute['attribute_id']]['store_attribute']) && $mapped_attributes_options[$attribute['attribute_id']]['store_attribute'])
                            $store_selected_option = $mapped_attributes_options[$attribute['attribute_id']]['store_attribute'];

                        if($mapped_attributes_options[$attribute['attribute_id']]['shopee_attribute'])
                            $shoppee_selected_option = $mapped_attributes_options[$attribute['attribute_id']]['shopee_attribute'];

                        if($mapped_attributes_options[$attribute['attribute_id']]['default_values'])
                            $default_values_selected = $mapped_attributes_options[$attribute['attribute_id']]['default_values'];

                        if($mapped_attributes_options[$attribute['attribute_id']]['default_value_id'])
                            $default_values_id_selected = $mapped_attributes_options[$attribute['attribute_id']]['default_value_id'];
                    }

                    if(!$attribute['is_mandatory'])
                    $html .= '<option value=""></option>';
                    if(isset($attributes_options) && is_array($attributes_options) && $attributes_options)
                    {
                        foreach ($attributes_options as $attribute_option) 
                        {
                            if($shoppee_selected_option && ($attribute_option['attribute_id']==$shoppee_selected_option)) {
                                $html .= '<option selected="selected" value="'.$attribute_option['attribute_id'].'">';
                                $html .= $attribute_option['attribute_name'];
                                $html .= '</option>';
                            } else if($attribute['is_mandatory'] && ($attribute_option['attribute_id']==$attribute['attribute_id'])){
                                $html .= '<option selected="selected" value="'.$attribute_option['attribute_id'].'">';
                                $html .= $attribute_option['attribute_name'];
                                $html .= '</option>';
                            } elseif($attribute_option['attribute_id']==$attribute['attribute_id']) {
                                $html .= '<option selected="selected" value="'.$attribute_option['attribute_id'].'">';
                                $html .= $attribute_option['attribute_name'];
                                $html .= '</option>';
                            } else {
                                $html .= '<option value="'.$attribute_option['attribute_id'].'">';
                                $html .= $attribute_option['attribute_name'];
                                $html .= '</option>';
                            }
                        }
                    }
                    
                    $html .= '</select>';
                    $html .= '</td>';
                    $html .= '<td>';

                    if(isset($default_values_selected) && $default_values_selected)
                    {
                        $html .= '<input type="text" class="form-control" name="profile_attribute_mapping['.$key.'][default_values]" onkeyup="getBrand(this)" data-id="'.$key.'" value ="'.$default_values_selected.'" />';
                    } else {
                        $html .= '<input type="text" class="form-control" name="profile_attribute_mapping['.$key.'][default_values]" onkeyup="getBrand(this)" data-id="'.$key.'" value ="" />';
                    }

                    if(isset($default_values_id_selected) && $default_values_id_selected)
                    {
                        $html .= '<input type="hidden" value="" name="profile_attribute_mapping['.$key.'][default_value_id]" value ="'.$default_values_id_selected.'" >';
                    } else {
                        $html .= '<input type="hidden" value="" name="profile_attribute_mapping['.$key.'][default_value_id]" value ="" >';
                    }
                    $html .= '</td>';
                    $html .= '<td class="left">';

                    // if (in_array($attribute['input_type'], array('DROP_DOWN', 'COMBO_BOX'))) {
                        $html .= '<select id="profile_attribute_mapping['.$key.'][store_attribute]" name="profile_attribute_mapping['.$key.'][store_attribute]" class="form-control">';
                        $html .= '<option value="">Select Mapping</option>';
                        $html .= '<optgroup label="Store Option">';
                        foreach ($options as $option) {
                            if($store_selected_option && ('option-'.$option['option_id']==$store_selected_option)) {
                                $html .= '<option show_option_mapping="1" selected="selected" value="option-'.$option['option_id'].'">';
                                $html .= $option['name'];
                                $html .= '</option>';
                            } else {
                                $html .= '<option show_option_mapping="1" value="option-'.$option['option_id'].'">';
                                $html .= $option['name'];
                                $html .= '</option>';
                            }

                        }
                        $html .= '</optgroup>';
                        $html .= '<optgroup label="Store Attributes">';
                        foreach ($results as $result) {
                            if($store_selected_option && ('attribute-'.$result['attribute_id']==$store_selected_option)) {
                                $html .= '<option show_option_mapping="0" selected="selected" value="attribute-'.$result['attribute_id'].'">';
                                $html .= $result['name'];
                                $html .= '</option>';
                            } else {
                                $html .= '<option show_option_mapping="0" value="attribute-'.$result['attribute_id'].'">';
                                $html .= $result['name'];
                                $html .= '</option>';
                            }
                        }
                        $html .= '</optgroup>';
                        $product_fields = array(); 
                        try{
                            $colomns = $this->db->query("SHOW COLUMNS FROM `".DB_PREFIX."product`;");
                            if($colomns->num_rows) {
                                $product_fields = $colomns->rows;
                            }
                            $this->array_sort_by_column($product_fields, 'Field');
                        }catch(Exception $e){
                            echo $e->getMessage();die;
                        }
                       
                        $html .= '<optgroup label="Product Fields">';
                        foreach ($product_fields as $result) {
                            $show_option_mapping = 0 ;
                            if(in_array($result['Field'],array('manufacturer_id')))
                                $show_option_mapping = 1 ;
                            if($store_selected_option && ('product-'.$result['Field']==$store_selected_option)) {
                                $html .= '<option show_option_mapping="'.$show_option_mapping.'" selected="selected" value="product-'.$result['Field'].'">';
                                $html .= ucfirst(str_replace('_', ' ', $result['Field']));
                                $html .= '</option>';
                            } else {
                                $html .= '<option show_option_mapping="'.$show_option_mapping.'" value="product-'.$result['Field'].'">';
                                $html .= ucfirst(str_replace('_', ' ', $result['Field']));
                                $html .= '</option>';
                            }
                        }
                        $html .= '</optgroup>';
                        $html .= '</select>';                  
                        $option_html = '';
                        $option_html .= '<a style="margin-left:1%;" class="center button" onclick="toggleOptions(' . $key . ')"> Map Option(s)</a><div style="display:none;" id="panel' . $key . '">';
                        $option_html .= '<table class="table table-bordered" id="option_mapping' . $key . '">';

                        $option_html .= '<thead>';
                        $option_html .= '<tr>';
                        $option_html .= '<td class="center">';
                        $option_html .= 'Store Option';
                        $option_html .= '</td>';
                        $option_html .= '<td class="center">';
                        $option_html .= 'Shopee Option';
                        $option_html .= '</td>';
                        $option_html .= '</tr>';
                        $option_html .= '<tr>';
                        $option_html .= '<td>';
                        $option_html .= '<input type="text" name="profile_attribute_mapping['.$key.'][option][store_attribute]" onkeyup="getStoreOptions(this)" data-id="'.$key.'" class="form-control" />';
                        $option_html .= '<input type="hidden" class="text-left" name="profile_attribute_mapping['.$key.'][option][store_attribute_id]"  />';
                        $option_html .= '</td>';
                        $option_html .= '<td>';
                        $option_html .= '<input type="text" name="profile_attribute_mapping['.$key.'][option][shopee_attribute]" onkeyup="getOptions(this)" data-id="'.$key.'" class="form-control" />';
                        $option_html .= '</td>';
                        $option_html .= '<td>';
                        $option_html .= '<button type="button" class="btn btn-primary" id="add_attribute" onclick="addAttribute(this,'.$key.');" ><i class="fa fa-plus" aria-hidden="true"></i></button>';
                        $option_html .= '</td>';
                        $option_html .= '</tr>';
                        $option_html .= '</thead>';
                        $option_html .= '<tbody>';

                        if(isset($mapped_options) && is_array($mapped_options) && $mapped_options)
                        {
                            foreach ($mapped_options as $key_p => $value) 
                            {
                                $option_html .= '<tr id="attribute-row'.$key.$key_p.'">';
                                $option_html .= '<td>';
                                if(isset($value['store_attribute']) && !empty($value['store_attribute']))
                                {
                                    $option_html .= '<input type="text"  name="profile_attribute_mapping['.$key.'][option]['.$key_p.'][store_attribute]" value="'.$value['store_attribute'].'" class="form-control" />';
                                    $option_html .= '<input type="hidden" name="profile_attribute_mapping['.$key.'][option]['.$key_p.'][store_attribute_id]" value="'.$value['store_attribute_id'].'"/>';
                                }
                                $option_html .= '</td>';
                                $option_html .= '<td>';
                                if(isset($value['shopee_attribute']) && !empty($value['shopee_attribute']))
                                {
                                    $option_html .= '<input type="text"  name="profile_attribute_mapping['.$key.'][option]['.$key_p.'][shopee_attribute]" value="'.$value['shopee_attribute'].'" class="form-control">';
                                }
                                
                                $option_html .= '</td>';
                                $option_html .= '<td>';
                                $option_html .= '<a onclick="$(\'#attribute-row'.$key.$key_p.'\').remove();" class="btn btn-danger"><i class="fa fa-trash" aria-hidden="true"></i></a>';
                                $option_html .= '</td>';
                                $option_html .= '</tr>';
                            }
                        }
                        $option_html .= '</tbody>';
                        $option_html .= '</table>';
                        $option_html .= '</div>';
                        $html .= $option_html;
                    // } else {
                    //     if (isset($mapped_attributes_options[$key]['store_attribute'])) {
                    //         $html .= '<input type="text" value="'.$mapped_attributes_options[$key]['store_attribute'].'" name="profile_attribute_mapping['.$key.'][store_attribute]" onkeyup="getBrand(this)" data-id="'.$key.'" class="form-control" />';
                    //     } else {
                    //         $html .= '<input type="text" class="form-control" name="profile_attribute_mapping['.$key.'][store_attribute]" onkeyup="getBrand(this)" data-id="'.$key.'" />';
                    //     }
                    // }

                    $html .= '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<div style="color: red; font-style: bold;">';
                $html .= 'No Attributes found for this Category!';
                $html .= '</div>';
            }
            
            $this->response->setOutput($html);
        } else {
            $this->response->setOutput($html);
        }
    }

    public function brandAuto()
    {
        $returnResponse = array();
        $this->load->model('extension/module/cedshopee/category');
        $data = $this->request->get;
        if (isset($data['filter_name']) && !empty($data['filter_name']) && isset($data['attribute_id']) && !empty($data['attribute_id']) && isset($data['catId']) && !empty($data['catId'])) {
            $attribute_id = $data['attribute_id']; 
            $returnResponse = $this->model_extension_module_cedshopee_category->getBrands($data['catId'],$attribute_id, $data['filter_name']);
        }
        $this->response->setOutput(json_encode($returnResponse));
    }

    public function getStoreOptions()
    {
        $returnResponse = array();
        $this->load->model('extension/module/cedshopee/category');
        $data = $this->request->get;
        if (isset($data['filter_name']) && !empty($data['filter_name']) && isset($data['attribute_id']) && !empty($data['attribute_id']) && isset($data['catId']) && !empty($data['catId'])) {
            $attribute_id = $data['attribute_id']; 
            $type_array = explode('-', $attribute_id );
            if (isset($type_array['0']) && ($type_array['0']=='product')){
                $this->load->model('catalog/manufacturer');
                $returnResponse = $this->model_catalog_manufacturer->getManufacturers(array('filter_name' => $data['filter_name']));
            } else if (isset($type_array['0']) && ($type_array['0']=='option')){
                $returnResponse = $this->model_extension_module_cedshopee_category->getStoreOptions($data['catId'],$type_array['1'], $data['filter_name']);
            }
            
        }
        $this->response->setOutput(json_encode($returnResponse));
    }

    public function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key=> $row) {
            $sort_col[$key] = $row[$col];
        }

        array_multisort($sort_col, $dir, $arr);
    }

    public function fetch() {

        $this->language->load('extension/module/cedshopee/category');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/cedshopee/category');
        $this->load->library('cedshopee');
        $cedshopee = Cedshopee::getInstance($this->registry);
        $response = $cedshopee->postRequest('item/categories/get', array());

        if (!isset($response['error']) && isset($response['categories'])) {
            $this->session->data['success'] = $this->language->get('text_success');
            $this->model_extension_module_cedshopee_category->addShopeeCategories($response['categories']);
        } else if (isset($response['msg'])) {
            $this->error['warning'] = $response['msg'];
        } else {
            $this->error['warning'] = 'No response from by shopee.com';
        }
        $this->getList();
    }
    public function delete() {
        $this->language->load('extension/module/cedshopee/category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/cedshopee/category');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $filter_group_id) {
                $this->model_extension_module_cedshopee_category->deleteCategory($filter_group_id);
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

            $this->response->redirect($this->url->link('extension/module/cedshopee/category', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
        }

        $this->getList();
    }
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/cedshopee/category')) {
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