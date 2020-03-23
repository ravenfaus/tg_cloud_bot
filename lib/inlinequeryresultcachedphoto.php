<?php

/**
 *
 */
class InlineQueryResultCachedPhoto
{
  public $type = 'photo';
  public $id;
  public $title;
  public $photo_file_id;
  public $caption;
  public $input_message_content;
  public $description;
  public $reply_markup;

  public function __construct($id, $title, $file_id, $caption='',
                            $content=Null, $desc='', $reply_markup=Null)
  {
    $this->id = $id;
    $this->title = $title;
    $this->photo_file_id = $file_id;
    $this->caption = $caption;
    $this->input_message_content = $content;
    $this->description = $desc;
    $this->reply_markup = $reply_markup;
  }

  public function get()
  {
    $arr = ['type' => $this->type,
            'id' => $this->id,
            'title' => $this->title,
            'photo_file_id' => $this->photo_file_id,
            'caption' => $this->caption,
            'description' => $this->description];
    if (!is_null($this->input_message_content))
      $arr['input_message_content'] = $this->input_message_content;
    if (!is_null($this->reply_markup))
        $arr['reply_markup'] = $this->reply_markup;
    return $arr;
  }
}
?>
