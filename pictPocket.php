<?php
/*
	Plugin Name: pictPocket
	Plugin URI: http://www.semageek.com/2009/06/27/pictpocket-un-plugin-wp-qui-identifie-et-bloque-les-voleurs-de-contenu/
	Description: Identifier les voleurs de contenus et les bloquer - Identify and block HotLinks.
	Version: 1.4.2
	Author: Semageek
	Author URI: http://www.semageek.com
	
	Copyright 2009-2011 Semageek
	This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/************************************************************************************
* 
*************************************************************************************/
function pictPocket_install () {
	
	
	
	update_option('pictpocket_version', '1.4.2');
		
	global $wpdb;
	
	$table_name = $wpdb->prefix . "pictPocket";
	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		pictPocket_CreateTable();
	}
	
	//la table existe est ce que le champ last_ref existe (passage version 1.0.0 à 1.1.0
	$sql_alter="ALTER TABLE `wp_pictPocket` ADD `last_ref` TEXT NULL AFTER `blocage`" ;
	$wpdb->query($sql_alter);	
	
	//la table existe est ce que le champ type existe (passage version 1.3.0 à 1.3.1
	$sql_alter="ALTER TABLE `wp_pictPocket` ADD `cate` TEXT NULL AFTER `blocage`" ;
	$wpdb->query($sql_alter);	
	
	$table_name = $wpdb->prefix . "pictPocket_autorisation";
	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		pictPocket_CreateTableAutorisation();
	}
	
	$table_name = $wpdb->prefix . "pictPocket_blocktext";
	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		pictPocket_CreateTableBlockText();
	}
	
	$table_name = $wpdb->prefix . "pictPocket_blockimage";
	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		pictPocket_CreateTableBlockImage();
	}
	
	
}
//
function pictPocket() {

	$action = mysql_real_escape_string($_GET['action']); //XSS
	
	if ($action == 'view') {
		$do = mysql_real_escape_string($_GET['do']); //XSS
		$id = mysql_real_escape_string($_GET['id']); //XSS
		pictPocket_view( $do,$id);
	} 
	elseif ($action == 'autorisation') {
		$do = mysql_real_escape_string($_GET['do']); //XSS
		$id = mysql_real_escape_string($_GET['id']); //XSS		
		pictPocket_autorisation( $do,$id);
	}
	elseif ($action == 'blocktext') {
		$do = mysql_real_escape_string($_GET['do']); //XSS
		$id = mysql_real_escape_string($_GET['id']); //XSS		
		pictPocket_blocagetexte( $do,$id);
	}
	elseif ($action == 'blockimage') {
		$do = mysql_real_escape_string($_GET['do']); //XSS
		$id = mysql_real_escape_string($_GET['id']); //XSS		
		pictPocket_blocageimage( $do,$id);
	}
	elseif(1) {
		pictPocket_main();
	}
}
/***********************************************************
* Ajout des pages dans l'interface admin
***********************************************************/
function pictPocket_add_pages() {
	
	$page = mysql_real_escape_string($_GET['page']); //XSS
	
	global $wpdb;
	$table_name = $wpdb->prefix . "pictPocket";
	
	
	#TOTAL dde voleur
	$qry_total = $wpdb->get_row("
		SELECT count(DISTINCT url) AS voleurs
		FROM $table_name WHERE blocage=''		
	");
	
	$voleurs= $qry_total->voleurs.'<br/>';
	
	
	if (($voleurs == 0)||($page == "pictpocket/pictPocket.php" )){
		add_menu_page('PictPocket', 'PictPocket', 8, __FILE__, 'pictPocket');
	}
	else{ 
		add_menu_page('PictPocket', 'PictPocket'."<span id='awaiting-mod' ><span class='pending-count'> ".$voleurs."</span></span>", 8, __FILE__, 'pictPocket');
	}
	
	add_submenu_page(__FILE__, __('Overview','pictpocket'), __('Overview','PictPocket'), 8, __FILE__, 'pictPocket');
    
	if ($voleurs == 0){
		add_submenu_page(__FILE__, __('HotLinks','PictPocket'), __('HotLinks','PictPocket'), 8, 'pictpocket_view', 'pictpocket_view');
	}
	else{
		add_submenu_page(__FILE__, __('HotLinks','PictPocket'), __('HotLinks','PictPocket')."<span id='awaiting-mod' ><span class='pending-count'> ".$voleurs."</span></span>", 8, 'pictpocket_view', 'pictpocket_view');
	}
	
	
	add_submenu_page(__FILE__, __('Autorisations','PictPocket'), __('Autorisations','PictPocket'), 8, 'pictpocket_autorisation', 'pictPocket_autorisation');
	add_submenu_page(__FILE__, __('Block by Text','PictPocket'), __('Block by Text','PictPocket'), 8, 'pictpocket_blocktext', 'pictPocket_blocktext');
	add_submenu_page(__FILE__, __('Block by Image','PictPocket'), __('Block by Image','PictPocket'), 8, 'pictpocket_blockimage', 'pictPocket_blockimage');
	
	
	
	add_submenu_page(__FILE__, __('Options','PictPocket'), __('Options','PictPocket'), 8, 'pictpocket_option', 'pictPocket_option');
	
}

//Effacement de toutes les tables
function pictPocket_RemoveTables() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "pictPocket";
	
	// Effecement de la table si elle existe
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$sql_droptable = "DROP TABLE " . $table_name . ";";
		if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
									else $page = 'wp-admin/upgrade'.'-functions.php';
		require_once(ABSPATH . $page);
		dbDelta($sql_droptable);
	}		
	
	$table_name = $wpdb->prefix . "pictPocket_autorisation";
	
	// Effecement de la table si elle existe
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$sql_droptable = "DROP TABLE " . $table_name . ";";
		if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
									else $page = 'wp-admin/upgrade'.'-functions.php';
		require_once(ABSPATH . $page);
		dbDelta($sql_droptable);
	}
	
	$table_name = $wpdb->prefix . "pictPocket_blocktext";
	
	// Effecement de la table si elle existe
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$sql_droptable = "DROP TABLE " . $table_name . ";";
		if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
									else $page = 'wp-admin/upgrade'.'-functions.php';
		require_once(ABSPATH . $page);
		dbDelta($sql_droptable);
	}
	
	$table_name = $wpdb->prefix . "pictPocket_blockimage";
	
	// Effecement de la table si elle existe
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$sql_droptable = "DROP TABLE " . $table_name . ";";
		if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
									else $page = 'wp-admin/upgrade'.'-functions.php';
		require_once(ABSPATH . $page);
		dbDelta($sql_droptable);
	}
	
	$table_name = $wpdb->prefix . "pictpocket";
	
	// Effecement de la table si elle existe
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$sql_droptable = "DROP TABLE " . $table_name . ";";
		if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
									else $page = 'wp-admin/upgrade'.'-functions.php';
		require_once(ABSPATH . $page);
		dbDelta($sql_droptable);
	}		
	
	$table_name = $wpdb->prefix . "pictpocket_autorisation";
	
	// Effecement de la table si elle existe
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$sql_droptable = "DROP TABLE " . $table_name . ";";
		if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
									else $page = 'wp-admin/upgrade'.'-functions.php';
		require_once(ABSPATH . $page);
		dbDelta($sql_droptable);
	}
	
	$table_name = $wpdb->prefix . "pictpocket_blocktext";
	
	// Effecement de la table si elle existe
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$sql_droptable = "DROP TABLE " . $table_name . ";";
		if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
									else $page = 'wp-admin/upgrade'.'-functions.php';
		require_once(ABSPATH . $page);
		dbDelta($sql_droptable);
	}
	
	$table_name = $wpdb->prefix . "pictpocket_blockimage";
	
	// Effecement de la table si elle existe
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$sql_droptable = "DROP TABLE " . $table_name . ";";
		if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
									else $page = 'wp-admin/upgrade'.'-functions.php';
		require_once(ABSPATH . $page);
		dbDelta($sql_droptable);
	}

	$table_name = $wpdb->prefix . "pictPocket";
	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		pictPocket_CreateTable();
	}
	
	//la table existe est ce que le champ last_ref existe (passage version 1.0.0 à 1.1.0
	$sql_alter="ALTER TABLE `wp_pictPocket` ADD `last_ref` TEXT NULL AFTER `blocage`" ;
	$wpdb->query($sql_alter);	
	
	//la table existe est ce que le champ type existe (passage version 1.3.0 à 1.3.1
	$sql_alter="ALTER TABLE `wp_pictPocket` ADD `cate` TEXT NULL AFTER `blocage`" ;
	$wpdb->query($sql_alter);	
	
	$table_name = $wpdb->prefix . "pictPocket_autorisation";
	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		pictPocket_CreateTableAutorisation();
	}
	
	$table_name = $wpdb->prefix . "pictPocket_blocktext";
	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		pictPocket_CreateTableBlockText();
	}
	
	$table_name = $wpdb->prefix . "pictPocket_blockimage";
	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		pictPocket_CreateTableBlockImage();
	}

}


//Creationn de la table des voleurs
function pictPocket_CreateTable() {
	global $wpdb;
	global $wp_db_version;
	$table_name = $wpdb->prefix . "pictPocket";
	$sql_createtable = "CREATE TABLE " . $table_name . " (
	id mediumint(9) NOT NULL AUTO_INCREMENT,	
	url text,
	visited text,
	ip text,
	blocage text,	
	cate text,
	last_ref text,
	last_pic text,
	timestamp text,
	UNIQUE KEY id (id)
	);";
	if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
								else $page = 'wp-admin/upgrade'.'-functions.php';
	require_once(ABSPATH . $page);
	dbDelta($sql_createtable);
}	

//Creation de la table des autorisation
function pictPocket_CreateTableAutorisation() {
	global $wpdb;
	global $wp_db_version;		
	
	$table_name = $wpdb->prefix . "pictPocket_autorisation";
	$sql_createtable = "CREATE TABLE " . $table_name . " (
	id mediumint(9) NOT NULL AUTO_INCREMENT,	
	cond text,	
	UNIQUE KEY id (id)
	);";
	if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
								else $page = 'wp-admin/upgrade'.'-functions.php';
	require_once(ABSPATH . $page);
	dbDelta($sql_createtable);	
	pictPocket_add_cond("!^$");	
	
	//ajout de la condition pour le blog en lui même
	$urlblogcond="!^".get_bloginfo('url' )."/.*$";	
	pictPocket_add_cond($urlblogcond);
	
	//Ajout de quelques conditions de base
	pictPocket_add_cond("!^http://www\\\\.feedburner\.com/.*$");
	pictPocket_add_cond("!^http://(www\\\\.)?google\.com/reader(/)?.*$");
	pictPocket_add_cond("!^http://(www\\\\.)?netvibes\..*(/)?.*$");
	pictPocket_add_cond("!^http://(www\\\\.)?wikio\..*(/)?.*$");
	pictPocket_add_cond("!^http://(www\\\\.)?google\..*(/)?.*$");
	pictPocket_add_cond("!^http://images\\\\.google\..*(/)?.*$");	
}

//Creation de la table des blocage texte
function pictPocket_CreateTableBlockText() {
	global $wpdb;
	global $wp_db_version;		
	
	$table_name = $wpdb->prefix . "pictPocket_blocktext";
	$sql_createtable = "CREATE TABLE " . $table_name . " (
	id mediumint(9) NOT NULL AUTO_INCREMENT,	
	cond text,	
	UNIQUE KEY id (id)
	);";
	if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
								else $page = 'wp-admin/upgrade'.'-functions.php';
	require_once(ABSPATH . $page);
	dbDelta($sql_createtable);	
	
}

//Creation de la table des blocage image
function pictPocket_CreateTableBlockImage() {
	global $wpdb;
	global $wp_db_version;		
	
	$table_name = $wpdb->prefix . "pictPocket_blockimage";
	$sql_createtable = "CREATE TABLE " . $table_name . " (
	id mediumint(9) NOT NULL AUTO_INCREMENT,	
	cond text,	
	UNIQUE KEY id (id)
	);";
	if($wp_db_version >= 5540)	$page = 'wp-admin/includes/upgrade.php';  
								else $page = 'wp-admin/upgrade'.'-functions.php';
	require_once(ABSPATH . $page);
	dbDelta($sql_createtable);	
	
}

// function to display the options page
function pictPocket_main() {	
	
	
	
	global $wpdb;
	
	if ( $_POST["pictPocket_htaccess"] ) {
		pictpocket_create_htaccess();		
	}
	
	if ( $_POST["pictPocket_remove_htaccess"] ) {
		pictpocket_remove_htaccess();		
	}
	
	if ( $_POST["pictPocket_clean_tables"] ) {
		pictPocket_RemoveTables();		
	}
	
	echo "<div class=\"wrap\">";	
	echo "<h2>PictPocket : ".__('Overview','pictpocket')."</h2>";
	
	$version=get_option('pictpocket_version');	
	echo __('Pictpocket version : ','pictpocket').$version.'<br/>';
	
	echo __('An original creation by Olivier Despont from','pictpocket').' <a href="http://www.semageek.com" title="SEMAGEEK : Actualit&eacute;s High Tech, Robot, &Eacute;lectronique, DIY et Arduino.">Semageek.com</a><br/>';
	
	
	echo "<h3>".__('Help Support This Plugin!','pictpocket')."</h3>";
	
	echo __('Please donate to the development of PictPocket using Paypal.','pictpocket');
	
	echo '<br/><form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
	echo '<input type="hidden" name="cmd" value="_s-xclick">';
	echo '<input type="hidden" name="hosted_button_id" value="FRKVNDSBPB47N">';
	echo '<input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/fr_FR/FR/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus s&eacute;curis&eacute;e !">';
	echo '<img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/fr_FR/i/scr/pixel.gif" width="1" height="1">';
	echo '</form>';

	
	echo "<h3>";
	_e('Management of the file','pictpocket');
	echo " .htacces</h3>";
	$home_path = get_home_path();
	$htaccess_file = $home_path.'.htaccess';
	//est ce que le plugin a déjà attaqué htacces
	if ( (!file_exists($home_path.'.htaccess') && is_writable($home_path)) || is_writable($home_path.'.htaccess') ){
		
			_e('File .htaccess exist.','pictpocket');
			echo "<br/>";
			$resultat=extract_from_markers( $htaccess_file, 'pictPocket');
			if ($resultat!=array())
			{
				_e('The configuration of .htaccess for PickPocket is Ok','pictpocket');
				echo "<br/><br/>";
				echo '<form method="post">';
				echo '<input type="hidden" name="pictPocket_remove_htaccess" value="true"></input>';
				echo '<td><input type="submit" value="'.__('Remove Pictpocket from .htaccess...','pictpocket').'" class="button-primary"></input>';				
				echo '</form>';
			}
			else
			{
				_e('The configuration of .htaccess for PickPocket is missing','pictpocket');
				echo "<br/><br/>";
				echo "<b>".__('Warning','pictpocket')."</b><br>";
				_e('Some webhost don\'t accept the Rewrite URL fonction necessary for the PictPocket plugin.','pictpocket');
				echo "<br/>";
				_e('In case of <b>Wordpress fail</b>, try to clear the .htaccess file in root forlder.','pictpocket');
				echo "<br/><br/>";				
				echo '<form method="post">';
				echo '<input type="hidden" name="pictPocket_htaccess" value="true"></input>';
				echo '<td><input type="submit" value="'.__('Auto Configuration...','pictpocket').'" class="button-primary"></input>';
				
				echo '</form>';
				
				
			}
	}
	else
	{
		//le fichier htacces n'existe pas ou est bloqué en ecriture
		_e('The .htaccess file don\'t exist or is not writeable.','pictpocket');
				
		echo "<br/>";
	}
	
	echo "<h3>".__('Overview','pictpocket')."</h3>";
	
	$table_name = $wpdb->prefix . "pictPocket";
	
	
	#TOTAL dde voleur
	$qry_total = $wpdb->get_row("
		SELECT count(DISTINCT url) AS voleurs
		FROM $table_name
		
	");	
	
	_e('Numbers of thieves : ','pictpocket');
	echo $qry_total->voleurs.'<br/>';
	$qry_total = $wpdb->get_results("SELECT visited FROM $table_name ");
	$visite_total=0;
	
	foreach ($qry_total as $qry){
		$visite_total=$visite_total+$qry->visited;
		
	}	
		
	_e('Numbers of hotlinking : ','pictpocket');
	echo $visite_total.'<br/>';
	
	$table_name = $wpdb->prefix . "pictPocket_autorisation";
	$qry_total = $wpdb->get_row("
		SELECT count(DISTINCT cond) AS voleurs
		FROM $table_name
		
	");	
	_e('Numbers of autorized website : ','pictpocket');
	echo $qry_total->voleurs.'<br/>';
	
	$table_name = $wpdb->prefix . "pictPocket_blocktext";
	$qry_total = $wpdb->get_row("
		SELECT count(DISTINCT cond) AS voleurs
		FROM $table_name
		
	");	
	
	_e('Numbers of website blocked by Text: ','pictpocket');
	echo $qry_total->voleurs.'<br/>';
	
	$table_name = $wpdb->prefix . "pictPocket_blockimage";
	$qry_total = $wpdb->get_row("
		SELECT count(DISTINCT cond) AS voleurs
		FROM $table_name
		
	");	
	
	_e('Numbers of website blocked by Image: ','pictpocket');
	echo $qry_total->voleurs.'<br/>';
	
	echo "<h3>".__('Manage Tables (if problem)','pictpocket')."</h3>";
	
	
	$table_name = $wpdb->prefix . "pictPocket";
	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		echo __('There is a problem with the table ','pictpocket').$table_name.'<br/>';
		echo __('Try to recreate the tables...','pictpocket').'<br/>';
	}	
	
	$table_name = $wpdb->prefix . "pictPocket_autorisation";	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		echo __('There is a problem with the table ','pictpocket').$table_name.'<br/>';
		echo __('Try to recreate the tables...','pictpocket').'<br/>';
	}
	
	$table_name = $wpdb->prefix . "pictPocket_blocktext";	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		echo __('There is a problem with the table ','pictpocket').$table_name.'<br/>';
		echo __('Try to recreate the tables...','pictpocket').'<br/>';
	}
	
	$table_name = $wpdb->prefix . "pictPocket_blockimage";	
	// Creation de la table si elle n'existe pas
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		echo __('There is a problem with the table ','pictpocket').$table_name.'<br/>';
		echo __('Try to recreate the tables...','pictpocket').'<br/>';
	}
	
	
	
	echo __('If you have some problems with pictPocket you can recreate the table.','pictpocket');
	echo '<br/>';
	echo __('This action will remove all data in pictPocket\'s table, be careful.','pictpocket');
	echo '<br/><br/>';
	echo '<form method="post">';
	echo '<input type="hidden" name="pictPocket_clean_tables" value="true"></input>';
	echo '<td><input type="submit" value="'.__('Recreate tables...','pictpocket').'" class="button-primary"></input>';				
	echo '</form>';
   
	
	echo "</div>";
}
/*****************************************************************************
* Ajoute une autorisation dans la table
******************************************************************************/
function pictPocket_add_cond($cond)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "pictPocket_autorisation";
	
	//on vérifie que la condition n'existe pas déja dans la table
	$qry = $wpdb->get_results("SELECT id FROM $table_name WHERE cond='$cond' ");
	if ($qry == null)
	{
		$insert = "INSERT INTO " . $table_name .
            " (cond) " .
            "VALUES ('$cond')";
		$results = $wpdb->query( $insert );
	}
}

/*****************************************************************************
* Ajoute un blocage texte dans la table
******************************************************************************/
function pictPocket_add_blocktext($cond)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "pictPocket_blocktext";
	
	//on vérifie que la condition n'existe pas déja dans la table
	$qry = $wpdb->get_results("SELECT id FROM $table_name WHERE cond='$cond' ");
	if ($qry == null)
	{
		$insert = "INSERT INTO " . $table_name .
            " (cond) " .
            "VALUES ('$cond')";
		$results = $wpdb->query( $insert );
	}
}

/*****************************************************************************
* Ajoute un blocage image dans la table
******************************************************************************/
function pictPocket_add_blockimage($cond)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "pictPocket_blockimage";
	
	//on vérifie que la condition n'existe pas déja dans la table
	$qry = $wpdb->get_results("SELECT id FROM $table_name WHERE cond='$cond' ");
	if ($qry == null)
	{
		$insert = "INSERT INTO " . $table_name .
            " (cond) " .
            "VALUES ('$cond')";
		$results = $wpdb->query( $insert );
	}
}

/*****************************************************************************
* Affichage de la page admin Autorisation
******************************************************************************/
function pictPocket_autorisation($do ='', $id='')
{
	global $wpdb;
	$table_name = $wpdb->prefix . "pictPocket_autorisation";
	
	$do = mysql_real_escape_string($_GET['do']); //XSS
	$id = mysql_real_escape_string($_GET['id']); //XSS	
	
	
	if ( $_POST["pictPocket_autorisation"] ) {		
		$new_url = mysql_real_escape_string($_POST["url"]);
		pictPocket_add_cond($new_url);
		pictpocket_create_htaccess();
	}
	
	if ( $do == 'delete' ) {
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE id =".$id);
		pictpocket_create_htaccess();
	}
			
	
	
	echo '<form method="post">';
	echo "<div class=\"wrap\"><h2>PictPocket : ".__('Autorisations','pictpocket')."</h2>";
	echo "<br/>";	
	
	$qry_total = $wpdb->get_results("SELECT * FROM $table_name ");
	
	if ($qry_total){	
		echo '<table class=\'widefat\' border="1">';
		
		echo '<thead><tr valign="top"><th>'.__('RewriteCond','pictpocket').'</th><th>'.__('Clear','pictpocket').'</th></tr></thead>';
		
		foreach ($qry_total as $qry){
				echo '<tr valign="top"><td>'.$qry->cond.'</td>';
				
		
		
		//fonction delete
		$chemin_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		echo '<td><a href="admin.php?page=pictpocket_autorisation&action=autorisation&do=delete&id='.$qry->id.'" title="'.__('Clear','pictpocket').'"><img src="'.$chemin_plugin.'/images/no.png" /></a>';
		
		
		echo'</td></tr>';
		
		}
		echo '<tfoot><tr valign="top"><th>'.__('RewriteCond','pictpocket').'</th><th>'.__('Clear','pictpocket').'</th></tr></tfoot>';
		echo '</table></div>';
	}
	
	echo '<h3>'.__('Add an autorisation...','pictpocket').'</h3>';
	echo __('Example','pictpocket').'<br/><br/>';
	echo '<b>!^http://(.+\.)?domain.tld/.*$</b> '.__('will autorised all the domain "domain.tld"','pictpocket').'<br/>';	
	echo '<b>!^http://sub.domain.tld/.*$</b> '.__('will autorised just the subdomain "sub.domain.tld"','pictpocket').'<br/>';		
	echo '<b>!^http://(.+\.)?domain\..*(/)?.*$</b> '.__('will autorised all the domain "domain" with all tld','pictpocket').'<br/><br/>';
	echo '<input type="text" name="url" value="" size="100"></input>';	
	echo '<input type="hidden" name="pictPocket_autorisation" value="true"></input>';
	echo '<input type="submit" value="'.__('Add','pictpocket').' &raquo;" class="button-primary action"></input>';
	
	
	
	echo "</div>";
	echo '</form>';
	
}

/*****************************************************************************
* Affichage de la page admin blocktexte
******************************************************************************/
function pictPocket_blocktext($do ='', $id='')
{
	global $wpdb;
	$table_name = $wpdb->prefix . "pictPocket_blocktext";
	
	$do = mysql_real_escape_string($_GET['do']); //XSS
	$id = mysql_real_escape_string($_GET['id']); //XSS	
	
	
	if ( $_POST["pictPocket_blocktext"] ) {		
		$new_url = mysql_real_escape_string($_POST["url"]);
		pictPocket_add_blocktext($new_url);
		pictpocket_create_htaccess();
	}
	
	if ( $do == 'delete' ) {
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE id =".$id);
		pictpocket_create_htaccess();
	}
			
	
	
	echo '<form method="post">';
	echo "<div class=\"wrap\"><h2>PictPocket : ".__('Blocked by Text\'s method','pictpocket')."</h2>";
	echo "<br/>";	
	
	$qry_total = $wpdb->get_results("SELECT * FROM $table_name ");
	if ($qry_total){
		echo '<table class=\'widefat\' border="1">';
		
		echo '<thead><tr valign="top"><th>'.__('RewriteCond','pictpocket').'</th><th>'.__('Clear','pictpocket').'</th></tr></thead>';
		
		foreach ($qry_total as $qry){
				echo '<tr valign="top"><td>'.$qry->cond.'</td>';
				
		
		
		//fonction delete
		$chemin_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		echo '<td><a href="admin.php?page=pictpocket_blocktext&action=blocktext&do=delete&id='.$qry->id.'" title="'.__('Clear','pictpocket').'"><img src="'.$chemin_plugin.'/images/no.png" /></a>';
		
		
		echo'</td></tr>';
		
		}		
		echo '<tfoot><tr valign="top"><th>'.__('RewriteCond','pictpocket').'</th><th>'.__('Clear','pictpocket').'</th></tr></tfoot>';
		echo '</table></div>';
	}
	
	echo '<h3>'.__('Add a block website by text method...','pictpocket').'</h3>';
	echo __('Example','pictpocket').'<br/><br/>';
	echo '<b>http://(.+\.)?domain.tld/.*$</b> '.__('will block all the domain "domain.tld"','pictpocket').'<br/>';	
	echo '<b>http://sub.domain.tld/.*$</b> '.__('will block just the subdomain "sub.domain.tld"','pictpocket').'<br/>';		
	echo '<b>http://(.+\.)?domain\..*(/)?.*$</b> '.__('will block all the domain "domain" with all tld','pictpocket').'<br/><br/>';
	echo '<input type="text" name="url" value="" size="100"></input>';	
	echo '<input type="hidden" name="pictPocket_blocktext" value="true"></input>';
	echo '<input type="submit" value="'.__('Add','pictpocket').' &raquo;" class="button-primary action"></input>';	
	echo '</form>';
	
}

/*****************************************************************************
* Affichage de la page admin blockimage
******************************************************************************/
function pictPocket_blockimage($do ='', $id='')
{
	global $wpdb;
	$table_name = $wpdb->prefix . "pictPocket_blockimage";
	
	$do = mysql_real_escape_string($_GET['do']); //XSS
	$id = mysql_real_escape_string($_GET['id']); //XSS	
	
	
	if ( $_POST["pictPocket_blockimage"] ) {		
		$new_url = mysql_real_escape_string($_POST["url"]);
		pictPocket_add_blockimage($new_url);
		pictpocket_create_htaccess();
	}
	
	if ( $do == 'delete' ) {
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE id =".$id);
		pictpocket_create_htaccess();
	}
			
	
	
	echo '<form method="post">';
	echo "<div class=\"wrap\"><h2>PictPocket : ".__('Blocked by Image\'s method','pictpocket')."</h2>";
	echo "<br/>";	
	
	$qry_total = $wpdb->get_results("SELECT * FROM $table_name ");
	
	if ($qry_total){
		echo '<table class=\'widefat\' border="1">';
		
		echo '<thead><tr valign="top"><th>'.__('RewriteCond','pictpocket').'</th><th>'.__('Clear','pictpocket').'</th></tr><thead>';
		
		foreach ($qry_total as $qry){
				echo '<tr valign="top"><td>'.$qry->cond.'</td>';
				
		
		
		//fonction delete
		$chemin_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		echo '<td><a href="admin.php?page=pictpocket_blockimage&action=blockimage&do=delete&id='.$qry->id.'" title="'.__('Clear','pictpocket').'"><img src="'.$chemin_plugin.'/images/no.png" /></a>';
		
		
		echo'</td></tr>';
		
		}
		echo '<tfoot><tr valign="top"><th>'.__('RewriteCond','pictpocket').'</th><th>'.__('Clear','pictpocket').'</th></tr></tfoot>';
		echo '</table></div>';
	}
	
	echo '<h3>'.__('Add a block website by image method...','pictpocket').'</h3>';
	echo __('Example','pictpocket').'<br/><br/>';
	echo '<b>http://(.+\.)?domain.tld/.*$</b> '.__('will block all the domain "domain.tld"','pictpocket').'<br/>';	
	echo '<b>http://sub.domain.tld/.*$</b> '.__('will block just the subdomain "sub.domain.tld"','pictpocket').'<br/>';		
	echo '<b>http://(.+\.)?domain\..*(/)?.*$</b> '.__('will block all the domain "domain" with all tld','pictpocket').'<br/><br/>';
	echo '<input type="text" name="url" value="" size="100"></input>';	
	echo '<input type="hidden" name="pictPocket_blockimage" value="true"></input>';
	echo '<input type="submit" value="'.__('Add','pictpocket').' &raquo;" class="button-primary action"></input>';	
	echo '</form>';
	
	
	
}
function pictpocket_create_htaccess()
{
	$home_path = get_home_path();
	$htaccess_file = $home_path.'.htaccess';
	$home_root = parse_url(get_option('home'));
	
	if ( isset( $home_root['path'] ) ) {
		$home_root = trailingslashit($home_root['path']);
	} else {
		$home_root = '/';
	}
	//conditions à écrire
	
	$rules .= "<IfModule mod_expires.c>\n";
	$rules .= "RewriteEngine On\n";
	$rules .= "RewriteBase $home_root\n";
	
	
	//gestion des autorisations + rajout des blocage pour ne pas les envoyer sur le moteur
	global $wpdb;
	$table_name = $wpdb->prefix . "pictPocket_autorisation";
	$qry_total = $wpdb->get_results("SELECT * FROM $table_name ");	
	if ($qry_total) {
		foreach ($qry_total as $qry){
				$rules .= "RewriteCond %{HTTP_REFERER} ".$qry->cond." [NC]\n";
		}
	}	
		
	$table_name = $wpdb->prefix . "pictPocket_blocktext";
	$qry_total = $wpdb->get_results("SELECT * FROM $table_name ");	
	if ($qry_total) {
		foreach ($qry_total as $qry){
				$rules .= "RewriteCond %{HTTP_REFERER} !^".$qry->cond." [NC]\n"; 
		}
	}
	
	$table_name = $wpdb->prefix . "pictPocket_blockimage";
	$qry_total = $wpdb->get_results("SELECT * FROM $table_name ");	
	
	if ($qry_total) {
		foreach ($qry_total as $qry){
				$rules .= "RewriteCond %{HTTP_REFERER} !^".$qry->cond." [NC]\n";
		}
	}
		
	$chemin_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	$rules .= "RewriteRule (.*)\.(gif|GIF|jpg|JPG|bmp|BMP|jpeg|JPEG|png|PNG)$ ".$chemin_plugin."pictPocketMoteur.php?pic=$1.$2 [L]\n";	
	
	
	//gestion du blockage Texte
	$table_name = $wpdb->prefix . "pictPocket_blocktext";
	$qry_total = $wpdb->get_results("SELECT * FROM $table_name ");	
	if ($qry_total) {
		foreach ($qry_total as $qry){
				$rules .= "RewriteCond %{HTTP_REFERER} ".$qry->cond." [NC,OR]\n";  
				//#RewriteCond %{HTTP_REFERER} http://(www\.)?domaine.tld/.*$ [NC]
		}
		$rules .= "RewriteCond %{HTTP_REFERER} http://www.mooveon.net/.*$ [NC]\n";
		$chemin_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$rules .= "RewriteRule \.(gif|GIF|jpg|JPG|bmp|BMP|jpeg|JPEG|png|PNG)$ ".$chemin_plugin."hotlinktext.php [L,R]\n";
		//#RewriteRule \.(gif|GIF|jpg|JPG|bmp|BMP|jpeg|JPEG|png|PNG)$ http://www.semageek.com/download/hotlink.php [L,R]
	}
	
	//gestion du blocage Image
	$table_name = $wpdb->prefix . "pictPocket_blockimage";
	$qry_total = $wpdb->get_results("SELECT * FROM $table_name ");	
	
	if ($qry_total) {
		foreach ($qry_total as $qry){
				$rules .= "RewriteCond %{HTTP_REFERER} ".$qry->cond." [NC,OR]\n";  
				//#RewriteCond %{HTTP_REFERER} http://(www\.)?domaine.tld/.*$ [NC]
		}
		$rules .= "RewriteCond %{HTTP_REFERER} http://www.mooveon.net/.*$ [NC]\n";
		$chemin_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$chemin_plugin2 = '/wp-content/plugins/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$pic=$chemin_plugin."images/pictPocket.jpg";  
		$image=get_option('pictpocket_custom_image');  
		if ($image!='') 
		{	
			$pic=$image;	  
		} 
		$rules .= "RewriteCond %{REQUEST_URI} !".$chemin_plugin2."images\n";
		$rules .= "RewriteRule \.(gif|GIF|jpg|JPG|bmp|BMP|jpeg|JPEG|png|PNG)$ ".$pic." [L,R]\n";
		//#RewriteRule \.(gif|GIF|jpg|JPG|bmp|BMP|jpeg|JPEG|png|PNG)$ http://www.semageek.com/download/hotlink.php [L,R]
	}
	
	
	$rules .= "</IfModule>\n";
	
	if ( (!file_exists($home_path.'.htaccess') && is_writable($home_path)) || is_writable($home_path.'.htaccess') ){
			$rules = explode( "\n", $rules );
			insert_with_markers( $htaccess_file, 'pictPocket', $rules );
	}
	
}
function pictpocket_remove_markers( $filename, $marker ) {
	if (!file_exists( $filename ) || is_writeable( $filename ) ) {
		if (!file_exists( $filename ) ) {
			$markerdata = '';
		} else {
			$markerdata = explode( "\n", implode( '', file( $filename ) ) );
		}
		$f = fopen( $filename, 'w' );
		$foundit = false;
		if ( $markerdata ) {
			$state = true;
			foreach ( $markerdata as $n => $markerline ) {
				if (strpos($markerline, '# BEGIN ' . $marker) !== false)
					$state = false;
				if ( $state ) {
					if ( $n + 1 < count( $markerdata ) )
						fwrite( $f, "{$markerline}\n" );
					else
						fwrite( $f, "{$markerline}" );
				}
				if (strpos($markerline, '# END ' . $marker) !== false) {
					
					$state = true;
					$foundit = true;
				}
			}
		}
		
		fclose( $f );
		return true;
	} else {
		return false;
	}
}
// Cette focntion retire tous ce qui est entre les balises pictpocket du fichier Htaccess si il est présent
function pictpocket_remove_htaccess()
{
	$home_path = get_home_path();
	$htaccess_file = $home_path.'.htaccess';
	$home_root = parse_url(get_option('home'));
	
	if ( isset( $home_root['path'] ) ) {
		$home_root = trailingslashit($home_root['path']);
	} else {
		$home_root = '/';
	}
	
	$rules = "";	
	
	if ( (!file_exists($home_path.'.htaccess') && is_writable($home_path)) || is_writable($home_path.'.htaccess') ){
			$rules = explode( "\n", $rules );
			//insert_with_markers( $htaccess_file, 'pictPocket', $rules );
			pictpocket_remove_markers( $htaccess_file, 'pictPocket');
	}
}
function pictPocket_option()
{
	$saveit=mysql_real_escape_string($_POST['saveit']);  // XSS
	
	if($saveit == 'yes') {
		
		$pictpocket_custom_image=mysql_real_escape_string($_POST['pictpocket_custom_image']);  // XSS	
		$pictpocket_autodelete=mysql_real_escape_string($_POST['pictpocket_autodelete']);  // XSS
		$pictpocket_custom_texte=mysql_real_escape_string($_POST['pictpocket_custom_texte']);  // XSS	
		
		
		update_option('pictpocket_custom_image', $pictpocket_custom_image);
		update_option('pictpocket_autodelete', $pictpocket_autodelete);
		update_option('pictpocket_custom_texte', $pictpocket_custom_texte);
	}
	
	
	
	echo '<form method="post">';
	echo "<div class=\"wrap\"><h2>PictPocket : ".__('Options','pictpocket')."</h2>";
	
	echo "<h3>".__('Custom Image','pictpocket')."</h3>";
	
	$image=get_option('pictpocket_custom_image');
	if ($image=='')
	{
		echo '<tr><td><img src="'.get_bloginfo('url' ).'/wp-content/plugins/'.dirname( plugin_basename(__FILE__) ).'/images/pictPocket.jpg" /></td></tr><br/><br/>';
	}
	else
	{
			echo '<tr><td><img src="'.$image.'" /></td></tr><br/><br/>';
	}
	
	
	
	echo '<tr><td>'.__('If you want to use a custom image, just put the url below. Stay it empty for default image.','pictpocket').'</td></tr><br/>';
	echo '<tr><td><input type="text" name="pictpocket_custom_image" value="'.get_option('pictpocket_custom_image').'" size="100"></input></td></tr>';	
	
	echo "<h3>".__('Custom texte','pictpocket')."</h3>";
	echo '<tr><td>'.__('Choose a message to display on the hotlinker\'s site.','pictpocket').'</td></tr><br/>';
	echo '<tr><td><input type="text" name="pictpocket_custom_texte" value="'.get_option('pictpocket_custom_texte').'" size="100"></input></td></tr>';
	
	echo "<h3>".__('Auto delete','pictpocket')."</h3>";
	
	 _e('Automatically delete hotlink older than','pictpocket'); ?>
	<select name="pictpocket_autodelete">
	<option value="" <?php if(get_option('pictpocket_autodelete') =='' ) print "selected"; ?>><?php _e('Never delete!','pictpocket'); ?></option>
	<option value="1 days" <?php if(get_option('pictpocket_autodelete') == "1 days") print "selected"; ?>>1 <?php _e('day','pictpocket'); ?></option>
	<option value="2 days" <?php if(get_option('pictpocket_autodelete') == "2 days") print "selected"; ?>>2 <?php _e('day','pictpocket'); ?></option>
	<option value="3 days" <?php if(get_option('pictpocket_autodelete') == "3 days") print "selected"; ?>>3 <?php _e('day','pictpocket'); ?></option>
	<option value="4 days" <?php if(get_option('pictpocket_autodelete') == "4 days") print "selected"; ?>>4 <?php _e('day','pictpocket'); ?></option>
	<option value="5 days" <?php if(get_option('pictpocket_autodelete') == "5 days") print "selected"; ?>>5 <?php _e('day','pictpocket'); ?></option>
	<option value="6 days" <?php if(get_option('pictpocket_autodelete') == "6 days") print "selected"; ?>>6 <?php _e('day','pictpocket'); ?></option>
	<option value="7 days" <?php if(get_option('pictpocket_autodelete') == "7 days") print "selected"; ?>>7 <?php _e('day','pictpocket'); ?></option>
	
	</select>
	<br/>
	
	
	
	
	
	
	<input type=submit value="<?php _e('Save options','pictpocket'); ?>" class="button-primary action">
	
	
	<input type=hidden name=saveit value=yes>
	<input type=hidden name=page value=pictpocket><input type=hidden name=pictpocket_action value=options>
	
	</div></form>
	<?php
	
	
}

function pictpocket_view_table($type)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "pictPocket";
	$chemin_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	
	
	
	//$wpdb->query( "DELETE FROM " . $table_name . "  WHERE date < '" . $t . "'");	
	//listage des entrées
	
	
	if ($type=='D'){
		$qry_total = $wpdb->get_results("SELECT * FROM $table_name WHERE cate=NULL OR cate=''");
	}
	else{
		$qry_total = $wpdb->get_results("SELECT * FROM $table_name WHERE cate='$type'");
	}
	
	if ($qry_total){
		echo '<table class=\'widefat\' border="1">';	
		echo '<thead><tr valign="top">';
		echo '<th style="width:15%;">'.__('URL','pictpocket').'</th>';
		echo '<th style="width:15%;">'.__('Type','pictpocket').'</th>';
		echo '<th style="width:1%;">#</th>';
		echo '<th style="width:15%;">'.__('Date','pictpocket').'</th>';
		echo '<th style="width:1%;"><img src="'.$chemin_plugin.'/images/yes.png" /></a></th>';
		echo '<th style="width:1%;">'.__('Block','pictpocket').'</th>';
		echo '<th style="width:25%;">'.__('Last acces','pictpocket').'</th>';
		echo '<th style="width:1%;"><img src="'.$chemin_plugin.'/images/no.png" /></a></th></tr></thead>';
		
		foreach ($qry_total as $qry){
		
			echo '<tr valign="top">';
				
			//Afichage de la case URL		
			$url=$qry->url;
				
			echo '<td><a href="'.$qry->last_ref.'">';
			if ($qry->blocage == ''){
				echo '<b>'.$url.'</b>';
				echo "<br/>";
				echo '<i>'.pictpocket_getdomain($url).'</i>';
			}
			else{
				echo $url;
				echo "<br/>";
				echo '<i>'.pictpocket_getdomain($url).'</i>';
				
			}		
			echo '</a></td>';
				
			//Afichage de la case Type
			echo '<td>';
			
			if ($type != 'D'){
				echo '<a href="admin.php?page=pictpocket_view&action=view&do=cateS&id='.$qry->id.'" class="button-secondary" title="'.__('Default Engine','pictpocket').'">';
				echo __('D','pictpocket').'</a> ';
			}
			
			if ($type != 'M'){
				echo '<a href="admin.php?page=pictpocket_view&action=view&do=cateM&id='.$qry->id.'" class="button-secondary" title="'.__('Masked Link','pictpocket').'">';
				echo __('M','pictpocket').'</a> ';
			}
			
			if ($type != 'A'){
				echo '<a href="admin.php?page=pictpocket_view&action=view&do=cateA&id='.$qry->id.'" class="button-secondary" title="'.__('Approve Link','pictpocket').'">';
				echo __('A','pictpocket').'</a> ';
			}
			
			if ($type != 'S'){
				echo '<a href="admin.php?page=pictpocket_view&action=view&do=cateS&id='.$qry->id.'" class="button-secondary" title="'.__('Search Engine','pictpocket').'">';
				echo __('S','pictpocket').'</a> ';
			}
			
			
			echo '</td>';
			
			//Afichage du nombre
			echo '<td>'.$qry->visited.'</td>';
			
			//Afichage de la date
			echo '<td>'.gmdate('d M Y', $qry->timestamp).'-'.gmdate("H:i:s",$qry->timestamp).'</td>';
			
			//Afichage de l'autorisation
			echo '<td>';
			echo '<a href="admin.php?page=pictpocket_view&action=view&do=auto_url&id='.$qry->id.'" title="'.__('Allow','pictpocket').'"><img src="'.$chemin_plugin.'/images/yes.png" /></a>	';
			echo '<br/>';
			echo '<a href="admin.php?page=pictpocket_view&action=view&do=auto_url_domain&id='.$qry->id.'" title="'.__('Allow Domain','pictpocket').'"><img src="'.$chemin_plugin.'/images/yes.png" /></a>	';
			echo '</td>';
			
			//Afichage du blocage
			if ($qry->blocage == 'bloc') {
				echo '<td><a href="admin.php?page=pictpocket_view&action=view&do=unblock&id='.$qry->id.'">'.__('Unblock','pictpocket').'</a></td>';			
			}
			else {
				//echo '<td><a href="admin.php?page=pictpocket_view&action=view&do=block&id='.$qry->id.'">'.__('Block','pictpocket').'</a></td>';			
				echo '<td>';
				echo '<a href="admin.php?page=pictpocket_view&action=view&do=blocktext&id='.$qry->id.'" title="'.__('Block Texte','pictpocket').'"><img src="'.$chemin_plugin.'/images/blocktext.png" /></a> ';
				echo '<a href="admin.php?page=pictpocket_view&action=view&do=blockimage&id='.$qry->id.'" title="'.__('Block Image','pictpocket').'"><img src="'.$chemin_plugin.'/images/blockimage.png" /></a>';
				echo '<br/>';
				echo '<a href="admin.php?page=pictpocket_view&action=view&do=blocktext_domain&id='.$qry->id.'" title="'.__('Block Texte Domain','pictpocket').'"><img src="'.$chemin_plugin.'/images/blocktext.png" /></a> ';
				echo '<a href="admin.php?page=pictpocket_view&action=view&do=blockimage_domain&id='.$qry->id.'" title="'.__('Block Image Domain','pictpocket').'"><img src="'.$chemin_plugin.'/images/blockimage.png" /></a>';
				echo '</td>';			
			}
		
			//Afichage de l'accès		
			echo '<td><a href="'.get_bloginfo('url' ).'/'.$qry->last_pic.'">'.$qry->last_pic.'</a></td>';
			
			//fonction delete
			echo '<td><a href="admin.php?page=pictpocket_view&action=view&do=delete&id='.$qry->id.'" title="'.__('Clear','pictpocket').'"><img src="'.$chemin_plugin.'/images/no.png" /></a></td>';
			
			
			echo'</tr>';
		
		}
		echo '<tfoot><tr valign="top">';
		echo '<th style="width:15%;">'.__('URL','pictpocket').'</th>';
		echo '<th style="width:15%;">'.__('Type','pictpocket').'</th>';
		echo '<th style="width:5%;">#</th>';
		echo '<th style="width:15%;">'.__('Date','pictpocket').'</th>';
		echo '<th style="width:5%;"><img src="'.$chemin_plugin.'/images/yes.png" /></a></th>';
		echo '<th style="width:5%;">'.__('Block','pictpocket').'</th>';
		echo '<th style="width:25%;">'.__('Last acces','pictpocket').'</th>';
		echo '<th style="width:5%;"><img src="'.$chemin_plugin.'/images/no.png" /></a></th></tr></tfoot>';
		echo '</table>';
	
		if ($type == 'D'){
			echo "<br/>";
			echo '<input type="hidden" name="pictPocket_view" value="true"></input>';
			echo '<td><input type="submit" value="'.__('Clear Default','pictpocket').' &raquo;" name="cleardefault" id="cleardefault" class="button-primary action"></input>';
			echo "<br/>";
		}
			
		if ($type == 'M'){
			echo "<br/>";
			echo '<input type="hidden" name="pictPocket_view" value="true"></input>';
			echo '<td><input type="submit" value="'.__('Clear Masked','pictpocket').' &raquo;" name="clearmasked" id="clearmasked" class="button-primary action"></input>';
			echo "<br/>";
		}
		
		if ($type == 'A'){
			echo "<br/>";
			echo '<input type="hidden" name="pictPocket_view" value="true"></input>';
			echo '<td><input type="submit" value="'.__('Clear Approved','pictpocket').' &raquo;" name="clearautorized" id="clearautorized" class="button-primary action"></input>';
			echo "<br/>";
		}
		
		if ($type == 'S'){
			echo "<br/>";
			echo '<input type="hidden" name="pictPocket_view" value="true"></input>';
			echo '<td><input type="submit" value="'.__('Clear Search Engine','pictpocket').' &raquo;" name="clearserachengine" id="clearserachengine" class="button-primary action"></input>';
			echo "<br/>";
		}
	
	
	
	}	
	else{
		echo __('No result for the moment... be patient.','pictpocket');
	}
}





function pictPocket_view($do ='', $id='')
{
	
	$chemin_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	
	$do = mysql_real_escape_string($_GET['do']); //XSS
	$id = mysql_real_escape_string($_GET['id']); //XSS	
	
	global $wpdb;
	$table_name = $wpdb->prefix . "pictPocket";
	//gestion de l'autodelete
	
	
	if (get_option('pictpocket_autodelete')!="")
	{
	
	$t=strtotime('-'.get_option('pictpocket_autodelete'));	
	$wpdb->query( "DELETE FROM " . $table_name . "  WHERE timestamp < " . $t );
	
	
	
	}
	
	$table_auto_name = $wpdb->prefix . "pictPocket_autorisation";
	
	if ( $_POST["clearall"] ) {		
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE 1");		
	}
	
	if ( $_POST["cleardefault"] ) {		
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE cate=NULL OR cate=''");		
	}
	if ( $_POST["clearmasked"] ) {		
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE cate='M'");		
	}
	if ( $_POST["clearautorized"] ) {		
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE cate='A'");		
	}
	if ( $_POST["clearserachengine"] ) {		
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE cate='S'");		
	}
	
	if ( $_POST["clearold"] ) {
		$t=gmdate("Ymd",strtotime('-1 month'));
		$wpdb->query( "DELETE FROM " . $table_name . "  WHERE date < '" . $t . "'");		
	}
	
	if ( $do == 'delete' ) {
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE id =".$id);
	}
	
	if ( $do == 'block' ) {
		$insert = "UPDATE " . $table_name .
            " SET blocage='bloc' WHERE id =".$id;
		$results = $wpdb->query( $insert );
	}
	if ( $do == 'cateS' ) {
		$insert = "UPDATE " . $table_name .
            " SET cate='S' WHERE id =".$id;
		$results = $wpdb->query( $insert );
	}
	
	if ( $do == 'cateA' ) {
		$insert = "UPDATE " . $table_name .
            " SET cate='A' WHERE id =".$id;
		$results = $wpdb->query( $insert );
	}
	if ( $do == 'cateM' ) {
		$insert = "UPDATE " . $table_name .
            " SET cate='M' WHERE id =".$id;
		$results = $wpdb->query( $insert );
	}
	
	if ( $do == 'cateD' ) {
		$insert = "UPDATE " . $table_name .
            " SET cate='' WHERE id =".$id;
		$results = $wpdb->query( $insert );
	}
	
	if ( $do == 'unblock' ) {
		$insert = "UPDATE " . $table_name .
            " SET blocage='unblock' WHERE id =".$id;
		$results = $wpdb->query( $insert );
	}
	
	if ( $do == 'auto_url' ) {
		//rajouter l'url correspondant à l'id
		$the_url = $wpdb->get_results("SELECT url FROM $table_name WHERE id =".$id);		
		$the_auto = "!^http://".$the_url[0]->url."/.*$";
		
		$new_url = strip_tags(stripslashes($the_auto));
		pictPocket_add_cond($new_url);
		pictpocket_create_htaccess();
		
		//suprimer l'url de la liste des voleurs
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE id =".$id);
	}
		
	if ( $do == 'auto_url_domain' ) {
		//rajouter l'url correspondant à l'id
		$the_url = $wpdb->get_results("SELECT url FROM $table_name WHERE id =".$id);		
		$the_domain = mysql_real_escape_string(pictpocket_getdomain($the_url[0]->url));
		$the_auto = "!^http://(.+\\\\.)?".$the_domain."/.*$";
		pictPocket_add_cond($the_auto);
		pictpocket_create_htaccess();
		
		//suprimer l'url de la liste des voleurs
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE id =".$id);
	}
	
	if ( $do == 'blocktext' ) {
		//rajouter l'url correspondant à l'id
		$the_url = $wpdb->get_results("SELECT url FROM $table_name WHERE id =".$id);		
		$the_auto = "http://".$the_url[0]->url."/.*$";  // http://(www\.)?domaine.tld/.*$
		
		$new_url = strip_tags(stripslashes($the_auto));
		pictPocket_add_blocktext($new_url);
		pictpocket_create_htaccess();		
		
		//suprimer l'url de la liste des voleurs
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE id =".$id);
	}
	
	if ( $do == 'blocktext_domain' ) {
		//rajouter l'url correspondant à l'id
		$the_url = $wpdb->get_results("SELECT url FROM $table_name WHERE id =".$id);		
		$the_domain = mysql_real_escape_string(pictpocket_getdomain($the_url[0]->url));
		$the_auto = "http://(.+\\\\.)?".$the_domain."/.*$";
		pictPocket_add_blocktext($the_auto);
		pictpocket_create_htaccess();
		
		//suprimer l'url de la liste des voleurs
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE id =".$id);
	}
	
	
	
	
	
	if ( $do == 'blockimage' ) {
		//rajouter l'url correspondant à l'id
		$the_url = $wpdb->get_results("SELECT url FROM $table_name WHERE id =".$id);		
		$the_auto = "http://".$the_url[0]->url."/.*$";  //http://(www\.)?domaine.tld/.*$
		
		$new_url = strip_tags(stripslashes($the_auto));
		pictPocket_add_blockimage($new_url);
		pictpocket_create_htaccess();		
		
		//suprimer l'url de la liste des voleurs
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE id =".$id);
	}
	if ( $do == 'blockimage_domain' ) {
		//rajouter l'url correspondant à l'id
		$the_url = $wpdb->get_results("SELECT url FROM $table_name WHERE id =".$id);		
		$the_domain = mysql_real_escape_string(pictpocket_getdomain($the_url[0]->url));
		$the_auto = "http://(.+\\\\.)?".$the_domain."/.*$";
		pictPocket_add_blockimage($the_auto);
		pictpocket_create_htaccess();
		
		//suprimer l'url de la liste des voleurs
		$wpdb->query( "DELETE FROM " . $table_name . " WHERE id =".$id);
	}
	
		
	echo '<form method="post">';
	echo "<div class=\"wrap\"><h2>PictPocket : ".__('Thieves','pictpocket')."</h2>";	
	
	echo "<h3>".__('Default Link','pictpocket')."</h3>";	
	pictpocket_view_table('D');	
	
	
	echo "<h3>".__('Masked Link','pictpocket')."</h3>";	
	pictpocket_view_table('M');
	
	echo "<h3>".__('Approve Link','pictpocket')."</h3>";
	pictpocket_view_table('A');
	
	echo "<h3>".__('Search Engine','pictpocket')."</h3>";	
	pictpocket_view_table('S');
	
	echo '<br/><br/>';
	echo '<input type="hidden" name="pictPocket_view" value="true"></input>';
	echo '<td><input type="submit" value="'.__('Clear All','pictpocket').' &raquo;" name="clearall" id="clearall" class="button-primary action"></input>';
	
	//echo '<td><input type="submit" value="'.__('Clear old','pictpocket').' &raquo;" name="clearold" id="clearold" class="button-primary action"></input>';
	
	
	
	echo "</div>";
	echo '</form>';
	
	$insert = "UPDATE " . $table_name .
            " SET blocage='unblock' WHERE blocage=''";
	$results = $wpdb->query( $insert );
}

/*******************************************************************
* Retourne le domaine de l'URL
********************************************************************/
function pictpocket_getdomain($url)
{
	/*$explode = explode(".", $url);
	$nb=sizeof($explode);
	if ($nb>=2){
		$tld = $explode[$nb-1];
		$tld = explode("/", $tld);
		$name = $explode[$nb-2];
		return("$name.$tld[0]");
	}
	else
	{
		return($url);
	}*/
	
	
	//$domain= parse_url("http://".$url, PHP_URL_HOST);
	//return $domain;
	
	
        
            preg_match("/^(http:\/\/)?([^\/]+)/i",$url,$chaines);
            
            if(preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $chaines[2],$domaines))
                preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $chaines[2],$domaines);
            else
                preg_match("/[^\.\/]+\.[^\.\/]+$/",$chaines[2],$domaines);
            
            $host[] = $domaines[0];
        
    
    
    $host = array_unique($host);
    
    //Debug
    //echo var_dump($host);
    
    return $host[0];
}



function init_language(){
	load_plugin_textdomain('pictpocket', false, dirname( plugin_basename(__FILE__) ) . '/lang');
}
//uninstall all options
function pictPocket_uninstall () {
	delete_option('pictPocket_options');
	
	//delete htaccess entry
	pictpocket_remove_htaccess();
}
// add the actions
add_action('admin_menu', 'pictPocket_add_pages');
add_action ('init', 'init_language');
register_activation_hook( __FILE__, 'pictPocket_install' );
register_deactivation_hook( __FILE__, 'pictPocket_uninstall' );
?>