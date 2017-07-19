<?php
// PickPocketMoteur//Version 1.4.2


$root = dirname(dirname(dirname(dirname(__FILE__))));
if (file_exists($root.'/wp-load.php'))
{	// WP 2.6
	require_once($root.'/wp-load.php');
}
else
{	// avant 2.6
	require_once($root.'/wp-config.php');
}	// WP 2.6require_once($root.'/'."wp-content/plugins/".plugin_basename(dirname(__FILE__)).'/watermark.php');

$pic= ($_GET['pic']);
$last_ref = (isset($_SERVER['HTTP_REFERER']) ? htmlentities($_SERVER['HTTP_REFERER']) : '');$url_parsee = parse_url($last_ref);$ref = $url_parsee['host'];
global $wpdb;
$table_name = $wpdb->prefix . "pictPocket";

//gestion du visites
$qry = $wpdb->get_results("SELECT visited FROM $table_name WHERE url='$ref' ");

if ($qry == null)
{
	//test			
	$visited = 1;
	$blocage = '';
	$ip = $_SERVER['REMOTE_ADDR'];
	$timestamp  = current_time('timestamp');
	
	$insert = "INSERT INTO " . $table_name .
            " ( url, visited, ip, blocage,cate,last_ref,last_pic, timestamp) " .
            "VALUES ('$ref','$visited','$ip','$blocage','$cate','$last_ref','$pic','$timestamp')";
	$results = $wpdb->query( $insert );
}
else
{

$visited=$qry[0]->visited+1;
	$insert = "UPDATE " . $table_name .            " SET visited=" .            "'$visited' WHERE url='$ref' ";		$results = $wpdb->query( $insert );			$insert = "UPDATE " . $table_name .            " SET last_ref=" .            "'$last_ref' WHERE url='$ref' ";		$results = $wpdb->query( $insert );
		

		$insert = "UPDATE " . $table_name .            " SET last_pic=" .            "'$pic' WHERE url='$ref' ";		$results = $wpdb->query( $insert );
}


$qry = $wpdb->get_results("SELECT blocage FROM $table_name WHERE url='$ref'");

 if ($qry[0]->blocage == 'bloc')  {  
	$chemin_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	$pic=chemin_plugin."/images/pictPocket.jpg";  
	$image=get_option('pictpocket_custom_image');  
	if ($image!='') 
	{	
	$pic=$image;	  
	}  	  
 
	$size=getimagesize($pic);	  
	header("Content-Type:{$size['mime']}");	  
	header("Content-Transfert-Encoding: binary");	  
	$fp=fopen($pic, "r");	  
	if ($fp) fpassthru($fp);  
}      
 else {	
	$pic=$root.'/'.$pic;	
	$size=getimagesize($pic);	
	header("Content-Type:{$size['mime']}");	
	header("Content-Transfert-Encoding: binary");	
	$fp=fopen($pic, "r");	if ($fp) fpassthru($fp); 
}


?>