<?php


//---Start Phase 1-----------------------------------------

add_action('rest_api_init', 'create_custon_endpoint_3');

function create_custon_endpoint_3() {
    register_rest_route('wp/v2', '/setConfig', array(
        'methods' => 'GET',
        'callback' => 'get_response_3',
    ));
}

function get_response_3($data) {
    $input_string = $data['config'];

    global $wpdb;

    $table_name = $wpdb->prefix . 'config'; // Ersetze 'deine_tabelle' durch den tatsächlichen Tabellennamen

    $wpdb->query("TRUNCATE TABLE $table_name");
    
    $wpdb->insert(
        $table_name,
        array('config' => $input_string),
        array('%s')
    );
    
    return rest_ensure_response($input_string);
}


add_action( 'rest_api_init', 'create_custon_endpoint_4' );
 
function create_custon_endpoint_4(){
    register_rest_route('wp/v2', '/getConfig', array(
        'methods' => 'GET',
        'callback' => 'get_response_4',
    ));
}

function get_response_4() {
	global $wpdb;
	
    $table_name = $wpdb->prefix . 'config'; 

    $result = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

    if ($result) {
        $config_value = $result->config;
        return $config_value;
    }
}

//---Ende Phase 1-----------------------------------------





//---Start Phase 2-----------------------------------------

add_action( 'rest_api_init', 'create_custon_endpoint' );
 
function create_custon_endpoint(){
    register_rest_route(
        'wp/v2',
        '/kwh',
        array(
            'methods' => 'GET',
            'callback' => 'get_response',
        )
    );
}
 
function get_response() {

    $result_kwh_akt_tag = get_kwh_akt_tag();
    $result_kwh_vor_tag = get_kwh_vor_tag();
	
    $result_kwh_akt_woche = get_kwh_akt_woche();
    $result_kwh_vor_woche = get_kwh_vor_woche();

    $result_kwh_akt_monat = get_kwh_akt_monat();
    $result_kwh_vor_monat = get_kwh_vor_monat();

    $result_kwh_akt_jahr = get_kwh_akt_jahr();
    $result_kwh_vor_jahr = get_kwh_vor_jahr();

	$data = array(
		'aktuell'       => number_format(get_aktuell(), 3),
		'kwh_akt_tag'   => number_format(rechne_kwh($result_kwh_akt_tag), 3),
		'kwh_vor_tag'   => number_format(rechne_kwh($result_kwh_vor_tag), 3),
		'kwh_akt_woche' => number_format(rechne_kwh($result_kwh_akt_woche), 3),
		'kwh_vor_woche' => number_format(rechne_kwh($result_kwh_vor_woche), 3),
		'kwh_akt_monat' => number_format(rechne_kwh($result_kwh_akt_monat), 3),
		'kwh_vor_monat' => number_format(rechne_kwh($result_kwh_vor_monat), 3),
		'kwh_akt_jahr'  => number_format(rechne_kwh($result_kwh_akt_jahr), 3),
		'kwh_vor_jahr'  => number_format(rechne_kwh($result_kwh_vor_jahr), 3)
	);
	
	// Das assoziative Array in JSON umwandeln
	$json_data = json_encode($data);
	wp_send_json($data);
}

add_action( 'rest_api_init', 'create_custon_endpoint_1' );
 
function create_custon_endpoint_1(){
    register_rest_route(
        'wp/v2',
        '/loadData',
        array(
            'methods' => 'GET',
            'callback' => 'get_response_1',
        )
    );
}

function get_response_1() {
	
	$api_url = 'https://shelly-88-eu.shelly.cloud/device/status';

	$post_data = array(
		'id' => '4855199b0fc4',
		'auth_key' => 'MWY2MGVhdWlkD679DF28D89AC19E01DD8DFB61EFCF89D9805DACB05E0797E646B49CF6D97847522E49A0DFE2BA9F',
	);
	
	$response = wp_remote_post($api_url, array(
		'body' => $post_data,
	));
	
	if (is_wp_error($response)) {
		die('Fehler beim Abrufen der API-Daten: ' . $response->get_error_message());
	}
	
	$body = wp_remote_retrieve_body($response);

	$data = json_decode($body, true);
	
	if ($data === null) {
		die('Fehler beim Dekodieren der JSON-Daten');
	}
	
	$total = $data['data']['device_status']['switch:0']['aenergy']['total']; 
	$uptime = $data['data']['device_status']['sys']['uptime'];
    
	global $wpdb;
	$data_to_insert = array(
		'uptime' => $uptime,
		'total' => $total
	);
	
	$table_name = $wpdb->prefix . 'api';

	$wpdb->insert($table_name, $data_to_insert);
}



function rechne_kwh($data){
	
	$num_results = count($data);

	$sum = 0;

	for ($i = 0; $i < $num_results - 1; $i++) {
		$kwh_1 = $data[$i]->total;
		$kwh_2 = $data[$i + 1]->total;

		if($kwh_1 > $kwh_2){
			$sum += $kwh_2;
		}

		else{
			$sum += $kwh_2 - $kwh_1; 
		}
	}

	$sum = $sum / 1000;

	return $sum;
}

function get_aktuell(){
	$api_url = 'https://shelly-88-eu.shelly.cloud/device/status';

	$post_data = array(
		'id' => '4855199b0fc4',
		'auth_key' => 'MWY2MGVhdWlkD679DF28D89AC19E01DD8DFB61EFCF89D9805DACB05E0797E646B49CF6D97847522E49A0DFE2BA9F',
	);
	
	$response = wp_remote_post($api_url, array(
		'body' => $post_data,
	));
	
	if (is_wp_error($response)) {
		die('Fehler beim Abrufen der API-Daten: ' . $response->get_error_message());
	}
	
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);

    if($data['data']['online']){
        $total = $data['data']['device_status']['switch:0']['apower']; 
    }

    else{
        $total = 0;
    }

    return $total;
}

function get_kwh_akt_tag(){
    global $wpdb;

    $table_name = $wpdb->prefix . 'api';
    $timestamp_column = 'timestamp';

    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE DATE($timestamp_column) = CURDATE()",
    );

    $results = $wpdb->get_results($sql);
    return $results;
}

function get_kwh_vor_tag(){
    global $wpdb;

    $table_name = $wpdb->prefix . 'api';
    $timestamp_column = 'timestamp';
    $yesterday_date = date('Y-m-d', strtotime('yesterday'));

    $sql = $wpdb->prepare(
		"SELECT * FROM $table_name WHERE DATE($timestamp_column) = %s",
        $yesterday_date
    );

    $results = $wpdb->get_results($sql);
    return $results;
}

function get_kwh_akt_woche(){
    global $wpdb;

    $table_name = $wpdb->prefix . 'api';
    $timestamp_column = 'timestamp';

    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE YEAR($timestamp_column) = YEAR(CURDATE()) AND WEEK($timestamp_column) = WEEK(CURDATE())",
    );

    $results = $wpdb->get_results($sql);
    return $results;
}

function get_kwh_vor_woche(){
	global $wpdb;

    $table_name = $wpdb->prefix . 'api';
    $timestamp_column = 'timestamp';

    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE YEAR($timestamp_column) = YEAR(CURDATE()) AND WEEK($timestamp_column) = WEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))",
    );

    $results = $wpdb->get_results($sql);
    return $results;
}

function get_kwh_akt_monat(){
	global $wpdb;

    $table_name = $wpdb->prefix . 'api';
    $timestamp_column = 'timestamp';

    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE YEAR($timestamp_column) = YEAR(CURDATE()) AND MONTH($timestamp_column) = MONTH(CURDATE())",
    );

    $results = $wpdb->get_results($sql);
    return $results;
}

function get_kwh_vor_monat(){
	global $wpdb;

    $table_name = $wpdb->prefix . 'api';
    $timestamp_column = 'timestamp';

	

    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE YEAR($timestamp_column) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH($timestamp_column) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))",
    );

    $results = $wpdb->get_results($sql);
    return $results;
}

function get_kwh_akt_jahr(){
    global $wpdb;

    $table_name = $wpdb->prefix . 'api';
    $timestamp_column = 'timestamp';

    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE YEAR($timestamp_column) = YEAR(CURDATE())",
    );

    $results = $wpdb->get_results($sql);
    return $results;
}

function get_kwh_vor_jahr(){
    global $wpdb;

    $table_name = $wpdb->prefix . 'api';
    $timestamp_column = 'timestamp';

    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE YEAR($timestamp_column) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))",
    );

    $results = $wpdb->get_results($sql);
    return $results;
}

//---Endde Phase 2-----------------------------------------







































