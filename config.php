<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('BILL_API_MODE', 'standard');

function woocommerce_in_the_house()
{
    $plugins = get_option( 'active_plugins' );
    foreach($plugins as $plugin){
        if ( strpos($plugin, 'woocommerce.php') !== false) {
            return true;
        }
    }
    return false;
}

function pagination($pages = '', $range = 4)
{  
     $showitems = ($range * 2)+1;  

     global $paged;
     
     if(empty($paged)) $paged = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
 
     if($pages == '')
     {
         global $wp_query;
         $pages = $wp_query->max_num_pages;
         if(!$pages)
         {
             $pages = 1;
         }
     }   
 
     if(1 != $pages)
     {
         echo "<div class=\"pagination_custom\"><span>Page ".$paged." of ".$pages."</span>";
         if($paged > 2 && $paged > $range+1 && $showitems < $pages) echo "<a href='".get_pagenum_link(1)."'>&laquo; First</a>";
         if($paged > 1 && $showitems < $pages) echo "<a href='".get_pagenum_link($paged - 1)."'>&lsaquo; Previous</a>";
 
         for ($i=1; $i <= $pages; $i++)
         {
             if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems ))
             {
                 echo ($paged == $i)? "<span class=\"current\">".$i."</span>":"<a href='".get_pagenum_link($i)."' class=\"inactive\">".$i."</a>";
             }
         }
 
         if ($paged < $pages && $showitems < $pages) echo "<a href=\"".get_pagenum_link($paged + 1)."\">Next &rsaquo;</a>";  
         if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages) echo "<a href='".get_pagenum_link($pages)."'>Last &raquo;</a>";
         echo "</div>\n";
     }
}


if (woocommerce_in_the_house()){
    /**
    * Add the field to the checkout
    */
    add_action( 'woocommerce_after_order_notes', 'my_custom_checkout_field' );
    
    function my_custom_checkout_field( $checkout ) {
        
        echo '<div id="my_custom_checkout_field"><h2>' . __('Informação Fiscal',"bill-faturacao") . '</h2>';
        
        woocommerce_form_field( 'vat_number', array(
        'type'          => 'text',
        'class'         => array('vat-number form-row-wide'),
        'label'         => __('NIF/VAT',"bill-faturacao"),
        'placeholder'   => __('Número de informação Fiscal que será colocado na Fatura. (VAT)',"bill-faturacao"),
        ), $checkout->get_value( 'vat_number' ));
        
        echo '</div>';
        
    }
    
    
    /**
    * Update the order meta with field value
    */
    add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta' );
    
    function my_custom_checkout_field_update_order_meta( $order_id ) {
        if ( ! empty( $_POST['vat_number'] ) ) {
            update_post_meta( $order_id, 'My VAT Number section', sanitize_text_field( $_POST['vat_number'] ) );
        }
    }
    
    /**
    * Display field value on the order edit page
    */
    add_action( 'woocommerce_admin_order_data_after_billing_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );
    
    function my_custom_checkout_field_display_admin_order_meta($order){
        echo '<p><strong>'.__('Número de Contribuinte',"bill-faturacao"). ':</strong> ' . get_post_meta( $order->get_id(), 'My VAT Number section', true ) . '</p>';
    }
    
}