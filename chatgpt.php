	<?php
	/*
	Plugin Name: ChatGPT-wordpress-integration
	Author: Anmol Chanana
	*/


	function my_plugin_activate()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'chat_key';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id int(9) NOT NULL AUTO_INCREMENT,
			publickey varchar(100) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	register_activation_hook(__FILE__, 'my_plugin_activate');


	// Create a form
	function my_plugin_form()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "chat_key";
		if (isset($_POST['publickey'])) {
			$key = $_POST['publickey'];
			$checkIfExists = $wpdb->get_var("SELECT id FROM $table_name");
			if ($checkIfExists > 0) {
				$wpdb->update(
					$table_name,
					array(
						'publickey' => $key
					),
					array('id' => $checkIfExists)
				);
			} else {
				$wpdb->insert(
					$table_name,
					array(
						'publickey' => $key
					)
				);
			}
		}

		$getPuclickey =  $wpdb->get_var("SELECT publickey FROM $table_name");

		echo '<form method="post" action="" style="display: flex; align-items: center;">';
		echo '<label for="name">Public key:</label>';
		echo '<input type="text" name="publickey" value="' . $getPuclickey . '" style="width: 30%;"><br><br>';
		echo '<input type="submit" value="Submit" style="background-color: #2271b1;border: none;color: #fff;padding: 6px 20px;border-radius: 3px;">';
		echo '</form> ';
		echo '<span>Add shortcode <b>simple_form_ajax_chatgpt</b></span>';
	}

	// Show the form
	function my_plugin_display_form()
	{
		add_menu_page('ChatGPT Integration', 'ChatGPT Integration', 'manage_options', 'my-form', 'my_plugin_form', 'dashicons-admin-generic', 6);
	}
	add_action('admin_menu', 'my_plugin_display_form');



	// Enqueue the script for the AJAX form submission
	function simple_form_ajax_enqueue_scripts()
	{
		wp_enqueue_script('simple-form-ajax', plugin_dir_url(__FILE__) . 'simple-form-ajax.js', array('jquery'), '1.0', true);
		wp_localize_script('simple-form-ajax', 'simple_form_ajax_params', array(
			'ajax_url' => admin_url('admin-ajax.php')
		));
	}
	add_action('wp_enqueue_scripts', 'simple_form_ajax_enqueue_scripts');

	//create shortcode to display the form
	function simple_form_ajax_form_shortcode()
	{
		ob_start();
	?>
	<form id="simple-form-ajax">
	    <textarea name="question" id="question" placeholder="Message"></textarea>
	    <input type="submit" value="Submit">
	</form>
	<div id="output"></div>
	<?php
		return ob_get_clean();
	}
	add_shortcode('simple_form_ajax_chatgpt', 'simple_form_ajax_form_shortcode');

	// Handle the form submission with AJAX
	function simple_form_ajax_submit()
	{
		// Do something with the form data
		global $wpdb;
		$table_name = $wpdb->prefix . "chat_key";
		$question = $_POST['form_data'];
		if (!empty($question)) {
			$sk = $wpdb->get_var("SELECT publickey FROM $table_name");
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/completions');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"model\": \"text-davinci-003\", \"prompt\": \"$question\", \"temperature\": 0, \"max_tokens\": 100}");
			$headers = array();
			$headers[] = 'Content-Type: application/json';
			$headers[] = "Authorization: Bearer $sk";
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$result = curl_exec($ch);
			if (curl_errno($ch)) {
				echo 'Error:' . curl_error($ch);
			}
			curl_close($ch);
			$result = json_decode($result, 1);
			$answer = $result['choices'][0]['text'];
			echo json_encode(array('succress' => true));
		}
		wp_die();
	}
	add_action('wp_ajax_simple_form_ajax_submit', 'simple_form_ajax_submit');
	add_action('wp_ajax_nopriv_simple_form_ajax_submit', 'simple_form_ajax_submit');
	?>