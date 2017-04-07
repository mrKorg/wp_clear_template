<?php

/**
 * Check if WooCommerce is active
 **/
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    // Woocommerce_support
    add_action('after_setup_theme', 'woocommerce_support');
    function woocommerce_support()
    {
        add_theme_support('woocommerce');
    }

    function getCurrentProdCatId()
    {
        $category = get_queried_object();
        if (isset($category->ID)) {
            return $category->ID;
        } elseif (isset($category->term_id)) {
            return $category->term_id;
        }
        return;
    }

    function getProductPrice($product)
    {
        $prodType = $product->get_type();
        $price = null;
        if ($prodType == 'variable') {
            if (count($product->get_variation_prices())) {
                $priceArray = $product->get_variation_prices();
                $prices = array_flip($priceArray['price']);
                arsort($prices);
                $prices = array_flip($prices);
                sort($prices);
                $last = count($prices) - 1;
                if ((int)$prices[0] != (int)$prices[$last]) {
                    $price = (int)$prices[0] . " - " . (int)$prices[$last] . " " . get_woocommerce_currency_symbol();
                } else {
                    $price = (int)$prices[0] . " " . get_woocommerce_currency_symbol();
                }
            }
        } elseif ($prodType = 'simple') {
            $price = $product->get_price() . " " . get_woocommerce_currency_symbol();
        }
        return $price;
    }

    // Add to cart
    if (!function_exists('woocommerce_template_loop_add_to_cart')) {
        function woocommerce_template_loop_add_to_cart()
        {
            global $product;

            if ($product->product_type == "variable") {
                woocommerce_variable_add_to_cart();
            } else {
                woocommerce_get_template('loop/add-to-cart.php');
            }
        }
    }
    add_action('wp_ajax_ajax_add_to_cart', 'ajax_add_to_cart_callback');
    add_action('wp_ajax_nopriv_ajax_add_to_cart', 'ajax_add_to_cart_callback');
    function ajax_add_to_cart_callback()
    {

        $product_id = $_POST['product_id'];
        $variation_id = $_POST['variation_id'];
        $variation = $_POST['variation'];

        $cartContent = WC()->cart->get_cart();
        foreach ($cartContent as $cartItem) {
            if ($cartItem['product_id'] == $product_id && $cartItem['variation_id'] == $variation_id) {
                wp_die('exist');
            }
        }

        $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($product_id));
        $quantity = empty($_POST['quantity']) ? 1 : apply_filters('woocommerce_stock_amount', $_POST['quantity']);
        $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);

        if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation)) {
            do_action('woocommerce_ajax_added_to_cart', $product_id);
            wp_die('add');
        } else {
            $this->json_headers();

            // If there was an error adding to the cart, redirect to the product page to show any errors
            $data = array(
                'error' => true,
                'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
            );
            echo json_encode($data);
        }
        wp_die();
    }

    // Variation
    function getVariationArray($product)
    {
        $licensesArr = [];
        $licenses = wc_get_product_terms($product->get_id(), 'pa_license', array('fields' => 'all'));
        foreach ($licenses as $license) {
            $licensesArr[$license->slug] = ['name' => $license->name, 'slug' => $license->slug, 'term_id' => $license->term_id, 'description' => $license->description];
        }
        $variationsArr = [];
        $variations = $product->get_available_variations();
        foreach ($variations as $variation) {

            $slug = $variation['attributes']['attribute_pa_license'];
            $variationsLicense = $licensesArr[$slug];
            $variationInfo = [
                'variation_id' => $variation['variation_id'],
                'variation_is_visible' => $variation['variation_is_visible'],
                'variation_is_active' => $variation['variation_is_active'],
                'price_html' => $variation['price_html']
            ];
            $variationsArr[] = array_merge($variationsLicense, $variationInfo);
        }
        return array_reverse($variationsArr);
    }

    if (!function_exists('wc_dropdown_variation_attribute_options')) {

        /**
         * Output a list of variation attributes for use in the cart forms.
         *
         * @param array $args
         * @since 2.4.0
         */
        function wc_dropdown_variation_attribute_options($args = array())
        {
            $args = wp_parse_args(apply_filters('woocommerce_dropdown_variation_attribute_options_args', $args), array(
                'options' => false,
                'attribute' => false,
                'product' => false,
                'selected' => false,
                'name' => '',
                'id' => '',
                'class' => '',
                'show_option_none' => __('Choose an option', 'woocommerce')
            ));

            $options = $args['options'];
            $product = $args['product'];
            $attribute = $args['attribute'];
            $name = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title($attribute);
            $id = $args['id'] ? $args['id'] : sanitize_title($attribute);
            $class = $args['class'];
            $show_option_none = $args['show_option_none'] ? true : false;
            $show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __('Choose an option', 'woocommerce'); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

            if (empty($options) && !empty($product) && !empty($attribute)) {
                $attributes = $product->get_variation_attributes();
                $options = $attributes[$attribute];
            }
            $html = '<div id="' . esc_attr($id) . '" ' . esc_attr($class) . '" data-attribute_name="attribute_' . esc_attr(sanitize_title($attribute)) . '"' . '" data-show_option_none="' . ($show_option_none ? 'yes' : 'no') . '">';

            if (!empty($options)) {
                if ($product && taxonomy_exists($attribute)) {
                    // Get terms if this is a taxonomy - ordered. We need the names too.
                    $terms = wc_get_product_terms($product->id, $attribute, array('fields' => 'all'));

                    foreach ($terms as $term) {
                        if (in_array($term->slug, $options)) {
                            $color = get_field('color', $term->taxonomy . '_' . $term->term_id);
                            if ($color) {
                                $html .= '<label class="' . esc_attr($id) . '" style="background: ' . $color . '" title="' . esc_html(apply_filters('woocommerce_variation_option_name', $term->name)) . '"><span></span>';
                            } else {
                                $html .= '<label class="' . esc_attr($id) . '" title="' . esc_html(apply_filters('woocommerce_variation_option_name', $term->name)) . '"><span>' . esc_html(apply_filters('woocommerce_variation_option_name', $term->name)) . '</span>';
                            }

                            $html .= '<input name="' . esc_attr($name) . '" type="radio" value="' . esc_attr($term->slug) . '" ' . selected(sanitize_title($args['selected']), $term->slug, false) . '>';
                            $html .= '</label>';
                        }
                    }
                } else {
                    foreach ($options as $option) {
                        // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                        $selected = sanitize_title($args['selected']) === $args['selected'] ? selected($args['selected'], sanitize_title($option), false) : selected($args['selected'], $option, false);
                        $html .= '<input name="' . esc_attr($name) . '[]" type="radio" value="' . esc_attr($option) . '" ' . $selected . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $option));
                    }
                }
            }

            $html .= '</div>';

            echo apply_filters('woocommerce_dropdown_variation_attribute_options_html', $html, $args);
        }
    }

    function get_variation_data_from_variation_id($item_id)
    {
        $_product = new WC_Product_Variation($item_id);
        $variation_data = $_product->get_variation_attributes();
        $variation_detail = woocommerce_get_formatted_variation($variation_data, true);  // this will give all variation detail in one line
        // $variation_detail = woocommerce_get_formatted_variation( $variation_data, false);  // this will give all variation detail one by one
        return $variation_detail; // $variation_detail will return string containing variation detail which can be used to print on website
        // return $variation_data; // $variation_data will return only the data which can be used to store variation data
    }

    if (!function_exists('woocommerce_form_field')) {

        /**
         * Outputs a checkout/address form field.
         *
         * @subpackage    Forms
         * @param string $key
         * @param mixed $args
         * @param string $value (default: null)
         * @todo This function needs to be broken up in smaller pieces
         */
        function woocommerce_form_field($key, $args, $value = null)
        {
            $defaults = array(
                'type' => 'text',
                'label' => '',
                'description' => '',
                'placeholder' => '',
                'maxlength' => false,
                'required' => false,
                'autocomplete' => false,
                'id' => $key,
                'class' => array(),
                'label_class' => array(),
                'input_class' => array(),
                'return' => false,
                'options' => array(),
                'custom_attributes' => array(),
                'validate' => array(),
                'default' => '',
            );

            $args = wp_parse_args($args, $defaults);
            $args = apply_filters('woocommerce_form_field_args', $args, $key, $value);

            if ($args['required']) {
                $args['class'][] = 'validate-required';
                $required = ' <abbr class="required" title="' . esc_attr__('required', 'woocommerce') . '">*</abbr>';
            } else {
                $required = '';
            }

            $args['maxlength'] = ($args['maxlength']) ? 'maxlength="' . absint($args['maxlength']) . '"' : '';

            $args['autocomplete'] = ($args['autocomplete']) ? 'autocomplete="' . esc_attr($args['autocomplete']) . '"' : '';

            if (is_string($args['label_class'])) {
                $args['label_class'] = array($args['label_class']);
            }

            if (is_null($value)) {
                $value = $args['default'];
            }

            // Custom attribute handling
            $custom_attributes = array();

            if (!empty($args['custom_attributes']) && is_array($args['custom_attributes'])) {
                foreach ($args['custom_attributes'] as $attribute => $attribute_value) {
                    $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
                }
            }

            if (!empty($args['validate'])) {
                foreach ($args['validate'] as $validate) {
                    $args['class'][] = 'validate-' . $validate;
                }
            }

            $field = '';
            $label_id = $args['id'];
            $field_container = '<p class="form-row" id="%2$s">%3$s</p>';

            switch ($args['type']) {
                case 'country' :

                    $countries = 'shipping_country' === $key ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();

                    if (1 === sizeof($countries)) {

                        $field .= '<strong>' . current(array_values($countries)) . '</strong>';

                        $field .= '<input type="hidden" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" value="' . current(array_keys($countries)) . '" ' . implode(' ', $custom_attributes) . ' class="country_to_state" />';

                    } else {

                        $field = '<select name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" ' . $args['autocomplete'] . ' class="country_to_state country_select ' . esc_attr(implode(' ', $args['input_class'])) . '" ' . implode(' ', $custom_attributes) . '>'
                            . '<option value="">' . __('Select a country&hellip;', 'woocommerce') . '</option>';

                        foreach ($countries as $ckey => $cvalue) {
                            $field .= '<option value="' . esc_attr($ckey) . '" ' . selected($value, $ckey, false) . '>' . __($cvalue, 'woocommerce') . '</option>';
                        }

                        $field .= '</select>';

                        $field .= '<noscript><input type="submit" name="woocommerce_checkout_update_totals" value="' . esc_attr__('Update country', 'woocommerce') . '" /></noscript>';

                    }

                    break;
                case 'state' :

                    /* Get Country */
                    $country_key = 'billing_state' === $key ? 'billing_country' : 'shipping_country';
                    $current_cc = WC()->checkout->get_value($country_key);
                    $states = WC()->countries->get_states($current_cc);

                    if (is_array($states) && empty($states)) {

                        $field_container = '<p class="form-row" id="%2$s" style="display: none">%3$s</p>';

                        $field .= '<input type="hidden" class="hidden" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" value="" ' . implode(' ', $custom_attributes) . ' />';

                    } elseif (is_array($states)) {

                        $field .= '<select name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" class="state_select ' . esc_attr(implode(' ', $args['input_class'])) . '" ' . implode(' ', $custom_attributes) . ' data-placeholder="' . esc_attr($args['placeholder']) . '" ' . $args['autocomplete'] . '>
						<option value="">' . __('Select a state&hellip;', 'woocommerce') . '</option>';

                        foreach ($states as $ckey => $cvalue) {
                            $field .= '<option value="' . esc_attr($ckey) . '" ' . selected($value, $ckey, false) . '>' . __($cvalue, 'woocommerce') . '</option>';
                        }

                        $field .= '</select>';

                    } else {

                        $field .= '<input type="text" class="input-text ' . esc_attr(implode(' ', $args['input_class'])) . '" value="' . esc_attr($value) . '"  ' . $args['autocomplete'] . ' name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" ' . implode(' ', $custom_attributes) . ' />';

                    }

                    break;
                case 'textarea' :

                    $field .= '<textarea name="' . esc_attr($key) . '" class="input-text ' . esc_attr(implode(' ', $args['input_class'])) . '" id="' . esc_attr($args['id']) . '" ' . $args['maxlength'] . ' ' . $args['autocomplete'] . ' ' . (empty($args['custom_attributes']['rows']) ? ' rows="2"' : '') . (empty($args['custom_attributes']['cols']) ? ' cols="5"' : '') . implode(' ', $custom_attributes) . '>' . esc_textarea($value) . '</textarea>';

                    break;
                case 'checkbox' :

                    $field = '<label class="checkbox ' . implode(' ', $args['label_class']) . '" ' . implode(' ', $custom_attributes) . '>
						<input type="' . esc_attr($args['type']) . '" class="input-checkbox ' . esc_attr(implode(' ', $args['input_class'])) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" value="1" ' . checked($value, 1, false) . ' /> '
                        . $args['label'] . $required . '</label>';

                    break;
                case 'password' :
                case 'text' :
                case 'email' :
                case 'tel' :
                case 'number' :

                    $field .= '<input type="' . esc_attr($args['type']) . '" class="input-text ' . esc_attr(implode(' ', $args['input_class'])) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" ' . $args['maxlength'] . ' ' . $args['autocomplete'] . ' value="' . esc_attr($value) . '" ' . implode(' ', $custom_attributes) . ' />';

                    break;
                case 'select' :

                    $options = $field = '';

                    if (!empty($args['options'])) {
                        foreach ($args['options'] as $option_key => $option_text) {
                            if ('' === $option_key) {
                                // If we have a blank option, select2 needs a placeholder
                                if (empty($args['placeholder'])) {
                                    $args['placeholder'] = $option_text ? $option_text : __('Choose an option', 'woocommerce');
                                }
                                $custom_attributes[] = 'data-allow_clear="true"';
                            }
                            $options .= '<option value="' . esc_attr($option_key) . '" ' . selected($value, $option_key, false) . '>' . esc_attr($option_text) . '</option>';
                        }

                        $field .= '<select name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" class="select ' . esc_attr(implode(' ', $args['input_class'])) . '" ' . implode(' ', $custom_attributes) . ' data-placeholder="' . esc_attr($args['placeholder']) . '" ' . $args['autocomplete'] . '>
							' . $options . '
						</select>';
                    }

                    break;
                case 'radio' :

                    $label_id = current(array_keys($args['options']));

                    if (!empty($args['options'])) {
                        foreach ($args['options'] as $option_key => $option_text) {
                            $field .= '<input type="radio" class="input-radio ' . esc_attr(implode(' ', $args['input_class'])) . '" value="' . esc_attr($option_key) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '_' . esc_attr($option_key) . '"' . checked($value, $option_key, false) . ' />';
                            $field .= '<label for="' . esc_attr($args['id']) . '_' . esc_attr($option_key) . '" class="radio ' . implode(' ', $args['label_class']) . '">' . $option_text . '</label>';
                        }
                    }

                    break;
            }

            if (!empty($field)) {
                $field_html = '';

                if ($args['label'] && 'checkbox' != $args['type']) {
                    $field_html .= '<label for="' . esc_attr($label_id) . '" class="' . esc_attr(implode(' ', $args['label_class'])) . '">' . $args['label'] . $required . '</label>';
                }

                $field_html .= $field;

                if ($args['description']) {
                    $field_html .= '<span class="description">' . esc_html($args['description']) . '</span>';
                }

                $container_class = 'form-row ' . esc_attr(implode(' ', $args['class']));
                $container_id = esc_attr($args['id']) . '_field';

                $after = !empty($args['clear']) ? '<div class="clear"></div>' : '';

                $field = sprintf($field_container, $container_class, $container_id, $field_html) . $after;
            }

            $field = apply_filters('woocommerce_form_field_' . $args['type'], $field, $key, $args, $value);

            if ($args['return']) {
                return $field;
            } else {
                echo $field;
            }
        }
    }

}