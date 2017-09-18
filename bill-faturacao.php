<?php

/*
Plugin Name: Bill.pt Invoices Woo - Create invoices with Bill.pt API
Description:  WordPress Plugin that allow you to use bill.pt API to create invoices.
Version: 0.5.12
Author: EpicBit
Author URI: https://epicbit.pt
License: GPLv2
Domain Path: /languages
Text Domain: bill-faturacao
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function my_plugin_load_plugin_textdomain() {
    load_plugin_textdomain( 'bill-faturacao', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'my_plugin_load_plugin_textdomain' );

include_once __DIR__ . '/config.php';
include_once __DIR__ . '/Api.php';
include_once __DIR__ . '/bill.php';

function activate_bill() {
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
}
register_activation_hook(__FILE__, 'activate_bill');


function deactivate_bill() {
    global $wpdb;
    $wpdb->query("DROP TABLE bill_config");
    $wpdb->query("DROP TABLE bill_contatos");
    $wpdb->query("DROP TABLE bill_produtos");
}
register_deactivation_hook(__FILE__, 'deactivate_bill');


if (woocommerce_in_the_house()){
    
    add_action('admin_menu', 'bill_plugin_settings');
    function bill_plugin_settings() {
        add_menu_page('Bill', 'Bill', 'administrator', 'bill_settings', 'bill_display_settings');
    }
    
    
    add_action( 'add_meta_boxes', 'bill_add_metabox' );
    function bill_add_metabox( $post ) {
        add_meta_box( 'bill_add_metabox', 'Bill.pt - Criar Documento', 'bill_add_sidebar', 'shop_order', 'side', 'core' );
    }
    function bill_add_sidebar( $post ){
        if( $post->post_status ==  "wc-processing" || $post->post_status ==  "wc-completed"){
            echo '<div style="height: 24px">
            <a type="button" class="button button-primary" target="_BLANK" style="float:right" href="admin.php?page=bill_settings&tab=encomendas&order='.$post->ID.'">' . __(
            "Ver / Gerar Documento","bill-faturacao") . '</a>
            </div>';
        }else{
            echo __("A encomenda tem que ser dada como paga para poder ser gerada.","bill-faturacao");
        }
    }
    
    function procurar_item_ajax_request() {
        if ( isset( $_POST["codigo"] )  && strlen( $_POST["codigo"] ) > 0 ){
            $bill = new Bill(BILL_API_MODE);
            
            if($bill->login()){
                $produto = $bill->getItemByCodigo($_POST['codigo']);
                
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
    add_action('wp_ajax_procurar_item', 'procurar_item_ajax_request');
    
    function test_ajax_load_scripts() {
        // load our jquery file that sends the $.post request
        wp_enqueue_script( "ajax-js", plugin_dir_url( __FILE__ ) . '/assets/ajax.js', array( 'jquery' ) );
        
        // make the ajaxurl var available to the above script
        wp_localize_script( 'ajax-js', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }
    add_action('wp_print_scripts', 'test_ajax_load_scripts');
    
    function bill_display_settings() {
        $bill = new Bill(BILL_API_MODE);
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