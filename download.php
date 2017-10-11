<?php
if (isset($_GET['file'])) { 
	$path_parts = pathinfo($_GET['file']);
	$file_name  = $path_parts['basename'];
	$file = sys_get_temp_dir() . '/' . $file_name;
	$file_size  = filesize($file);
	$type = $_GET['type'];

	header('Pragma: public'); /* Needed for IE6 */
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); /* Needed for IE6 */
	header('Expires: -1');
	header('Content-Disposition: attachment; filename="' . $file_name . '.' . $type . '"');
	header('Content-Length: ' . $file_size);
	header('Content-Type: application/vnd.ms-outlook;charset=UTF-8'); 
	header('Content-Type: application/octet-stream');
	
	readfile($file);
} else {
	header('Location: index.php');
} 
?> 