<?php
/**
* Plugin Name: Diploma Authentication
* Plugin URI: http://www.cabm.net.br
* Description: Authenticates diplomas or certificates given in courses
* Version: 0.0.1
* Author: LFABM
* Author URI: http://www.cabm.net.br
* License: CC BY
*/

//Activates debugging on WP
//wp-config.php constant WP_DEBUG

register_activation_hook( __FILE__ , 'lfabm_diploma_authentication_install');

function lfabm_diploma_authentication_install(){
	
	//Documentacao: https://codex.wordpress.org/Creating_Tables_with_Plugins

	global $wpdb;

	$wpdb->query("	CREATE TABLE wp_lfabm_diploma (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			name tinytext NOT NULL,
			codigo char(10) NOT NULL,
			UNIQUE KEY id (id))");

	$page_title = 'Validador de Diploma';
	$page_name = 'Plugin Validador de Diploma';

	// Opcoes gravadas em wp_option
	delete_option("lfabm_diploma_authentication_page_title");
	add_option("lfabm_diploma_authentication_page_title", $page_title, '', 'yes');
	
	delete_option("lfabm_diploma_authentication_page_name");
	add_option("lfabm_diploma_authentication_page_name", $page_name, '', 'yes');
	
	delete_option("lfabm_diploma_authentication_page_id");
	add_option("lfabm_diploma_authentication_page_id", '0', '', 'yes');

	$page = get_page_by_title( $page_title );

	if ( !$page ) {

		// Cria o post
		$p = array();
		$p['post_title'] = $page_title;
		$p['post_content'] = '[lfabm_diploma_authentication_form]';
		$p['post_status'] = 'publish';
		$p['post_type'] = 'page';
		$p['comment_status'] = 'closed';
		$p['ping_status'] = 'closed';
		$p['post_category'] = array(1);//Sem categoria

		// Grava o post
		$page_id = wp_insert_post( $p );

	} else {
		//Faz o update no post caso o pluging já tenha sido instalado previamente
		$page_id = $page->ID;

		//Verifica se a página não está na lixeira
		$page->post_status = 'publish';
		$page->post_content = '[lfabm_diploma_authentication_form]';
		$page_id = wp_update_post( $page );

	}

	delete_option( 'lfabm_diploma_authentication_page_id' );
	add_option( 'lfabm_diploma_authentication_page_id', $page_id );

}

function cria_shortcode($codigo){

	if (isset($_POST['codigo'])) {

		global $wpdb;

		$diploma = $wpdb->get_results(" SELECT name 
						FROM wp_lfabm_diploma 
						WHERE codigo = '{$_POST['codigo']}'");

		if (isset($diploma)) {
			if (!empty($diploma)) {		
				$nome = 'Diploma emitido para: '.$diploma[0]->name;
			} else {
				$nome = 'C&oacute;digo Inv&aacute;lido';
			}
		}
	}
	
	ob_start();
	require(__DIR__.'/templates/validador_tpl.php');

	return ob_get_clean();
}

//executa a função que gera a tela de validação do diploma quando encontrar o shortcode passado como primeiro parâmetro na função add_shortcode
add_shortcode( 'lfabm_diploma_authentication_form', 'cria_shortcode' );

register_deactivation_hook( __FILE__ , 'lfabm_diploma_authentication_uninstall');

//Configuração do Plugin
add_action('admin_menu', 'lfabm_diploma_authentication_menu');

function lfabm_diploma_authentication_menu() {
	add_submenu_page(	'options-general.php',
				'P&aacute;gina de Configuração do Plugin de Valida&ccedil;&atilde;o de Diploma',
      				'Configura&ccedil;&atilde;o do Plugin de Valida&ccedil;&atilde;o de Diploma', 
      				'administrator', 
      				'lfabm-diploma-authentication-settings', 
      				'lfabm_diploma_authentication_settings_page');
}

function lfabm_diploma_authentication_settings_page() {        

	if (isset($_POST['nome'])) {
		if (!empty($_POST['nome'])) {
			global $wpdb;
			
			$cod = generateRandomString();

			if( $wpdb->query('	INSERT INTO wp_lfabm_diploma (name, codigo)
						VALUES ("'.$_POST['nome'].'", "'.$cod.'")')) {
				
				$msg = 'Diploma para: '.$_POST['nome'].', criado e gravado com sucesso, c&oacute;digo gerado: '.$cod;
			} else {
				$msg = 'Erro ao criar e gravar o diploma';
			}
		}
	}

	include('templates/config_tpl.php');
} 

function generateRandomString($length = 10) {

	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function lfabm_diploma_authentication_uninstall(){

	global $wpdb;

	$wpdb->query("DROP TABLE IF EXISTS wp_lfabm_diploma");

	$page_title = get_option( "lfabm_diploma_authentication_page_title" );
	$page_name = get_option( "lfabm_diploma_authentication_page_name" );

	// recupera o id da página do plugin
	$page_id = get_option( 'lfabm_diploma_authentication_page_id' );
	if( $page_id ) {

		wp_delete_post( $page_id ); // coloca a página na lixeira

	}

	delete_option("lfabm_diploma_authentication_page_title");
	delete_option("lfabm_diploma_authentication_page_name");
	delete_option("lfabm_diploma_authentication_page_id");
}
?>
