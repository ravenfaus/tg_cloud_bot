<?php

/**
 *
 */
class Document
{
  public $file_id;
  public $file_unique_id;
  public $thumb;
  public $file_name;
  public $mime_type;
  public $file_size;

  function __construct($doc)
  {
    $this->file_id = $doc['file_id'];
    $this->file_unique_id = $doc['file_unique_id'];
    $this->thumb = $doc['thumb'];
    $this->file_name = $doc['file_name'];
    $this->mime_type = $doc['mime_type'];
    $this->file_size = $doc['file_size'];
  }
}
?>
