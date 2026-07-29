<?php
/* Mailmodo integration for Green Forms */
if (!defined('UAP_CORE') && !defined('ABSPATH')) exit;
class lepopup_mailmodo_class {
	var $default_parameters = array(
		"api-key" => "",
		"list" => "",
		"list-id" => "",
		"fields" => array(
			'email' => '',
            'first_name' => '',
            'last_name' => '',
            'name' => '',
            'gender' => '',
            'phone' => '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'postal_code' => '',
            'designation' => '',
            'company' => '',
            'industry' => '',
            'description' => '',
		)
	);
	var $fields_meta;
	function __construct() {
		$this->fields_meta = array(
			'email' => array('title' => esc_html__('Email', 'lepopup'), 'description' => esc_html__('Email address of the contact.', 'lepopup')),
			'first_name' => array('title' => esc_html__('First name', 'lepopup'), 'description' => esc_html__('First name of the contact.', 'lepopup')),
			'last_name' => array('title' => esc_html__('Last name', 'lepopup'), 'description' => esc_html__('Last name of the contact.', 'lepopup')),
            'name' => array('title' => esc_html__('Full name', 'lepopup'), 'description' => esc_html__('Full name of the contact.', 'lepopup')),
            'gender' => array('title' => esc_html__('Gender', 'lepopup'), 'description' => esc_html__('Gender of the contact.', 'lepopup')),
            'phone' => array('title' => esc_html__('Phone #', 'lepopup'), 'description' => esc_html__('Phone number of the contact.', 'lepopup')),
            'address1' => array('title' => esc_html__('Address 1', 'lepopup'), 'description' => esc_html__('Address 1 of the contact.', 'lepopup')),
            'address2' => array('title' => esc_html__('Address 2', 'lepopup'), 'description' => esc_html__('Address 2 of the contact.', 'lepopup')),
            'city' => array('title' => esc_html__('City', 'lepopup'), 'description' => esc_html__('City of the contact.', 'lepopup')),
            'state' => array('title' => esc_html__('State', 'lepopup'), 'description' => esc_html__('State of the contact.', 'lepopup')),
            'country' => array('title' => esc_html__('Country', 'lepopup'), 'description' => esc_html__('Country of the contact.', 'lepopup')),
            'postal_code' => array('title' => esc_html__('Postal code', 'lepopup'), 'description' => esc_html__('Postal code of the contact.', 'lepopup')),
            'designation' => array('title' => esc_html__('Designation', 'lepopup'), 'description' => esc_html__('Designation of the contact.', 'lepopup')),
            'company' => array('title' => esc_html__('Company', 'lepopup'), 'description' => esc_html__('Company of the contact.', 'lepopup')),
            'industry' => array('title' => esc_html__('Industry', 'lepopup'), 'description' => esc_html__('Industry of the contact.', 'lepopup')),
            'description' => array('title' => esc_html__('Description', 'lepopup'), 'description' => esc_html__('Description of the contact.', 'lepopup')),
		);
		if (is_admin()) {
			add_filter('lepopup_providers', array(&$this, 'providers'), 10, 1);
			add_action('wp_ajax_lepopup-mailmodo-settings-html', array(&$this, "admin_settings_html"));
			add_action('wp_ajax_lepopup-mailmodo-list', array(&$this, "admin_lists"));
		}
		add_filter('lepopup_integrations_do_mailmodo', array(&$this, 'front_submit'), 10, 2);
	}
	
	function providers($_providers) {
		if (!array_key_exists("mailmodo", $_providers)) $_providers["mailmodo"] = esc_html__('Mailmodo', 'lepopup');
		return $_providers;
	}
	
	function admin_settings_html() {
		global $wpdb, $lepopup;
		if (current_user_can('manage_options')) {
			if (array_key_exists('data', $_REQUEST)) {
				$data = json_decode(base64_decode(trim(stripslashes($_REQUEST['data']))), true);
				if (is_array($data)) $data = array_merge($this->default_parameters, $data);
				else $data = $this->default_parameters;
			} else $data = $this->default_parameters;
			$checkbox_id = $lepopup->random_string();
			$html = '
			<div class="lepopup-properties-item">
				<div class="lepopup-properties-label">
					<label>'.esc_html__('API Key', 'lepopup').'</label>
				</div>
				<div class="lepopup-properties-tooltip">
					<i class="fas fa-question-circle lepopup-tooltip-anchor"></i>
					<div class="lepopup-tooltip-content">'.esc_html__('Enter your Mailmodo API Key.', 'lepopup').'</div>
				</div>
				<div class="lepopup-properties-content">
					<input type="text" name="api-key" value="'.esc_html($data['api-key']).'" />
					<label class="lepopup-integrations-description">'.sprintf(esc_html__('Find your Mailmodo API Key %shere%s.', 'lepopup'), '<a href="https://manage.mailmodo.com/app/settings/apikey" target="_blank">', '</a>').'</label>
				</div>
			</div>
			<div class="lepopup-properties-item">
				<div class="lepopup-properties-label">
					<label>'.esc_html__('List ID', 'lepopup').'</label>
				</div>
				<div class="lepopup-properties-tooltip">
					<i class="fas fa-question-circle lepopup-tooltip-anchor"></i>
					<div class="lepopup-tooltip-content">'.esc_html__('Select desired List ID.', 'lepopup').'</div>
				</div>
				<div class="lepopup-properties-content">
					<div class="lepopup-properties-group lepopup-integrations-ajax-options">
						<input type="text" name="list" value="'.esc_html($data['list']).'" data-deps="api-key" readonly="readonly" />
						<input type="hidden" name="list-id" value="'.esc_html($data['list-id']).'" />
					</div>
				</div>
			</div>
			<div class="lepopup-properties-item">
				<div class="lepopup-properties-label">
					<label>'.esc_html__('Fields', 'lepopup').'</label>
				</div>
				<div class="lepopup-properties-tooltip">
					<i class="fas fa-question-circle lepopup-tooltip-anchor"></i>
					<div class="lepopup-tooltip-content">'.esc_html__('Map popup fields to Mailmodo fields.', 'lepopup').'</div>
				</div>
				<div class="lepopup-properties-content">
					<div class="lepopup-properties-pure lepopup-integrations-static-inline">
						<table>';
		foreach ($this->default_parameters['fields'] as $key => $value) {
			$html .= '
							<tr>
								<th>'.esc_html($this->fields_meta[$key]['title']).'</td>
								<td>
									<div class="lepopup-input-shortcode-selector">
										<input type="text" name="fields['.$key.']" value="'.esc_html(array_key_exists($key, $data['fields']) ? $data['fields'][$key] : $value).'" class="widefat" />
										<div class="lepopup-shortcode-selector" onmouseover="lepopup_shortcode_selector_set(this)";><span><i class="fas fa-code"></i></span></div>
									</div>
									<label class="lepopup-integrations-description">'.esc_html($this->fields_meta[$key]['description'].' ('.$key.')').'</label>
								</td>
							</tr>';
		}
		$html .= '				
						</table>
					</div>
				</div>
			</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $html;
			echo json_encode($return_object);
		}
		exit;
	}
	
	function admin_lists() {
		global $wpdb, $lepopup;
		$lists = array();
		if (current_user_can('manage_options')) {
			if (array_key_exists('deps', $_REQUEST)) {
				$deps = json_decode(base64_decode(trim(stripslashes($_REQUEST['deps']))), true);
				if (!is_array($deps)) $deps = null;
			} else $deps = null;

			if (!is_array($deps) || !array_key_exists('api-key', $deps) || empty($deps['api-key'])) {
				$return_object = array('status' => 'ERROR', 'message' => esc_html__('Invalid API credentials.', 'lepopup'));
				echo json_encode($return_object);
				exit;
			}
			
			$result = $this->connect($deps['api-key'], 'getAllContactLists');
			if (is_array($result)) {
                if (array_key_exists('Error', $result)) {
					$return_object = array('status' => 'ERROR', 'message' => $result['Error']);
					echo json_encode($return_object);
					exit;
                }
				if (!array_key_exists('listDetails', $result) || sizeof($result['listDetails']) == 0) {
					$return_object = array('status' => 'ERROR', 'message' => esc_html__('No lists found.', 'lepopup'));
					echo json_encode($return_object);
					exit;
				}
				foreach($result['listDetails'] as $list) {
					if (is_array($list)) {
						if (array_key_exists('name', $list)) {
							$lists[$list['name']] = $list['name'];
						}
					}
				}
			} else {
				$return_object = array('status' => 'ERROR', 'message' => esc_html__('Invalid server response.', 'lepopup'));
				echo json_encode($return_object);
				exit;
			}
			if (empty($lists)) {
				$return_object = array('status' => 'ERROR', 'message' => esc_html__('No lists found.', 'lepopup'));
				echo json_encode($return_object);
				exit;
			}
			
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['items'] = $lists;
			echo json_encode($return_object);
		}
		exit;
	}

	function front_submit($_result, $_data) {
		global $wpdb, $lepopup;
		$data = array_merge($this->default_parameters, $_data);
		if (empty($data['api-key']) || empty($data['list-id'])) return $_result;
		if (empty($data['fields']) || !is_array($data['fields'])) return $_result;
		if (empty($data['fields']['email']) || !preg_match("/^[_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $data['fields']['email'])) return $_result;

		$post_data = array(
			'email' => $data['fields']['email'],
            'listName' => $data['list-id'],
			'data' => array()
		);
		foreach ($data['fields'] as $key => $value) {
			if (!empty($value) && $key != 'email') {
				$post_data['data'][$key] = $value;
			}
		}
		
        $result = $this->connect($data['api-key'], 'addToList', $post_data);

		return $_result;
	}
	
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		try {
			$url = 'https://api.mailmodo.com/api/v1/'.ltrim($_path, '/');
			$header = array(
				'Content-Type: application/json',
				'Accept: application/json',
				'mmApiKey: '.$_api_key
			);
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			curl_close($curl);
			$result = json_decode($response, true);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}
}
$lepopup_mailmodo = new lepopup_mailmodo_class();
?>