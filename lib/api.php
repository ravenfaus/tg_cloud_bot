<?php
require_once('curl.php');
class api
{
	private $url = 'https://api.telegram.org/bot';
	private $furl = 'https://api.telegram.org/file/bot';
	private $token;
	private $db;

	public function __construct($token, $db = Null)
	{
		$this->token = $token;
		$this->db = $db;
	}

	private function request($method, $params = array())
	{
		$c = new curl();
		$r = $c->request($this->url.$this->token."/".$method, 'POST', $params);

		$j = json_decode($r, true);
		if($j)
			return $j;
		else
			return $r;
	}

	public function getFile($file_id)
	{
		$params = array
		(
			'file_id' => $file_id
		);
		return $this->request('getFile', $params);
	}

	public function sendDocument($id, $document, $thumb='', $caption='', $reply_markup=null)
	{
		$params = array
		(
			'chat_id' => $id,
			'document' => $document,
			'thumb' => $thumb,
			'caption' => $caption,
			'reply_markup' => ($reply_markup == null ? null : json_encode($reply_markup))
		);
		return $this->request('sendDocument', $params);
	}

	public function sendMessage($id, $text, $reply_markup=null, $reply_to=null, $parse_mode=null)
	{
		$params = array
		(
			'chat_id' => $id,
			'text' => $text,
			'reply_to_message_id' => $reply_to,
			'parse_mode' => $parse_mode,
			'reply_markup' => ($reply_markup == null ? null : json_encode($reply_markup))
		);
		return $this->request('sendMessage', $params);
	}

	public function editMessageText($id, $mid, $text='', $reply_markup=null)
	{
		$params = array
		(
			'text' => $text,
			'chat_id' => $id,
			'message_id' => $mid,
			'reply_markup' => ($reply_markup == null ? null : json_encode($reply_markup))
		);
		return $this->request('editMessageText', $params);
	}

	public function editMessageCaption($id, $mid, $caption='', $reply_markup=null)
	{
		$params = array
		(
			'caption' => $caption,
			'chat_id' => $id,
			'message_id' => $mid,
			'reply_markup' => ($reply_markup == null ? null : json_encode($reply_markup))
		);
		return $this->request('editMessageCaption', $params);
	}

	public function deleteMessage($id, $mid)
	{
		$params = array
		(
			'chat_id' => $id,
			'message_id' => $mid,
		);
		return $this->request('deleteMessage', $params);
	}

	public function answerCallbackQuery($id, $text=null, $show_alert=null)
	{
		$params = array
		(
			'callback_query_id' => $id,
			'text' => $text,
			'show_alert' => $show_alert
		);
		return $this->request('answerCallbackQuery', $params);
	}

	//https://core.telegram.org/bots/api#sendphoto
	public function sendPhoto($id, $photo, $caption='', $reply_markup=null, $disable_notification=false, $parse_mode='HTML')
	{
		$params = array
		(
			'chat_id' => $id,
			'caption' => $caption,
			'photo' => $photo,
			'disable_notification' => $disable_notification,
			'parse_mode' => $parse_mode,
			'reply_markup' => ($reply_markup == null ? null : json_encode($reply_markup))
		);
		return $this->request('sendPhoto', $params);
	}

	//https://core.telegram.org/bots/api#sendchataction
	public function sendChatAction($id, $action)
	{
		$params = array
		(
			'chat_id' => $id,
			'action' => $action
		);
		return $this->request('sendChatAction', $params);
	}

	public function setWebhook($url)
	{
		$params = array
		(
			'url' => $url
		);
		return $this->request('setWebhook', $params);
	}

	public function downloadFile($file_path, $file_target) {
    $rh = fopen($this->furl.$this->token.'/'.$file_path, 'rb');
    $wh = fopen($file_target, 'w+b');
    if (!$rh || !$wh) {
        return false;
    }

    while (!feof($rh)) {
        if (fwrite($wh, fread($rh, 4096)) === FALSE) {
            return false;
        }
        echo ' ';
        flush();
    }

    fclose($rh);
    fclose($wh);

    return true;
		}

		public function answerInlineQuery($id, $results, $cache=300,
										$is_personal=false, $next_offset='',
										$switch_pm_text='', $switch_pm_parameter='')
		{
			$params = array
			(
				'inline_query_id' => $id,
				'results' => json_encode($results),
				'cache_time' => $cache,
				'is_personal' => $is_personal,
				'next_offset' => $next_offset,
				'switch_pm_text' => $switch_pm_text,
				'switch_pm_parameter' => $switch_pm_parameter
			);
			return $this->request('answerInlineQuery', $params);
		}
}
?>
