<?php 

/**
 * 
 */

include_once(DIR_SYSTEM.'library/barcodeGenerator/vendor/autoload.php');
include_once(DIR_SYSTEM.'library/qrcodeGenerator/vendor/autoload.php');

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;

class ControllerExtensionModuleCedshopeeShippingLabel extends Controller
{
	
	// function __construct(argument)
	// {
	// 	# code...
	// }

	public function index() {
		$data = array();

		if (isset($this->request->get['order_id'])) {
			$order_id = $this->request->get['order_id'];
		}

		$sql = $this->db->query("SELECT order_data FROM `" . DB_PREFIX . "cedshopee_order` WHERE opencart_order_id = '" . $order_id . "'");

		if($sql->num_rows) {
			$shopee_order_info = json_decode($sql->row['order_data'],true);

			if(isset($shopee_order_info['tracking_no']) && $shopee_order_info['tracking_no']){
				$tracking_number = $shopee_order_info['tracking_no'];
			}elseif(isset($this->request->get['tracking_no']) && $this->request->get['tracking_no']){
				$tracking_number = $this->request->get['tracking_no'];
			}

			if(isset($tracking_number) && isset($this->request->get['route_code']) && $this->request->get['route_code']) {
				$route_code = $this->request->get['route_code'];
				// $sql = array(
				// 	'create_time' => 1570205718,
				// 	'ship_by_date' => 1603857646,
				// 	'tracking_no' => 620877808501,
				// 	'currency' => 'PHP',
				// 	'total_amount' => 11.25,
				// 	'country' => 'MY',
				// ); 

				$qrCode = new QrCode('https://bit.ly/2vLdDwL');

				$qrCode->setSize(60);
				$qrCode->setMargin(1); 

				// Set advanced options
				$qrCode->setWriterByName('png');
				$qrCode->setEncoding('ISO-8859-1');
				$qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH());
				$qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
				$qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
				// $qrCode->setLabel('', 16, DIR_SYSTEM.'library/qrcodeGenerator/assets/fonts/noto_sans.otf', LabelAlignment::CENTER());
				// $qrCode->setLogoPath(DIR_SYSTEM.'library/qrcodeGenerator/assets/images/symfony.png');
				// $qrCode->setLogoSize(150, 200);
				$qrCode->setValidateResult(false);

				// Round block sizes to improve readability and make the blocks sharper in pixel based outputs (like png).
				// There are three approaches:
				$qrCode->setRoundBlockSize(true, QrCode::ROUND_BLOCK_SIZE_MODE_MARGIN); // The size of the qr code is shrinked, if necessary, but the size of the final image remains unchanged due to additional margin being added (default)
				$qrCode->setRoundBlockSize(true, QrCode::ROUND_BLOCK_SIZE_MODE_ENLARGE); // The size of the qr code and the final image is enlarged, if necessary
				$qrCode->setRoundBlockSize(true, QrCode::ROUND_BLOCK_SIZE_MODE_SHRINK); // The size of the qr code and the final image is shrinked, if necessary

				// Set additional writer options (SvgWriter example)
				$qrCode->setWriterOptions(['exclude_xml_declaration' => true]);

				// Save it to a file
				$file_path = 'qrcodeGenerator/qrcode.png';

				if(!is_dir(DIR_IMAGE.'qrcodeGenerator')){
					mkdir(DIR_IMAGE.'qrcodeGenerator', 0777);
					chmod(DIR_IMAGE.'qrcodeGenerator', 0777);
				}

				$qrCode->writeFile(DIR_IMAGE.$file_path);

				$dataUri = $qrCode->writeDataUri();

				// bar code gernator
				
				$generator = new Picqer\Barcode\BarcodeGeneratorSVG();
				$barcode_order_id = $generator->getBarcode('228668800120001', $generator::TYPE_CODE_128, 1, 30);
				$barcode_route_code = $generator->getBarcode($route_code, $generator::TYPE_CODE_128, 3, 140);
				$barcode_tracking_number = $generator->getBarcode($tracking_number, $generator::TYPE_CODE_128, 3, 140);
				// die;
				$this->load->language('extension/module/cedshopee/shipping_label');

				$data['title'] = $this->language->get('text_shipping');

				if ($this->request->server['HTTPS']) {
					$data['base'] = HTTPS_SERVER;
				} else {
					$data['base'] = HTTP_SERVER;
				}

				$data['direction'] = $this->language->get('direction');
				$data['lang'] = $this->language->get('code');

				$this->load->model('sale/order');

				$this->load->model('catalog/product');

				$this->load->model('setting/setting');

				$data['orders'] = array();

				$orders = array();

			
				$order_info = $this->model_sale_order->getOrder($order_id);

				// Make sure there is a shipping method
				if ($order_info && $order_info['shipping_code']) {
					$store_info = $this->model_setting_setting->getSetting('config', $order_info['store_id']);

					if ($store_info) {
						$store_address = $store_info['config_address'];
						$store_email = $store_info['config_email'];
						$store_telephone = $store_info['config_telephone'];
					} else {
						$store_address = $this->config->get('config_address');
						$store_email = $this->config->get('config_email');
						$store_telephone = $this->config->get('config_telephone');
					}

					if ($order_info['invoice_no']) {
						$invoice_no = $order_info['invoice_prefix'] . $order_info['invoice_no'];
					} else {
						$invoice_no = '';
					}

					if ($order_info['shipping_address_format']) {
						$format = $order_info['shipping_address_format'];
					} else {
						$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
					}

					$find = array(
						'{firstname}',
						'{lastname}',
						'{company}',
						'{address_1}',
						'{address_2}',
						'{city}',
						'{postcode}',
						'{zone}',
						'{zone_code}',
						'{country}'
					);

					$replace = array(
						'firstname' => $order_info['shipping_firstname'],
						'lastname'  => $order_info['shipping_lastname'],
						'company'   => $order_info['shipping_company'],
						'address_1' => $order_info['shipping_address_1'],
						'address_2' => $order_info['shipping_address_2'],
						'city'      => $order_info['shipping_city'],
						'postcode'  => $order_info['shipping_postcode'],
						'zone'      => $order_info['shipping_zone'],
						'zone_code' => $order_info['shipping_zone_code'],
						'country'   => $order_info['shipping_country']
					);

					$shipping_address = str_replace(array("\r\n", "\r", "\n"), ', ', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), ', ', trim(str_replace($find, $replace, $format))));

					$this->load->model('tool/upload');

					$product_data = array();

					$products = $this->model_sale_order->getOrderProducts($order_id);

					foreach ($products as $product) {
						$option_weight = '';

						$product_info = $this->model_catalog_product->getProduct($product['product_id']);

						if ($product_info) {
							$option_data = array();

							$options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

							foreach ($options as $option) {
								if ($option['type'] != 'file') {
									$value = $option['value'];
								} else {
									$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

									if ($upload_info) {
										$value = $upload_info['name'];
									} else {
										$value = '';
									}
								}

								$option_data[] = array(
									'name'  => $option['name'],
									'value' => $value
								);

								$product_option_value_info = $this->model_catalog_product->getProductOptionValue($product['product_id'], $option['product_option_value_id']);

								if ($product_option_value_info) {
									$option_weight = 0;
									if ($product_option_value_info['weight_prefix'] == '+') {
										$option_weight += $product_option_value_info['weight'];
									} elseif ($product_option_value_info['weight_prefix'] == '-') {
										$option_weight -= $product_option_value_info['weight'];
									}
								}
							}

							$product_data[] = array(
								'name'     => $product_info['name'],
								'model'    => $product_info['model'],
								'option'   => $option_data,
								'quantity' => $product['quantity'],
								'location' => $product_info['location'],
								'sku'      => $product_info['sku'],
								'upc'      => $product_info['upc'],
								'ean'      => $product_info['ean'],
								'jan'      => $product_info['jan'],
								'isbn'     => $product_info['isbn'],
								'mpn'      => $product_info['mpn'],
								'weight'   => $this->weight->format(($product_info['weight'] + (float)$option_weight) * $product['quantity'], $product_info['weight_class_id'], $this->language->get('decimal_point'), $this->language->get('thousand_point'))
							);
						}
					}

					$this->load->model('tool/image');

					if (is_file(DIR_IMAGE.$file_path)) {
						$qr_image = $this->model_tool_image->resize($file_path, 60, 60);
					}else{
						$qr_image = '';
					}

					$data['orders'][] = array(
						'order_id'	       => $order_id,
						'order_date'       => date("j M", $shopee_order_info['create_time']),
						'fulfill_date'     => date("j M", $shopee_order_info['ship_by_date']),
						'cod_method'	   => $shopee_order_info['cod'],
						'cod'			   => $shopee_order_info['currency'].' '.$shopee_order_info['total_amount'],
						'consignee'		   => $order_info['firstname'].' '.$order_info['lastname'],
						'origin'		   => $shopee_order_info['country'],
						'qr_code'		   => $qr_image,
						'barcode_order_id' => $barcode_order_id,
						'route_code'       => $route_code,
						'barcode_route_code' => $barcode_route_code,
						'tracking_number'  => $tracking_number,
						'barcode_tracking_number' => $barcode_tracking_number,
						'date_added'       => date($this->language->get('date_format_short'), strtotime($order_info['date_added'])),
						'seller_name'      => $this->config->get('cedshopee_seller_name'),
						'seller_address'    => $this->config->get('cedshopee_seller_add'),
						'seller_contact'  	=> $this->config->get('cedshopee_seller_contact'),
						'email'            => $order_info['email'],
						'telephone'        => $order_info['telephone'],
						'shipping_address' => $shipping_address,
						'shipping_method'  => $order_info['shipping_method'],
						'product'          => $product_data,
					);
				}
			}
		}

		$this->response->setOutput($this->load->view('extension/module/cedshopee/shipping_label', $data));
	}
}