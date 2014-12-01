<?php

$imageDomain = 'http://s1-dev.uvnimg.com/';
$imagePath = $_REQUEST['path'];

$matches = array();
preg_match("/_([0-9]*)x([0-9]*).(jpg|png|gif|bmp|jpeg)$/", $imagePath, $matches);
if (count($matches)>2) {
    $width  = $matches[1];
    $height = $matches[2];
    $type   = $matches[3];
}

$hash = md5($imagePath);

$folder = substr($hash, 0, 5);

if (!is_dir('/tmp/img/' . $folder)) {
    mkdir('/tmp/img/' . $folder, 0777, true);
}

if (file_exists('/tmp/img/' . $folder . '/' . $hash . '.jpg')) {
    $output = file_get_contents('/tmp/img/' . $folder . '/' . $hash . '.jpg');
    header('Content-Type: image/jpeg');
    print $output;
    exit(0);
}

$image = $imageDomain . $_REQUEST['path'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $image);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$output       = curl_exec($ch);
$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($responseCode == 200 || $responseCode == 201) {
    file_put_contents('/tmp/img/' . $folder . '/' . $hash . '.jpg', $output);
    header('Content-Type: image/jpeg');
    print $output;
    exit(0);
}

$srcpng = dirname(__FILE__).'/Univision_2013_logo.png';
$srcjpg = dirname(__FILE__).'/univision_logo.jpg';
$srcgif = dirname(__FILE__).'/univision_logo.gif';
$srcbmp = dirname(__FILE__).'/Univision_logo.bmp';

if ($type == 'jpeg') {
    $type = 'jpg';
}

switch($type) {
    case 'bmp':
        $srcImg = imagecreatefromwbmp($srcbmp);
        $w = 275;
        $h = 183;
        break;
    case 'gif':
        $srcImg = imagecreatefromgif($srcgif);
        $w = 200;
        $h = 200;
        break;
    case 'jpg':
        $srcImg = imagecreatefromjpeg($srcjpg);
        $w = 473;
        $h = 466;
        break;
    case 'png':
        $srcImg = imagecreatefrompng($srcpng);
        $w = 300;
        $h = 265;
        break;
    default:
        return "Unsupported picture type!";
}

$newImg = imagecreatetruecolor($width, $height);

// preserve transparency
if ($type == "gif" or $type == "png") {
    imagecolortransparent($newImg, imagecolorallocatealpha($newImg, 0, 0, 0, 127));
    imagealphablending($newImg, false);
    imagesavealpha($newImg, true);
}

$ratioFull = $w/$h;
$ratioThumb = $width/$height;

//print $ratioFull . '<br/>';
//print $ratioThumb . '<br/>';

if ($ratioThumb > $ratioFull) {
    $src_w = $w;
    $src_h = $src_w / $ratioThumb;
    $src_x = 0;
    $src_y = 0;
    //print 'thumb > full' . '<br/>';
} else {
    $src_h = $h;
    $src_w = $src_h * $ratioThumb;
    $src_y = 0;
    $src_x = ($w - $src_w)/2;
    //print 'else' . '<br/>';
}

//print ' src x : ' . $src_x . ' y : ' . $src_y . ' w : ' . $src_w . ' h : ' . $src_h;
//exit;

imagecopyresampled($newImg, $srcImg, 0, 0, $src_x, $src_y, $width, $height, $w, $h);

header('Content-type: image/' . $type);
switch ($type) {
    case 'bmp':
        imagewbmp($newImg);
        break;
    case 'gif':
        imagegif($newImg);
        break;
    case 'jpg':
        imagejpeg($newImg);
        break;
    case 'png':
        imagepng($newImg);
        break;
}
imagedestroy($newImg);
exit(0);
