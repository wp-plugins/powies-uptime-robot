<?php
/*
Plugin Name: Powies Uptime Robot
Plugin URI: http://www.powie.de/wordpress/pum
Description: Powies Uptime Robot Plugin with Shortcode and Widget
Version: 0.9.0
License: GPLv2
Author: Thomas Ehrhardt
Author URI: http://www.powie.de
*/

//Define some stuff
define( 'PUM_PLUGIN_DIR', dirname( plugin_basename( __FILE__ ) ) );
define( 'PUM_PLUGIN_URL', plugins_url( dirname( plugin_basename( __FILE__ ) ) ) );
load_plugin_textdomain( 'pum', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

//call widgets file
include('status-cloud-widget.php');

//CSS
function pum_scripts() {
	wp_enqueue_style('pum', plugins_url('pum.css',__FILE__), array(), 1); //main css
}
add_action( 'wp_enqueue_scripts', 'pum_scripts' );

//Admin Menu
add_action('admin_menu', 'pum_create_menu');
function pum_create_menu() {
	// or create options menu page
	add_options_page(__('Uptime Robot Setup'),__('Uptime Robot Setup'), 9, PUM_PLUGIN_DIR.'/pum_settings.php');
}

//create custom plugin settings menu
//add_action('admin_menu', 'pag_create_menu');

//add_action('wp_head', 'plinks_websnapr_header');
//Shortcode
add_shortcode('pum', 'pum_shortcode');
//Hook for Activation
register_activation_hook( __FILE__, 'pum_activate' );
//Hook for Deactivation
register_deactivation_hook( __FILE__, 'pum_deactivate' );

add_action('admin_init', 'pum_register_settings' );
function pum_register_settings() {
	//register settings
	register_setting( 'pum-settings', 'pum-apikey');			//API Key
	register_setting( 'pum-settings', 'pum-cache' );			//Letzte abgefragte Daten
	register_setting( 'pum-settings', 'pum-time' );			    //Timestamp abgefragte Daten
}

function pum_shortcode( $atts ) {
	$json = pum_get_data();
	$sc = '<table><tr>
			<th>'.__('Status', 'pum').'</th>
			<th>'.__('Monitor Name', 'pum').'</th>
			<th>'.__('Uptime', 'pum').'</th></tr>';
	foreach ($json->monitors->monitor as $monitor) {
		$sc.='<tr><td><span class="pum stat'.$monitor->status.'">
                      '.pum_status_type($monitor->status).'</span></td>
				  <td>'.$monitor->friendlyname.'</td>
				  <td>'.$monitor->alltimeuptimeratio.' %</td></tr>';
	}
	$sc.='</table>';
	$sc.=__('Updated at', 'pum'). ' '.get_date_from_gmt( date('Y-m-d H:i:s' ,get_option( 'pum-time' )), get_option('time_format'));
	//$sc.=__('Updated at', 'pum'). ' '.date_i18n(get_option('time_format'), get_option( 'pum-time' ));
	return $sc;
}

//Activate
function pum_activate() {
	// do not generate any output here
	add_option('postfield-rows',5);
	add_option('after-post-msg', __('Thanks for your post. We will review your post befor publication.','pag'));
}

//Deactivate
function pum_deactivate() {
	// do not generate any output here
}

function pum_status_type($status){
	switch ($status) {
		case 0:
			$r = __('paused', 'pum');
			break;
		case 1:
			$r = __('not checked yet', 'pum');
			break;
		case 2:
			$r = __('up', 'pum');
			break;
		case 8:
			$r = __('seems down', 'pum');
			break;
		case 9:
			$r = __('down', 'pum');
			break;
		default:
			$r = __('unknown', 'pum');
	} // switch
	return $r;
}

function pum_get_data() {
	// check for cached copy
	$cache = get_option( 'pum-cache' );

	if ($cache != '' && time() < $cache['timestamp'] + 600) { // cache is < 10 minutes old. use it.
		$json = json_decode($cache['data']);
	}
	else { // cache is stale
		// set up request
		$api_key = get_option( 'pum-apikey' ); // My Settings > API Information > Monitor-specific API keys > Select a Monitor > Click to Create One
		$url = "http://api.uptimerobot.com/getMonitors?apiKey=" . $api_key . "&logs=1&showTimezone=1&format=json&noJsonCallback=1";

		// request via cURL
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		$responseJSON = curl_exec($c);
		curl_close($c);

		$json = json_decode($responseJSON);

		// don't cache if there's a failture
		if ($json !== NULL && $json->stat != 'fail') {
			// save to cached  option
			update_option('pum-cache', array ( 'data' => $responseJSON, 'timestamp' => time()));
			update_option('pum-time', time() );
		}
	}
	return $json;
}

?>