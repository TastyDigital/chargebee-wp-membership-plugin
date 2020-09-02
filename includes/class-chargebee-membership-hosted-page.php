<?php

class CBHostedPages {

    function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'add_cb_hosted_page_js'));
        add_shortcode('cb_account_portal', array($this, 'print_cb_js'));
        add_shortcode('cb_product_subscribe_hosted', array($this, 'print_cb_subscribe_js'));

        add_action('wp_ajax_portal_session_object', array($this, 'create_portal_session_obj'));
        add_action('wp_ajax_hosted_page_object', array($this, 'create_hosted_page_obj'));

        add_action('user_register', array($this, 'create_chargebee_customer_on_wp_reg'));
        add_action('login_footer', array($this, 'print_cb_logout_js'));
    }

    function add_cb_hosted_page_js() {
        wp_register_script('axios', plugins_url('/js/axios-min.js', dirname(__FILE__)), array(), '0.16.2');
        wp_register_script('cb_hosted_page_js', 'https://js.chargebee.com/v2/chargebee.js', array('axios'), '2');
        wp_register_script('cb_hp_subscribe', plugins_url('/js/cb-hp-v3-subscribe.js', dirname(__FILE__)), array('jquery'));
        wp_register_script('cb_hp_account_portal', plugins_url('/js/cb-hp-v3-acportal.js', dirname(__FILE__)), array('jquery'));
    }

    function print_cb_js($atts, $content = "") {

        extract(shortcode_atts(array('class' => ''), $atts));

        $current_user = wp_get_current_user();

        ob_start();
        ?>
        <a href="#" id="cb-account-portal" class="<?php echo $class; ?>" target="_blank"><?php echo esc_html($content); ?></a>

        <?php
        wp_enqueue_script('axios');
        wp_enqueue_script('cb_hosted_page_js');
        $localization_array = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'cb_site' => get_option('cbm_site_name'),
            'is_logged_in' => is_user_logged_in()
        );
        wp_localize_script('cb_hp_account_portal', 'cb_ac_button', $localization_array);
        wp_enqueue_script('cb_hp_account_portal');


        $output = ob_get_clean();
        return $output;
    }

    function print_cb_logout_js() {

        if (isset($_GET['loggedout']) && $_GET['loggedout'] == 'true') {
            ?>
            <script src="https://js.chargebee.com/v2/chargebee.js"></script>
            <script type="text/javascript">

                (function () {
                    var chargebeeInstance = Chargebee.init({
                        site: "<?php echo get_option('cbm_site_name'); ?>"
                    });
                    chargebeeInstance.logout();
                })();
            </script>
            <?php
        }
    }

    function print_cb_subscribe_js($atts, $content = "") {

        extract(shortcode_atts(array('product_id' => '', 'class' => ''), $atts));

        $current_user = wp_get_current_user();

        ob_start();
        ?>
        <a href="#" data-cbproduct="<?php echo strip_tags($product_id); ?>" class="cb-subscribe  <?php echo $class; ?>" target="_blank"><?php echo esc_html($content); ?></a>

        <?php
        wp_enqueue_script('axios');
        wp_enqueue_script('cb_hosted_page_js');
        $localization_array = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'cb_site' => get_option('cbm_site_name'),
            'is_logged_in' => is_user_logged_in()
        );
        wp_localize_script('cb_hp_subscribe', 'cb_sub_button', $localization_array);
        wp_enqueue_script('cb_hp_subscribe');

        $output = ob_get_clean();
        return $output;
    }

    function create_portal_session_obj() {

        header('Content-Type: application/json');
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $throw_resposne = json_encode(array("good_to_go" => "no", "msg" => "no_process"));

        if ($user_id) {

            //check nonce
            //$nonce = $_POST[];
            //wp_verify_nonce( $nonce, 'cb_portal_session_for_'.$user_id );

            $customer_id = get_user_meta($user_id, 'chargebee_user_id', true);
            if ($customer_id) {
                $url = 'portal_sessions';
                $parameters = array(
                    'customer[id]' => $customer_id,
                    'redirect_url' => esc_url(site_url()),
                );
                $method = 'post';

                $res = Chargebee_Membership_Request::chargebee_api_request($url, $parameters, $method);

                // Check for empty response.
                if (!empty($res)) {
                    $res_code = wp_remote_retrieve_response_code($res);
                    $res_data_obj = wp_remote_retrieve_body($res);
                    if (200 === $res_code) {
                        $res_data_obj = json_decode($res_data_obj);
                        $portal_obj = $res_data_obj->portal_session;
                        $throw_resposne = json_encode(array("good_to_go" => "yeah", "portal_obj" => $portal_obj));
                    } else {
                        $throw_resposne = json_encode(array("good_to_go" => "no", "msg" => "no_200"));
                    }
                }
            } else {
                $throw_resposne = json_encode(array("good_to_go" => "no", "msg" => "no_cb_user_id"));
            }
        }
        echo $throw_resposne;
        exit();
    }

    function create_hosted_page_obj() {


        header('Content-Type: application/json');
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $plan_id = $_POST['plan'];
        $throw_resposne = json_encode(array("good_to_go" => "no", "msg" => "no_process"));


        if ($user_id) {

            $customer_id = get_user_meta($user_id, 'chargebee_user_id', true);
            if ($customer_id) {
                $url = 'hosted_pages/checkout_new';
                $parameters = array(
                    'subscription[plan_id]' => $plan_id,
                    'customer[id]' => $customer_id,
                    'redirect_url' => esc_url(site_url())
                );
                $method = 'post';

                $res = Chargebee_Membership_Request::chargebee_api_request($url, $parameters, $method);

                $account_url = '';
                // Check for empty response.
                if (!empty($res)) {
                    $res_code = wp_remote_retrieve_response_code($res);
                    $res_data_obj = wp_remote_retrieve_body($res);
                    if (200 === $res_code) {
                        $res_data_obj = json_decode($res_data_obj);
                        $hosted_page_obj = $res_data_obj->hosted_page;
                        $throw_resposne = json_encode(array("good_to_go" => "yeah", "hosted_page" => $hosted_page_obj));
                    } else {
                        $throw_resposne = json_encode(array("good_to_go" => "no", "msg" => "no_200"));
                    }
                }
            } else {
                $throw_resposne = json_encode(array("good_to_go" => "no", "msg" => "no_cb_user_id"));
            }
        }
        echo $throw_resposne;
        exit();
    }

    function create_chargebee_customer_on_wp_reg($new_user_id) {

        $user_info = get_userdata($new_user_id);
        if (!is_admin()) {
            $options = get_option('cbm_general');
            $plan_id = '';
            if (!empty($options)) {
                $plan_id = !empty($options['cbm_default_level']) ? $options['cbm_default_level'] : '';
            }
            if (!empty($plan_id) && $plan_id != '0') {
                $url = 'subscriptions';
                $parameters = array(
                    'plan_id' => $plan_id,
                    'customer[first_name]' => $user_info->first_name,
                    'customer[last_name]' => $user_info->last_name,
                    'customer[email]' => $user_info->user_email,
                    'billing_address[first_name]' => $user_info->first_name,
                    'billing_address[last_name]' => $user_info->last_name,
                );
                $cbm_request_obj = new Chargebee_Membership_Request();
                $res = $cbm_request_obj->chargebee_api_request($url, $parameters, 'post');

                if (!empty($res)) {

                    $res_code = wp_remote_retrieve_response_code($res);

                    // Check code of response.
                    if (200 === $res_code) {
                        $res_data = json_decode(wp_remote_retrieve_body($res));
                        $customer_data = $res_data->customer;
                        $customer_id = $customer_data->id;
                        // Update usermeta for customer id.

                        $cb_plan_id = $res_data->subscription->plan_id;


                        $product = Chargebee_Membership_Product_Query::get_product_data($cb_plan_id);
                        if (!empty($product)) {
                            if (!empty($product->price) && !empty($product->currency_code) && !empty($product->period) && !empty($product->period_unit)) {
                                $price = $product->price . ' ' . $product->currency_code . ' / ' . $product->period . ' ' . $product->period_unit;
                            } else {
                                $price = '0.00 ' . $product->currency_code . ' / ' . $product->period . ' ' . $product->period_unit;
                            }
                        }
                        $new_subscriptions[] = array(
                            'subscription_id' => $res_data->subscription->id,
                            'product_id' => $res_data->subscription->plan_id,
                            'product_name' => !empty($product->product_name) ? $product->product_name : '',
                            'product_decs' => !empty($product->description) ? $product->description : '',
                            'status' => !empty($res_data->subscription->status) ? $res_data->subscription->status : '',
                            'product_price' => $price,
                            'trail_start' => (!empty($res_data->subscription->trial_start) ) ? date('d/m/Y', $res_data->subscription->trial_start) : '',
                            'trial_end' => (!empty($res_data->subscription->trial_end) ) ? date('d/m/Y', $res_data->subscription->trial_end) : '',
                        );
                        update_user_meta($new_user_id, 'chargebee_user_subscriptions', $new_subscriptions);

                        update_user_meta($new_user_id, 'chargebee_user_id', $customer_id);
                        $user_info->set_role('chargebee_member'); //change role to chargebee 
                    }
                }
            } else {
                $this->just_create_cb_customer($new_user_id);
            }
        }
    }

    function just_create_cb_customer($new_user_id) {

        $user_info = get_userdata($new_user_id);
        if (in_array('subscriber', (array) $user_info->roles)) {
            //The user has the "author" role
            $url = 'customers';
            $parameters = array(
                'first_name' => $user_info->first_name,
                'last_name' => $user_info->last_name,
                'email' => $user_info->user_email,
                'billing_address[first_name]' => $user_info->first_name,
                'billing_address[last_name]' => $user_info->last_name,
            );
            $customer_id = '';

            $cbm_request_obj = new Chargebee_Membership_Request();
            $res = $cbm_request_obj->chargebee_api_request($url, $parameters, 'post');

            if (!empty($res)) {

                $res_code = wp_remote_retrieve_response_code($res);

                // Check code of response.
                if (200 === $res_code) {
                    $res_data = json_decode(wp_remote_retrieve_body($res));
                    $customer_data = $res_data->customer;
                    $customer_id = $customer_data->id;
                    // Update usermeta for customer id.
                    update_user_meta($new_user_id, 'chargebee_user_id', $customer_id);
                    $user_info->set_role('chargebee_member'); //change role to chargebee 
                }
            }
            //chargebee_member
        }
    }

}

new CBHostedPages();
?>