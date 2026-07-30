<?php
/* Sender integration for Green Popups */
if ( ! defined( 'ABSPATH' ) ) exit;
class lepopup_sender_class {
	var $default_parameters = array(
		"api-key" => "",
		"groups" => array(),
		"fields" => array('email' => '', 'firstname' => '', 'lastname' => '', 'phone' => '')
	);
	
	function __construct() {
		if (is_admin()) {
			add_filter('lepopup_providers', array(&$this, 'providers'), 10, 1);
			add_action('wp_ajax_lepopup-sender-settings-html', array(&$this, "admin_settings_html"));
			add_action('wp_ajax_lepopup-sender-fields', array(&$this, "admin_fields_html"));
			add_action('wp_ajax_lepopup-sender-groups', array(&$this, "admin_groups_html"));
		}
		add_filter('lepopup_integrations_do_sender', array(&$this, 'front_submit'), 10, 2);
	}
	
	function providers($_providers) {
		if (!array_key_exists("sender", $_providers)) $_providers["sender"] = esc_html__('Sender', 'lepopup');
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
					<label>'.esc_html__('API Access Token', 'lepopup').'</label>
				</div>
				<div class="lepopup-properties-tooltip">
					<i class="fas fa-question-circle lepopup-tooltip-anchor"></i>
					<div class="lepopup-tooltip-content">'.esc_html__('Enter your Sender API Access Token.', 'lepopup').'</div>
				</div>
				<div class="lepopup-properties-content">
					<input type="text" name="api-key" value="'.esc_html($data['api-key']).'" />
					<label class="lepopup-integrations-description">'.sprintf(esc_html__('Find your Sender API Access Token %shere%s.', 'lepopup'), '<a href="https://app.sender.net/settings/tokens" target="_blank">', '</a>').'</label>
				</div>
			</div>
			<div class="lepopup-properties-item">
				<div class="lepopup-properties-label">
					<label>'.esc_html__('Fields', 'lepopup').'</label>
				</div>
				<div class="lepopup-properties-tooltip">
					<i class="fas fa-question-circle lepopup-tooltip-anchor"></i>
					<div class="lepopup-tooltip-content">'.esc_html__('Map form fields to Sender fields.', 'lepopup').'</div>
				</div>
				<div class="lepopup-properties-content">
					<div class="lepopup-properties-pure lepopup-integrations-static-inline">
						<table>
							<tr>
								<th>'.esc_html__('Email', 'lepopup').'</th>
								<td>
									<div class="lepopup-input-shortcode-selector">
										<input type="text" name="fields[email]" value="'.esc_html(array_key_exists('email', $data['fields']) ? $data['fields']['email'] : '').'" class="widefat" />
										<div class="lepopup-shortcode-selector" onmouseover="lepopup_shortcode_selector_set(this)";><span><i class="fas fa-code"></i></span></div>
									</div>
									<label class="lepopup-integrations-description">'.esc_html__('Email Address.', 'lepopup').'</label>
								</td>
							</tr>
							<tr>
								<th>'.esc_html__('First name', 'lepopup').'</th>
								<td>
									<div class="lepopup-input-shortcode-selector">
										<input type="text" name="fields[firstname]" value="'.esc_html(array_key_exists('firstname', $data['fields']) ? $data['fields']['firstname'] : '').'" class="widefat" />
										<div class="lepopup-shortcode-selector" onmouseover="lepopup_shortcode_selector_set(this)";><span><i class="fas fa-code"></i></span></div>
									</div>
									<label class="lepopup-integrations-description">'.esc_html__('First name.', 'lepopup').'</label>
								</td>
							</tr>
							<tr>
								<th>'.esc_html__('Last name', 'lepopup').'</th>
								<td>
									<div class="lepopup-input-shortcode-selector">
										<input type="text" name="fields[lastname]" value="'.esc_html(array_key_exists('lastname', $data['fields']) ? $data['fields']['lastname'] : '').'" class="widefat" />
										<div class="lepopup-shortcode-selector" onmouseover="lepopup_shortcode_selector_set(this)";><span><i class="fas fa-code"></i></span></div>
									</div>
									<label class="lepopup-integrations-description">'.esc_html__('Last name.', 'lepopup').'</label>
								</td>
							</tr>
							<tr>
								<th>'.esc_html__('Phone', 'lepopup').'</th>
								<td>
									<div class="lepopup-input-shortcode-selector">
										<input type="text" name="fields[phone]" value="'.esc_html(array_key_exists('phone', $data['fields']) ? $data['fields']['phone'] : '').'" class="widefat" />
										<div class="lepopup-shortcode-selector" onmouseover="lepopup_shortcode_selector_set(this)";><span><i class="fas fa-code"></i></span></div>
									</div>
									<label class="lepopup-integrations-description">'.esc_html__('Phone number.', 'lepopup').'</label>
								</td>
							</tr>
						</table>
					</div>
					<div class="lepopup-properties-pure lepopup-integrations-ajax-inline">';
			if (!empty($data['api-key'])) {
				$fields_data = $this->get_fields_html($data['api-key'], $data['fields']);
				if ($fields_data['status'] == 'OK') $html .= $fields_data['html'];
			}
			$html .= '
					</div>
					<a class="lepopup-button lepopup-button-small" onclick="return lepopup_integrations_ajax_inline_html(this);" data-inline="fields" data-deps="api-key"><i class="fas fa-download"></i><label>'.esc_html__('Load Fields', 'lepopup').'</label></a>
				</div>
			</div>
			<div class="lepopup-properties-item">
				<div class="lepopup-properties-label">
					<label>'.esc_html__('Groups', 'lepopup').'</label>
				</div>
				<div class="lepopup-properties-tooltip">
					<i class="fas fa-question-circle lepopup-tooltip-anchor"></i>
					<div class="lepopup-tooltip-content">'.esc_html__('Select groups.', 'lepopup').'</div>
				</div>
				<div class="lepopup-properties-content">
					<div class="lepopup-properties-pure lepopup-integrations-ajax-inline">';
			if (!empty($data['api-key'])) {
				$groups_data = $this->get_groups_html($data['api-key'], $data['groups']);
				if ($groups_data['status'] == 'OK') $html .= $groups_data['html'];
			}
			$html .= '
					</div>
					<a class="lepopup-button lepopup-button-small" onclick="return lepopup_integrations_ajax_inline_html(this);" data-inline="groups" data-deps="api-key"><i class="fas fa-download"></i><label>'.esc_html__('Load Groups', 'lepopup').'</label></a>
				</div>
			</div>';
			$return_object = array();
			$return_object['status'] = 'OK';
			$return_object['html'] = $html;
			echo json_encode($return_object);
		}
		exit;
	}
	
	function admin_fields_html() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (array_key_exists('deps', $_REQUEST)) {
				$deps = json_decode(base64_decode(trim(stripslashes($_REQUEST['deps']))), true);
				if (!is_array($deps)) $deps = null;
			} else $deps = null;
			if (!is_array($deps) || !array_key_exists('api-key', $deps) || empty($deps['api-key'])) {
				$return_object = array('status' => 'ERROR', 'message' => esc_html__('Invalid API Access Token.', 'lepopup'));
				echo json_encode($return_object);
				exit;
			}
			$return_object = $this->get_fields_html($deps['api-key'], $this->default_parameters['fields']);
			echo json_encode($return_object);
		}
		exit;
	}

	function get_fields_html($_key, $_fields) {
		global $wpdb, $lepopup;
		$result = $this->connect($_key, 'fields?limit=100');
		$fields_html = '';
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('message', $result)) {
				return array('status' => 'ERROR', 'message' => $result['message']);
			} else {
				if (array_key_exists('data', $result) && sizeof($result['data']) > 0) {
					$fields_html = '
			<table>';
					foreach ($result['data'] as $field) {
						if (is_array($field)) {
							if (array_key_exists('title', $field) && array_key_exists('name', $field)) {
                                if (in_array($field['name'], array('{$email}', '{$firstname}', '{$lastname}', '{$phone}'))) continue;
								$fields_html .= '
				<tr>
					<th>'.esc_html($field['title']).'</th>
					<td>
						<div class="lepopup-input-shortcode-selector">
							<input type="text" name="fields['.esc_html($field['name']).']" value="'.esc_html(array_key_exists($field['name'], $_fields) ? $_fields[$field['name']] : '').'" class="widefat" />
							<div class="lepopup-shortcode-selector" onmouseover="lepopup_shortcode_selector_set(this)";><span><i class="fas fa-code"></i></span></div>
						</div>
						<label class="lepopup-integrations-description">'.esc_html($field['title']).' ('.esc_html($field['name']).').</label>
					</td>
				</tr>';
							}
						}
					}
					$fields_html .= '
			</table>';
				} else {
					return array('status' => 'ERROR', 'message' => esc_html__('No fields found.', 'lepopup'));
				}
			}
		} else {
			return array('status' => 'ERROR', 'message' => esc_html__('Inavlid API Access Token.', 'lepopup'));
		}
		return array('status' => 'OK', 'html' => $fields_html);
	}

	function admin_groups_html() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (array_key_exists('deps', $_REQUEST)) {
				$deps = json_decode(base64_decode(trim(stripslashes($_REQUEST['deps']))), true);
				if (!is_array($deps)) $deps = null;
			} else $deps = null;
			if (!is_array($deps) || !array_key_exists('api-key', $deps) || empty($deps['api-key'])) {
				$return_object = array('status' => 'ERROR', 'message' => esc_html__('Invalid API Access Token.', 'lepopup'));
				echo json_encode($return_object);
				exit;
			}
			$return_object = $this->get_groups_html($deps['api-key'], $this->default_parameters['groups']);
			echo json_encode($return_object);
		}
		exit;
	}

	function get_groups_html($_key, $_groups) {
		global $wpdb, $lepopup;
		$result = $this->connect($_key, 'groups?limit=100');
		$groups_html = '';
		if (!empty($result) && is_array($result)) {
			if (array_key_exists('message', $result)) {
				return array('status' => 'ERROR', 'message' => $result['message']);
			} else {
				if (array_key_exists('data', $result) && sizeof($result['data']) > 0) {
                    $groups_html .= '
                    <div class="lepopup-properties-pure" style="margin: 5px 0;">';
                    foreach ($result['data'] as $group) {
                        if (array_key_exists($group['id'], $_groups)) $checked = $_groups[$group['id']];
                        else $checked = 'off';
                        $checkbox_id = $lepopup->random_string(16);
                        $groups_html .= '
                        <div class="lepopup-properties-pure" style="margin: 2px 0;">
                            <input class="lepopup-checkbox lepopup-checkbox-classic lepopup-checkbox-medium" id="group-'.esc_html($checkbox_id).'" type="checkbox" value="on" name="groups['.esc_html($group['id']).']"'.($checked == 'on' ? ' checked="checked"' : '').' /><label for="group-'.esc_html($checkbox_id).'"></label> '.esc_html($group['title']).'
                        </div>';
					}
                    $groups_html .= '
                    </div>';
				} else {
					return array('status' => 'ERROR', 'message' => esc_html__('No groups found.', 'lepopup'));
				}
			}
		} else {
			return array('status' => 'ERROR', 'message' => esc_html__('Inavlid API Access Token.', 'lepopup'));
		}
		return array('status' => 'OK', 'html' => $groups_html);
	}
	
	function front_submit($_result, $_data) {
		global $wpdb, $lepopup;
		$data = array_merge($this->default_parameters, $_data);
		if (empty($data['api-key'])) return $_result;
		if (empty($data['fields']) || !is_array($data['fields'])) return $_result;
		if (empty($data['fields']['email']) || !preg_match("/^[_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $data['fields']['email'])) return $_result;

		$output = array('fields' => array(), 'groups' => array());
		foreach ($data['fields'] as $key => $value) {
			if (empty($value)) continue;
            if (array_key_exists($key, $this->default_parameters['fields'])) $output[$key] = $value;
            else $output['fields'][$key] = $value;
		}
        foreach ($data['groups'] as $group => $value) {
            if ($value == 'on') $output['groups'][] = $group;
        }
		
		$result = $this->connect($data['api-key'], 'subscribers', $output, 'POST');

		return $_result;
	}
	
	function connect($_api_key, $_path, $_data = array(), $_method = '') {
		$headers = array(
            'Authorization: Bearer '.$_api_key,
			'Content-Type: application/json;charset=UTF-8',
			'Accept: application/json'
		);
		try {
			$url = 'https://api.sender.net/v2/'.ltrim($_path, '/');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if (!empty($_data)) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($_data));
			}
			if (!empty($_method)) {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
			}
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
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
$lepopup_sender = new lepopup_sender_class();
?>