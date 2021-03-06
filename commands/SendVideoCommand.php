<?php

/**
 * Created by PhpStorm.
 * User: Mohamad Amin
 * Date: 3/26/2016
 * Time: 3:22 PM
 */

namespace Longman\TelegramBot\Commands\UserCommands {

    use Longman\TelegramBot\Commands\UserCommand;
    use Longman\TelegramBot\Conversation;
    use Longman\TelegramBot\Entities\ReplyKeyboardHide;
    use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
    use Longman\TelegramBot\Request;
    use Longman\TelegramBot\Telegram;

    class SendVideoCommand extends UserCommand {

        protected $name = 'sendvideo';                      //your command's name
        protected $description = 'ارسال فیلم';          //Your command description
        protected $usage = '/sendvideo';                    // Usage of your command
        protected $version = '1.0.0';
        protected $enabled = true;
        protected $public = true;
        protected $message;

        protected $conversation;
        protected $telegram;

        public function __construct(Telegram $telegram, $update) {
            parent::__construct($telegram, $update);
            $this->telegram = $telegram;
        }

        public function execute() {

            $databaser = new \VideoDatabaser();
            $message = $this->getMessage();              // get Message info

            $chat = $message->getChat();
            $user = $message->getFrom();
            $chat_id = $chat->getId();
            $user_id = $user->getId();
            $text = $message->getText(true);
            $message_id = $message->getMessageId();      //Get message Id

            $data = [];
            $data['chat_id'] = $chat_id;
            $channels = \AdminDatabase::getHelpersChannels($user->getUsername());
            if ($text == 'فیلم و متن') {
                $text = '';
            }

            $this->conversation = new Conversation($user_id, $chat_id, $this->getName());
            if (!isset($this->conversation->notes['state'])) {
                $state = '0';
            } else {
                $state = $this->conversation->notes['state'];
            }

            if ($text == 'بازگشت ⬅️') {
                --$state;
                $this->conversation->notes['state'] = $state;
                $this->conversation->update();
                $text = '';
            }

            switch ($state) {
                case 0:
                    if (empty($text) || !in_array($text, $channels)) {
                        if (!empty($text) && !in_array($text, $channels)) {
                            $data = [];
                            $data['chat_id'] = $chat_id;
                            $data['text'] = 'متاسفیم. به نظر نمیاید که شما ادمین این کانال باشید :(';
                            $data['reply_markup'] = new ReplyKeyboardHide(['selective' => true]);
                            $result = Request::sendMessage($data);
                            $this->conversation->stop();
                            $this->telegram->executeCommand("start");
                            break;
                        } else {
                            $data['text'] = 'کانال را انتخاب کنید:';
                            $keyboard = [];
                            $i = 0;
                            foreach ($channels as $key) {
                                $j = (int) floor($i/2);
                                $keyboard[$j][$i % 2] = $key;
                                $i++;
                            }
                            $keyboard[] = ['❌ بی‌خیال'];
                            $data['reply_markup'] = new ReplyKeyboardMarkup(
                                [
                                    'keyboard' => $keyboard,
                                    'resize_keyboard' => true,
                                    'one_time_keyboard' => true,
                                    'selective' => true
                                ]
                            );
                            $result = Request::sendMessage($data);
                            break;
                        }
                    }
                    $this->conversation->notes['channelName'] = $text;
                    $text = '';
                    $this->conversation->notes['state'] = ++$state;
                    $this->conversation->update();
                case 1:
                    if (empty($text)) {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'پیام خود را وارد کنید: (کمتر از ۱۵۰ کاراکتر)';
                        $keyboard = [
                            ['بدون متن'],
                            ['بازگشت ⬅️', '❌ بی‌خیال']
                        ];
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    if ($text == 'بدون متن') {
                        $this->conversation->notes['messageText'] = '';
                    } else {
                        $this->conversation->notes['messageText'] = $text;
                    }
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 2:
                    if ($message->getVideo() == null) {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'فیلم را بفرستید';
                        $keyboard = [['بازگشت ⬅️', '❌ بی‌خیال']];
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['video'] = $message->getVideo()->getFileId();
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 3:
                    if (empty($text) || !is_numeric($text)) {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'سال ارسال پیام خود را وارد کنید';
                        $keyboard = [
                            ['1395', '1396', '1397'],
                            ['بازگشت ⬅️', '❌ بی‌خیال']
                        ];
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['year'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 4:
                    if (empty($text) || !is_numeric($text) || intval($text)<1 || intval($text)>12) {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'ماه ارسال پیام را وارد کنید:';
                        $keyboard = [
                            ['1', '2', '3', '4'],
                            ['5', '6', '7', '8'],
                            ['9', '10', '11', '12'],
                            ['بازگشت ⬅️', '❌ بی‌خیال']
                        ];
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['month'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 5:
                    if (empty($text) || !is_numeric($text) || intval($text)<1 || intval($text)>31) {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'روز ارسال پیام را وارد کنید:';
                        if ($this->conversation->notes['month'] < 7) {
                            $keyboard = [
                                ['1', '2', '3', '4', '5', '6', '7', '8'],
                                ['9', '10', '11', '12', '13', '14', '15', '16'],
                                ['17', '18', '19', '20', '21', '22', '23', '24'],
                                ['25', '26', '27', '28', '29', '30', '31', ' '],
                                ['بازگشت ⬅️', '❌ بی‌خیال']
                            ];
                        } else {
                            $keyboard = [
                                ['1', '2', '3', '4', '5', '6', '7', '8'],
                                ['9', '10', '11', '12', '13', '14', '15', '16'],
                                ['17', '18', '19', '20', '21', '22', '23', '24'],
                                ['25', '26', '27', '28', '29', '30', ' ', ' '],
                                ['بازگشت ⬅️', '❌ بی‌خیال']
                            ];
                        }
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['day'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 6:
                    if (empty($text) || !is_numeric($text) || intval($text)<0 || intval($text)>24) {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'ساعت (۲۴ ساعته) ارسال پیام را وارد کنید:';
                        $keyboard = [['بازگشت ⬅️', '❌ بی‌خیال']];
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['hour'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 7:
                    if (empty($text) || !is_numeric($text) || intval($text)<0 || intval($text)>59) {
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'دقیقه‌ی ارسال پیام را وارد کنید:';
                        $keyboard = [['بازگشت ⬅️', '❌ بی‌خیال']];
                        $data['reply_markup'] = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $this->conversation->notes['minute'] = $text;
                    $this->conversation->notes['state'] = ++$state;
                    $text = '';
                    $this->conversation->update();
                case 8:
                    if (empty($text) || !($text == '✔️ تایید و ارسال')) {

                        $time = $this->conversation->notes['year'].'-'.
                            $this->conversation->notes['month'].'-'.
                            $this->conversation->notes['day'].'-'.
                            $this->conversation->notes['hour'].'-'.
                            $this->conversation->notes['minute'];

                        $keyboard = [['✔️ تایید و ارسال'],['بازگشت ⬅️', '❌ بی‌خیال']];
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = 'پیش نمایش:';
                        Request::sendMessage($data);
                        $data['video'] = $this->conversation->notes['video'];
                        $data['caption'] = $this->conversation->notes['messageText'];
                        Request::sendVideo($data);
                        if (\PersianTimeGenerator::getTimeInMilliseconds($time) < round(microtime(true))) {
                            $data['text'] = 'هشدار! زمان انتخابی شما قبل از حال است! در این صورت پیام شما در لحظه فرستاده خواهد شد.';
                            Request::sendMessage($data);
                        }
                        $reply_keyboard_markup = new ReplyKeyboardMarkup(
                            [
                                'keyboard' => $keyboard,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true,
                                'selective' => true
                            ]
                        );
                        $data['reply_markup'] = $reply_keyboard_markup;
                        $data['text'] = 'برای ارسال پست بالا در تاریخ و زمان '.
                            \PersianDateFormatter::format($this->conversation->notes).' دکمه‌ی ارسال را کلیک کنید. ';
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $databaser->addMessageToDatabase(
                        $this->conversation->notes['messageText'] . "\n" . '@mohandes_plus',
                        $this->conversation->notes['video'],
                        '@' . $this->conversation->notes['channelName'],
                        $chat_id,
                        $this->conversation->notes['year'].'-'.
                        $this->conversation->notes['month'].'-'.
                        $this->conversation->notes['day'].'-'.
                        $this->conversation->notes['hour'].'-'.
                        $this->conversation->notes['minute']
                    );
                    $data = [];
                    $data['chat_id'] = $chat_id;
                    $data['text'] = "پیام شما ارسال خواهد شد :)";
                    $data['reply_markup'] = new ReplyKeyboardHide(['selective' => true]);
                    $result = Request::sendMessage($data);
                    $this->conversation->stop();
                    $this->telegram->executeCommand("start");
                    break;
            }

            return $result;

        }



    }
}

namespace {

    require __DIR__ . '/../vendor/autoload.php';

    class VideoDatabaser {

        public function addMessageToDatabase($messageText, $fileId, $channelName, $chatId, $time)
        {

            $times = explode("-", $time);
            $calendar = new jDateTime(true, true, 'Asia/Tehran');
            $timestamp = $calendar->mktime(
                intval($times[3]),
                intval($times[4]),
                0,
                intval($times[1]),
                intval($times[2]),
                intval($times[0])
            );

            /*$database = new medoo([
                'database_type' => 'mysql',
                'database_name' => 'mohandesplusbot',
                'server' => 'localhost',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4'
            ]);*/
            $database = new medoo([
                'database_type' => 'mysql',
                'database_name' => 'mohandesplusbot',
                'server' => 'localhost',
                'username' => 'root',
                'password' => 'MohandesPlus',
                'charset' => 'utf8mb4'
            ]);
            $database->insert("queue", [
                "Channel" => $channelName,
                "ChatId" => $chatId,
                "Type" => 3,
                "Video" => $fileId,
                "Text" => $messageText,
                "Time" => $timestamp
            ]);
        }

    }

}