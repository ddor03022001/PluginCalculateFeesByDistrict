<?php
/*
 * Plugin Name: Woocommerce Fees By District
 * Version: 1.0.3
 * Description: Thêm lựa chọn tỉnh/thành phố, quận/huyện và xã/phường/thị trấn vào form checkout
 * Author: Quang Huy
 * Author URI: https://github.com/ddor03022001
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

include 'cities/tinh_thanhpho.php';

register_activation_hook(   __FILE__, array( 'Woo_Address_Selectbox_Class', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'Woo_Address_Selectbox_Class', 'on_deactivation' ) );
register_uninstall_hook(    __FILE__, array( 'Woo_Address_Selectbox_Class', 'on_uninstall' ) );

add_action( 'plugins_loaded', array( 'Woo_Address_Selectbox_Class', 'init' ) );
class Woo_Address_Selectbox_Class
{
    protected static $instance;
    
	protected $_version = '1.0.3';
	public $_optionName = 'devvn_woo_district';
	public $_optionGroup = 'devvn-district-options-group';
	public $_defaultOptions = array(
	    'active_village'	=>	'',
        'required_village'	=>	'',
        'to_vnd'	        =>	'',
	);

    public static function init(){
        is_null( self::$instance ) AND self::$instance = new self;
        return self::$instance;
    }
    
	public function __construct(){    	
        
        $this->define_constants();
        
    	add_filter( 'woocommerce_checkout_fields' , array($this, 'custom_override_checkout_fields'), 999999 );
    	add_filter( 'woocommerce_checkout_fields', array($this, 'order_fields'), 999999 );
    	add_filter( 'woocommerce_states', array($this, 'vietnam_cities_woocommerce') );
    	
    	add_action( 'wp_enqueue_scripts', array($this, 'devvn_enqueue_UseAjaxInWp') );  
    	add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
    	 
    	add_action( 'wp_ajax_load_diagioihanhchinh', array($this, 'load_diagioihanhchinh_func') );
		add_action( 'wp_ajax_nopriv_load_diagioihanhchinh', array($this, 'load_diagioihanhchinh_func') ); 
				
		add_filter('woocommerce_localisation_address_formats', array($this, 'devvn_woocommerce_localisation_address_formats') );
		add_filter('woocommerce_order_formatted_billing_address', array($this, 'devvn_woocommerce_order_formatted_billing_address'), 10, 2);		
		
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array($this, 'devvn_after_shipping_address'), 10, 1 );
		add_filter('woocommerce_order_formatted_shipping_address', array($this, 'devvn_woocommerce_order_formatted_shipping_address'), 10, 2);
		
		add_filter('woocommerce_order_details_after_customer_details', array($this, 'devvn_woocommerce_order_details_after_customer_details'), 10);
		
		add_action( "woocommerce_init", array($this, "devvn_district_zone_shipping_woocommerce_init") );
		
		//my account 
		add_filter('woocommerce_my_account_my_address_formatted_address',array($this, 'devvn_woocommerce_my_account_my_address_formatted_address'),10,3);
		
		//Options
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_mysettings') );
		
		add_option( $this->_optionName, $this->_defaultOptions );
		
		include_once( 'includes/functions-admin.php' );
        include_once( 'includes/apps.php' );
    }

    public function define_constants()
        {
            if (!defined('DEVVN_DWAS_VERSION_NUM'))
                define('DEVVN_DWAS_VERSION_NUM', $this->_version);
            if (!defined('DEVVN_DWAS_URL'))
                define('DEVVN_DWAS_URL', plugin_dir_url(__FILE__));
            if (!defined('DEVVN_DWAS_BASENAME'))
                define('DEVVN_DWAS_BASENAME', plugin_basename(__FILE__));
            if (!defined('DEVVN_DWAS_PLUGIN_DIR'))
                define('DEVVN_DWAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
        }

    public static function on_activation(){
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "activate-plugin_{$plugin}" );
        
    }

    public static function on_deactivation(){
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "deactivate-plugin_{$plugin}" );
        
    }

    public static function on_uninstall(){
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
    }
	
	function admin_menu() {
		add_options_page(
			__('Woo District Address Setting','devvn'), 
			__('Woo District Address','devvn'),
			'manage_options',
			'devvn-district-address',
			array(
				$this,
				'devvn_district_setting'
			)
		);
	}
	
	function register_mysettings() {
		register_setting( $this->_optionGroup, $this->_optionName );
	}
	
	function  devvn_district_setting() {		
		include 'includes/options-page.php';
	}
    
	function vietnam_cities_woocommerce( $states ) {
		global $tinh_thanhpho;
	  	$states['VN'] = $tinh_thanhpho;	 
	  	return $states;
	}
	
	function custom_override_checkout_fields( $fields ) { 	
		global $tinh_thanhpho;	
		
		//Billing
		$fields['billing']['billing_last_name'] = array(
		    'label' => __('Họ và tên', 'devvn'),
		    'placeholder' => _x('Nhập đầy đủ họ và tên của bạn', 'placeholder', 'devvn'),
		    'required' => true,
		    'class' => array('form-row-wide'),
		    'clear' => true
		);
		$fields['billing']['billing_state'] = array(
			'label'			=> __('Tỉnh/Thành phố', 'woocommerce'),
			'required' 		=> true,
			'type'			=> 'select',
			'class'    		=> array( 'form-row-wide', 'address-field', 'update_totals_on_change' ),
			'placeholder'	=> 'Chọn tỉnh/thành phố',
			'options'   	=> $tinh_thanhpho,
		);
		$fields['billing']['billing_city'] = array(
			'label'		=> __('Quận/Huyện', 'woocommerce'),
			'required' 	=> true,
			'type'		=>	'select',
			'class'    	=> array( 'form-row-wide', 'address-field', 'update_totals_on_change'  ),
			'placeholder'	=>	'Chọn quận/huyện',
			'options'   => array(
		        ''	=> __('', 'woocommerce' ),
		    ),
		);
		if(!$this->get_options()){
			$fields['billing']['billing_address_2'] = array(
				'label'		=> __('Xã/Phường/Thị trấn', 'woocommerce'),
				'required' 	=> true,
				'type'		=>	'select',
				'class'    	=> array( 'form-row-wide', 'address-field'  ),
				'placeholder'	=>	'Chọn xã/phường/thị trấn',
				'options'   => array(
			        ''	=> __('', 'woocommerce' )
			    ),
			);
            if($this->get_options('required_village')){
                $fields['billing']['billing_address_2']['required'] = false;
            }
		}
		$fields['billing']['billing_address_1']['placeholder'] = 'Ví dụ: Số 18 Ngõ 86 Phú Kiều';
		
		//Shipping		
		$fields['shipping']['shipping_last_name'] = array(
		    'label' => __('Họ và tên', 'devvn'),
		    'placeholder' => _x('Nhập đầy đủ họ và tên của bạn', 'placeholder', 'devvn'),
		    'required' => true,
		    'class' => array('form-row-wide'),
		    'clear' => true
		);
		$fields['shipping']['shipping_state'] = array(
			'label'		=> __('Tỉnh/Thành phố', 'woocommerce'),
			'required' 	=> true,
			'type'		=>	'select',
			'class'    	=> array( 'form-row-wide', 'address-field', 'update_totals_on_change'  ),
			'placeholder'	=>	'Chọn tỉnh/thành phố',
			'options'   => $tinh_thanhpho,
		);
		$fields['shipping']['shipping_city'] = array(
			'label'		=> __('Quận/Huyện', 'woocommerce'),
			'required' 	=> true,
			'type'		=>	'select',
			'class'    	=> array( 'form-row-wide', 'address-field', 'update_totals_on_change'  ),
			'placeholder'	=>	'Chọn quận/huyện',
			'options'   => array(
		        ''	=> __('', 'woocommerce' ),
		    ),
		);
		if(!$this->get_options()){
			$fields['shipping']['shipping_address_2'] = array(
				'label'		=> __('Xã/Phường/Thị trấn', 'woocommerce'),
				'required' 	=> true,
				'type'		=>	'select',
				'class'    	=> array( 'form-row-wide', 'address-field'  ),
				'placeholder'	=>	'Chọn xã/phường/thị trấn',
				'options'   => array(
			        ''	=> __('', 'woocommerce' )
			    ),
			);
            if($this->get_options('required_village')){
                $fields['shipping']['shipping_address_2']['required'] = false;
            }
		}
		$fields['shipping']['shipping_phone'] = array(
			'label' => __('Phone', 'woocommerce'),
			'placeholder' => _x('Phone', 'placeholder', 'woocommerce'),
			'required' => false,
			'class' => array('form-row-wide'),
			'clear' => true
		);
		$fields['shipping']['shipping_address_1']['placeholder'] = 'Ví dụ: Số 18 Ngõ 86 Phú Kiều';
		
		return $fields;
	}
	
	function order_fields($fields) {
	 
	  //billing
	  $order_billing = array(
	    "billing_last_name",
        "billing_phone",
	    "billing_email",
		"billing_state",
		"billing_city"
	  );	  
	  if(!$this->get_options()){
	  	$order_billing[] = "billing_address_2";
	  }
	  $order_billing[] = "billing_address_1";
	  
	  foreach($order_billing as $field_billing)
	  {
	    $ordered_fields2[$field_billing] = $fields["billing"][$field_billing];
	  }
	  $fields["billing"] = $ordered_fields2;
	  
	  //shipping
	  $order_shipping = array(
	    "shipping_last_name",
	    "shipping_phone",
		"shipping_state",
		"shipping_city",
	  );
	  if(!$this->get_options()){
	  	$order_shipping[] = "shipping_address_2";
	  }	  
	  $order_shipping[] = "shipping_address_1";
	  
	  foreach($order_shipping as $field_shipping)
	  {
	    $ordered_fields[$field_shipping] = $fields["shipping"][$field_shipping];
	  }
	  $fields["shipping"] = $ordered_fields;
	  
	  return $fields;
	}
	
	function search_in_array($array, $key, $value)
	{
	    $results = array();
	
	    if (is_array($array)) {
	        if (isset($array[$key]) && $array[$key] == $value) {
	            $results[] = $array;
	        }
	
	        foreach ($array as $subarray) {
	            $results = array_merge($results, $this->search_in_array($subarray, $key, $value));
	        }
	    }
	
	    return $results;
	}
	
    function check_file_open_status($file_url = ''){
        if(!$file_url) return false;
        $cache_key = '_check_get_address_file_status';
        $status    = get_transient( $cache_key );

        if ( false !== $status ) {
            return $status;
        }

        $response = wp_safe_remote_get(
            esc_url_raw( $file_url ),
            array(
                'redirection' => 0,
            )
        );

        $response_code = intval( wp_remote_retrieve_response_code( $response ) );

        if($response_code === 200) {
            set_transient( $cache_key, $response_code, 1 * DAY_IN_SECONDS );
            return $response_code;
        }

        return false;
    }

	function devvn_enqueue_UseAjaxInWp()
        {
            $page_id = array(wc_get_page_id('checkout'), wc_get_page_id('cart'));
            $edit_address = get_option('woocommerce_myaccount_page_id');
            if (($page_id && in_array(get_the_ID(), $page_id) ) || ($edit_address && is_page($edit_address)) || apply_filters('vn_checkout_allow_script_all_page', false)) {
                wp_enqueue_style('dwas_styles', plugins_url('/assets/css/devvn_dwas_style.css', __FILE__), array(), $this->_version, 'all');

                wp_enqueue_script('devvn_tinhthanhpho', plugins_url('assets/js/devvn_tinhthanh.js', __FILE__), array('jquery', 'select2'), $this->_version, true);

                $get_address = DEVVN_DWAS_URL . 'get-address.php';
                if($this->check_file_open_status($get_address) != 200){
                    $get_address = admin_url( 'admin-ajax.php');
                }

                $php_array = array(
                    'admin_ajax' => admin_url('admin-ajax.php'),
                    'get_address'		=>	$get_address,
                    'home_url' => home_url(),
                    'formatNoMatches' => __('No value', 'devvn-vncheckout'),
                    'phone_error' => __('Phone number is incorrect', 'devvn-vncheckout'),
                    'loading_text' => __('Loading...', 'devvn-vncheckout'),
                    'loadaddress_error' => __('Phone number does not exist', 'devvn-vncheckout')
                );
                wp_localize_script('devvn_tinhthanhpho', 'vncheckout_array', $php_array);
            }
        }
	
	function load_diagioihanhchinh_func()
	{
		$matp = isset($_POST['matp']) ? wc_clean(wp_unslash($_POST['matp'])) : '';
		$maqh = isset($_POST['maqh']) ? intval($_POST['maqh']) : '';
		if ($matp) {
			$result = $this->get_list_district($matp);
			wp_send_json_success($result);
		}
		if ($maqh) {
			$result = $this->get_list_village($maqh);
			wp_send_json_success($result);
		}
		wp_send_json_error();
		die();
	}

	function devvn_get_name_location($arg = array(), $id = '', $key = '')
        {
            if (is_array($arg) && !empty($arg)) {
                $nameQuan = $this->search_in_array($arg, $key, $id);
                $nameQuan = isset($nameQuan[0]['name']) ? $nameQuan[0]['name'] : '';
                return $nameQuan;
            }
            return false;
        }
	
		function get_name_city($id = '')
        {
            global $tinh_thanhpho;
            $tinh_thanhpho = apply_filters('devvn_states_vn', $tinh_thanhpho);
            if (is_numeric($id)) {
                $id_tinh = sprintf("%02d", intval($id));
                if (!is_array($tinh_thanhpho) || empty($tinh_thanhpho)) {
                    include 'cities/tinh_thanhpho_old.php';
                }
            } else {
                $id_tinh = wc_clean(wp_unslash($id));
            }
            $tinh_thanhpho_name = (isset($tinh_thanhpho[$id_tinh])) ? $tinh_thanhpho[$id_tinh] : '';
            return $tinh_thanhpho_name;
        }
	
		function get_name_district($id = '')
        {
            include 'cities/quan_huyen.php';
            $id_quan = sprintf("%03d", intval($id));
            if (is_array($quan_huyen) && !empty($quan_huyen)) {
                $nameQuan = $this->search_in_array($quan_huyen, 'maqh', $id_quan);
                $nameQuan = isset($nameQuan[0]['name']) ? $nameQuan[0]['name'] : '';
                return $nameQuan;
            }
            return false;
        }
	
		function get_name_village($id = '')
        {
            include 'cities/xa_phuong_thitran.php';
            $id_xa = sprintf("%05d", intval($id));
            if (is_array($xa_phuong_thitran) && !empty($xa_phuong_thitran)) {
                $name = $this->search_in_array($xa_phuong_thitran, 'xaid', $id_xa);
                $name = isset($name[0]['name']) ? $name[0]['name'] : '';
                return $name;
            }
            return false;
        }
	
	function devvn_woocommerce_localisation_address_formats($arg){
		unset($arg['default']);
		unset($arg['VN']);
		$arg['default'] = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{country}";
		$arg['VN'] = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{country}";
		return $arg;
	}
		
	function devvn_woocommerce_order_formatted_billing_address($eArg,$eThis){
		
		$nameTinh = $this->get_name_city(get_post_meta( $eThis->get_id(), '_billing_state', true ));
		$nameQuan = $this->get_name_district(get_post_meta( $eThis->get_id(), '_billing_city', true ));
		$nameXa = $this->get_name_village(get_post_meta( $eThis->get_id(), '_billing_address_2', true ));
		
		unset($eArg['state']);
		unset($eArg['city']);
		unset($eArg['address_2']);
		
		$eArg['state'] = $nameTinh;
		$eArg['city'] = $nameQuan;
		$eArg['address_2'] = $nameXa;
		
		return $eArg;
	}	
	
	function devvn_woocommerce_order_formatted_shipping_address($eArg,$eThis){
		
		$nameTinh = $this->get_name_city(get_post_meta( $eThis->get_id(), '_billing_state', true ));
		$nameQuan = $this->get_name_district(get_post_meta( $eThis->get_id(), '_billing_city', true ));
		$nameXa = $this->get_name_village(get_post_meta( $eThis->get_id(), '_billing_address_2', true ));
		
		unset($eArg['state']);
		unset($eArg['city']);
		unset($eArg['address_2']);
		
		$eArg['state'] = $nameTinh;
		$eArg['city'] = $nameQuan;
		$eArg['address_2'] = $nameXa;
		
		return $eArg;
	}
	
	function devvn_woocommerce_my_account_my_address_formatted_address($args, $customer_id, $name){
				
		$nameTinh = $this->get_name_city(get_user_meta( $customer_id, $name.'_state', true ));
		$nameQuan = $this->get_name_district(get_user_meta( $customer_id, $name.'_city', true ));
		$nameXa = $this->get_name_village(get_user_meta( $customer_id, $name.'_address_2', true ));
		
		unset($args['address_2']);
		unset($args['city']);
		unset($args['state']);
		
		$args['state'] = $nameTinh;
		$args['city'] = $nameQuan;
		$args['address_2'] = $nameXa;
		
		return $args;
	}
	
	function get_list_district($matp = '')
        {
            if (!$matp) return false;
            if (is_numeric($matp)) {
                include 'cities/quan_huyen_old.php';
                $matp = sprintf("%02d", intval($matp));
            } else {
                include 'cities/quan_huyen.php';
                $matp = wc_clean(wp_unslash($matp));
            }
            $result = $this->search_in_array($quan_huyen, 'matp', $matp);
            usort($result, array($this, 'natorder'));
            return $result;
        }
	
	function get_list_village($maqh = ''){
		if(!$maqh) return false;		
		include 'cities/xa_phuong_thitran.php';
		$id_xa = sprintf("%05d", intval($maqh));
		$result = $this->search_in_array($xa_phuong_thitran,'maqh',$id_xa);
		return $result;
	}
	
	function devvn_after_shipping_address($order){
	  echo '<p><strong>'.__('Số ĐT người nhận').':</strong> <br>' . get_post_meta( $order->get_id(), '_shipping_phone', true ) . '</p>';
	}
	
	function devvn_woocommerce_order_details_after_customer_details($order){
		ob_start();		
		if ( $order->shipping_phone ) : ?>
			<tr>
				<th><?php _e( 'Shipping Telephone:', 'woocommerce' ); ?></th>
				<td><?php echo esc_html( $order->shipping_phone ); ?></td>
			</tr>
		<?php endif;
		echo ob_get_clean();
	}
	
	public function get_options($option = 'active_village'){
		$flra_options = wp_parse_args(get_option($this->_optionName),$this->_defaultOptions);
		return isset($flra_options[$option])?$flra_options[$option]:false;
	} 
	
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'woocommerce_district_shipping_styles', plugins_url( '/assets/css/admin.css', __FILE__ ) );
		wp_register_script( 'woocommerce_district_shipping_rate_rows', plugins_url( '/assets/js/admin-district-shipping.js', __FILE__ ), array( 'jquery', 'wp-util' ) );
		wp_localize_script( 'woocommerce_district_shipping_rate_rows', 'woocommerce_district_shipping_rate_rows', array(
			'i18n' => array(
				'delete_rates' => __( 'Delete the selected boxes?', 'woocommerce-table-rate-shipping' ),
			),
			'delete_box_nonce' => wp_create_nonce( "delete-box" ),
		) );
	}
	
	function devvn_district_zone_shipping_woocommerce_init() {
		if ( $this->devvn_district_zone_shipping_check_woo_version() ) {
			add_filter( 'woocommerce_shipping_methods', array($this, 'devvn_district_zone_woocommerce_shipping_methods') );
			add_action( 'woocommerce_shipping_init', array($this, 'devvn_district_zone_woocommerce_shipping_init') );
		}
	}
	/*Check version*/
	function devvn_district_zone_shipping_check_woo_version( $minimum_required = "2.6" ) {
		$woocommerce = WC();
		$version = $woocommerce->version;	
		$active = version_compare( $version, $minimum_required, "ge" );
		return( $active );
	}
	/*filter woocommerce_shipping_methods*/
	function devvn_district_zone_woocommerce_shipping_methods( $methods ) {
		$methods['devvn_district_zone_shipping'] = 'DevVn_District_Zone_Shipping';
		return $methods;
	}
	function devvn_district_zone_woocommerce_shipping_init() {
		if ( class_exists( 'WC_Shipping_Method' ) ) {		
			if ( !class_exists( "DevVn_District_Zone_Shipping" ) ) {				
				require_once( 'includes/class-devvn-district-zone-shipping.php' );
			}
	  }
	}
		
}
}//End if active woo
if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
    add_filter('default_checkout_billing_country ', 'devvn_change_default_checkout_country');
}else{
    add_filter('default_checkout_country ', 'devvn_change_default_checkout_country');
}
function devvn_change_default_checkout_country() {
  return 'VN'; 
}

add_filter( 'woocommerce_default_address_fields' , 'devvn_custom_override_default_address_fields' );
function devvn_custom_override_default_address_fields( $address_fields ) {
     unset($address_fields['first_name']);
     unset($address_fields['postcode']);
     unset($address_fields['address_2']);
     unset($address_fields['state']);
     unset($address_fields['city']);
     $address_fields['last_name'] = array(
	    'label' => __('Họ và tên', 'devvn'),
	    'placeholder' => _x('Nhập đầy đủ họ và tên của bạn', 'placeholder', 'devvn'),
	    'required' => true,
	    'class' => array('form-row-wide'),
	    'clear' => true
	 );
	 //order
	 $order = array(
	    "last_name",
	    "company",
	    "country",
	    "address_1",	    
	  );
	  foreach($order as $field){
	  	$ordered_fields[$field] = $address_fields[$field];
	  }
	  $address_fields = $ordered_fields;
	 
     return $address_fields;
}