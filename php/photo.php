<?php

/**
 * Class for manipulating images
 */
class SimpleImage {

   /** @var resource */ var $image;
   /** @var mime */ var $image_type;

   /**
    * Loads the image file
    * @param string $filename
    * @return void
    */
   function load($filename) {
      $image_info = getimagesize($filename);
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {
         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {
         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {
         $this->image = imagecreatefrompng($filename);
      }
   }

   /**
    * Save the file
    * @param string $filename
    * @param mime $image_type
    * @param real $compression
    * @param octal $permissions
    * @return void
    */
   function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image,$filename);
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image,$filename);
      }
      if( $permissions != null) {
         chmod($filename,$permissions);
      }
   }

   /**
    * Outputs the file
    * @param mime $image_type
    * @return void
    */
   function output($image_type=IMAGETYPE_JPEG) {
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image);
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image);
      }
   }

   /**
    * Get the width of the picture
    * @return integer
    */
   function getWidth() {
      return imagesx($this->image);
   }

   /**
    * Get the height of the picture
    * @return integer
    */
   function getHeight() {
      return imagesy($this->image);
   }

   /**
    * Helper functino for crop
    * @param integer $max_size_width
    * @param integer $max_size_height
    * @return resource
    */
   function auto( $max_size_width, $max_size_height ) {
      return $this->crop( $max_size_width, $max_size_height );
   }

   /**
    * Resize the picture to the Height, respects aspect ratio
    * @param integer $height
    * @return resource
    */
   function resizeToHeight($height) {
      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      return $this->resize($width,$height);
   }

   /**
    * Resize the picture to the width, respects aspect ratio
    * @param integer $width
    * @return resource
    */
   function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      return $this->resize($width,$height);
   }

   /**
    * Resize the picture to a scale
    * @param integer $scale
    * @return resource
    */
   function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100;
      return $this->resize($width,$height);
   }

   /**
    * Resize the picture to height and width, doesnt respect aspect ratio
    * @param integer $width
    * @param integer $height
    * @return resource
    */
   function resize($width,$height) {
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image;
      return $new_image;
   }


   /**
    * Crops a picture to the dimensions and resizes the picture if necesary
    * @param integer $max_w
    * @param integer $max_h
    * @return resource
    */
   function crop( $max_w, $max_h ) {

    $bigestSide = $max_h > $max_w ? $max_h : $max_w;

    if( $this->getHeight() > $this->getWidth() ) {
        // biger height, resize by width
        $image = $this->resizeToWidth( $max_w );
    } elseif ( $this->getHeight() < $this->getWidth() ) {
        // biger width, resize by height
        $image = $this->resizeToHeight( $max_h );
    } else {
        // same dimensions resize to max
        $image = $this->resize( $max_w, $max_h );
    }

    $new_h = imagesy( $image );
    $new_w = imagesy( $image );

    if( $new_h > $new_w ) {
        //biger height, crop height
        $start_x = 0; //width
        $start_y = abs( $new_h - $max_h )/2; //height
    } elseif( $new_h < $new_w ) {
        //biger width, crop width
        $start_x = abs( $new_w - $max_w )/2; //width
        $start_y = 0; //height
    } elseif( $new_w == $new_w ) {
        //same height and width return resized image
        $this->image = $image;
        return $this->image;
    }

    $new_image = imagecreatetruecolor( $max_w, $max_h );
    imagecopyresampled($new_image, $image, 0, 0, $start_x, $start_y, $max_w, $max_h, $this->getWidth(), $this->getHeight());

    $this->image = $new_image;
    return $new_image;

   }
}

?>