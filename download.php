<?php
set_time_limit(0);
session_start();

function get_mime_type($filename) {
	$idx = explode('.', $filename);
	$count_explode = count($idx);
	$idx = strtolower($idx[$count_explode-1]);

	$mimetypes = array( 
		'txt'  => 'text/plain',
		'htm'  => 'text/html',
		'html' => 'text/html',
		'php'  => 'text/html',
		'css'  => 'text/css',
		'js'   => 'application/javascript',
		'json' => 'application/json',
		'xml'  => 'application/xml',
		'swf'  => 'application/x-shockwave-flash',
		'flv'  => 'video/x-flv',

		'png'  => 'image/png',
		'jpe'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg'  => 'image/jpeg',
		'gif'  => 'image/gif',
		'bmp'  => 'image/bmp',
		'ico'  => 'image/vnd.microsoft.icon',
		'tiff' => 'image/tiff',
		'tif'  => 'image/tiff',
		'svg'  => 'image/svg+xml',
		'svgz' => 'image/svg+xml',

		'zip' => 'application/zip',
		'rar' => 'application/x-rar-compressed',
		'exe' => 'application/x-msdownload',
		'msi' => 'application/x-msdownload',
		'cab' => 'application/vnd.ms-cab-compressed',

		'mp3' => 'audio/mpeg',
		'qt'  => 'video/quicktime',
		'mov' => 'video/quicktime',

		'pdf' => 'application/pdf',
		'psd' => 'image/vnd.adobe.photoshop',
		'ai'  => 'application/postscript',
		'eps' => 'application/postscript',
		'ps'  => 'application/postscript',

		'doc'  => 'application/msword',
		'rtf'  => 'application/rtf',
		'xls'  => 'application/vnd.ms-excel',
		'ppt'  => 'application/vnd.ms-powerpoint',
		'docx' => 'application/msword',
		'xlsx' => 'application/vnd.ms-excel',
		'pptx' => 'application/vnd.ms-powerpoint',

		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
	);

	if (isset($mimetypes[$idx])) {
		return $mimetypes[$idx];
	} else {
		return 'application/octet-stream';
	}
}

if (!isset($_SESSION['token'])) {
	header('Location: index.php');
} else {
	if (isset($_POST['ext'])) { $ext = $_POST['ext']; }
	if (isset($_POST['name'])) { $name = $_POST['name']; }
	
	if (isset($_POST['file'])) {
		$file = sys_get_temp_dir() . '/' . basename($_POST['file']);
		$filename = htmlspecialchars(basename($name));

		if ($ext != 'plain')
			$filename .= '.' . $ext;

		if(!is_file($file))
			exit('File not found');

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

		if ($ext == 'msg' || $ext == 'pst') {
			header('Content-Encoding: UTF-8');
			header('Content-Type: application/vnd.ms-outlook;charset=UTF-8'); 
			header('Content-Type: application/octet-stream');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . filesize($file));
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		} else {
			header('Last-Modified: ' . gmdate ('D, d M Y H:i:s', filemtime ($file)).' GMT');
			header('Cache-Control: private', false);
			
			if ($ext == 'plain') {
				$mime = get_mime_type($filename);
				
				header('Content-Type: ' . $mime);
			} else {
				header('Content-Type: application/zip');
			}
			
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . filesize($file));
			header('Content-Disposition: attachment; filename=" ' . $filename . '"');
			header('Connection: close');
		}

		readfile($file);
		unlink($file);
	} else {
		header('Location: index.php');
	}
}
?>