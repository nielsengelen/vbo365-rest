<?php
function get_mime_type($filename) {
    $idx = explode('.', $filename);
    $count_explode = count($idx);
    $idx = strtolower($idx[$count_explode-1]);

    $mimet = array( 
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

        /* Images */
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

        /* Archives */
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        /* Audio and video */
        'mp3' => 'audio/mpeg',
        'qt'  => 'video/quicktime',
        'mov' => 'video/quicktime',

        /* Adobe */
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai'  => 'application/postscript',
        'eps' => 'application/postscript',
        'ps'  => 'application/postscript',

        /* Microsoft Office */
        'doc'  => 'application/msword',
        'rtf'  => 'application/rtf',
        'xls'  => 'application/vnd.ms-excel',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',

        /* Open Office */
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );

    if (isset($mimet[$idx])) {
        return $mimet[$idx];
    } else {
        return 'application/octet-stream';
    }
}

if (isset($_GET['file'])) { 
    $file =  str_replace('..', '', isset($_GET['file'])?$_GET['file']:'');
    if (isset($_GET['ext'])) { $ext = $_GET['ext']; }
    if (isset($_GET['name'])) { $name = $_GET['name']; }
    $filename = basename($name);

    if ($ext != "plain")
        $filename .= '.' . $ext;

    if(!is_file($file))
        exit();

    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

    if ($ext == "msg" || $ext == "pst") {
        header('Content-Encoding: UTF-8');
        header('Content-Type: application/vnd.ms-outlook;charset=UTF-8'); 
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Transfer-Encoding: binary ');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    } else {
        header('Last-Modified: ' . gmdate ('D, d M Y H:i:s', filemtime ($file)).' GMT');
        header('Cache-Control: private', false);
        if ($ext == "plain") {
            $mime = get_mime_type($filename);
            header('Content-Type: ' . $mime);
        } else {
            header('Content-Type: application/zip');
        }
        header('Content-Disposition: attachment; filename=" ' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($file));
        header('Connection: close');
    }

    readfile($file);
} else {
    header('Location: index.php');
}
?>