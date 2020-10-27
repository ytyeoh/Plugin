<?php
class ControllerExtensionModuleCedshopeeColumnLeft extends Controller {
    public function eventMenu($route, &$data)
    {
        $this->load->language('extension/module/cedshopee');
        
        $cedshopee_menu = array();
                 
	     // Category
	     $cedshopee_menu[] = array(
				   'name'	   => $this->language->get('ced_shopee_category_text'),
				   'href'     => $this->url->link('extension/module/cedshopee/category', 'user_token=' . $this->session->data['user_token'], true),
				   'children' => array()
			);

		  // Profile
	     $cedshopee_menu[] = array(
				   'name'	   => $this->language->get('ced_shopee_profile_text'),
				   'href'     => $this->url->link('extension/module/cedshopee/profile', 'user_token=' . $this->session->data['user_token'], true),
				   'children' => array()
			);

		// Product
	     $cedshopee_menu[] = array(
				   'name'	   => $this->language->get('ced_shopee_product_text'),
				   'href'     => $this->url->link('extension/module/cedshopee/product', 'user_token=' . $this->session->data['user_token'], true),
				   'children' => array()
			);

		// shopee order menu
		$cedshopee_menu_order=array();
		
			$cedshopee_menu_order[] = array(
				'name'	   => $this->language->get('ced_shopee_import_order_text'),
				'href'     => $this->url->link('extension/module/cedshopee/order', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
			$cedshopee_menu_order[] = array(
				'name'	   => $this->language->get('ced_shopee_fail_order_text'),
				'href'     => $this->url->link('extension/module/cedshopee/order/fail', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
			
			$cedshopee_menu[] = array(
				'name'	   => $this->language->get('ced_shopee_order_text'),
				'href'     => '',
				'children' => $cedshopee_menu_order
			);

	 // Discount
	 $cedshopee_menu[] = array(
				'name'	   => $this->language->get('ced_shopee_discount_text'),
				'href'     => $this->url->link('extension/module/cedshopee/discount', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);
	 
	 // Return
	 $cedshopee_menu[] = array(
				'name'	   => $this->language->get('ced_shopee_return_text'),
				'href'     => $this->url->link('extension/module/cedshopee/return', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);

	 // Logistics
	 $cedshopee_menu[] = array(
				'name'	   => $this->language->get('ced_shopee_logistics_text'),
				'href'     => $this->url->link('extension/module/cedshopee/logistics', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);

	 // Logistics
	 $cedshopee_menu[] = array(
				'name'	   => $this->language->get('ced_shopee_log_text'),
				'href'     => $this->url->link('extension/module/cedshopee/log', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);

	  // Configuration
	 $cedshopee_menu[] = array(
				'name'	   => $this->language->get('ced_shopee_config_text'),
				'href'     => $this->url->link('extension/module/cedshopee', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);

		$data['menus'][] = array(
		'id'       => 'menu-shopee',
		'icon'	   => 'icon-industry',
		'name'	   => $this->language->get('ced_shopee'),
		'href'     => '',
		'children' => $cedshopee_menu
		);
        
    }
}
