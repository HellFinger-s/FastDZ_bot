<?php
$botToken = '1496175647:AAG2syVnKrOSTW57Y-WkkypAo1d-hAeZRD8';
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
$pathToIsbns = '/home/hellfigers/PhpstormProjects/cdz/ISBNS/';//директория, где хранятся фотографии с ответами | directory where stores photos with answers
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);
$id = false;
$client = td_json_client_create();
td_json_client_send($client,json_encode(['@type' => 'setLogVerbosityLevel', 'new_verbosity_level' => '3']));
$result = td_json_client_execute($client, json_encode(['@type' => 'getAuthorizationState']));

while (true) {//основной цикл | main loop
    $res = td_json_client_receive($client, 10);
    $update = json_decode($res, true);
    switch ($update['@type']):
        case 'updateAuthorizationState'://обработка авторизации | authorization processing
            switch ($update['authorization_state']['@type']):
                case 'authorizationStateWaitTdlibParameters':
                    $query = json_encode(['@type' => 'setTdlibParameters', 'parameters' => $td_parameters]);
                    td_json_client_send($client,$query);
                    break;
                case 'authorizationStateWaitEncryptionKey':
                    $query = json_encode(['@type' => 'checkDatabaseEncryptionKey', 'encryption_key' => '']);
                    td_json_client_send($client,$query);
                    break;
                case 'authorizationStateWaitPhoneNumber':
                    $query = json_encode(['@type' => 'checkAuthenticationBotToken', 'token' => $botToken]);
                    td_json_client_send($client,$query);
                    break;
                case 'authorizationStateReady':
                    print('AUTHORIZATION IS COMPLETE').PHP_EOL;;
                    break;
            endswitch;
            break;

        case 'updateNewMessage';//обработка нового сообщения | handle a new message
            switch($update['message']['content']['@type']){
                case 'messageText':
                    if($update['message']['is_outgoing'] == false) {
                        $chatId = $update['message']['chat_id'];
                        $text = $update['message']['content']['text']['text'];
                        $messageId = $update['message']['id'];
                        switch ($text) {//обработка текста сообщения | handle message text
                            case '/start':
                                $data = $pdo->query('SELECT `userId` FROM `userInfo`')->fetchAll(PDO::FETCH_COLUMN);
                                if (in_array($chatId, $data) == false) {//обработка нового пользователя | handle new user
                                    $statement = $pdo->prepare('INSERT INTO `userInfo` SET `userId` = :userId, `userBalance` = :userBalance, `freeDays` = :freeDays, `waitingSum` = :waitingSum');
                                    $statement->execute(array('userId' => $chatId, 'userBalance' => 0, 'freeDays' => 3, 'waitingSum' => 0));
                                }
                                $freeDays = getLineWithCertainUIDFromBD($chatId, $pdo)[0]['freeDays'];
                                switch($freeDays){//обработка разного количества оставшихся бесплатных дней | handle different number of remaining free days
                                    case 1:
                                        sendTextMessage('Привет, отправь мне сообщение в формате XX/YY, где XX - ISBN книги, а YY - номер страницы или задания. ISBN ты найдешь на одной из первых страниц учебника, пример приведен ниже. Если тебе нужна помощь, напиши мне помощь. Сейчас у тебя есть '.$freeDays.' бесплатный день. Когда они закончатся, 24 часа пользования ботом будут стоить 1 рубль. Пополнить баланс ты можешь, написав мне пополнить. С помощью кнопок ⬅ и ➡ ты можешь получить соседние страницы/номера',
                                            $client, $chatId);
                                        break;
                                    case 0:
                                        sendTextMessage('Привет, отправь мне сообщение в формате XX/YY, где XX - ISBN книги, а YY - номер страницы или задания. ISBN ты найдешь на одной из первых страниц учебника, пример приведен ниже. Если тебе нужна помощь, напиши мне помощь. Сейчас у тебя есть '.$freeDays.' бесплатных дней. Когда они закончатся, 24 часа пользования ботом будут стоить 1 рубль. Пополнить баланс ты можешь, написав мне пополнить. С помощью кнопок ⬅ и ➡ ты можешь получить соседние страницы/номера',
                                            $client, $chatId);
                                        break;
                                    default:
                                        sendTextMessage('Привет, отправь мне сообщение в формате XX/YY, где XX - ISBN книги, а YY - номер страницы или задания. ISBN ты найдешь на одной из первых страниц учебника, пример приведен ниже. Если тебе нужна помощь, напиши мне помощь. Сейчас у тебя есть '.$freeDays.' бесплатных дня.  Когда они закончатся, 24 часа пользования ботом будут стоить 1 рубль. Пополнить баланс ты можешь, написав мне пополнить. С помощью кнопок ⬅ и ➡ ты можешь получить соседние страницы/номера',
                                            $client, $chatId);
                                }
                                sendPhoto('/home/hellfigers/PhpstormProjects/cdz/ex.jpeg', $client, $chatId, 'Для этой книги нужно отправить мне сообщение формата 5-358-00257-X/YY, где YY - номер страницы или задания');
                                sendPhoto('/home/hellfigers/PhpstormProjects/cdz/button.jpeg', $client, $chatId, 'Так же управлять мной ты можешь с помощью интерактивной панели с кнопками, которую ты откроешь, нажав обведенную на фотографии кнопку в телеграм');
                                break;
                            case 'помощь':// user need help
                                sendTextMessage('отправь мне сообщение в формате XX/YY, где XX - ISBN книги, а YY - номер страницы или задания. ISBN ты найдешь на одной из первых страниц учебника, пример приведен на фотографии ниже. С помощью кнопок ⬅ и ➡ ты можешь получить соседние страницы/номера',
                                    $client, $chatId);
                                sendPhoto('/home/hellfigers/PhpstormProjects/cdz/ex.jpeg', $client, $chatId);
                                break;
                            case 'баланс':// user want to know his balance
                                $answerFromBd = getLineWithCertainUIDFromBD($chatId, $pdo);
                                $balance = $answerFromBd[0]['userBalance'];
                                sendTextMessage('Ваш текущий баланс: ' . $balance . ' рублей.', $client, $chatId);
                                break;
                            case 'пополнить':// user want to pay
                                sendTextMessage('Введите целое, больше 59 число, на которое будет пополнен баланс. Напишите выход, чтобы выйти из оплаты', $client, $chatId);
                                $statement = $pdo->prepare('UPDATE `userInfo` SET `waitingSum` = :waitingSum WHERE `userId` =:userId');
                                $statement->execute(array('userId' => $chatId, 'waitingSum' => 1));
                                break;
                            case 'выход':// exit from payment
                                sendTextMessage('Вы вышли из оплаты', $client, $chatId);
                                $statement = $pdo->prepare('UPDATE `userInfo` SET `waitingSum` = :waitingSum WHERE `userId` =:userId');
                                $statement->execute(array('userId' => $chatId, 'waitingSum' => 0));
                                break;
                            case '⬅'://предыдущая фотография | previous photo
                                $answerFromBd = getLineWithCertainUIDFromBD($chatId, $pdo);
                                $prevIsbn = $answerFromBd[0]['lastISBN'];
                                $newIsbn = substr($prevIsbn, 0, strpos($prevIsbn, '/')+1).(string)((int)(substr($prevIsbn, strpos($prevIsbn, '/')+1))-1);
                                if(file_exists($pathToIsbns.$newIsbn.'.jpeg')) {
                                    if(checkTime($pdo, $chatId, $client) != "empty balance") {
                                        sendPhoto($pathToIsbns.$newIsbn.'.jpeg', $client, $chatId);
                                        updatePhotoInfo($pdo, $chatId, $newIsbn);
                                    }
                                } else {
                                    sendTextMessage('Извините, я не нашел такого фото.', $client, $chatId);
                                }
                                break;
                            case '➡'://следующая фотография | next photo
                                $answerFromBd = getLineWithCertainUIDFromBD($chatId, $pdo);
                                $prevIsbn = $answerFromBd[0]['lastISBN'];
                                $newIsbn = substr($prevIsbn, 0, strpos($prevIsbn, '/')+1).(string)((int)(substr($prevIsbn, strpos($prevIsbn, '/')+1))+1);
                                if(file_exists($pathToIsbns.$newIsbn.'.jpeg')) {
                                    if(checkTime($pdo, $chatId, $client) != "empty balance") {
                                        sendPhoto($pathToIsbns.$newIsbn.'.jpeg', $client, $chatId);
                                        updatePhotoInfo($pdo, $chatId, $newIsbn);
                                    }
                                } else {
                                    sendTextMessage('Извините, я не нашел такого фото.', $client, $chatId);
                                }
                                break;
                            default:
                                $answerFromBd = getLineWithCertainUIDFromBD($chatId, $pdo);
                                $balance = $answerFromBd[0]['userBalance'];
                                $freeDays = $answerFromBd[0]['freeDays'];
                                $waitingSum = $answerFromBd[0]['waitingSum'];
                                if ($waitingSum == 1) {//бот ждет суммы пополнения | bot is waiting sum of pay
                                    if (stringIsPositiveInt($text)) {
                                        if ((int)$text <= 59) {
                                            sendTextMessage('Пожалуйста, введите число большее 59', $client, $chatId);
                                        } else {
                                            if ((int)$text > 10000) {
                                                sendTextMessage('Пожалуйста, введите число меньшее 10000', $client, $chatId);
                                            } else {
                                                $statement = $pdo->prepare('UPDATE `userInfo` SET `waitingSum` = :waitingSum WHERE `userId` =:userId');
                                                $statement->execute(array('userId' => $chatId, 'waitingSum' => 0));
                                                sendInvoice($client, $chatId, $text);
                                            }
                                        }
                                    } else {
                                        sendTextMessage('Введите целое положительное число, большее 59 или нажмите кнопку выход, чтобы прервать оплату', $client, $chatId);
                                    }
                                } else {//пользователь прислал isbn | user sent isbn
                                    if(file_exists($pathToIsbns.$text.'.jpeg')){
                                        if(checkTime($pdo, $chatId, $client) != "empty balance") {
                                            sendPhoto($pathToIsbns.$text.'.jpeg', $client, $chatId);
                                            updatePhotoInfo($pdo, $chatId, $text);
                                        }
                                    } else {
                                        sendTextMessage('Извините, я не нашел такого фото. Если вы вводили вторым параметром номер страницы, попробуйте ввести номер задания и наоборот.', $client, $chatId);
                                    }
                                }
                                break;
                        }
                    }
                    break;
                case 'messagePaymentSuccessfulBot'://обработка успешного платежа | successful payment handle
                    $sum = $update['message']['content']['total_amount']/100;
                    $preBalance = getLineWithCertainUIDFromBD($chatId, $pdo)[0]['userBalance'];
                    $nowBalance = $preBalance + $sum;
                    $statement = $pdo->prepare('UPDATE `userInfo` SET `userBalance` = :userBalance WHERE `userId` =:userId');
                    $statement->execute(array('userId' => $chatId, 'userBalance' => $nowBalance));
                    sendTextMessage('Вы успешно пополнили баланс на '.$sum.' рублей. Теперь он составляет '.$nowBalance.' рублей', $client, $chatId);
                    $statement = $pdo->prepare('UPDATE `userInfo` SET `waitingSum` = :waitingSum WHERE `userId` =:userId');
                    $statement->execute(array('userId' => $chatId, 'waitingSum' => 0));
                    break;
            }
            break;
        case 'updateNewPreCheckoutQuery'://обработка запроса перед оформлением платежа | handle pre checkout query
            $queryId = $update['id'];
            $query = json_encode([
                '@type' => 'answerPreCheckoutQuery',
                'pre_checkout_query_id' => $queryId,
                'error_message' => '']);
            td_json_client_send($client, $query);
            break;
    endswitch;
}


//функция получения строки из базы данных по определенному user id | function for getting a string from the database by a certain user id
function getLineWithCertainUIDFromBD($chatId, $pdo){
    $statement = $pdo->prepare('SELECT * FROM `userInfo` WHERE `userId` = :userId');
    $statement->execute(array('userId' => $chatId));
    $answerFromBd = $statement->fetchAll();
    return $answerFromBd;
}

//функция для отправки текстового сообщения | function for sending text message
function sendTextMessage($messageText, $client, $chatId){
    $keyboardType = [
      '@type' => 'keyboardButtonTypeText'
    ];
    $keyboardButtons = [
        [
            ['@type' => 'keyboardButton',
             'text' => 'помощь',
             'type' => $keyboardType
            ],
            ['@type' => 'keyboardButton',
                'text' => 'баланс',
                'type' => $keyboardType
            ]
        ],
        [
            ['@type' => 'keyboardButton',
                'text' => 'пополнить',
                'type' => $keyboardType
            ],
            ['@type' => 'keyboardButton',
                'text' => 'выход',
                'type' => $keyboardType
            ]
        ],
        [
            ['@type' => 'keyboardButton',
                'text' => '⬅',
                'type' => $keyboardType
            ],
            ['@type' => 'keyboardButton',
                'text' => '➡',
                'type' => $keyboardType
            ]
        ]
        ];
    $replyMarkup = [
        '@type'=>'replyMarkupShowKeyboard',
        'rows'=> $keyboardButtons,
        'resize_keyboard'=>true,
        'one_time'=>false,
        'is_personal'=>true];
    $formattedText = ['text' => $messageText, 'entities' => null];
    $inputMessage = [
        '@type' => 'inputMessageText',
        'text' => $formattedText,
        'disable_web_page_preview' => false,
        'clear_draft' => false];
    $query = json_encode([
        '@type' => 'sendMessage',
        'chat_id' => $chatId,
        'reply_to_message_id' => '0',
        'disable_notification' => false,
        'from_background' => false,
        'reply_markup' => $replyMarkup,
        'input_message_content' => $inputMessage]);
    td_json_client_send($client, $query);
}

//функция, которая проверяет является ли число положительным и целым | function that checks whether a number is positive and integer
function stringIsPositiveInt($num){
  $intNum = (int) $num;
  return ($intNum == $num && is_int($intNum) && $num > 0);
}


//функция, которая проверяет прошло ли 24 с момента предыдущего снятия денег с баланса | function that checks
// whether 24 hours have passed since the previous withdrawal of money from the balance
function checkTime($pdo, $chatId, $client){
    $answerFromBd = getLineWithCertainUIDFromBD($chatId, $pdo);
    $dateFromBD = $answerFromBd[0]['lastTime'];
    $dateFromBDInSec = strtotime($answerFromBd[0]['lastTime']);
    $dateNow = date("Y-m-d H:i:s");
    $dateNowInSec = strtotime(date("Y-m-d H:i:s"));
    $balance = $answerFromBd[0]['userBalance'];
    $freeDays = $answerFromBd[0]['freeDays'];
    if($dateFromBD == null){//пользователь ни разу не пользовался
        $freeDays -=1;
        $statement = $pdo->prepare('UPDATE `userInfo` SET `freeDays` = :freeDays WHERE `userId` =:userId');
        $statement->execute(array('userId' => $chatId, 'freeDays' => $freeDays));
        $statement = $pdo->prepare('UPDATE `userInfo` SET `lastTime` = :lastTime WHERE `userId` =:userId');
        $statement->execute(array('userId' => $chatId, 'lastTime' => $dateNow));
    } else {
        if($dateNowInSec - $dateFromBDInSec >= 100){//прошло 24 часа
            if($freeDays > 0){
                $freeDays -= 1;
                $statement = $pdo->prepare('UPDATE `userInfo` SET `freeDays` = :freeDays WHERE `userId` =:userId');
                $statement->execute(array('userId' => $chatId, 'freeDays' => $freeDays));
                sendTextMessage('С вашего аккаунта списан один рубль.', $client, $chatId,[]);
                $statement = $pdo->prepare('UPDATE `userInfo` SET `lastTime` = :lastTime WHERE `userId` =:userId');
                $statement->execute(array('userId' => $chatId, 'lastTime' => $dateNow));
            } else{
                if($balance > 0){
                    $balance -= 1;
                    $statement = $pdo->prepare('UPDATE `userInfo` SET `userBalance` = :userBalance WHERE `userId` =:userId');
                    $statement->execute(array('userId' => $chatId, 'userBalance' => $balance));
                    sendTextMessage('С вашего аккаунта списан один рубль.', $client, $chatId,[]);
                    $statement = $pdo->prepare('UPDATE `userInfo` SET `lastTime` = :lastTime WHERE `userId` =:userId');
                    $statement->execute(array('userId' => $chatId, 'lastTime' => $dateNow));
                } else{
                    sendTextMessage('Извините, на вашем балансе недостаточно суммы для выполнения операции.', $client, $chatId,[]);
                    return 'empty balance';
                }
            }
        }
    }
}


//функция, которая отправляет фото | function that send photo
function sendPhoto($path, $client, $chatId, $captionText = null){
    $photo = [
        '@type' => 'inputFileLocal',
        'path' => $path
    ];
    $caption = [
      'text' => $captionText,
      'entities' => null
    ];
    $inputMessage = [
        '@type' => 'inputMessagePhoto',
        'photo'=>$photo,
        'thumbnail' => null,
        'added_sticker_file_ids' => null,
        'width' => getimagesize($path)[0],
        'height' => getimagesize($path)[1],
        'caption' => $caption,
        'ttl' => 0];
    $query = json_encode([
        '@type' => 'sendMessage',
        'chat_id' => $chatId,
        'reply_to_message_id' => '0',
        'disable_notification' => false,
        'from_background' => false,
        'reply_markup' => null,
        'input_message_content' => $inputMessage]);
    td_json_client_send($client, $query);
}


//функция, которая отправляет счёт | function that send invoice
function sendInvoice($client, $chatId, $text){
    $priceParts = [['label' => 'руб', 'amount' => (int)$text*100]];
    var_dump("INVOICE");
    var_dump((int)$text * 100);
    $invoice = [
        'currency' => 'RUB',
        'price_parts' => $priceParts,
        'is_test' => true,
        'need_name' => false,
        'need_phone_number' => false,
        'need_email_address' => false,
        'need_shipping_address' => false,
        'send_phone_number_to_provider' => false,
        'send_email_address_to_provider' => false,
        'is_flexible' => false];
    $inputMessage = [
        '@type' => 'inputMessageInvoice',
        'invoice' => $invoice,
        'title' => 'Пополнение баланса',
        'description' => 'Нажми на кнопку ниже, чтобы перейти к оплате ⬇️',
        'payload' => base64_encode('12'),
        'provider_token' => '401643678:TEST:ee462abe-f47e-448a-94a5-ad5f04a7cd8e',
        'start_parameter' => 'start'
    ];
    $query = json_encode([
        '@type' => 'sendMessage',
        'chat_id' => $chatId,
        'input_message_content' => $inputMessage]);
    td_json_client_send($client, $query);
    var_dump($query);
    var_dump("INVOICE");
}

//обновление последнего фото | update last photo
function updatePhotoInfo($pdo, $chatId, $isbn){
    $statement = $pdo->prepare('UPDATE `userInfo` SET `lastISBN` = :lastISBN WHERE `userId` =:userId');
    $statement->execute(array('userId' => $chatId, 'lastISBN' => $isbn));
}