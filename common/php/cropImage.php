<?php


  $x = @$_REQUEST['x'] ? intval($_REQUEST['x']) : ( @$_REQUEST['x1'] ? intval($_REQUEST['x1']):0);
  $y = @$_REQUEST['y'] ? intval($_REQUEST['y']) :( @$_REQUEST['y1'] ? intval($_REQUEST['y1']):0);
  $w = @$_REQUEST['w'] ? intval($_REQUEST['w']) : null;
  $h = @$_REQUEST['h'] ? intval($_REQUEST['h']) : null;
  $x2 = @$_REQUEST['x2'] ? intval($_REQUEST['x2']) : 0;
  $y2 = @$_REQUEST['y2'] ? intval($_REQUEST['y2']) : 0;
  $url = @$_REQUEST['url'] ? $_REQUEST['url'] : null;
  $img = null;

  if (!$url) {
    header('Location: images/100x100-check.gif');
    return;
  }else {  //get image for any URL
    $img = loadURLContent($url);
  }


  if (!$img) {
    header('Location: images/100x100-check.gif');
    return;
  }

if (array_key_exists('rotate', $_REQUEST)) {
  $rotate = $_REQUEST['rotate'];
}

  // calculate image size
  // note - we never change the aspect ratio of the image!
  if(!@$w && @$x && @$x2){
    $w = abs($x2 -$x);
  }
  if(!@$h && @$y && @$y2){
    $h = abs($y2 -$y);
  }
  if(@$x && @$x2){
    $x = min($x,$x2);
  }
  if(@$y && @$y2){
    $y = min($y,$y2);
  }
  $img_resized = imagecreatetruecolor($w, $h)  or die;
  if ( @$rotate ) {
    $img = imagerotate($img,$rotate,0);
  }
  imagecopyresampled($img_resized, $img, 0, 0, $x, $y, $w, $h, $w, $h)  or die;

  $resized_file = tempnam('/tmp', 'resized');


  imagepng($img_resized, $resized_file);
  imagedestroy($img);
  imagedestroy($img_resized);

  $resized = file_get_contents($resized_file);

  unlink($resized_file);
  header('Content-type: image/png');

  // output to browser
  echo $resized;



  /**
  * download image from given url
  *
  * @param mixed $url
  * @return resource|null
  */
  function loadURLContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/null');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	//return the output as a string from curl_exec
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);	//don't include header in output
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);	// follow server header redirects
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);	// don't verify peer cert
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);	// timeout after ten seconds
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);	// no more than 5 redirections

    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    //error_log(" data = ". $data);

    $error = curl_error($ch);
    if ($error) {
      $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
      error_log("$error ($code)" . " url = ". $url);
      curl_close($ch);
      return false;
    } else {
      curl_close($ch);
      if($data){
        $img = imagecreatefromstring($data);
        return $img;
      }
      return null;
    }
  }
?>
