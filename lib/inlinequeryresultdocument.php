<?php

/**
 *
 */
class InlineQueryResultDocument
{
  public $type = 'document';
  public $id;
  public $title;
  public $caption;
  public $document_url;
  public $mime_type;

  function __construct($id, $title, $url, $mime)
  {
    $this->id = $id;
    $this->title = $title;
    $this->document_url = $url;
    $this->mime_type = $mime;
  }
}


?>
