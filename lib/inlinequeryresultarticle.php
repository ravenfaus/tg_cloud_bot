<?php

/**
 *
 */
class InlineQueryResultArticle
{
  public $type = 'article';
  public $id;
  public $title;
  public $input_message_content;
  public $description;

  public function __construct($id, $title, $content, $desc='')
  {
    $this->id = $id;
    $this->title = $title;
    $this->input_message_content = $content;
    $this->description = $desc;
    return ['type' => $this->type,
            'id' => $this->id,
            'title' => $this->title,
            'input_message_content' => $this->input_message_content,
            'description' => $this->description];
  }
}


?>
