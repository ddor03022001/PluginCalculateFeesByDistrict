<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
devvn_creat_shipping_rates_table();
function devvn_creat_shipping_rates_table(){
	global $wpdb;
	$wpdb->hide_errors();	
	$collate = '';	
	if ( $wpdb->has_cap( 'collation' ) ) {
		$collate = $wpdb->get_charset_collate();
	}
	$table_name = $wpdb->prefix . 'woocommerce_devvn_district_shipping_rates';
	
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "
		  CREATE TABLE $table_name (
		  box_id bigint(20) NOT NULL auto_increment,
		  box_district varchar(200) NOT NULL,
		  box_cost varchar(200) NOT NULL,
		  box_title varchar(200) NOT NULL,
		  shipping_method_id bigint(20) NOT NULL,
		  box_length varchar(200) NOT NULL,
		  box_width varchar(200) NOT NULL,
		  box_height varchar(200) NOT NULL,
		  box_max_weight varchar(200) NOT NULL,
		  box_cost_per_weight_unit varchar(200) NOT NULL,
		  box_cost_percent varchar(200) NOT NULL,
		  
		  PRIMARY KEY  (box_id)
		) $collate;";
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}else{
		if($wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'box_cost_percent'") != 'box_cost_percent'){			
			$wpdb->query("ALTER TABLE $table_name ADD COLUMN box_length varchar(200) NOT NULL");
			$wpdb->query("ALTER TABLE $table_name ADD COLUMN box_width varchar(200) NOT NULL");
			$wpdb->query("ALTER TABLE $table_name ADD COLUMN box_height varchar(200) NOT NULL");
			$wpdb->query("ALTER TABLE $table_name ADD COLUMN box_max_weight varchar(200) NOT NULL");
			$wpdb->query("ALTER TABLE $table_name ADD COLUMN box_cost_per_weight_unit varchar(200) NOT NULL");
			$wpdb->query("ALTER TABLE $table_name ADD COLUMN box_cost_percent varchar(200) NOT NULL");
		}
	}
}
function get_qh_option($matp = '', $selected = ''){
	$list_quanhuyen = new Woo_Address_Selectbox_Class;
	$list_quanhuyen = $list_quanhuyen->get_list_district($matp);
	if(is_array($list_quanhuyen) && !empty($list_quanhuyen)){
		$option = '<option value=""></option>';
		foreach ($list_quanhuyen as $value){
			$option .= '<option value="'.$value['maqh'].'" '.selected($selected,$value['maqh']).'>'.$value['name'].'</option>';
		}
	}
	return $option;
}
function devvn_box_shipping_admin_rows( $method ) {
	global $wpdb;
	wp_enqueue_script( 'woocommerce_district_shipping_rate_rows' );	
	
	$instance_id = intval($method->instance_id);		
	$zoneID = $wpdb->get_row( "SELECT zone_id FROM `".$wpdb->prefix."woocommerce_shipping_zone_methods` WHERE `instance_id` = ".$instance_id, ARRAY_A);
	$zoneID = isset($zoneID['zone_id'])?intval($zoneID['zone_id']):'';
	if($zoneID){
		$get_qh = $wpdb->get_results( "SELECT location_code 
										FROM `".$wpdb->prefix."woocommerce_shipping_zone_locations` 
										WHERE `zone_id` = ".$zoneID." 
										AND `location_type` = 'state'
									", ARRAY_A);
		if($get_qh && is_array($get_qh) && !empty($get_qh)){
		$firstQH = $get_qh[0]['location_code'];
		$firstQH = explode(':', $firstQH);
		if($firstQH[0] == 'VN'){
		?>
		<table id="flat_rate_boxes" class="shippingrows widefat" cellspacing="0" style="position:relative;">
			<thead>
				<tr>
					<th class="check-column"><input type="checkbox"></th>
					<th><?php _e( 'Quận/huyện', 'devvn' ); ?></th>
					<th><?php _e( 'Phí vận chuyển', 'devvn' ); ?></th>
					<th><?php _e( 'Tiêu đề', 'devvn' ); ?></th>				
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th colspan="10"><a href="#" class="add-box button button-primary"><?php _e( 'Thêm quận/huyện', 'devvn' ); ?></a> <a href="#" class="remove button"><?php _e( 'Xóa lựa chọn', 'devvn' ); ?></a></th>
				</tr>
			</tfoot>
			<tbody class="flat_rate_boxes" data-boxes="">
				<?php
				$hasqh = $method->get_boxes();
				if($hasqh && is_array($hasqh) && !empty($hasqh)):				
				$stt = 0;
				foreach ($hasqh as $data):
				?>
				<tr class="flat_rate_box">
					<td class="check-column">
						<input type="checkbox" name="select" />
						<input type="hidden" class="box_id" name="box_id[<?php echo $stt;?>]" value="<?php echo $data['box_id'];?>" />
					</td>
					<td><select class="select" name="box_district[<?php echo $stt;?>]"><?php echo get_qh_option($firstQH[1],$data['box_district']);?></select></td>
					<td><input type="number" class="text" name="box_cost[<?php echo $stt;?>]" placeholder="<?php _e( '0', 'devvn' ); ?>" size="4" value="<?php echo $data['box_cost'];?>" /></td>
					<td><input type="text" class="text" name="box_title[<?php echo $stt;?>]" placeholder="<?php _e( 'Ví dụ', 'devvn' ); ?>" value="<?php echo $data['box_title'];?>" /></td>	
				</tr>
				<?php $stt++;endforeach;endif;?>
			</tbody>
		</table>
		<script type="text/template" id="tmpl-district-rate-box-row-template">
		<tr class="flat_rate_box">
			<td class="check-column">
				<input type="checkbox" name="select" />
				<input type="hidden" class="box_id" name="box_id[{{{ data.index }}}]" value="{{{ data.box.box_id }}}" />
			</td>
			<td><select class="select" name="box_district[{{{ data.index }}}]" data-value="{{{ data.box.box_district }}}"><?php echo get_qh_option($firstQH[1],'');?></select></td>
			<td><input type="text" class="text" name="box_cost[{{{ data.index }}}]" placeholder="<?php _e( '0', 'devvn' ); ?>" size="4" value="{{{ data.box.box_cost }}}" /></td>
			<td><input type="text" class="text" name="box_title[{{{ data.index }}}]" placeholder="<?php _e( 'Tiêu đề của hình thức vận chuyển', 'devvn' ); ?>" value="{{{ data.box.box_title }}}" /></td>	
		</tr>
		</script>
		<?php	
		}	
		}
	}
}

function devvn_box_shipping_admin_rows_process( $shipping_method_id ) {
	global $wpdb;

	// Clear cache
	$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_ship_%')" );

	// Save rates
	$box_ids          = isset( $_POST['box_id'] ) ? array_map( 'intval', $_POST['box_id'] ) : array();
	$box_districts    = isset( $_POST['box_district'] ) ? array_map( 'woocommerce_clean', $_POST['box_district'] ) : array();
	$box_costs        = isset( $_POST['box_cost'] ) ? array_map( 'woocommerce_clean', $_POST['box_cost'] ) : array();
	$box_titles       = isset( $_POST['box_title'] ) ? array_map( 'woocommerce_clean', $_POST['box_title'] ) : array();
	
	// Get max key
	$max_key = ( $box_ids ) ? max( array_keys( $box_ids ) ) : 0;

	for ( $i = 0; $i <= $max_key; $i++ ) {

		if ( ! isset( $box_ids[ $i ] )) {
			continue;
		}

		$box_id                   = $box_ids[ $i ];
		$box_district             = isset($box_districts[ $i ])?$box_districts[ $i ]:'';
		$box_cost                 = isset($box_costs[ $i ])?$box_costs[ $i ]:'';
		$box_title                = isset($box_titles[ $i ])?$box_titles[ $i ]:'';
		$box_length = $box_width = $box_height = $box_max_weight = $box_cost_per_weight_unit = $box_cost_percent = 0;	

		if ( $box_id > 0 ) {

			// Update row
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_devvn_district_shipping_rates',
				array(
					'box_district'           	=> $box_district,
					'box_cost'               	=> $box_cost,
					'box_title'              	=> $box_title,
					'shipping_method_id'     	=> $shipping_method_id,
					'box_length'               	=> $box_length,
					'box_width'                	=> $box_width,
					'box_height'               	=> $box_height,
					'box_max_weight'           	=> $box_max_weight,					
					'box_cost_per_weight_unit' 	=> $box_cost_per_weight_unit,
					'box_cost_percent'         	=> $box_cost_percent,
				),
				array(
					'box_id' => $box_id
				),
				array(
					'%s',
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				),
				array(
					'%d'
				)
			);		

		} else {

			// Insert row
			$result = $wpdb->insert(
				$wpdb->prefix . 'woocommerce_devvn_district_shipping_rates',
				array(
					'box_district'           	=> $box_district,
					'box_cost'               	=> $box_cost,
					'box_title'              	=> $box_title,
					'shipping_method_id'     	=> $shipping_method_id,
					'box_length'               	=> $box_length,
					'box_width'                	=> $box_width,
					'box_height'               	=> $box_height,
					'box_max_weight'           	=> $box_max_weight,
					'box_cost_per_weight_unit' 	=> $box_cost_per_weight_unit,
					'box_cost_percent'         	=> $box_cost_percent,
				),
				array(
					'%s',
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);
		}
	}
}

add_action('wp_ajax_woocommerce_district_rate_box_delete', 'woocommerce_district_rate_box_delete');
function woocommerce_district_rate_box_delete() {
	check_ajax_referer( 'delete-box', 'security' );

	if ( is_array( $_POST['box_id'] ) ) {
		$box_ids = array_map( 'intval', $_POST['box_id'] );
	} else {
		$box_ids = array( intval( $_POST['box_id'] ) );
	}

	if ( ! empty( $box_ids ) ) {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_devvn_district_shipping_rates WHERE box_id IN (" . implode( ',', $box_ids ) . ")" );
	}

	die();
}
