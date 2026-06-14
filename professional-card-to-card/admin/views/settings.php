<?php
defined('ABSPATH') || exit;

// ۱. ذخیره اطلاعات تنظیمات در صورت ارسال پست
if (isset($_POST['p2p_save_settings_data'])) {
    if (!isset($_POST['p2p_settings_nonce']) || !wp_verify_nonce($_POST['p2p_settings_nonce'], 'p2p_save_settings_action')) {
        wp_die('ممیزی امنیتی نانس در فرآیند تنظیمات.');
    }

    $updated_settings = array(
        'enable_receipt'             => isset($_POST['enable_receipt']) ? 'yes' : 'no',
        'require_last4'              => sanitize_text_field($_POST['require_last4']),
        'enable_sms'                 => isset($_POST['enable_sms']) ? 'yes' : 'no',
        'sms_api_key'                => sanitize_text_field($_POST['sms_api_key']),
        'sms_sender'                 => sanitize_text_field($_POST['sms_sender']),
        'sms_admin_mobile'           => sanitize_text_field($_POST['sms_admin_mobile']),
        'enable_telegram'            => isset($_POST['enable_telegram']) ? 'yes' : 'no',
        'telegram_bot_token'         => sanitize_text_field($_POST['telegram_bot_token']),
        'telegram_chat_id'           => sanitize_text_field($_POST['telegram_chat_id']),
        'recaptcha_site_key'         => sanitize_text_field($_POST['recaptcha_site_key']),
        'recaptcha_secret_key'       => sanitize_text_field($_POST['recaptcha_secret_key']),
        'theme'                      => sanitize_text_field($_POST['theme']),
        'email_template'             => sanitize_textarea_field($_POST['email_template']),
        'delete_tables_on_uninstall' => isset($_POST['delete_tables_on_uninstall']) ? 'yes' : 'no'
    );

    update_option('p2p_settings', $updated_settings);
    Logger::log('تنظیمات عمومی درگاه کارت به کارت بروزرسانی شد.', 'SETTINGS_UPDATE');

    echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شدند.</p></div>';
}

$settings = get_option('p2p_settings');

// پیش‌فرض‌ها در صورت موجود نبودن مقادیر تک‌برگ
$enable_receipt     = isset($settings['enable_receipt']) ? $settings['enable_receipt'] : 'yes';
$require_last4      = isset($settings['require_last4']) ? $settings['require_last4'] : 'required';
$enable_sms         = isset($settings['enable_sms']) ? $settings['enable_sms'] : 'no';
$sms_api_key        = isset($settings['sms_api_key']) ? $settings['sms_api_key'] : '';
$sms_sender         = isset($settings['sms_sender']) ? $settings['sms_sender'] : '';
$sms_admin_mobile   = isset($settings['sms_admin_mobile']) ? $settings['sms_admin_mobile'] : '';
$enable_telegram    = isset($settings['enable_telegram']) ? $settings['enable_telegram'] : 'no';
$telegram_bot_token = isset($settings['telegram_bot_token']) ? $settings['telegram_bot_token'] : '';
$telegram_chat_id   = isset($settings['telegram_chat_id']) ? $settings['telegram_chat_id'] : '';
$recaptcha_site_key   = isset($settings['recaptcha_site_key']) ? $settings['recaptcha_site_key'] : '';
$recaptcha_secret_key = isset($settings['recaptcha_secret_key']) ? $settings['recaptcha_secret_key'] : '';
$theme                      = isset($settings['theme']) ? $settings['theme'] : 'glassmorphism';
$email_template             = isset($settings['email_template']) ? $settings['email_template'] : '';
$delete_tables_on_uninstall = isset($settings['delete_tables_on_uninstall']) ? $settings['delete_tables_on_uninstall'] : 'no';
?>

<div class="wrap" style="direction: rtl;">
    <h1 style="font-family: inherit; margin-bottom: 20px;"><?php _e('تنظیمات درگاه کارت به کارت حرفه‌ای', 'professional-card-to-card'); ?></h1>
    
    <form method="post" action="">
        <input type="hidden" name="p2p_settings_nonce" value="<?php echo wp_create_nonce('p2p_save_settings_action'); ?>" />
        
        <div style="background: #fff; padding: 25px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 900px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            
            <!-- بخش ۱: فیلدهای فلو و نمایش -->
            <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:0; color: #2c3e50;"><span class="dashicons dashicons-visibility" style="margin-top: 2px;"></span> <?php _e('فرم‌های فرانت‌اند و فیلدهای پرداخت', 'professional-card-to-card'); ?></h3>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label><strong><?php _e('قابلیت آپلود فیش رسید', 'professional-card-to-card'); ?></strong></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_receipt" value="1" <?php checked($enable_receipt, 'yes'); ?> />
                                <?php _e('کاربران بتوانند تصویر فیش واریز را پس از کارت به کارت بصورت اختیاری آپلود کنند (حداکثر حجم ۲ مگابایت)', 'professional-card-to-card'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="require_last4"><strong><?php _e('الزام وارد کردن ۴ رقم آخر کارت فرستنده', 'professional-card-to-card'); ?></strong></label></th>
                        <td>
                            <select name="require_last4" id="require_last4">
                                <option value="required" <?php selected($require_last4, 'required'); ?>><?php _e('فعال باشد و تکمیل آن اجباری است  (توصیه شده)', 'professional-card-to-card'); ?></option>
                                <option value="optional" <?php selected($require_last4, 'optional'); ?>><?php _e('فعال باشد ولی تکمیل آن اختیاری است', 'professional-card-to-card'); ?></option>
                                <option value="none" <?php selected($require_last4, 'none'); ?>><?php _e('کاملاً غیرفعال و مخفی باشد', 'professional-card-to-card'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="theme"><strong><?php _e('پوسته و تم بصری فرانت‌اند (شورتکد)', 'professional-card-to-card'); ?></strong></label></th>
                        <td>
                            <select name="theme" id="theme">
                                <option value="light" <?php selected($theme, 'light'); ?>><?php _e('پوسته روشن کلاسیک', 'professional-card-to-card'); ?></option>
                                <option value="dark" <?php selected($theme, 'dark'); ?>><?php _e('پوسته تاریک مدرن', 'professional-card-to-card'); ?></option>
                                <option value="glassmorphism" <?php selected($theme, 'glassmorphism'); ?>><?php _e('تم فوق‌العاده شیک شیشه‌ای (Glassmorphism)', 'professional-card-to-card'); ?></option>
                            </select>
                            <p class="description"><?php _e('هنگام استفاده از شورتکد [card_to_card_form] فرم پرداخت با فرمت پوسته انتخاب شده رندر خواهد شد.', 'professional-card-to-card'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- بخش ۲: گوگل ری‌کپچا -->
            <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:30px; color: #2c3e50;"><span class="dashicons dashicons-shield-alt" style="margin-top: 2px;"></span> <?php _e('امنیت با گوگل reCAPTCHA نسخه 2', 'professional-card-to-card'); ?></h3>
            <p class="description" style="color: gray; margin-bottom: 15px;"><?php _e('جهت ممانعت از ثبت فیش‌های فیک اسپم توسط ربات‌ها، کلیدهای کپچا گوگل را تنظیم فرمایید. در صورت خالی ماندن فیلدها کپچا غیرفعال خواهد بود.', 'professional-card-to-card'); ?></p>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="recaptcha_site_key"><?php _e('کلید سایت (Site Key)', 'professional-card-to-card'); ?></label></th>
                        <td>
                            <input type="text" name="recaptcha_site_key" id="recaptcha_site_key" value="<?php echo esc_attr($recaptcha_site_key); ?>" class="regular-text" style="direction:ltr; text-align:left;" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="recaptcha_secret_key"><?php _e('کلید سری (Secret Key)', 'professional-card-to-card'); ?></label></th>
                        <td>
                            <input type="text" name="recaptcha_secret_key" id="recaptcha_secret_key" value="<?php echo esc_attr($recaptcha_secret_key); ?>" class="regular-text" style="direction:ltr; text-align:left;" />
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- بخش ۳: هماهنگی ایمیل تایید فرم -->
            <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:30px; color: #2c3e50;"><span class="dashicons dashicons-email-alt" style="margin-top: 2px;"></span> <?php _e('الگوی ایمیل اطلاع‌رسانی مدیریت', 'professional-card-to-card'); ?></h3>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="email_template"><?php _e('شرح پیام ایمیل مدیریت', 'professional-card-to-card'); ?></label></th>
                        <td>
                            <textarea name="email_template" id="email_template" rows="6" class="large-text" style="width: 100%;"><?php echo esc_textarea($email_template); ?></textarea>
                            <p class="description">
                                <?php _e('می‌توانید از شورتکدهای زیر در الگوی خود استفاده نمائید:', 'professional-card-to-card'); ?><br>
                                <code>{full_name}</code> : نام خریدار | <code>{mobile}</code> : موبایل | <code>{amount}</code> : مبلغ | <code>{order_id}</code> : سفارش | <code>{transaction_id}</code> : پرداخت
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- بخش ۴: اعلان پیامک و تلگرام -->
            <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:30px; color: #2c3e50;"><span class="dashicons dashicons-megaphone" style="margin-top: 2px;"></span> <?php _e('اعلان‌های جانبی (پیامک ایرانی و تلگرام)', 'professional-card-to-card'); ?></h3>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <!-- پیامک -->
                    <tr>
                        <th scope="row"><strong><?php _e('سرویس پیامکی فعال باشد', 'professional-card-to-card'); ?></strong></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_sms" value="1" <?php checked($enable_sms, 'yes'); ?> />
                                <?php _e('فعالسازی اعلان پیامکی تراکنش به مدیر و خریدار (وب‌سرویس کاوه‌نگار)', 'professional-card-to-card'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sms_api_key"><?php _e('کلید کدر رمز پیامکی (API Key)', 'professional-card-to-card'); ?></label></th>
                        <td>
                            <input type="text" name="sms_api_key" id="sms_api_key" value="<?php echo esc_attr($sms_api_key); ?>" class="regular-text" style="direction:ltr; text-align:left;" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sms_sender"><?php _e('شماره خط اختصاصی پیامک فرستنده', 'professional-card-to-card'); ?></label></th>
                        <td>
                            <input type="text" name="sms_sender" id="sms_sender" value="<?php echo esc_attr($sms_sender); ?>" placeholder="مانند: 10006363" class="regular-text" style="direction:ltr; text-align:left;" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sms_admin_mobile"><?php _e('شماره خط موبایل همراه ناظر ادمین', 'professional-card-to-card'); ?></label></th>
                        <td>
                            <input type="text" name="sms_admin_mobile" id="sms_admin_mobile" value="<?php echo esc_attr($sms_admin_mobile); ?>" placeholder="مانند: 09120000000" class="regular-text" style="direction:ltr; text-align:left;" />
                        </td>
                    </tr>

                    <!-- تلگرام -->
                    <tr style="border-top: 1px dashed #eee;">
                        <th scope="row"><strong><?php _e('سرویس تلگرام فعال باشد', 'professional-card-to-card'); ?></strong></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_telegram" value="1" <?php checked($enable_telegram, 'yes'); ?> />
                                <?php _e('ارسال اتوماتیک جزییات برای ربات تلگرامی ادمین', 'professional-card-to-card'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="telegram_bot_token"><?php _e('توکن ربات تلگرام (Bot Token)', 'professional-card-to-card'); ?></label></th>
                        <td>
                            <input type="text" name="telegram_bot_token" id="telegram_bot_token" value="<?php echo esc_attr($telegram_bot_token); ?>" class="large-text" style="direction:ltr; text-align:left;" placeholder="مانند: 123456:ABC-DEF1234ghIkl-zyx" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="telegram_chat_id"><?php _e('شناسه چت یا کانال ادمین (Chat ID)', 'professional-card-to-card'); ?></label></th>
                        <td>
                            <input type="text" name="telegram_chat_id" id="telegram_chat_id" value="<?php echo esc_attr($telegram_chat_id); ?>" class="regular-text" style="direction:ltr; text-align:left;" placeholder="مانند: 9876543210" />
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- بخش ۵: برچسب و دیتابیس پاکسازی حین حذف -->
            <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top:30px; color: #b7791f;"><span class="dashicons dashicons-trash" style="margin-top: 2px;"></span> <?php _e('حریم خصوصی و حذف اطلاعات', 'professional-card-to-card'); ?></h3>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><strong><?php _e('پاکسازی کامل داده‌ها با حذف افزونه', 'professional-card-to-card'); ?></strong></th>
                        <td>
                            <label>
                                <input type="checkbox" name="delete_tables_on_uninstall" value="1" <?php checked($delete_tables_on_uninstall, 'yes'); ?> />
                                <span style="color:#c53030; font-weight:bold;"><?php _e('بله، با پاک شدن کامل افزونه تمامی جداول تراکنش‌ها و پوشه فیزیکی رسیدها بطور برگشت‌ناپذیر پاکسازی گردند.', 'professional-card-to-card'); ?></span>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 25px; border-top: 2px solid #edf2f7; padding-top:15px; text-align: left;">
                <button type="submit" name="p2p_save_settings_data" class="button button-primary button-large" style="padding:4px 30px; font-weight:bold; font-size:1.1em;"><?php _e('ذخیره کلی تغییرات تنظیمات', 'professional-card-to-card'); ?></button>
            </div>
        </div>
    </form>
</div>
