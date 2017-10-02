<?php
/**
 * Download page for both MSG & PST files
 */

if (isset($_GET['file'])) { 
	$file = $_GET['file'];
	$type = $_GET['type'];
	
	header('Pragma: public');
	header('Expires: 0');
	header('Content-Encoding: UTF-8');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Content-type: application/vnd.ms-outlook;charset=UTF-8'); 
	header('Content-Type: application/force-download');
	header('Content-Type: application/octet-stream');
	header('Content-Type: application/download');
	header('Content-Transfer-Encoding: binary ');
	header('Content-Disposition: attachment; filename="' . basename($file) . '.' . $type . '"');
	
	readfile($file);
} else {
	header('Location: index.php');
} 
?> 