<?php
/* Functions for PayPal Currency Converter PRO for WooCommerce
 */
 
// Multisite handling
// see if site is network activated
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	// Makes sure the plugin is defined before trying to use it
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}
if (is_plugin_active_for_network(plugin_basename(__FILE__))) {  // path to plugin folder and main file
	define("PPCC_NETWORK_ACTIVATED", true);
	
}
else {
	define("PPCC_NETWORK_ACTIVATED", false);
}

//constant PPCC options name
$option_name = 'ppcc-options';

// Wordpress function 'get_site_option' and 'get_option'
function get_ppcc_option($option_name) {

	if(PPCC_NETWORK_ACTIVATED == true) {

		// Get network site option
		return get_site_option($option_name);
	}
	else {

		// Get blog option
		if(function_exists('get_blog_option')){
			return get_blog_option(get_current_blog_id(),$option_name);			
		}
		else{
			return get_option($option_name);
		}
	}
}

// Wordpress function 'update_site_option' and 'update_option'
function update_ppcc_option($option_name, $option_value) {

	if(PPCC_NETWORK_ACTIVATED== true) {

		// Update network site option
		return update_site_option($option_name, $option_value);
	}
	else {

	// Update blog option
	return update_option($option_name, $option_value);
	}
}

// Wordpress function 'delete_site_option' and 'delete_option'
function delete_ppcc_option($option_name) {

	if(PPCC_NETWORK_ACTIVATED== true) {

		// Delete network site option
		return delete_site_option($option_name);
	}
	else {

	// Delete blog option
	return delete_option($option_name);
	}
}
/*check CURL*/
function ppcc_is_curl_installed() {
    if  (in_array  ('curl', get_loaded_extensions())) {
        return true;
    }
    else {
        return false;
    }
}
 
 //print the currency inside the description of PayPal payment Method using {...} enclosings*
function update_paypal_description(){
	global $woocommerce;
	global $option_name;
	$options = get_ppcc_option($option_name);
	$paypal_options = get_ppcc_option('woocommerce_paypal_settings'); //PayPal Standard
	$ppdg_options = get_ppcc_option('woocommerce_paypal_digital_goods_settings'); //PayPal Digital Goods
	$ptn = "({.*})";
	@preg_match($ptn, $paypal_options['description'], $matches);
	if (count($matches)>0){

		$replace_string='{' .$options['conversion_rate'].$options['target_currency'].'/'.get_woocommerce_currency().'}';
		$paypal_options['description'] = preg_replace($ptn, $replace_string, $paypal_options['description']);
		$ppdg_options['description'] = preg_replace($ptn, $replace_string, $ppdg_options['description']);
	}
	update_ppcc_option( 'woocommerce_paypal_settings', $paypal_options ); //PayPal Standard
	update_ppcc_option( 'woocommerce_paypal_digital_goods_settings', $ppdg_options );//PayPal Digital Goods
}

/*Use CURL*/
function ppcc_file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

 
//retrieve EX data from the api
function get_exchangerate($from,$to) {
	global $option_name;
	//update the retrieval counter
	$options = get_ppcc_option($option_name);
	$options['retrieval_count'] = $options['retrieval_count'] + 1;
	update_ppcc_option( $option_name, $options );
	$precision = $options['precision'];

	switch ($options['api_selection']) {
	    case "oer_api_id":
	    	if (!isset($options['oer_api_id'])){
			echo '<div class="error settings-error"><p>Please register an Open Exchange Rate API ID first!</p></div>';
			return 1;
			exit;
			}
			$url = 'http://openexchangerates.org/api/latest.json?app_id='.$options['oer_api_id']; 
			$json = @ppcc_file_get_contents_curl($url); 
			$data = json_decode($json);
			if(isset($data->error)or !@ppcc_file_get_contents_curl($url)){
				echo '<div class="error settings-error"><p>openexchangerates.org says: '.$data->description.' <br/></p></div>';
				return 1;
				exit;
				}
			return (string)(round($data->rates->$to/$data->rates->$from,$precision));
	        break;

	    case "fixer_io_api_id":

			$url = 'http://data.fixer.io/api/latest?access_key='.$options['fixer_io_api_id'].'&symbols='.$from.','.$to; 

			$json = @ppcc_file_get_contents_curl($url); 
			$data = json_decode($json);

			if(!@ppcc_file_get_contents_curl($url) or (isset($data->error))){
				echo '<div class="error settings-error"><p>api.fixer.io says: '.@$data->error->code.' <br/>'.@$data->error->type.' <br/>'.@$data->error->info.' <br/></p></div>';
				return 1;
				exit;
				}

			return (string)(round($data->rates->$to/$data->rates->$from,$precision));
	        break;

	    case "xignite":

			$url = 'http://globalcurrencies.xignite.com/xGlobalCurrencies.json/GetRealTimeRate?Symbol=' . $from . $to . '&_token=' . $options['xignite_api_id'].'&_fields=Outcome,Mid'; 
			$json = @ppcc_file_get_contents_curl($url); 
			$data = json_decode($json);

			if(($data->Outcome !='Success')or !@ppcc_file_get_contents_curl($url)){
				echo '<div class="error settings-error"><p>XIGNITE says: '.$data->Outcome.' <br/></p></div>';
				return 1;
				exit;
				}
			return (string)(round($data->MID,$precision));
	        break;

	    case "apilayer":

			$url = 'http://www.apilayer.net/api/live?access_key=' . $options['apilayer_api_id'] . '&currencies=' . $to . ',' . $from;
			$json = @ppcc_file_get_contents_curl($url); 
			$data = json_decode($json);

			if(!($data->success)or !@ppcc_file_get_contents_curl($url)){
				echo '<div class="error settings-error"><p>APILAYER says: '.$data->error->info.' <br/></p></div>';
				return 1;
				exit;
				}

			return (string)(round(($data->quotes->USD.$to/$data->quotes->USD.$from),$precision));
	        break;

	    case "currencyconverterapi":

			$url = 'https://free.currencyconverterapi.com/api/v5/convert?q=' . $from . '_' . $to . '&compact=y';
			$json = @ppcc_file_get_contents_curl($url); 
			$data = json_decode($json);

			if(isset($data->error)or !@ppcc_file_get_contents_curl($url)){
				echo '<div class="error settings-error"><p>APILAYER says: '.$data->error.' <br/></p></div>';
				return 1;
				exit;
				}
				$couple =$from . '_' . $to;

			return (string)(round(($data->$couple->val),$precision));
	        break;

	    case "bnro"://XML only
	        $url = @ppcc_file_get_contents_curl("http://www.bnro.ro/nbrfxrates.xml");
			$dom   = new DOMDocument();
			@$dom->loadHTML($url);
			$xpath = new DOMXPath($dom);

			$rate = $xpath->evaluate("string(//*[@currency='".$to."'])");
			$multiplier = $xpath->evaluate("string(//*[@currency='".$to."']/@multiplier)");

			if ($multiplier!='') { 
				$rate=$rate*$multiplier;
			}
			if($rate == '' or @ppcc_file_get_contents_curl($url)){
				echo '<div class="error settings-error"><p>Could not retrieve data for '.$from.' to '.$to.' from BNRO <br/><a href"'.$requestUrl.'">'.$requestUrl.'</a></p></div>';
				return 1;
				exit;
			}		
			return (string)round((1/$rate),$precision );	
	        break;

	   /* legacy... replaced by api_fixer_io
	    case "ecb":
			$XML=@simplexml_load_file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");
			if ($XML==false){
				echo '<div class="error settings-error"><p>Can not load ECB Exchange Rates!</p></div>';
				return 1;
				exit;
				};
			$efx_data =  array();      
			foreach($XML->Cube->Cube->Cube as $rate){
				$efx_data= $efx_data + array((string)$rate["currency"][0] => (string)$rate["rate"][0]);  
			}
			$efx_data= $efx_data + array("EUR" => 1);
			
			if(!isset($efx_data[$from])){
				echo '<div class="error settings-error"><p>Could not retrieve data for '.$from.' to '.$to.' from ECB <br/><a href"'.$XML.'">'.$XML.'</a></p></div>';
				return 1;
				exit;
			}	
			//to small -> invert
			if($efx_data[$to]/$efx_data[$from] < 0.1){
				return (string)round(1/$efx_data[$from]/$efx_data[$to],$precision );
			} 
			else{
				return (string)round($efx_data[$to]/$efx_data[$from],$precision );				
			}

	        break;
		*/
	    case "custom":
	        return $options['conversion_rate'];
	        break;    
	    default:
	        echo '<div class="error settings-error"><p>Please select EXR Source first</p></div>';
			return 1;
			exit;
	} //end of switch
	
}

/******************* ( ajax show log service or update currency exchange rate)**********/
add_action('wp_ajax_ppcc', 'ppcc_listener');
add_action('wp_ajax_nopriv_ppcc', 'ppcc_listener');

function ppcc_listener() {
	global $option_name;
	//admin_url('admin-ajax.php')
	
	
	$returncode = '';

	global $woocommerce;
	$options = get_ppcc_option($option_name); //read the plugins options
	
	// Logs
	if ( 'yes' == $options['debug'] )
		$log =  new WC_Logger();
				
	
	if($_GET['ppcc_function'] == 'cexr_update'){ //http://.../wp-admin/admin-ajax.php?action=ppcc&ppcc_function=cexr_update
	
		$returncode=currency_exchange_rate_update();
		
	}

	if ( 'yes' == $options['debug'] ) {
		$log->add( 'ppcc', $errmsg);

	}

	
	echo $returncode;

	die();
}
	
	
/**
 * Checks if WooCommerce is active
 * @return bool true if WooCommerce is active, false otherwise
 */
 
if(!function_exists ( 'is_woocommerce_active' )){
	function is_woocommerce_active() {

		$active_plugins = (array) get_ppcc_option( 'active_plugins', array() );

		if ( is_multisite() )
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}	
}

	
//Scheduler
add_action('ppcc_cexr_update', 'currency_exchange_rate_update');
	
//currency_exchange_rate_update
function currency_exchange_rate_update() {
	global $woocommerce;
	global $option_name;


	if (is_multisite()){
		$blog_list = array();		
		if(function_exists('get_sites')){
			$blog_list= get_sites();
		} 
		elseif(function_exists('wp_get_sites'))  {
			$blog_list= wp_get_sites();
		} 

		foreach ($blog_list as $blog) {
			switch_to_blog($blog['blog_id']);

			$blogs_handled+=', '.$blog['blog_id'].'-'.get_blog_details($blog['blog_id'])->blogname;
			
			$options = get_ppcc_option($option_name);
			
			//echo $blog['blog_id'].'-'.get_blog_details($blog['blog_id'])->blogname.'<br>'.print_r($options).'<hr>';
			if ('on'==$options['auto_update']){
				$exrdata = get_exchangerate(get_woocommerce_currency(),$options['target_currency']);
				$options['conversion_rate'] = $exrdata;
				$options['time_stamp']= current_time( 'timestamp' );
				$options['retrieval_count'] = $options['retrieval_count'] + 1;
				update_ppcc_option($option_name, $options );
				if ( 'on' == $options['exrlog'] ){
					wp_mail(get_ppcc_option('admin_email'), 'PayPal Currency Converter PRO for WooCommerce', 'Exchangerate was updated by schedule at '.date( 'Y-m-d H:i:s',$options['time_stamp']).' - Retrival no.:'.$options['retrieval_count']. ' - Exchangerate='.$exrdata.$options['target_currency'].'/'.get_woocommerce_currency()).' for Blog name: .'.$blog['blog_id'].'-'.get_blog_details($blog['blog_id'])->blogname;
				}
				
			}
			$log = new WC_Logger();
			$log->add( 'ppcc', 'Scheduled update for Blog name: .'.$blog['blog_id'].'-'.get_blog_details($blog['blog_id'])->blogname.' retrieved from:'.$options['api_selection'].' Retrival no.:'.$options['retrieval_count']. ' Exchangerate='.$exrdata.$options['target_currency'].'/'.get_woocommerce_currency());
			update_paypal_description();
				
			restore_current_blog();

		}
	
	}
	else{

		$options = get_ppcc_option($option_name);
		// echo "<pre>";
		// print_r($options);
		// echo "</pre>";
		
		if ('on'==$options['auto_update']){

			$exrdata = get_exchangerate(get_woocommerce_currency(),$options['target_currency']);
			$options['conversion_rate'] = $exrdata;

			//echo $options['conversion_rate'];

			$options['time_stamp']= current_time( 'timestamp' );
			$options['retrieval_count'] = $options['retrieval_count'] + 1;
			update_ppcc_option($option_name, $options );
			if ( 'on' == $options['exrlog'] ){
				wp_mail(get_ppcc_option('admin_email'), 'PPCC Currency Converter', 'Exchangerate was updated by schedule at '.date( 'Y-m-d H:i:s',$options['time_stamp']).' - Retrival no.:'.$options['retrieval_count']. ' - Exchangerate='.$exrdata.$options['target_currency'].'/'.get_woocommerce_currency()).' for Site name: .'.get_ppcc_option( 'blogname' );
			}
		}

		$log = new WC_Logger();
		$log->add( 'ppcc','Scheduled update for site name: '.get_ppcc_option( 'blogname' ).' retrieved from:'.$options['api_selection'].' Retrival no.:'.$options['retrieval_count']. ' Exchangerate='.$exrdata.$options['target_currency'].'/'.get_woocommerce_currency());
	}
}


function ppcc_admin_tabs( $page, $tabs, $current=NULL ) {
    if ( is_null( $current ) ) {
        if ( isset( $_GET['tab'] ) ) {
            $current = $_GET['tab'];
        }
    }
    $content = '';
    $content .= '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $tabname ) {
        if ( $current == $tab ) {
            $class = ' nav-tab-active';
        } else {
            $class = '';    
        }
        $content .= '<a class="nav-tab' . $class . '" href="?page=' . 
            $page . '&tab=' . $tab . '">' . $tabname . '</a>';
    }
    $content .= '</h2>';                 
    echo $content;
    if ( ! $current ) 
        $current = key( $tabs );
    require_once( $current . '.php' );
    return;
}

function ppcc_main_function() {
    $my_plugin_tabs = array(
        'mainsettings'  => 'Main settings',
        'exchangerateprovider' => 'Exchange Rate Provider',
        'handlingfee' => 'Handling fees',
    );
    $my_plugin_page = 'ppcc_options';
    echo ppcc_admin_tabs( $my_plugin_page, $my_plugin_tabs );
}

?>