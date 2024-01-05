<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$flra_options = wp_parse_args(get_option($this->_optionName),$this->_defaultOptions);
if($flra_options['to_vnd']) {
    add_filter('woocommerce_currency_symbol', 'woo_district_change_existing_currency_symbol', 10, 2);
    function woo_district_change_existing_currency_symbol($currency_symbol, $currency)
    {
        switch ($currency) {
            case 'VND':
                $currency_symbol = 'VNĐ';
                break;
        }
        return $currency_symbol;
    }
}