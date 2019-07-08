<?php
  require_once (dirname(__FILE__) . '/utils.php');//get utilities


  $polygons = isset($_REQUEST['polygons']) ? json_decode($_REQUEST['polygons'],true) : null;
  $url = isset($_REQUEST['url']) ? $_REQUEST['url'] : null;
  $transparency = array_key_exists('trans',$_REQUEST) ? intval($_REQUEST['trans']) : 80;
  if($transparency < 0) $transparency = 0;
  if($transparency > 100) $transparency = 100;
  $colourNum = intval( 255 * (100-$transparency)/100);
  $image = null;

  if (!$url) {
    ob_clean();
    header('Location: /images/100x100-check.gif');
    return;
  }else {  //get image for any URL
    $image = loadURLContent($url);
  }

  if (!$image) {
    ob_clean();
    header('Location: /images/100x100-check.gif');
    return;
  }

  if ($polygons) {
    $bounds = array();
    foreach ($polygons as $polygon) {
      $bounds = array_merge($bounds,getBoundingRect($polygon));
    }
    $bounds = getBoundingRect($bounds);
    $w = $bounds[4] - $bounds[0]+1;// add 1 to include end points
    $h = $bounds[5] - $bounds[1]+1;
    $originX = $bounds[0];
    $originY = $bounds[1];

    // crop the image to bounding box to reduce work - todo test image size against bbox size if < 90% then die
    $img_resized = imagecreatetruecolor($w, $h)  or die;
    imagealphablending($img_resized, false);//preserve alpha
    imagesavealpha($img_resized, true);
    imagecopyresampled($img_resized, $image, 0, 0, $originX, $originY, $w, $h, $w, $h)  or die;
    imagedestroy($image);
    $image = $img_resized;

    // create mask of areas we need to keep
    $mask = imagecreatetruecolor($w, $h);
    imagefill($mask, 0, 0,     imagecolorallocate($mask, $colourNum, $colourNum, $colourNum));

    $opaque = imagecolorallocate($mask, 255, 255, 255);//allocate white for opaque
    foreach ($polygons as $polygon) {// draw all polygons to keep, tranlating points to new origin
      $transPoly = getTranslatedPoly($polygon,-$originX,-$originY, true);
      imagefilledpolygon($mask, $transPoly,count($transPoly)/2, $opaque);
    }

    $workImage = imagecreatetruecolor( $w, $h );
    imagesavealpha( $workImage, true );
    imagefill( $workImage, 0, 0, imagecolorallocatealpha( $workImage, 0, 0, 0, 127 ) );

    for( $x = 0; $x < $w; $x++ ) {
      for( $y = 0; $y < $h; $y++ ) {
        $alpha = imagecolorsforindex( $mask, imagecolorat( $mask, $x, $y ) );
        $alpha = 127 - floor( $alpha[ 'red' ] / 2 );
        $color = imagecolorsforindex( $image, imagecolorat( $image, $x, $y ) );
        if ($color['alpha'] > $alpha)
            $alpha = $color['alpha'];
        if ($alpha == 127) {
            continue;
            $color['red'] = 0;
            $color['blue'] = 0;
            $color['green'] = 0;
        }
        imagesetpixel( $workImage, $x, $y, imagecolorallocatealpha( $workImage, $color[ 'red' ], $color[ 'green' ], $color[ 'blue' ], $alpha ) );
      }
    }

    imagedestroy($image);
    imagedestroy($mask);
    $image = $workImage;
  }
  if (array_key_exists('rotate', $_REQUEST)) {
    $rotate = $_REQUEST['rotate'];
    $image = imagerotate($image,$rotate,0);
  }

  ob_clean(); //hack to remove injected space from buffer
  header('Content-type: image/png');
  imagepng($image);
  imagedestroy($image);

?>
