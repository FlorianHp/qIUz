<?php

$path = isset($_ENV['WEB_DIR']) ? $_ENV['WEB_DIR'] : './web';
$file = $path . explode('?', urldecode($_SERVER['REQUEST_URI']))[0];

if (is_file($file)) {
  $ps  = explode('.', $file);
  $ext = strtolower($ps[count($ps) - 1]);

  $mimeTypes = [
    // Images
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'webp' => 'image/webp',
    'avif' => 'image/avif',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon',

    // Fonts
    'ttf' => 'font/ttf',
    'otf' => 'font/otf',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'eot' => 'application/vnd.ms-fontobject',

    // Documents
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt' => 'text/plain',
    'rtf' => 'application/rtf',

    // Audio
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'm4a' => 'audio/mp4',

    // Video
    'mp4' => 'video/mp4',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime',
    'wmv' => 'video/x-ms-wmv',
    'flv' => 'video/x-flv',
    'webm' => 'video/webm',

    // Archives
    'zip' => 'application/zip',
    'rar' => 'application/vnd.rar',
    'tar' => 'application/x-tar',
    'gz' => 'application/gzip',
    '7z' => 'application/x-7z-compressed',

    // Web files
    'html' => 'text/html',
    'htm' => 'text/html',
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'xml' => 'application/xml',

    // Miscellaneous
    'csv' => 'text/csv',
    'ics' => 'text/calendar',
    'eot' => 'application/vnd.ms-fontobject'
  ];
  
  if ($ext != 'php') {
    $cacheControl  = isset($_ENV['WEB_CACHE']) ? $_ENV['WEB_CACHE'] : 'public, max-age=3600';
    $lastModified  = filemtime($file);
    $hash          = md5_file($file);
    $formattedTime = gmdate('D, d M Y H:i:s \G\M\T', $lastModified);

    // Set headers for the file
    header('Content-Type: '   . (isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'application/octet-stream'));
    header('Cache-Control: '  . $cacheControl);
    header('ETag: W/"'        . $hash . '"');
    header('Last-Modified: '  . $formattedTime);

    // Check if the client has a cached version
    if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $formattedTime) || (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === 'W/"' . $hash . '"')) {
      header('HTTP/1.1 304 Not Modified');
    } else {
      readfile($file);
    }

    exit;
  }
}
