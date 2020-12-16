<?php
$bot_token = '1278159311:AAFScj8Om-XoL-8h59PDDvzZXS_gRolJwaM';
$td_parameters = [
    'api_id' => '425178' ,
    'api_hash' => 'a47dce755a2fb2099b7d3f462196c7b1',
    'database_directory' => '../home/hellfingers/to_write',
    'use_message_database' => true,
    'use_secret_chats' => true,
    'system_language_code' => 'en-GB',
    'device_model' => 'Iphone 6',
    'system_version' => '5.0.2',
    'application_version' => '1.1.1'
];
$host = '194.67.111.111';
$db   = 'hellfingers';
$user = 'hellfingers';
$pass = 'Hellfingers20!';
$charset = 'utf8';
$pathToIsbns = '/home/hellfigers/PhpstormProjects/cdz/ISBNS/';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);
$id = false;
$client = td_json_client_create();
td_json_client_send($client,json_encode(['@type' => 'setLogVerbosityLevel', 'new_verbosity_level' => '1']));
$result = td_json_client_execute($client, json_encode(['@type' => 'getAuthorizationState']));



while (true) {//основной цикл | main loop
    $res = td_json_client_receive($client, 10);
    $update = json_decode($res, true);
    var_dump($update);
    switch ($update['@type']):

        case 'updateAuthorizationState'://обработка авторизации | authorization processing
            switch ($update['authorization_state']['@type']):
                case 'authorizationStateWaitTdlibParameters':
                    $query = json_encode(['@type' => 'setTdlibParameters', 'parameters' => $td_parameters]);
                    td_json_client_send($client,$query);
                    var_dump('1');
                    break;
                case 'authorizationStateWaitEncryptionKey':
                    $query = json_encode(['@type' => 'checkDatabaseEncryptionKey', 'encryption_key' => '']);
                    td_json_client_send($client,$query);
                    var_dump('2');
                    break;
                case 'authorizationStateReady':
                    var_dump('AAAAAAAAAAAAAAAAAAAAAAAAAAAA');
                    print('AUTHORIZATION IS COMPLETE').PHP_EOL;;
                    break;
            endswitch;
            break;

        case 'updateNewMessage';//обработка нового сообщения | handle a new message
            switch($update['message']['content']['@type']){
                case 'messageText':
                    if($update['message']['is_outgoing'] == false) {
                        $chat_id = $update['message']['chat_id'];
                        $text = $update['message']['content']['text']['text'];
                        $message_id = $update['message']['id'];
                        switch ($text) {//обработка текста сообщения | handle message text
                            case '/start':
                                $data = $pdo->query('SELECT `userId` FROM `userInfo`')->fetchAll(PDO::FETCH_COLUMN);
                                if (in_array($chat_id, $data) == false) {//обработка нового пользователя | handle new user
                                    $statement = $pdo->prepare('INSERT INTO `userInfo` SET `userId` = :userId, `userBalance` = :userBalance, `freeDays` = :freeDays, `waitingSum` = :waitingSum');
                                    $statement->execute(array('userId' => $chat_id, 'userBalance' => 0, 'freeDays' => 3, 'waitingSum' => 0));
                                }
                                $freeDays = getLineWithCertainUIDFromBD($chat_id, $pdo)[0]['freeDays'];
                                switch($freeDays){//обработка разного количества оставшихся бесплатных дней | handle different number of remaining free days
                                    case 1:
                                        sendTextMessage('Привет, отправь мне ссылку на тест, чтобы получить ответы. Если тебе нужна помощь, отправь мне /help. Сейчас у тебя есть '.$freeDays.' бесплатный день. Когда они закончатся ответ на тест будет списывать 2 рубля с твоего баланса. Пополниь его ты можешь, отправив мне команду /pay',
                                            $client, $chat_id, [[['Помощь'=>'/help'], ['Баланс'=>'/balance']], [['Пополнить'=>'/pay'], ['Выйти'=>'/exit']]]);
                                        break;
                                    case 0:
                                        sendTextMessage('Привет, отправь мне ссылку на тест, чтобы получить ответы. Если тебе нужна помощь, отправь мне /help. Сейчас у тебя есть '.$freeDays.' бесплатных дней на тест. Когда они закончатся ответ на тест будет списывать 2 рубля с твоего баланса. Пополниь его ты можешь, отправив мне команду /pay',
                                            $client, $chat_id, [[['Помощь'=>'/help'], ['Баланс'=>'/balance']], [['Пополнить'=>'/pay'], ['Выйти'=>'/exit']]]);
                                        break;
                                    default:
                                        sendTextMessage('Привет, отправь мне ссылку на тест, чтобы получить ответы. Если тебе нужна помощь, отправь мне /help. Сейчас у тебя есть '.$freeDays.' бесплатных дня на тест. Когда они закончатся ответ на тест будет списывать 2 рубля с твоего баланса. Пополниь его ты можешь, отправив мне команду /pay',
                                            $client, $chat_id, [[['Помощь'=>'/help'], ['Баланс'=>'/balance']], [['Пополнить'=>'/pay'], ['Выйти'=>'/exit']]]);
                                }
                                break;
                            case '/help':
                                sendTextMessage('Отправь мне ссылку, чтобы получить ответ на тест. Отправь мне /balance, чтобы узнать твой текущий баланс. Отправь мне /pay чтобы пополнить баланс',
                                    $client, $chat_id, [[['Помощь'=>'/help'], ['Баланс'=>'/balance']], [['Пополнить'=>'/pay'], ['Выйти'=>'/exit']]]);
                                break;
                            case '/balance':
                                $answerFromBd = getLineWithCertainUIDFromBD($chat_id, $pdo);
                                $balance = $answerFromBd[0]['userBalance'];
                                $freeUrls = $answerFromBd[0]['freeUrls'];
                                sendTextMessage('Ваш текущий баланс: ' . $balance . ' рублей. Осталось бесплатных ссылок на тесты: ' . $freeUrls, $client, $chat_id, []);
                                break;
                            case '/pay':
                                sendTextMessage('Введите целое, больше 59 число, на которое будет пополнен баланс. Напишите /exit, чтобы выйти из оплаты', $client, $chat_id, []);
                                $statement = $pdo->prepare('UPDATE `userInfo` SET `waitingSumm` = :waitingSumm WHERE `userId` =:userId');
                                $statement->execute(array('userId' => $chat_id, 'waitingSumm' => 1));
                                break;
                            case '/exit':
                                sendTextMessage('Вы вышли из оплаты', $client, $chat_id, []);
                                $statement = $pdo->prepare('UPDATE `userInfo` SET `waitingSumm` = :waitingSumm WHERE `userId` =:userId');
                                $statement->execute(array('userId' => $chat_id, 'waitingSum' => 0));
                                break;
                            default:
                                $answerFromBd = getLineWithCertainUIDFromBD($chat_id, $pdo);
                                $balance = $answerFromBd[0]['userBalance'];
                                $freeDays = $answerFromBd[0]['freeDays'];
                                $waitingSum = $answerFromBd[0]['waitingSum'];
                                if(substr($text, 0, 5) == "/isbn") {
                                    if(checkTime($pdo, $chat_id, $client)) {//проверка бесплатного временного интервала | check free time interval
                                        if(file_exists($pathToIsbns.substr($text, 6).'.jpeg')) {
                                            sendPhoto($pathToIsbns.substr($text, 6).'.jpeg', $client, $chat_id, []);
                                        }
                                        else {
                                            sendTextMessage('Извините, я не нашел такого фото.', $client, $chat_id, []);
                                        }
                                    }
                                    else {
                                        if($freeDays > 0 || $balance > 0) {
                                            if(file_exists($pathToIsbns.substr($text, 6).'.jpeg')){
                                                sendPhoto($pathToIsbns.substr($text, 6).'.jpeg', $client, $chat_id, []);
                                                checkTime($pdo, $chat_id, $client);
                                            }
                                            else {
                                                sendTextMessage('Извините, я не нашел такого фото.', $client, $chat_id, []);
                                            }
                                        }
                                        else {
                                            sendTextMessage('У вас не осталось бесплатных попыток. Пожалуйста, пополните баланс используя команду /pay', $client, $chat_id, []);
                                        }
                                    }

                                }
                                else {
                                    if ($waitingSum == 1) {//бот ждет суммы пополнения | bot is waiting sum of pay
                                        if (is_positive_int($text)) {
                                            if ((int)$text <= 59) {
                                                sendTextMessage('Пожалуйста, введите число большее 59', $client, $chat_id, []);
                                            } else {
                                                if ((int)$text > 10000) {
                                                    sendTextMessage('Пожалуйста, введите число меньшее 10000', $client, $chat_id, []);
                                                } else {
                                                    $statement = $pdo->prepare('UPDATE `userInfo` SET `waitingSumm` = :waitingSumm WHERE `userId` =:userId');
                                                    $statement->execute(array('userId' => $chat_id, 'waitingSum' => 0));
                                                    sendInvoice($client, $chat_id, $text);
                                                }
                                            }
                                        } else {
                                            sendTextMessage('Введите целое положительное число, большее 59 или напишите /exit, чтобы выйти из оплаты', $client, $chat_id, []);
                                        }
                                    } else {//ссылка на тест | message text contains link on test
                                        $answers = [];
                                        if(checkTime($pdo, $chat_id, $client))
                                        {
                                            $answers = getAnswers($text);
                                            if (count($answers) != 0) {
                                                for ($i = 0; $i < count($answers); $i++) {
                                                    sendTextMessage($answers[$i], $client, $chat_id, []);
                                                }
                                            } else {
                                                sendTextMessage('Извините, не удалось получить ответ на тест. Деньги с вашего баланса не списаны. Попробуйте ещё раз или напишите админу', $client, $chat_id, []);
                                            }
                                        }
                                        else{
                                            if ($freeDays > 0 || $balance > 0) {
                                                $answers = getAnswers($text);
                                                if (count($answers) != 0) {
                                                    for ($i = 0; $i < count($answers); $i++) {
                                                        sendTextMessage($answers[$i], $client, $chat_id, []);
                                                    }
                                                    checkTime($pdo, $chat_id, $client);
                                                } else {
                                                    sendTextMessage('Извините, не удалось получить ответ на тест. Деньги с вашего баланса не списаны. Попробуйте ещё раз или напишите админу', $client, $chat_id, []);
                                                }
                                            } else {
                                                sendTextMessage('У вас не осталось бесплатных попыток. Пожалуйста, пополните баланс используя команду /pay', $client, $chat_id, []);
                                            }
                                        }
                                    }
                                }
                                break;
                        }
                    }
                    break;
                case 'messagePaymentSuccessfulBot'://обработка успешного платежа | successful payment handle
                    $sum = $update['message']['content']['total_amount']/100;
                    $preBalance = getLineWithCertainUIDFromBD($chat_id, $pdo)[0]['userBalance'];
                    $nowBalance = $preBalance + $sum;
                    $statement = $pdo->prepare('UPDATE `userInfo` SET `userBalance` = :userBalance WHERE `userId` =:userId');
                    $statement->execute(array('userId' => $chat_id, 'userBalance' => $nowBalance));
                    sendTextMessage('Вы успешно пополнили баланс на '.$sum.' рублей. Теперь он составляет '.$nowBalance.' рублей', $client, $chat_id, []);
                    $statement = $pdo->prepare('UPDATE `userInfo` SET `waitingSumm` = :waitingSumm WHERE `userId` =:userId');
                    $statement->execute(array('userId' => $chat_id, 'waitingSum' => 0));
                    break;
            }
            break;

        case 'updateNewPreCheckoutQuery'://обработка запроса перед оформлением платежа | handle pre checkout query
            $query_id = $update['id'];
            $query = json_encode([
                '@type' => 'answerPreCheckoutQuery',
                'pre_checkout_query_id' => $query_id,
                'error_message' => '']);
            td_json_client_send($client, $query);
            break;
    endswitch;
}


//функция получения строки из базы данных по определенному user id | function for getting a string from the database by a certain user id
function getLineWithCertainUIDFromBD($chat_id, $pdo){
    $statement = $pdo->prepare('SELECT * FROM `userInfo` WHERE `userId` = :userId');
    $statement->execute(array('userId' => $chat_id));
    $answerFromBd = $statement->fetchAll();
    return $answerFromBd;
}

//тестовая функция для получения тестовых ответов | test function for getting test answers
function getAnswers($testUrl){
    $answers = [];
    if($testUrl == 'рабочая ссылка'){
        $answers = [0 => 'first answer', 1 => 'second answer', 2 => 'third answer'];
    }
    return $answers;
}

//функция для отправки текстового сообщения | function for sending text message
function sendTextMessage($MessageText, $client, $chat_id, $keyboard_buttons){
    $reply_markup = [
        '@type'=>'replyMarkupShowKeyboard',
        'rows'=> [[['Помощь'=>'/help'], ['Баланс'=>'/balance']], [['Пополнить'=>'/pay'], ['Выйти'=>'/exit']]],
        'resize_keyboard'=>true,
        'one_time'=>true,
        'is_personal'=>true];
    $formatted_text = ['text' => $MessageText, 'entities' => []];
    $input_message = [
        '@type' => 'inputMessageText',
        'text' => $formatted_text,
        'disable_web_page_preview' => false,
        'clear_draft' => false];
    $query = json_encode([
        '@type' => 'sendMessage',
        'chat_id' => $chat_id,
        'reply_to_message_id' => '0',
        'disable_notification' => false,
        'from_background' => false,
        'reply_markup' => $reply_markup,
        'input_message_content' => $input_message]);
    td_json_client_send($client, $query);
}

//функция, которая проверяет является ли число положительным и целым | function that checks whether a number is positive and integer
function is_positive_int($num){
  $intNum = (int) $num;
  return ($intNum == $num && is_int($intNum) && $num > 0);
}


//функция, которая проверяет прошло ли 24 с момента предыдущего снятия денег с баланса | function that checks
// whether 24 hours have passed since the previous withdrawal of money from the balance
function checkTime($pdo, $chat_id, $client){
    $answerFromBd = getLineWithCertainUIDFromBD($chat_id, $pdo);
    $dateFromBD = $answerFromBd[0]['lastTime'];
    $dateFromBDInSec = strtotime($answerFromBd[0]['lastTime']);
    $dateNow = date("Y-m-d H:i:s");
    $dateNowInSec = strtotime(date("Y-m-d H:i:s"));
    $balance = $answerFromBd[0]['userBalance'];
    $freeDays = $answerFromBd[0]['freeDays'];
    if($dateFromBD == null){//пользователь ниразу не пользовался
        $freeDays -=1;
        $statement = $pdo->prepare('UPDATE `userInfo` SET `freeDays` = :freeDays WHERE `userId` =:userId');
        $statement->execute(array('userId' => $chat_id, 'freeDays' => $freeDays));
        $statement = $pdo->prepare('UPDATE `userInfo` SET `lastTime` = :lastTime WHERE `userId` =:userId');
        $statement->execute(array('userId' => $chat_id, 'lastTime' => $dateNow));
        return false;
    }
    else{
        if($dateNowInSec - $dateFromBDInSec >= 100){//прошло 24 часа
            if($freeDays > 0){
                $freeDays -= 1;
                $statement = $pdo->prepare('UPDATE `userInfo` SET `freeDays` = :freeDays WHERE `userId` =:userId');
                $statement->execute(array('userId' => $chat_id, 'freeDays' => $freeDays));
            }
            else{
                if(balance > 0){
                    $balance -= 1;
                    $statement = $pdo->prepare('UPDATE `userInfo` SET `userBalance` = :userBalance WHERE `userId` =:userId');
                    $statement->execute(array('userId' => $chat_id, 'userBalance' => $balance));
                }
            }
            sendTextMessage('С вашего аккаунта списан один рубль.', $client, $chat_id,[]);
            $statement = $pdo->prepare('UPDATE `userInfo` SET `lastTime` = :lastTime WHERE `userId` =:userId');
            $statement->execute(array('userId' => $chat_id, 'lastTime' => $dateNow));
            return false;
        }
        else{
            return true;
        }
    }
}


//функция, которая отправляет фото | function that send photo
function sendPhoto($path, $client, $chat_id, $keyboard_buttons){
    $photo = [
        '@type' => 'inputFileLocal',
        'path' => $path
    ];
    $reply_markup = [
        '@type'=>'replyMarkupShowKeyboard',
        'rows'=>$keyboard_buttons,
        'resize_keyboard'=>true,
        'one_time'=>false,
        'is_personal'=>true];
    $input_message = [
        '@type' => 'inputMessagePhoto',
        'photo'=>$photo,
        'thumbnail' => null,
        'added_sticker_file_ids' => null,
        'width' => getimagesize($path)[0],
        'height' => getimagesize($path)[1],
        'caption' => null,
        'ttl' => 0];
    $query = json_encode([
        '@type' => 'sendMessage',
        'chat_id' => $chat_id,
        'reply_to_message_id' => '0',
        'disable_notification' => false,
        'from_background' => false,
        'reply_markup' => $reply_markup,
        'input_message_content' => $input_message]);
    td_json_client_send($client, $query);
}


//функция, которая отправляет счёт | function that send invoice
function sendInvoice($client, $chat_id, $text){
    $price_parts = [['label' => 'руб', 'amount' => ((int)$text) * 100]];
    $invoice = [
        'currency' => 'RUB',
        'price_parts' => $price_parts,
        'is_test' => true,
        'need_name' => false,
        'need_phone_number' => false,
        'need_email_address' => false,
        'need_shipping_address' => false,
        'send_phone_number_to_provider' => false,
        'send_email_address_to_provider' => false,
        'is_flexible' => false];
    $input_message = [
        '@type' => 'inputMessageInvoice',
        'invoice' => $invoice,
        'title' => 'Пополнение баланса',
        'description' => 'Нажми на кнопку ниже, чтобы перейти к оплате ⬇️',
        'payload' => base64_encode('12'),
        'provider_token' => '401643678:TEST:650d6b16-cc82-470b-b66b-88ea3bc42acd',
        'start_parameter' => 'start'
    ];
    $query = json_encode([
        '@type' => 'sendMessage',
        'chat_id' => $chat_id,
        'input_message_content' => $input_message]);
    td_json_client_send($client, $query);
}