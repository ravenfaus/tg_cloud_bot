<?php
define('PATH', realpath('./'));
// Require all files from lib
$files = glob('lib/*.php');

foreach ($files as $file) {
    require_once($file);
}
// Require all files from src
$files = glob('src/*.php');

foreach ($files as $file) {
    require_once($file);
}

$data = getRequest();
$config = json_decode(file_get_contents(PATH."/config.json"));
$api = new api($config->telegram->token);
$db = new database('src/users.db');
if (isset($data['callback_query']))
  parse_callback($data['callback_query']);
elseif (isset($data['message'])) {
  parse_message($data['message']);
} elseif (isset($data['inline_query'])) {
  parse_inline($data['inline_query']);
}

function parse_inline($iquery)
{
    global $api, $db;
    $id = $iquery['id'];
    $user = new User($iquery['from']);
    $query = $iquery['query'];
    switch ($query) {
      case '':
      case '/f':
      case '/p':
        $offset = empty($iquery['offset']) ? 0 : $iquery['offset'];
        $arr = [];
        if ($query == '/p')
          $count = get_photos_arr($arr, $db, $user->id, 50, $offset);
        else
          $count = get_files_arr($arr, $db, $user->id, 50, $offset);
        if ($count == 0)
        {
          $r = $api->answerInlineQuery($id, null, 0, true, 0, 'Add media', 'add');
          $api->sendMessage($user->id, $r['description']);
          break;
        }
        if ($count == 50)
          $offset += $count;
        else
          $offset = '';
        $r = $api->answerInlineQuery($id, $arr, 0, true,
          $offset, 'Add media', 'add');
        $api->sendMessage($user->id, $r['description']);
        break;
      default:
        $count = 0;
        $arr = [];
        $files = $db->find_files($user->id, $query, 'photo');
        if ($files->fetchArray())
        {
          $files->reset();
          while ($f = $files->fetchArray()) {
            $art = new InlineQueryResultCachedPhoto(
                $f['id'],
                $f['name'],
                $f['file_id'],
                '',
                Null,
                time_elapsed_string('@'.$f['date'])
              );
            array_push($arr, $art->get());
            $count++;
          }
        } else {
          $files = $db->find_files($user->id, $query, 'document');
          while ($f = $files->fetchArray()) {
            $art = new InlineQueryResultCachedDocument(
                $f['id'],
                $f['name'],
                $f['file_id'],
                '',
                Null,
                time_elapsed_string('@'.$f['date'])
              );
            array_push($arr, $art->get());
            $count++;
          }
        }
        if ($count == 0)
        {
          $api->answerInlineQuery($id, null, 0, true, 0, 'Add media', 'add');
          break;
        }
        $api->answerInlineQuery($id, $arr, 0, true,
          '', 'Add media', 'add');
        break;
    }
}

function get_photos_arr(&$arr, $db, $uid, $limit, $offset)
{
  $files = $db->get_photos($uid, $limit, $offset);
  $count = 0;
  while ($f = $files->fetchArray()) {
    $art = new InlineQueryResultCachedPhoto(
        $f['id'],
        $f['name'],
        $f['file_id'],
        '',
        Null,
        time_elapsed_string('@'.$f['date'])
      );
    array_push($arr, $art->get());
    $count++;
  }
  return $count;
}

function get_files_arr(&$arr, $db, $uid, $limit, $offset)
{
  $files = $db->get_files($uid, $limit, $offset);
  $count = 0;
  while ($f = $files->fetchArray()) {
    $art = new InlineQueryResultCachedDocument(
        $f['id'],
        $f['name'],
        $f['file_id'],
        '',
        Null,
        time_elapsed_string('@'.$f['date'])
      );
    array_push($arr, $art->get());
    $count++;
  }
  return $count;
}

function get_list($db, $uid, $limit, $offset)
{
  $text = "You have " . $db->get_files_count($uid) . " files total" . PHP_EOL . PHP_EOL;
  $files = $db->get_all($uid, $limit, $offset);
  while ($f = $files->fetchArray()) {
    $text .= "🕙File added " . time_elapsed_string('@'.$f['date']) . PHP_EOL;
    $text .= "📄Name: " . $f['name'] . PHP_EOL;
    $text .= "📚Type: " . $f['type'] . PHP_EOL;
    $text .= "📖View: /f" . $f['id'] . PHP_EOL . PHP_EOL;
  }
  return $text;
}

function parse_callback($clk)
{
  global $api, $db;
  $cid = $clk['id'];
  $user = new User($clk['from']);
  $data = explode(' ', $clk['data']);
  switch ($data[0]) {
    case 'name':
      $db->set_last_msg($user->id, $clk['data']);
      $api->sendMessage($user->id, 'Enter new name for file');
      break;
    case 'delete':
      $db->delete_file($data[1], $user->id);
      //$r = $api->editMessageCaption($user->id, $clk['message']['message_id'],
        //'File was deleted.');
      $api->deleteMessage($user->id, $clk['message']['message_id']);
      $api->sendMessage($user->id, 'File was deleted');
      break;
    case 'list':
      if ($data[1] == 'show')
      {
        $files = $db->get_all($user->id, 5, $data[2]);
        while ($f = $files->fetchArray())
        {
          $text = fileInfo($f);
          $ik = new InlineKeyboard();
          $ik->addRow([new InlineButton('Name', 'name ' . $f['id']),
                      new InlineButton('Delete', 'delete ' . $f['id'])]);
          sendFile($api, $user->id, $f['file_id'], $f['type'],
            $text, $ik->replyMarkup());
        }
        break;
      }
      $offset = $data[1];
      $count = $db->get_files_count($user->id);
      $list = get_list($db, $user->id, 5, $offset);
      $prev_btn = '⬅️';
      $next_btn = '➡️';
      $ik = new InlineKeyboard();
      if ($offset == 0) {
          $prev_btn = 'Start';
          $prev_offset = 0;
      } else {
          $prev_offset = $offset - 5;
      }
      if (($offset + 5) > $count) {
          $next_btn = 'End';
          $next_offset = $offset;
      }
      else {
          $next_offset = $offset + 5;
      }
      $ik->addRow([new InlineButton($prev_btn, 'list ' . $prev_offset),
                  new InlineButton($next_btn, 'list ' . $next_offset)]);
      $ik->addRow([new InlineButton('Show', 'list show ' . $offset)]);
      $api->editMessageText($user->id, $clk['message']['message_id'],
        $list, $ik->replyMarkup());
      break;
  }
  $api->answerCallbackQuery($cid);
}

function parse_message($msg)
{
	global $api, $db;
	$user = new User($msg['from']);
	if (!$db->user_exists($user->id))
		$db->add_user($user);

  if (isset($msg['document'])) {
    $doc = new Document($msg['document']);
    $id = $db->add_file($user->id, $doc->file_id, $doc->file_name, 'document');
    $ik = new InlineKeyboard();
    $ik->addRow([new InlineButton('Rename', 'name ' . $id),
                new InlineButton('Delete', 'delete ' . $id)]);
    $api->sendMessage($user->id, "File was uploaded." . PHP_EOL .
    "ID: /f" . $id, $ik->replyMarkup(), $msg['message_id']);
    return;
  } elseif (isset($msg['photo'])) {
    $photo = new PhotoSize(end($msg['photo']));
    $id = $db->add_file($user->id, $photo->file_id, $msg['caption'], 'photo');
    $ik = new InlineKeyboard();
    $ik->addRow([new InlineButton('✏️Rename', 'name ' . $id),
                new InlineButton('❌Delete', 'delete ' . $id)]);
    $api->sendMessage($user->id, "Photo was uploaded." . PHP_EOL .
    "ID: /f" . $id, $ik->replyMarkup(), $msg['message_id']);
    return;
  }
  // get file
  $message = $msg['text'];
  switch ($message) {
    case '/help':
      $api->sendMessage($user->id, 'Send me files and i will store him');
      break;
    case '/start add':
      $text = "Send me any photo, file, music, video or contact you'd like to save". PHP_EOL .
      "Type RavenSaveBot in other chats to access it any time later.".PHP_EOL.
      "You can search your files by name or file extension.";
      $api->sendMessage($user->id, $text);
      break;
    case '/start':
      $api->sendMessage($user->id, 'welcome');
      break;
    case '/list':
      $list = get_list($db, $user->id, 5, 0);
      $ik = new InlineKeyboard();
      $ik->addRow([new InlineButton('⬅️', 'list 0'),
                  new InlineButton('➡️', 'list 5')]);
      $ik->addRow([new InlineButton('Show', 'list show 0')]);
      $api->sendMessage($user->id, $list, $ik->replyMarkup());
      break;
    case '/drop':
        $db->erase_files($user->id);
        $api->sendMessage($user->id, 'All files was deleted');
        break;
    default:
      $last_msg = $db->get_last_msg($user->id);
      if (!empty($last_msg)) {
        $last_msg = explode(' ', $last_msg);
        switch ($last_msg[0]) {
          case 'name':
            $r = $db->update_filename($last_msg[1], $message);
            $api->sendMessage($user->id, 'Name was updated');
            $db->set_last_msg($user->id, '');
            break;
        }
      } else {
        preg_match('/(^\/f)(\d+)/', $message, $matches);
        if ($matches[1] == '/f') {
            $file = $db->get_file($matches[2], $user->id);
            if ($file) {
                $text = fileInfo($file);
                $ik = new InlineKeyboard();
                $ik->addRow([new InlineButton('✏️Rename', 'name ' . $file['id']),
                            new InlineButton('❌Delete', 'delete ' . $file['id'])]);
                sendFile($api, $user->id, $file['file_id'], $file['type'],
                  $text, $ik->replyMarkup());
            }
            else
              $api->sendMessage($user->id, '❌File with this id not exists');
        }
      }
      break;
  }
}

function sendFile($api, $uid, $id, $type, $text='', $reply_markup='')
{
  switch ($type) {
    case 'photo':
      $api->sendPhoto($uid, $id, $text, $reply_markup);
      break;
    case 'video':
      break;
    case 'audio':
      break;
    default:
      $api->sendDocument($uid, $id, '', $text, $reply_markup);
      break;
  }
}

function fileInfo($file)
{
  $text = '📄File name: ' . $file['name'] . PHP_EOL;
  $text .= '✏️ID: /f' . $file['id'] . PHP_EOL;
  $text .= '🕙Uploaded ' . time_elapsed_string('@' . $file['date']);
  return $text;
}

// From: https://stackoverflow.com/a/18602474
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function getRequest()
{
	$postdata = file_get_contents("php://input");
	$json = json_decode($postdata, true);
	if($json)
		return $json;
	return $postdata;
}
?>
