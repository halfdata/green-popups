<?php
/* Interspire integration for Green Forms */
if ( ! defined( 'ABSPATH' ) ) exit;
class lepopup_interspire_class {
	var $default_parameters = array(
		"url" => "",
		"username" => "",
		"token" => "",
		"list" => "",
		"list-id" => "",
		"fields" => array('EMAIL' => '')
	);
	
	function __construct() {
		if (is_admin()) {
			add_filter('lepopup_providers', array(&$this, 'providers'), 10, 1);
			add_action('wp_ajax_lepopup-interspire-settings-html', array(&$this, "admin_settings_html"));
			add_action('wp_ajax_lepopup-interspire-list', array(&$this, "admin_lists"));
			add_action('wp_ajax_lepopup-interspire-fields', array(&$this, "admin_fields_html"));
			add_action('wp_ajax_lepopup-interspire-groups', array(&$this, "admin_groups_html"));
		}
		add_filter('lepopup_integrations_do_interspire', array(&$this, 'front_submit'), 10, 2);
	}
	
	function providers($_providers) {
		if (!array_key_exists("interspire", $_providers)) $_providers["interspire"] = esc_html__('Interspire', 'lepopup');
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
					<label>'.esc_html__('XML Path', 'lepopup').'</label>
				</div>
				<div class="lepopup-properties-tooltip">
					<i class="fas fa-question-circle lepopup-tooltip-anchor"></i>
					<div class="lepopup-tooltip-content">'.esc_html__('Enter your Interspire XML Path. You can find it in Advanced User Settings.', 'lepopup').'</div>
				</div>
				<div class="lepopup-properties-content">
					<input type="text" name="url" value="'.esc_html($data['url']).'" />
					<label class="lepopup-integrations-description">'.esc_html__('Enter your Interspire XML Path. You can find it in Advanced User Settings.', 'lepopup').'</label>
				</div>
			</div>
			<div class="lepopup-properties-item">
				<div class="lepopup-properties-label">
					<label>'.esc_html__('XML Username', 'lepopup').'</label>
				</div>
				<div class="lepopup-properties-tooltip">
					<i class="fas fa-question-circle lepopup-tooltip-anchor"></i>
					<div class="lepopup-tooltip-content">'.esc_html__('Enter your Interspire XML Username. You can find it in Advanced User Settings.', 'lepopup').'</div>
				</div>
				<div class="lepopup-properties-content">
					<input type="text" name="username" value="'.esc_html($data['username']).'" />
					<label class="lepopup-integrations-description">'.esc_html__('Enter your Interspire XML Username. You can find it in Advanced User Settings.', 'lepopup').'</label>
				</div>
			</div>
			<div class="lepopup-properties-item">
				<div class="lepopup-properties-label">
					<label>'.esc_html__('XML Token', 'lepopup').'</label>
				</div>
				<div class="lepopup-properties-tooltip">
					<i class="fas fa-question-circle lepopup-tooltip-anchor"></i>
					<div class="lepopup-tooltip-content">'.esc_html__('Enter your Interspire XML Token. You can find it in Advanced User Settings.', 'lepopup').'</div>
				</div>
				<div class="lepopup-properties-content">
					<input type="text" name="token" value="'.esc_html($data['token']).'" />
					<label class="lepopup-integrations-description">'.esc_html__('Enter your Interspire XML Token. You can find it in Advanced User Settings.', 'lepopup').'</label>
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
						<input type="text" name="list" value="'.esc_html($data['list']).'" data-deps="url,username,token" readonly="readonly" />
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
					<div class="lepopup-tooltip-content">'.esc_html__('Map form fields to Interspire fields.', 'lepopup').'</div>
				</div>
				<div class="lepopup-properties-content">
					<div class="lepopup-properties-pure lepopup-integrations-static-inline">
						<table>
							<tr>
								<th>'.esc_html__('Email Address', 'lepopup').'</th>
								<td>
									<div class="lepopup-input-shortcode-selector">
										<input type="text" name="fields[EMAIL]" value="'.esc_html(array_key_exists('EMAIL', $data['fields']) ? $data['fields']['EMAIL'] : '').'" class="widefat" />
										<div class="lepopup-shortcode-selector" onmouseover="lepopup_shortcode_selector_set(this)";><span><i class="fas fa-code"></i></span></div>
									</div>
									<label class="lepopup-integrations-description">'.esc_html__('Email Address', 'lepopup').'</label>
								</td>
							</tr>
						</table>
					</div>
					<div class="lepopup-properties-pure lepopup-integrations-ajax-inline">';
			if (!empty($data['url']) && !empty($data['username']) && !empty($data['token']) && !empty($data['list-id'])) {
				$fields_data = $this->get_fields_html($data['url'], $data['username'], $data['token'], $data['list-id'], $data['fields']);
				if ($fields_data['status'] == 'OK') $html .= $fields_data['html'];
			}
			$html .= '
					</div>
					<a class="lepopup-button lepopup-button-small" onclick="return lepopup_integrations_ajax_inline_html(this);" data-inline="fields" data-deps="url,username,token,list-id"><i class="fas fa-download"></i><label>'.esc_html__('Load Fields', 'lepopup').'</label></a>
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

			if (!is_array($deps) || !array_key_exists('url', $deps) || empty($deps['url'])
                || !array_key_exists('username', $deps) || empty($deps['username'])
                || !array_key_exists('token', $deps) || empty($deps['token'])) {
				$return_object = array('status' => 'ERROR', 'message' => esc_html__('Invalid API credentials.', 'lepopup'));
				echo json_encode($return_object);
				exit;
			}

            $xml = '
            <xmlrequest>
                <username>'.$deps['username'].'</username>
                <usertoken>'.$deps['token'].'</usertoken>
                <requesttype>lists</requesttype>
                <requestmethod>GetLists</requestmethod>
                <details>
                </details>
            </xmlrequest>';
			$response = $this->connect($deps['url'], $xml);
            if (empty($response)) {
				$return_object = array('status' => 'ERROR', 'message' => esc_html__('Invalid server response.', 'lepopup'));
				echo json_encode($return_object);
				exit;
            }
            $p = xml_parser_create();
            $r = xml_parse_into_struct($p, $response, $values, $index);
            xml_parser_free($p);
            if ($r == 0 || !isset($index['STATUS']) || $values[$index['STATUS'][0]]['value'] != 'SUCCESS') {
				$return_object = array('status' => 'ERROR', 'message' => esc_html__('Invalid server response.', 'lepopup'));
				echo json_encode($return_object);
				exit;
            }
            $i = 0;
            foreach ($index['LISTID'] as $idx) {
                $lists[$values[$idx]['value']] = $values[$index['NAME'][$i]]['value'];
                $i++;
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

	function admin_fields_html() {
		global $wpdb;
		if (current_user_can('manage_options')) {
			if (array_key_exists('deps', $_REQUEST)) {
				$deps = json_decode(base64_decode(trim(stripslashes($_REQUEST['deps']))), true);
				if (!is_array($deps)) $deps = null;
			} else $deps = null;
			if (!is_array($deps) || !array_key_exists('url', $deps) || empty($deps['url'])
                || !array_key_exists('username', $deps) || empty($deps['username'])
                || !array_key_exists('token', $deps) || empty($deps['token'])
                || !array_key_exists('list-id', $deps) || empty($deps['list-id'])) {
				$return_object = array('status' => 'ERROR', 'message' => esc_html__('Invalid API credentials or List ID.', 'lepopup'));
				echo json_encode($return_object);
				exit;
			}
			$return_object = $this->get_fields_html($deps['url'], $deps['username'], $deps['token'], $deps['list-id'], $this->default_parameters['fields']);
			echo json_encode($return_object);
		}
		exit;
	}

	function get_fields_html($_url, $_username, $_token, $_list, $_fields) {
		global $wpdb, $lepopup;
        $xml = '
        <xmlrequest>
            <username>'.$_username.'</username>
            <usertoken>'.$_token.'</usertoken>
            <requesttype>lists</requesttype>
            <requestmethod>GetCustomFields</requestmethod>
            <details>
                <listids>'.$_list.'</listids>
            </details>
        </xmlrequest>';
		$response = $this->connect($_url, $xml);
        if (empty($response)) {
            return array('status' => 'ERROR', 'message' => esc_html__('Invalid server response.', 'lepopup'));
        }
        $p = xml_parser_create();
        $r = xml_parse_into_struct($p, $response, $values, $index);
        xml_parser_free($p);
        if ($r == 0 || !isset($index['STATUS']) || $values[$index['STATUS'][0]]['value'] != 'SUCCESS') {
            return array('status' => 'ERROR', 'message' => esc_html__('Invalid server response.', 'lepopup'));
        }
        if (empty($index['FIELDID'])) {
            return array('status' => 'ERROR', 'message' => esc_html__('No fields found.', 'lepopup'));
        }
        $fields_html = '
            <table>';
        $i = 0;
        foreach ($index['FIELDID'] as $idx) {
            $fields_html .= '
				<tr>
					<th>'.esc_html($values[$index['NAME'][$i]]['value']).'</th>
					<td>
						<div class="lepopup-input-shortcode-selector">
							<input type="text" name="fields['.esc_html($values[$idx]['value']).']" value="'.esc_html(array_key_exists($values[$idx]['value'], $_fields) ? $_fields[$values[$idx]['value']] : '').'" class="widefat" />
							<div class="lepopup-shortcode-selector" onmouseover="lepopup_shortcode_selector_set(this)";><span><i class="fas fa-code"></i></span></div>
						</div>
						<label class="lepopup-integrations-description">'.esc_html($values[$index['NAME'][$i]]['value']).'</label>
					</td>
				</tr>';
            $i++;
        }
        $fields_html .= '
            </table>';
		return array('status' => 'OK', 'html' => $fields_html);
	}

	function front_submit($_result, $_data) {
		global $wpdb, $lepopup;
		$data = array_merge($this->default_parameters, $_data);
		if (empty($data['url']) || empty($data['username']) || empty($data['token']) || empty($data['list-id'])) return $_result;
		if (empty($data['fields']) || !is_array($data['fields'])) return $_result;
		if (empty($data['fields']['EMAIL']) || !preg_match("/^[_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $data['fields']['EMAIL'])) return $_result;

        $custom_fields_xml = '';
		foreach ($data['fields'] as $key => $value) {
			if (!empty($value) && $key != 'EMAIL') {
				$custom_fields_xml .= '
                <item>
                    <fieldid>'.$key.'</fieldid>
                    <value>'.$value.'</value>
                </item>';
			}
		}
        
        $xml = '
        <xmlrequest>
            <username>'.$data['username'].'</username>
            <usertoken>'.$data['token'].'</usertoken>
            <requesttype>subscribers</requesttype>
            <requestmethod>AddSubscriberToList</requestmethod>
            <details>
                <emailaddress>'.$data['fields']['EMAIL'].'</emailaddress>
                <mailinglist>'.$data['list-id'].'</mailinglist>
                <format>html</format>
                <confirmed>yes</confirmed>';
                        if (!empty($custom_fields_xml)) {
                            $xml .= '
                <customfields>'.$custom_fields_xml.'
                </customfields>';
                        }
                        $xml .= '
            </details>
        </xmlrequest>';
		$response = $this->connect($data['url'], $xml);
		return $_result;
	}
	
	function connect($_url, $_request_body) {
		try {
			$curl = curl_init($_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array());
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $_request_body);
			curl_setopt($curl, CURLOPT_TIMEOUT, 120);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			curl_close($curl);
		} catch (Exception $e) {
			$response = false;
		}
		return $response;
	}
}
$lepopup_interspire = new lepopup_interspire_class();
?>