<?php
namespace ProfessionalCardToCard;

defined('ABSPATH') || exit;

class Notifications {

    /**
     * ارسال ایمیل به ادمین سایت جهت ثبت تراکنش جدید
     */
    public static function send_admin_email($transaction_id) {
        global $wpdb;
        $transaction = Database::get_transaction($transaction_id);
        if (!$transaction) {
            return false;
        }

        $settings = get_option('p2p_settings');
        $admin_email = get_option('admin_email');
        
        $template = isset($settings['email_template']) ? $settings['email_template'] : '';
        if (empty($template)) {
            $template = "یک پرداخت کارت به کارت جدید ثبت شده است.\nنام خریدار: {full_name}\nشماره موبایل: {mobile}\nمبلغ: {amount} تومان\nشماره سفارش: {order_id}";
        }

        // جایگزینی فیلدهای الگو
        $body = str_replace(
            array('{full_name}', '{mobile}', '{amount}', '{order_id}', '{transaction_id}'),
            array($transaction->full_name, $transaction->mobile, number_format($transaction->amount), $transaction->order_id, $transaction->id),
            $template
        );

        $subject = sprintf('پرداخت کارت به کارت جدید - سفارش #%s', $transaction->order_id ? $transaction->order_id : 'سایت');
        $headers = array('Content-Type: text/plain; charset=UTF-8');

        $mail_result = wp_mail($admin_email, $subject, $body, $headers);
        
        if ($mail_result) {
            Logger::log(sprintf('ایمیل اطلاع‌رسانی برای تراکنش شماره %d به آدرس %s با موفقیت ارسال شد.', $transaction->id, $admin_email), 'EMAIL_SENT');
        } else {
            Logger::log(sprintf('خطا در ارسال ایمیل اطلاع‌رسانی تراکنش شماره %d به آدرس %s.', $transaction->id, $admin_email), 'EMAIL_ERROR');
        }

        return $mail_result;
    }

    /**
     * ارسال پیام به بات تلگرام ادمین در صورت فعال بودن سابینت
     */
    public static function send_telegram_notification($transaction_id) {
        $settings = get_option('p2p_settings');
        if (empty($settings['enable_telegram']) || $settings['enable_telegram'] !== 'yes') {
            return false;
        }

        $bot_token = isset($settings['telegram_bot_token']) ? $settings['telegram_bot_token'] : '';
        $chat_id = isset($settings['telegram_chat_id']) ? $settings['telegram_chat_id'] : '';

        if (empty($bot_token) || empty($chat_id)) {
            Logger::log('ارسال پیام به تلگرام شکست خورد: توکن ربات یا چت‌آیدی وارد نشده است.', 'TELEGRAM_ERROR');
            return false;
        }

        $transaction = Database::get_transaction($transaction_id);
        if (!$transaction) return false;

        $message = "🔔 <b>پرداخت کارت به کارت جدید</b>\n\n";
        $message .= "👤 نام خریدار: " . esc_html($transaction->full_name) . "\n";
        $message .= "📱 موبایل: " . esc_html($transaction->mobile) . "\n";
        $message .= "💰 مبلغ: " . number_format($transaction->amount) . " تومان\n";
        $message .= "🔢 ۴ رقم آخر کارت: " . esc_html($transaction->last4digits) . "\n";
        if ($transaction->order_id) {
            $message .= "📦 شماره سفارش: #" . esc_html($transaction->order_id) . "\n";
        }
        $message .= "🕒 زمان ثبت: " . esc_html($transaction->created_at) . "\n";

        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        
        $response = wp_remote_post($url, array(
            'body' => array(
                'chat_id' => $chat_id,
                'text' => $message,
                'parse_mode' => 'HTML'
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            Logger::log('خطا در برقراری ارتباط با سرور بات تلگرام: ' . $response->get_error_message(), 'TELEGRAM_ERROR');
            return false;
        }

        Logger::log('اعلان تلگرامی تراکنش با موفقیت به تلگرام ادمین مخابره شد.', 'TELEGRAM_SENT');
        return true;
    }

    /**
     * ارسال پیامک به مدیر یا کاربر از طریق وب‌سرویس ملی/ایرانی آی‌پی‌پنل یا کاوه‌نگار
     */
    public static function send_sms_notification($transaction_id, $type = 'admin') {
        $settings = get_option('p2p_settings');
        if (empty($settings['enable_sms']) || $settings['enable_sms'] !== 'yes') {
            return false;
        }

        $api_key = isset($settings['sms_api_key']) ? $settings['sms_api_key'] : '';
        $sender_number = isset($settings['sms_sender']) ? $settings['sms_sender'] : '';
        $admin_mobile = isset($settings['sms_admin_mobile']) ? $settings['sms_admin_mobile'] : '';

        if (empty($api_key)) {
            Logger::log('خطا در ارسال پیامک: کلید وب‌سرویس پیامکی تعریف نشده است.', 'SMS_ERROR');
            return false;
        }

        $transaction = Database::get_transaction($transaction_id);
        if (!$transaction) return false;

        $recipient = ($type === 'admin') ? $admin_mobile : $transaction->mobile;
        if (empty($recipient)) return false;

        // آماده‌سازی متن پیامک
        if ($type === 'admin') {
            $sms_text = sprintf(
                "کارت به کارت جدید ثبت شد\nنام: %s\nمبلغ: %s تومان\nسفارش: %s\nجهت بررسی به پنل مراجعه کنید.",
                $transaction->full_name,
                number_format($transaction->amount),
                $transaction->order_id ? $transaction->order_id : '-'
            );
        } else {
            $sms_text = sprintf(
                "کاربر گرامی %s، پرداخت کارت به کارت شما به مبلغ %s تومان ثبت شد و در حال بررسی توسط مدیریت است.\nکد پیگیری: %d",
                $transaction->full_name,
                number_format($transaction->amount),
                $transaction->id
            );
        }

        // یکپارچه‌سازی وب‌سرویس ایرانی به عنوان دمو (مثال: کاوه‌نگار)
        $url = 'https://api.kavenegar.com/v1/' . $api_key . '/sms/send.json';
        
        $response = wp_remote_post($url, array(
            'body' => array(
                'receptor' => $recipient,
                'sender'   => $sender_number,
                'message'  => $sms_text
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            Logger::log('خطا در وب‌سرویس پیامک کاوه‌نگار: ' . $response->get_error_message(), 'SMS_ERROR');
            return false;
        }

        Logger::log(sprintf('پیامک اطلاع‌رسانی با موفقیت به شماره %s ارسال شد.', $recipient), 'SMS_SENT');
        return true;
    }
}
