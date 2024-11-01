<?php

if (!defined('WPINC')) { // Exit if accessed directly
    die;
}

if (!function_exists('is_plugin_active')) {
    require_once ( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if (!function_exists('zendashipping_registration_hook')) {

    /**
     * Let's install the plugin
     *
     * @author Metizsoft Solutions
     * @since  1.0.0
     */
    function zendashipping_registration_hook() {
        
    }

}

/*
 * Check if WooCommerce is active
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    function zenda_shipping_method() {
        if (!class_exists('Zenda_Shipping_Method')) {

            class Zenda_Shipping_Method extends WC_Shipping_Method {
                
                var $username, $password, $mode, $apiurl, $maxweight, $pickup_postcode, $isocountries, $debug_mode, $countries;
                
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id = 'zenda';
                    $this->method_title = __('Zenda Checkout Configuration', 'zenda');
                    //$this->method_description = __('Zenda Checkout Configuration', 'zenda');

                    $this->init();

                    $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Zenda Checkout', 'zenda');

                    $this->mode = isset($this->settings['mode']) ? $this->settings['mode'] : 'sandbox';
                    $this->username = isset($this->settings['username']) ? $this->settings['username'] : '';
                    $this->password = isset($this->settings['password']) ? $this->settings['password'] : '';
                    $this->pickup_postcode = isset($this->settings['pickup_postcode']) ? $this->settings['pickup_postcode'] : '';
                    $this->debug_mode = isset($this->settings['debug_mode']) ? $this->settings['debug_mode'] : 'no';
                    $this->availability = isset($this->settings['availability']) ? $this->settings['availability'] : 'all';
                    $this->countries = isset($this->settings['countries']) ? $this->settings['countries'] : [];
                    $this->default_package_max_weight = (double)66.1;
                    $this->default_package_max_volumn = (double)61000.1;
                    //$this->default_package_max_weight = (isset($this->settings['default_package_max_weight']) && (double)$this->settings['default_package_max_weight'] > 0) ? (double)$this->settings['default_package_max_weight'] : 66.1;
                    //$this->default_package_max_volumn = (isset($this->settings['default_package_max_volumn']) && (double)$this->settings['default_package_max_volumn'] > 0) ? (double)$this->settings['default_package_max_volumn'] : 61000.1;
                    //$this->pound_to_kg = 0.4536;

                    if ($this->mode == "live") {
                        $this->apiurl = "https://prd-api.zenda.global/v1/";
                    } else {
                        $this->apiurl = "https://uat2-api.zenda.global/v1/";
                    }
                }

                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields();
                    $this->init_settings();

                    // Save settings in admin if you have any defined
                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                }

                /**
                 * Define settings field for this shipping
                 * @return void 
                 */
                function init_form_fields() {

                    $this->form_fields = array(
                        'enabled' => array(
                            'title' => __('Enable', 'zenda'),
                            'type' => 'checkbox',
                            'default' => 'yes'
                        ),
                        'title' => array(
                            'title' => __('Title displayed at checkout', 'zenda'),
                            'type' => 'text',
                            'default' => __('Zenda Checkout', 'zenda')
                        ),
                        'enableflatrate' => array(
                            'title' => __('Enable Flat Rate', 'zenda'),
                            'type' => 'checkbox',
                            'class' => 'enableflatrate',
                            'default' => __('no', 'zenda'),
                        ),
                        'flatrate' => array(
                            'title' => __('Flat Rate', 'zenda'),
                            'type' => 'number',
                            'class' => 'flatrate',
                            'default' => __('', 'zenda')
                        ),
                        'username' => array(
                            'title' => __('Zenda Username', 'zenda'),
                            'type' => 'text',
                            'default' => __('', 'zenda')
                        ),
                        'password' => array(
                            'title' => __('Zenda Password', 'zenda'),
                            'type' => 'password',
                            'default' => __('', 'zenda')
                        ),
                        'mode' => array(
                            'title' => __('Live Account', 'zenda'),
                            'type' => 'select',
                            'default' => 'sandbox',
                            'options' => array("sandbox" => "No", "live" => "Yes"),
                        ),
                        'pickup_postcode' => array(
                            'title' => __('Pickup Postcode', 'zenda'),
                            'type' => 'text',
                            'default' => __('', 'zenda')
                        ),
                        'default_package_max_weight' => array(
                            'title' => __('maximum package weight', 'zenda'),
                            'type' => 'text',
                            'default' => __('', 'zenda')
                        ),
                        'default_package_max_volumn' => array(
                            'title' => __('maximum package volume', 'zenda'),
                            'type' => 'text',
                            'default' => __('', 'zenda')
                        ),
                        'debug_mode' => array(
                            'title' => __('Debug Mode', 'zenda'),
                            'label' => __('Enable debug mode', 'zenda'),
                            'type' => 'checkbox',
                        ),
                        'availability' => array(
                            'title' => __('Ship to Applicable Countries', 'zenda'),
                            'type' => 'select',
                            'default' => 'all',
                            'class' => 'availability wc-enhanced-select',
                            'options' => array(
                                'all' => __('All allowed countries', 'zenda'),
                                'specific' => __('Specific Countries', 'zenda'),
                            ),
                        ),
                        'countries' => array(
                            'title' => __('Countries', 'zenda'),
                            'type' => 'multiselect',
                            'default' => 'US',
                            'css' => 'height: 200px;',
                            'options' => $this->getvalidcountry()
                        ),
                    );
                    echo '<script>jQuery(document).ready(function(){
                                jQuery("#toplevel_page_woocommerce ul li").removeClass("current");
                                jQuery("#toplevel_page_woocommerce ul li").each(function(){
                                        var $this = jQuery(this);
                                        if($this.find("a").text() == "Zenda Checkout"){
                                                $this.addClass("current");
                                        }
                                });
                                jQuery("#woocommerce_zenda_default_package_max_weight").parents("tr").hide();
                                jQuery("#woocommerce_zenda_default_package_max_volumn").parents("tr").hide();
                                jQuery("#woocommerce_zenda_enableflatrate").click(function(){
                                    if(jQuery(this).prop("checked") == true){
                                        jQuery("#woocommerce_zenda_flatrate").parents("tr").show();
                                    }else{
                                        jQuery("#woocommerce_zenda_flatrate").parents("tr").hide();
                                    }
                                });
                        })</script>';

                    if ($this->get_option('enableflatrate', '') == 'yes') {
                        echo '<script>
                            jQuery(document).ready(function(){
                                jQuery("#woocommerce_zenda_flatrate").parents("tr").show();
                        })</script>';
                    } else {
                        echo '<script>
                            jQuery(document).ready(function(){
                                jQuery("#woocommerce_zenda_flatrate").parents("tr").hide();
                        })</script>';
                    }
                }

                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping($package = array()) {

                    $weight = 0;
                    $cost = 0;
                    $volume = 0;

                    $zenda_setting = maybe_unserialize(get_option('woocommerce_zenda_settings'));

                    $this->username = isset($zenda_setting['username']) ? $zenda_setting['username'] : '';
                    $this->password = isset($zenda_setting['password']) ? $zenda_setting['password'] : '';
                    $this->mode = isset($zenda_setting['mode']) ? $zenda_setting['mode'] : 'sandbox';
                    $this->enableflatrate = isset($zenda_setting['enableflatrate']) ? $zenda_setting['enableflatrate'] : 'no';
                    $this->flatrate = isset($zenda_setting['flatrate']) ? $zenda_setting['flatrate'] : '0.00';
                    $this->maxweight = 6000;
                    if ($this->mode == "live") {
                        $this->apiurl = "https://prd-api.zenda.global/v1/";
                    } else {
                        $this->apiurl = "https://uat2-api.zenda.global/v1/";
                    }

                    foreach ($package['contents'] as $item_id => $values) {
                        $_product = $values['data'];
                        $weight = $weight + (float) $_product->get_weight() * $values['quantity'];
                    }

                    /* $weight = wc_get_weight($weight, 'lbs');

                      if ($weight == 0 || $weight > $this->maxweight) {
                      if ($weight > $this->maxweight) {
                      $this->debug(__(sprintf('Too heavy to be delivered must be less than %skg your package weighs : %sKg', $this->maxweight, $weight), 'zenda'), 'error');
                      } else {
                      $this->debug(__(sprintf('Too light weight is 0 your package weighs : %sKg', $weight), 'zenda'), 'error');
                      }
                      return;
                      } */

                    if (!is_callable('curl_init')) {
                        $this->debug(__('CURL is not enabled!', 'zenda'));
                        return;
                    }
                    if ($this->pickup_postcode == '') {
                        $this->debug(__('Please add pickup postcode!', 'zenda'));
                        return;
                    }

                    if ($package["destination"]["postcode"] === '') {
                        return;
                    }

                    $this->isocountries = include plugin_dir_path(__FILE__) . '/iso_country_code.php';
                    //echo 'te';
                    $shippingPrice = $this->_getShippingPrice($package);
                    $taxAndDuty = $this->_getTaxAndDuty($shippingPrice, $package);
                    $totalShippingPrice = $shippingPrice + $taxAndDuty;
                    $shippingtext = $this->shippingtext($shippingPrice, $taxAndDuty);
                    /* echo '$shippingPrice : '.$shippingPrice.'<br>';
                      echo '$taxAndDuty : '.$taxAndDuty.'<br>';
                      echo '$shippingtext : '.$shippingtext.'<br>';
                      exit; */
                    if ($shippingPrice > 0.00001) {
                        $rate = array(
                            'id' => $this->id,
                            'label' => $this->title . $shippingtext,
                            'cost' => $totalShippingPrice
                        );
                        $this->add_rate($rate);
                    }
                }

                protected function shippingtext($shippingPrice, $taxAndDuty) {
                    $dynamicText = sprintf(__(' (%s shipping + %s prepaid tax and duty)', 'zenda'), wc_price($shippingPrice), wc_price($taxAndDuty));
                    return $dynamicText;
                }

                protected function _getTaxAndDuty($shippingPrice, $package) {

                    $products = array();
                    $allItems = $package['contents'];
                    $items = array();
                    foreach ($allItems as $item_id => $item) {
                        $_product = $item['data'];
                        $products[] = array(
                            'SKUCode' => $_product->get_sku(),
                            'description' => $_product->get_title(),
                            'value' => (float) $_product->get_price(),
                            'qty' => $item['quantity']
                        );
                    }

                    $totalTaxAndDuty = $this->getTaxAndDuty(
                            $shippingPrice,
                            $this->_getOriginDetails()['countryCode'],
                            $this->_getDestinationDetails($package)['countryCode'],
                            WC()->cart->get_cart_contents_total(),
                            get_woocommerce_currency(),
                            $products
                    );
                   
                    return $totalTaxAndDuty;
                }

                public function getTaxAndDuty($shippingPrice, $sourceCountry, $destinationCountry, $currentCartTotal, $cartCurrencyCode, $products) {

                    if($cartCurrencyCode == 0){
                       $cartCurrencyCode = "USD";
                    }
                    $requestBody = array(
                        'haltOnCommodityException' =>  true,
                        'sourceCountry' => $sourceCountry,
                        'shippingPrice' => $shippingPrice,
                        'destinationCountry' => $destinationCountry,
                        'currentCartValue' => (float) $currentCartTotal,
                        'cartCurrencyCode' => $cartCurrencyCode,
                        'products' => $products
                    );
                     //$msg .= "Test '" . $cartCurrencyCode . "'";
                     //   $this->debug($msg);
                    $totalTaxAndDuty = 0.00;
                                        
                    try {
                        $this->authenticate();

                        if ($this->_getAccessToken()) {

                            $args = array(
                                'method' => 'POST',
                                'timeout' => 30,
                                'user-agent' => $_SERVER['HTTP_USER_AGENT'],
                                'headers' => ['Content-Type' => 'application/json', 'token' => $this->_getAccessToken()],
                                'body' => json_encode($requestBody)
                            );
                            $urlParam = $this->apiurl . "quotes/baskets";
                            $content = wp_remote_post($urlParam, $args);
                            $return = wp_remote_retrieve_body($content);
                            $responseBody = json_decode($return, true);

                            if (isset($responseBody['response'][0]['totalTax']) && isset($responseBody['response'][0]['totalDuty'])
                            ) {
                                $totalTax = $responseBody['response'][0]['totalTax'];
                                $totalDuty = $responseBody['response'][0]['totalDuty'];
                                $totalTaxAndDuty = $totalTax + $totalDuty;
                            } elseif (isset($responseBody['alerts'][0]['code']) && isset($responseBody['alerts'][0]['message'])
                            ) {
                                $msg = $responseBody['alerts'][0]['message'];
                                if (isset($responseBody['commodityException'][0]['exceptionMessage'])) {
                                    $msg .= ': ' . $responseBody['commodityException'][0]['exceptionMessage'];
                                }
                                $this->debug($msg);
                            }
                        }
                    } catch (\Exception $e) {
                        $this->debug('Something wrong in tax and duties detail');
                    }

                    return $totalTaxAndDuty;
                }

                protected function _getShippingPrice($package) {
                    $totalShippingPrice = 0.00;
                    if ($this->enableflatrate == 'yes') {
                        $packages = $this->composePackages($package);
                        if(!empty($packages)) {
                            $totalShippingPrice = (float) $this->flatrate;
                        }
                    } else {

                        /*$maxDimension = $this->getMaxValidDimension(
                                get_woocommerce_currency(),
                                $this->_getOriginDetails(),
                                $this->_getDestinationDetails($package)
                        );*/
                        
                        $packages = $this->composePackages($package);
                        //echo '<pre>';print_r($packages);exit;
                        if (!empty($packages)) {
                            foreach ($packages as $key => $parcel) {
                                // Sum up the shipping price
                                $currency = get_woocommerce_currency();
                                $d_country = $package["destination"]["country"];
                                $d_suburb = $package["destination"]["city"];
                                $d_state = $package["destination"]["state"];
                                $d_postcode = $package["destination"]["postcode"];

                                $totalShippingPrice += $this->getShippingPrice(
                                        $currency,
                                        $this->_getOriginDetails(),
                                        $this->_getDestinationDetails($package),
                                        (float) $parcel['weight'],
                                        (float) $parcel['volume']
                                );
                                //echo '<pre>';print_r($totalShippingPrice);exit;
                            }
                        }
                    }
                    return $totalShippingPrice;
                }

                public function getShippingPrice($currencyCode, $originDetails, $destinationDetails, $packageWeight, $packageVolume) {

                    $requestBody = $this->getShippingRequestBody($currencyCode, $originDetails, $destinationDetails, $packageWeight, $packageVolume);
                    //echo '<pre>';print_r($requestBody);
                    $shippingPrice = 0.00;

                    try {
                        $this->authenticate();

                        if ($this->_getAccessToken()) {

                            $args = array(
                                'method' => 'POST',
                                'timeout' => 30,
                                'user-agent' => $_SERVER['HTTP_USER_AGENT'],
                                'headers' => ['Content-Type' => 'application/json', 'token' => $this->_getAccessToken()],
                                'body' => json_encode($requestBody)
                            );
                            $urlParam = $this->apiurl . "quotes/shipments/";
                            $content = wp_remote_post($urlParam, $args);
                            $return = wp_remote_retrieve_body($content);
                            $responseBody = json_decode($return, true);
                            //echo '<pre>';print_r($responseBody);exit;

                            if (isset($responseBody[0]) && isset($responseBody[0]['cost']['value'])) {
                                $shippingPrice = $responseBody[0]['cost']['value'];
                            } elseif (isset($responseBody['alerts'][0]['code']) && isset($responseBody['alerts'][0]['message'])
                            ) {
                                $msg = $responseBody['alerts'][0]['message'];
                                $this->debug($msg);
                            }
                        }
                    } catch (\Exception $e) {
                        $this->debug('Something wrong in shipping detail');
                    }

                    return $shippingPrice;
                }

                public function getShippingRequestBody($currencyCode, $originDetails, $destinationDetails, $packageWeight, $packageVolume) {

                    $requestBody = array(
                        'serviceLevel' => '',
                        'origin' => array(
                            'postalCode' => $originDetails['postalCode'],
                            'countryCode' => $originDetails['countryCode']
                        ),
                        'destination' => array(
                            'postalCode' => $destinationDetails['postalCode'],
                            'countryCode' => $destinationDetails['countryCode']
                        ),
                        'currencyCode' => $currencyCode,
                        'parcel' => array(
                            'metrics' => array(
                                array(
                                    'metricType' => 'WEIGHT',
                                    'metricValue' => $packageWeight,
                                    'metricUnit' => $this->getWeightUnit()
                                ),
                                array(
                                    'metricType' => 'VOLUME',
                                    'metricValue' => $packageVolume,
                                    'metricUnit' => $this->getVolumeUnit()
                                )
                            )
                        )
                    );
                    //echo '<pre>';print_r($requestBody);exit;
                    return $requestBody;
                }
                
                public function getWeightUnit()
                {
                    $weightUnit = get_option('woocommerce_weight_unit');
                    return $this->_unitMap($weightUnit);
                }
                public function getVolumeUnit() {
                    return $this->_unitMap('inch');
                }
                protected function _unitMap($val)
                {
                    $units = [
                        'lbs' => 'LB',
                        'kgs' => 'KG',
                        'inch' => 'IN3',
                        'cm' => 'CM3'
                    ];

                    if (isset($units[$val])) {
                        return $units[$val];
                    }

                    return false;
                }

                protected function _getOriginDetails() {

                    $store_raw_country = get_option('woocommerce_default_country');
                    $split_country = explode(":", $store_raw_country);
                    $store_country = isset($split_country[0]) ? $split_country[0] : 'US';

                    $postalCode = $this->pickup_postcode ?: get_option('woocommerce_store_postcode');
                    return array(
                        'countryCode' => $this->isocountries[$store_country],
                        'postalCode' => $postalCode
                    );
                }

                /**
                 * Get destination country details
                 *
                 * @return array
                 */
                protected function _getDestinationDetails($package) {

                    $d_country = $package["destination"]["country"];
                    $d_postcode = $package["destination"]["postcode"];

                    $countryCode = isset($this->isocountries[$d_country]) ? $this->isocountries[$d_country] : 'USA';
                    $postalCode = ($d_postcode != '') ? $d_postcode : '';

                    return array(
                        'countryCode' => $countryCode,
                        'postalCode' => $postalCode
                    );
                }

                protected function _getMaxWeight() {
                    return $this->default_package_max_weight;
                }
                protected function _getMaxVolume() {
                    return $this->default_package_max_volumn;
                }
                protected function composePackages($package) {

                    $allItems = $package['contents'];
                    //echo '<pre>';print_r($allItems);
                    $maxWeight = $this->_getMaxWeight();
                    $maxVolume = $this->_getMaxVolume();
                    $items = array();
                    foreach ($allItems as $item_id => $item) {

                        $_product = $item['data'];
                        $itemWeight = (float) $_product->get_weight();
                        $itemWeight = wc_get_weight($itemWeight, 'lbs');

                        $itemLength = (float) $_product->get_length();
                        $itemWidth = (float) $_product->get_width();
                        $itemHeight = (float) $_product->get_height();

                        $itemVolume = 1;
                        if ($itemLength && $itemWidth && $itemHeight) {
                            $itemVolume = ($itemLength * $itemWidth * $itemHeight);
                        }
                        echo $maxWeight.' qty1'.$maxVolume;
                        if ($itemWeight >= $maxWeight || $itemVolume >= $maxVolume) {
                            $message = "Sorry, one of the items is overweight or oversized. Maximum package weight allowed is 66 lbs and volume is 61,000 in³";
                            $this->debug($message, 'error');
                            return array();
                        }
                        //echo $item['quantity'].' qty';
                        for ($i = 0; $i < $item['quantity']; $i++) {
                            $items[] = [
                                'weight' => $itemWeight,
                                'volume' => $itemVolume
                            ];
                        }
                    }
                    //echo '<pre>';print_r($items);

                    $parcels = array();
                    $numberOfItems = count($items);
                    for ($i = 0; $i < $numberOfItems; $i++) {

                        $parcelWeight = $items[$i]['weight'];
                        $parcelVolume = $items[$i]['volume'];
                        for ($j = $i + 1; $j < $numberOfItems; $j++) {

                            if (($parcelWeight + $items[$j]['weight'] > $maxWeight) || ($parcelVolume + $items[$j]['volume'] > $maxVolume)) {
                                break;
                            }

                            $parcelWeight += $items[$j]['weight'];
                            $parcelVolume += $items[$j]['volume'];
                        }
                        $i = $j - 1;
                        $parcels[] = [
                            'weight' => $parcelWeight,
                            'volume' => $parcelVolume
                        ];
                    }
                    //echo '<pre>';print_r($parcels);exit;
                    return $parcels;
                }

                public function getMaxValidDimension($currencyCode, $originDetails, $destinationDetails) {
                    $maxValidWeight = $this->_getMaxValidWeight();
                    $maxValidVolume = $this->_getMaxValidVolume();
                    if (!$maxValidWeight || !$maxValidVolume) {
                        $requestBody = $this->getShippingRequestBody(
                                $currencyCode,
                                $originDetails,
                                $destinationDetails,
                                1,
                                1
                        );
                        try {
                            $this->authenticate();

                            if ($this->_getAccessToken()) {

                                $args = array(
                                    'method' => 'POST',
                                    'timeout' => 30,
                                    'user-agent' => $_SERVER['HTTP_USER_AGENT'],
                                    'headers' => ['Content-Type' => 'application/json', 'token' => $this->_getAccessToken()],
                                    'body' => json_encode($requestBody)
                                );
                                $urlParam = $this->apiurl . "quotes/shipments/";
                                $content = wp_remote_post($urlParam, $args);
                                $return = wp_remote_retrieve_body($content);
                                $responseBody = json_decode($return, true);
                                if (isset($responseBody[0]) && isset($responseBody[0]['maxValidDimension'])) {
                                    foreach ($responseBody[0]['maxValidDimension'] as $dimension) {
                                        if (isset($dimension['metricType']) && isset($dimension['metricValue']) && isset($dimension['metricUnit'])) {
                                            if ($dimension['metricType'] == "WEIGHT") {
                                                $maxValidWeight = $this->convertWeight($dimension['metricValue'], $dimension['metricUnit']);
                                                WC()->session->set('MaxValidWeight', $maxValidWeight);
                                            }
                                            if ($dimension['metricType'] == "VOLUME") {
                                                $maxValidVolume = $this->convertVolume($dimension['metricValue'], $dimension['metricUnit']);
                                                WC()->session->set('MaxValidVolume', $maxValidVolume);
                                            }
                                        }
                                    }
                                } elseif (isset($responseBody['alerts'][0]['code']) && isset($responseBody['alerts'][0]['message'])
                                ) {
                                    $message = $responseBody['alerts'][0]['message'];
                                    $this->debug($message, 'error');
                                }
                            }
                        } catch (\Exception $e) {
                            $message = $e->getCode() . " - " . $e->getMessage() . "\n" . $e->getTraceAsString();
                            $this->debug($message, 'error');
                        }
                    }

                    return ['weight' => $maxValidWeight, 'volume' => $maxValidVolume];
                }

                protected function _getMaxValidWeight() {
                    return WC()->session->get('MaxValidWeight');
                }

                protected function _getMaxValidVolume() {
                    return WC()->session->get('MaxValidVolume');
                }

                function convertWeight($weight, $unit) {
                    $KG_TO_POUND = 2.20462262;
                    switch (strtoupper($unit)) {
                        case 'KG':
                            $weight = $weight * $KG_TO_POUND;
                            break;
                    }

                    return $weight;
                }

                function convertVolume($volume, $unit) {
                    $M2_TO_IN3 = 61023.7441;
                    $CM3_TO_IN3 = 0.0610237441;
                    switch (strtoupper($unit)) {
                        case 'M3':
                            $volume = $volume * $M2_TO_IN3;
                            break;
                        case 'CM3':
                            $volume = $volume * $CM3_TO_IN3;
                            break;
                    }

                    return $volume;
                }
                
                public function getvalidcountry() {
                    $country = [
                        'AT' => 'Austria',
                        'BE' => 'Belgium',
                        'BG' => 'Bulgaria',
                        'HR' => 'Croatia',
                        'CY' => 'Cyprus',
                        'CZ' => 'Czech Republic',
                        'EE' => 'Estonia',
                        'FR' => 'France',
                        'DE' => 'Germany',
                        'GR' => 'Greece',
                        'HU' => 'Hungary',
                        'IE' => 'Ireland',
                        'IT' => 'Italy',
                        'LV' => 'Latvia',
                        'LT' => 'Lithuania',
                        'LU' => 'Luxembourg',
                        'MT' => 'Malta',
                        'NL' => 'Netherlands',
                        'PL' => 'Poland',
                        'PT' => 'Portugal',
                        'RO' => 'Romania',
                        'SK' => 'Slovakia',
                        'SI' => 'Slovenia',
                        'ES' => 'Spain',
                        'GB' => 'United Kingdom (UK)'
                    ];
                    return $country;
                }

                function authenticate() {

                    $status = true;
                    $message = '';
                    if ($this->_isAccessTokenExpired()) {
                        $request = ['username' => $this->username, 'password' => $this->password, 'scope' => 'openid'];
                        $args = array(
                            'method' => 'POST',
                            'timeout' => 30,
                            'user-agent' => $_SERVER['HTTP_USER_AGENT'],
                            'headers' => ['Content-Type' => 'application/json'],
                            'body' => json_encode($request)
                        );
                        $urlParam = $this->apiurl . "token/";
                        $content = wp_remote_post($urlParam, $args);
                        $return = wp_remote_retrieve_body($content);
                        $responseBody = json_decode($return, true);
                        if (isset($responseBody['access_token'])) {
                            // The access token expires then update session data
                            $accessTokenExpiry = $responseBody['expires_in'];
                            $accessTokenExpiry = time() + $accessTokenExpiry;
                            $responseBody['access_token_expires_at'] = $accessTokenExpiry;
                            WC()->session->set('ZendaAuthDetails', $responseBody);
                        } else {
                            $status = false;
                            $message = "API connection could not be established. {$responseBody['error']}: {$responseBody['error_description']}.";
                        }
                    }
                    $return = ['status' => $status, 'message' => $message];
                    return $return;
                }

                function _isAccessTokenExpired() {

                    $authDetails = $this->_getAuthDetails();
                    $accessTokenExpired = true;

                    if (isset($authDetails['access_token']) && isset($authDetails['access_token_expires_at'])) {
                        $accessTokenExpiry = $authDetails['access_token_expires_at'];
                        $accessTokenExpired = $accessTokenExpiry < time();
                    }
                    return $accessTokenExpired;
                }

                function _getAuthDetails() {
                    return WC()->session->get('ZendaAuthDetails');
                }

                function _getAccessToken() {
                    $authDetails = $this->_getAuthDetails();
                    if (isset($authDetails['access_token'])) {
                        return $authDetails['access_token'];
                    }
                    return false;
                }

                function debug($message, $messageType = 'error') {
                    if ($this->debug_mode === 'yes') {
                        if (!wc_has_notice($message, $messageType)) {
                            wc_add_notice($message, $messageType);
                        }
                    }
                }

            }

        }
    }

    add_action('woocommerce_shipping_init', 'zenda_shipping_method');

    function add_zenda_shipping_method($methods) {
        $methods[] = 'Zenda_Shipping_Method';
        return $methods;
    }

    add_filter('woocommerce_shipping_methods', 'add_zenda_shipping_method');

    function zenda_validate_order($posted) {

        $packages = WC()->shipping->get_packages();

        $chosen_methods = WC()->session->get('chosen_shipping_methods');

        if (is_array($chosen_methods) && in_array('zenda', $chosen_methods)) {

            foreach ($packages as $i => $package) {

                if ($chosen_methods[$i] != "zenda") {
                    continue;
                }
            }
        }
    }

    add_action('woocommerce_review_order_before_cart_contents', 'zenda_validate_order', 10);
    add_action('woocommerce_after_checkout_validation', 'zenda_validate_order', 10);
}