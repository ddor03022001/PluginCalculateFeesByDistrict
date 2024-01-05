<div class="wrap">
	<h1>Woocommerce District Address</h1>
	<p>Plugin được viết và phát triển bởi <a href="http://levantoan.com" target="_blank" title="Đến web của Toản">Lê Văn Toản</a></p>

	<form method="post" action="options.php" novalidate="novalidate">
	<?php
	settings_fields( $this->_optionGroup );
	$flra_options = wp_parse_args(get_option($this->_optionName),$this->_defaultOptions);
	?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="activeplugin"><?php _e('Ẩn mục phường/xã','devvn')?></label></th>
					<td>
						<label><input type="checkbox" name="<?=$this->_optionName?>[active_village]" <?php checked('1',$flra_options['active_village'])?> value="1" /> <?php _e('Ẩn mục phường/xã','devvn')?></label>	                   
					</td>
				</tr>
                <tr>
                    <th scope="row"><label for="required_village"><?php _e('KHÔNG bắt buộc nhập phường/xã','devvn')?></label></th>
                    <td>
                        <label><input type="checkbox" name="<?=$this->_optionName?>[required_village]" <?php checked('1',$flra_options['required_village'])?> value="1" /> <?php _e('Không bắt buộc','devvn')?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="to_vnd"><?php _e('Chuyển ₫ sang VNĐ','devvn')?></label></th>
                    <td>
                        <label><input type="checkbox" name="<?=$this->_optionName?>[to_vnd]" <?php checked('1',$flra_options['to_vnd'])?> value="1" id="to_vnd"/> <?php _e('Cho phép chuyển sang VNĐ','devvn')?></label><br>
                        <small>Xem thêm <a href="http://levantoan.com/thay-doi-ky-hieu-tien-te-dong-viet-nam-trong-woocommerce-d-sang-vnd/" target="_blank"> cách thiết lập đơn vị tiền tệ ₫ (Việt Nam đồng)</a></small>
                    </td>
                </tr>
                <?php do_settings_fields($this->_optionGroup, 'default'); ?>
			</tbody>
		</table>
		<?php do_settings_sections($this->_optionGroup, 'default'); ?>
		<?php submit_button();?>
	</form>	
</div>
