<?php
namespace ProfessionalCardToCard;

defined('ABSPATH') || exit;

class Qr_Generator {

    /**
     * تولید لینک تصویر کد QR بر اساس شماره کارت و مبلغ
     * از وب‌سرویس عمومی گوگل چارت استفاده می‌کنیم که امن، پرسرعت و رایگان است.
     * 
     * @param string $card_number شماره کارت بانکی
     * @param string $bank_name نام بانک
     * @param string $holder_name نام صاحب حساب
     * @param float $amount مبلغ تراکنش اختیاری
     */
    public static function generate_bank_qr($card_number, $bank_name, $holder_name, $amount = 0) {
        // استاندارد متنی تراکنش شتابی ایران در کدهای QR
        // فرمت عموما شامل شماره کارت، نام صاحب کارت و در صورت تمایل مبلغ است.
        $text = "کارت به کارت\n";
        $text .= "بانک: " . $bank_name . "\n";
        $text .= "شماره کارت: " . $card_number . "\n";
        $text .= "صاحب حساب: " . $holder_name;
        
        if ($amount > 0) {
            $text .= "\nمبلغ: " . number_format($amount) . " ریال";
        }

        // تبدیل متن به فرمت URL ایمن
        $encoded_text = urlencode($text);

        // استفاده از Google Charts API
        return "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl={$encoded_text}&choe=UTF-8";
    }
}
