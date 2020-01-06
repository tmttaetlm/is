<?php

//define('ROOT', 'home/vladimir/Code/php/is');
//require_once 'home/vladimir/Code/php/is/application/Components/Config.php';
//require_once 'home/vladimir/Code/php/is/application/Components/DbSkd.php';
define('ROOT', 'var/www/is');
require_once ROOT.'/application/Components/Config.php';
require_once ROOT.'/application/Components/DbSkd.php';
Use Components\DbSkd;
Use Components\Config;

function api($api,$api_params = null){
    $token="941676067:AAEEuka-y8fo0syBivZQbAbvUyU1JLPF55I";

    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL,'https://api.telegram.org/bot'.$token.'/'.$api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$api_params);
    $result=curl_exec($ch);
    return $result;
    curl_close($ch);
}

function getTexts($section,$property) {
    $ini_params = parse_ini_file(dirname(__FILE__).'/text_data.ini',true);
    return $ini_params[$section][$property];
}

function execQuery($query,$params,$second_time = false) {
    $tsql = !$second_time ? getTexts('QUERIES',$query) : str_replace('1', '2', getTexts('QUERIES',$query));
    $db = DbSkd::getInstance();
    $result = strpos($tsql, 'SELECT') != 0 ? $db->execQuery($tsql,$params) : $db->updateQuery($tsql,$params);
    return $result;
}

$curdatetime = date('d.m.Y H:i:s', time()+(6*60*60));

$offset = file_get_contents(dirname(__FILE__).'/updates.txt');
$api_params = ['offset' => (int)$offset+1];
$result = api('getUpdates', $api_params);
$json = json_decode($result, true);
$updates = $json['result'];

$logText = '';

if (!empty($updates)) {
    foreach ($updates as $value) {

        $updateId = $value['update_id'];
        $chatId = $value['message']['chat']['id'];
        $fName = $value['message']['chat']['first_name'];
        $text = $value['message']['text'];
        $contact = $value['message']['contact']['phone_number'];
        $datetime = date('d.m.Y H:i:s', $value['message']['date']+(6*60*60));
        $reply_markup = '';

        if (!empty($contact)) {
            $param['contact1'] = $contact; $param['contact2'] = $contact;
            $data = execQuery('chk_phone',$param);
            unset($param);
            if (!empty($data)){
                $isAllSync = true;
                foreach ($data as $val) {
                    $num = $val['HomePhone'] == $contact ? '1' : '2';
                    if ($val['Chat_ID'.$num] == 0) { $isAllSync = false; break; }
                }
                if ($isAllSync) {
                    $sendText = getTexts('MESSAGES','all_sync');
                } else {
                    $param['contact'] = $contact; $param['chatId'] = $chatId; $param['subs'] = '1'; 
                    $data = execQuery('ins_chat1',$param);
                    $data = execQuery('ins_chat2',$param);
                    unset($param);
                    $sendText = getTexts('MESSAGES','cont_sync');
                    $reply_markup = ['remove_keyboard'=>true];
                    $logText = $logText.$datetime." | ".$curdatetime." Подключен новый пользователь ".$fName." ".$chatId." ".$contact."\n";
                }
            } else {
                $sendText = getTexts('MESSAGES','cont_notfound');
                $logText = $logText.$datetime." | ".$curdatetime." Номер телефона не найден в базе: ".$fName." ".$chatId." ".$contact."\n";
            }
        }

        if (!empty($text)){
            $param['chatId1'] = $chatId; $param['chatId2'] = $chatId;
            $data = execQuery('chk_chat',$param);
            unset($param);
            if (empty($data)) {
                $sendText = getTexts('MESSAGES','cont_inquire');
                $reply_markup = ['keyboard'=>[[['text'=>"Менің телефон номерімді жіберу\nОтправить мой номер телефона", 'request_contact'=>true]]],'resize_keyboard'=>true];
            } else {
                $num = $data[0]['Chat_ID1'] == $chatId ? '1' : '2';
                $phone = $data[0]['Chat_ID1'] == $chatId ? 'HomePhone' : 'WorkPhone';
                switch ($text) {
                    case '/start':
                        if ($data[0]['IsSubscribed'.$num] === '0') {
                            $param['chatId'] = $chatId; $param['subs'] = '1';
                            $data = execQuery('act_deact_subs',$param);
                            $data = execQuery('act_deact_subs',$param,true);
                            unset($param);
                            $sendText = getTexts('MESSAGES','subs_activate');
                            $logText = $logText.$datetime." | ".$curdatetime." Пользователь ".$fName."(".$data[0][$phone]."|".$chatId.") активировал подписку\n";
                        } else {
                            $sendText = getTexts('MESSAGES','alrd_activate');
                        }
                        break;
                    case '/stop':
                        if ($data[0]['IsSubscribed'.$num] === '1') {
                            $param['chatId'] = $chatId; $param['subs'] = '0';
                            $data = execQuery('act_deact_subs',$param);
                            $data = execQuery('act_deact_subs',$param,true);
                            unset($param);
                            $sendText = getTexts('MESSAGES','subs_deactivate');
                            $logText = $logText.$datetime." | ".$curdatetime." Пользователь ".$fName."(".$data[0][$phone]."|".$chatId.") отключил подписку\n";
                        } else {
                            $sendText = getTexts('MESSAGES','alrd_deactivate');
                        }
                        break;
                    case '/help':
                        $sendText = getTexts('MESSAGES','about');
                        break;
                    default:
                        $sendText = getTexts('MESSAGES','no_command');
                        $logText = $logText.$datetime." | ".$curdatetime." Пользователь ".$fName."(".$data[0][$phone]."|".$chatId."): ".$text."\n";
                        break;
                }
            }
        }
        $params = ['chat_id' => $chatId, 'text' => $sendText];
        if (!empty($reply_markup)) {
            $params['reply_markup'] = json_encode($reply_markup);
        }
        $result = api('sendMessage', $params);
        $resultJSON = json_decode($result, true);
        if (!$resultJSON['ok']) {
            $logText = $logText.$datetime." | ".$curdatetime." Error ".$resultJSON["error_code"].": ".$resultJSON["description"]." (".$fName." | ".$chatId." | ".$sendText.")\n";
        }
        file_put_contents(dirname(__FILE__).'/log.txt', $logText, FILE_APPEND);
    }
    file_put_contents(dirname(__FILE__).'/updates.txt', strval($updateId));
}
