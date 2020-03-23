<?php

/**
 *
 */
class PhotoSize
{
  public $file_id;
  public $file_unique_id;
  public $width;
  public $height;
  public $file_size;

  function __construct($photo)
  {
    $this->file_id = $photo['file_id'];
    $this->file_unique_id = $photo['file_unique_id'];
    $this->width = $photo['width'];
    $this->height = $photo['height'];
    $this->file_size = $photo['file_size'];
  }
}
?>
