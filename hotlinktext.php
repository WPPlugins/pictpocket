<?php
 
 // PickPocketMoteur
 // Version 1.4.2


 $root = dirname(dirname(dirname(dirname(__FILE__))));
if (file_exists($root.'/wp-load.php'))
{	// WP 2.6
	require_once($root.'/wp-load.php');
}
else
{	// avant 2.6
	require_once($root.'/wp-config.php');
}

	
  $text=get_option('pictpocket_custom_texte');

  header('WWW-Authenticate: Basic realm="'.$text.'"');
  header('HTTP/1.0 401 Unauthorized');
?>