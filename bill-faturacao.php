<?php

/*
Plugin Name: Bill.pt Invoices Woo - Create invoices with Bill.pt API
Description:  WordPress Plugin that allow you to use bill.pt API to create invoices.
Version: 1.0.6
Author: EpicBit
Author URI: https://epicbit.pt
Domain Path: /languages
Text Domain: bill-faturacao
License: GPL2
 
Bill.pt Invoices Woo is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.
 
Bill.pt Invoices Woo is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License along with Bill.pt Invoices Woo. If not, see {License URI}.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bill_pt_my_plugin_load_plugin_textdomain() {
    load_plugin_textdomain( 'bill-faturacao', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'bill_pt_my_plugin_load_plugin_textdomain' );

include_once __DIR__ . '/config.php';
include_once __DIR__ . '/Api.php';
include_once __DIR__ . '/bill.php';

function bill_pt_activate_bill() {
    global $wpdb;
    $wpdb->query("CREATE TABLE IF NOT EXISTS `bill_config`(
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    config VARCHAR(20),
    value TEXT
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS `bill_contatos`(
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nif VARCHAR(30),
    email VARCHAR(50),
    codigo VARCHAR(50)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS `bill_produtos`(
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    codigo VARCHAR(60)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;");

    $config['api_mode'] = "standard";
    $config['debug'] = 0;
    
    $wpdb->insert('bill_config',[
    'config' => 'default_config','value' => json_encode($config)],['%s','%s']);
}
register_activation_hook(__FILE__, 'bill_pt_activate_bill');


function bill_deactivate_bill() {
    global $wpdb;
    $wpdb->query("DROP TABLE bill_config");
    $wpdb->query("DROP TABLE bill_contatos");
    $wpdb->query("DROP TABLE bill_produtos");
}
register_deactivation_hook(__FILE__, 'bill_deactivate_bill');


if (bill_pt_woocommerce_in_the_house()){
    
    add_action('admin_menu', 'bill_pt_plugin_settings');
    function bill_pt_plugin_settings() {
        add_menu_page('Bill', 'Bill', 'administrator', 'bill_settings', 'bill_pt_display_settings');
    }
    
    
    add_action( 'add_meta_boxes', 'bill_pt_add_metabox' );
    function bill_pt_add_metabox( $post ) {
        add_meta_box( 'bill_add_metabox', 'Bill.pt - Criar Documento', 'bill_pt_add_sidebar', 'shop_order', 'side', 'core' );
    }
    function bill_pt_add_sidebar( $post ){
        if( $post->post_status ==  "wc-processing" || $post->post_status ==  "wc-completed"){
            echo '<div style="height: 24px">
            <a type="button" class="button button-primary" target="_BLANK" style="float:right" href="admin.php?page=bill_settings&tab=encomendas">' . __(
            "Ver / Gerar Documento","bill-faturacao") . '</a>
            </div>';
        }else{
            echo __("A encomenda tem que ser dada como paga para poder ser gerada.","bill-faturacao");
        }
    }
    
    function bill_pt_procurar_item_ajax_request() {
        if ( isset( $_POST["codigo"] )  && strlen( $_POST["codigo"] ) > 0 ){
            $bill = new BillPT(BILL_API_MODE);
            
            if($bill->login()){
                $produto = $bill->getItemByCodigo(sanitize_text_field($_POST['codigo']));
                
                if(isset($produto->error)){
                    echo json_encode($produto);
                    die();
                }
                
                if(isset($produto->data[0])){
                    $bill->updateItemDB($produto->data[0]);
                    echo json_encode( $produto->data[0] );
                    die();
                }
                
            }
            $erro[] = __('Não foi possivel fazer o pedido. Possivel os seus dados da api estão errados.',"bill-faturacao");
            echo json_encode(['error' => $erro]);
            die();
        }
        
        $erro[] = __('Deve introduzir o código do produto.',"bill-faturacao");
        echo json_encode(['error' => $erro]);
        die();
    }
    add_action('wp_ajax_procurar_item', 'bill_pt_procurar_item_ajax_request');
    
    function bill_pt_test_ajax_load_scripts() {
        // load our jquery file that sends the $.post request
        wp_enqueue_script( "ajax-js", plugin_dir_url( __FILE__ ) . '/assets/ajax.js', array( 'jquery' ) );
        
        // make the ajaxurl var available to the above script
        wp_localize_script( 'ajax-js', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }
    add_action('wp_print_scripts', 'bill_pt_test_ajax_load_scripts');
    
    function bill_pt_display_settings() {
        $bill = new BillPT(BILL_API_MODE);
        $bill->login();
        $bill->updateDefaultConfig();
        if( $bill->isLogged() ){
            $bill->updateConfig();
            $bill->createDocument();
            $bill->sendEmail();
        } else {
            $_GET['tab'] = "configuracoes";
            $bill->addError(__('Deverá configurar o seu token fazendo login com os seus dados.',"bill-faturacao"));
            $bill->printErrors();
        }
        include __DIR__ . '/display_settings.php';
    }
    
}