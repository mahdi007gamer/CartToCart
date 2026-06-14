<?php
namespace ProfessionalCardToCard;

defined('ABSPATH') || exit;

if (class_exists('WC_Payment_Gateway')) {

    class class_woocommerce_gateway extends \WC_Payment_Gateway {

        public function __construct() {
            $this->id                 = 'professional_card_to_card';
            $this->icon               = ''; // در صورت تمایل می‌توان تصویر آیکون درگاه را لود کرد
            $this->has_fields         = true;
            $this->method_title       = __('کارت به کارت حرفه‌ای (پرداخت دستی)', 'professional-card-to-card');
            $this->method_description = __('پرداخت مستقیم مبلغ سفارش از طریق انتقال کارت به کارت به حساب فروشگاه و ارسال فرست کار اطلاعات پرداخت.', 'professional-card-to-card');

            // لود فیلدهای تنظیمات درگاه در مدیریت ووکامرس
            $this->init_form_fields();
            $this->init_settings();

            // دریافت مقادیر تنظیمات ذخیره شده
            $this->title        = $this->get_option('title');
            $this->description  = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');

            // ذخیره تغییرات کنترلر تنظیمات
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // هوک برای استایل دهی یا افزودن کدهای JS در صفحه پرداخت
            add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_assets'));
        }

        /**
         * لود فرم تکی تنظیمات درگاه در ادمین ووکامرس
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __('فعالسازی/غیرفعالسازی', 'professional-card-to-card'),
                    'type'    => 'checkbox',
                    'label'   => __('فعالسازی درگاه پرداخت کارت به کارت', 'professional-card-to-card'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __('عنوان روش پرداخت', 'professional-card-to-card'),
                    'type'        => 'text',
                    'description' => __('عنوانی که خریدار در صفحه تسویه حساب مشاهده می‌کند.', 'professional-card-to-card'),
                    'default'     => __('کارت به کارت (بانک ملی و ملت)', 'professional-card-to-card'),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __('توضیحات درگاه فرانت‌اند', 'professional-card-to-card'),
                    'type'        => 'textarea',
                    'description' => __('دستورالعمل کلی که در زیر عنوان درگاه در صفحه پرداخت نمایش داده می‌شود.', 'professional-card-to-card'),
                    'default'     => __('لطفا مبلغ سفارش را به یکی از شماره کارت‌های فروشگاه واریز کرده و مشخصات پرداخت را در فرم زیر ثبت فرمایید.', 'professional-card-to-card'),
                ),
                'instructions' => array(
                    'title'       => __('پیام راهنمای پس از ثبت نهایی', 'professional-card-to-card'),
                    'type'        => 'textarea',
                    'description' => __('توضیحاتی که پس از ثبت نهایی سفارش در صفحه تشکر و ایمیل برای مشتری پیام داده می‌شود.', 'professional-card-to-card'),
                    'default'     => __('تراکنش کارت به کارت شما ثبت و در انتظار تایید مدیریت است. به محض بررسی، پردازش سفارش شما آغاز خواهد شد.', 'professional-card-to-card'),
                )
            );
        }

        /**
         * لود استایل و تم شیشه ای در سبد پرداخت
         */
        public function enqueue_checkout_assets() {
            if (is_checkout()) {
                wp_enqueue_style('p2p-checkout-style', P2P_URL . 'assets/css/p2p-frontend.css', array(), P2P_VERSION);
            }
        }

        /**
         * تمپلیت نمایش کارت‌ها و ورودی اطلاعات در صفحه تسویه حساب ووکامرس
         */
        public function payment_fields() {
            if (!empty($this->description)) {
                echo wpautop(wp_kses_post($this->description));
            }

            $active_cards = Database::get_active_cards();
            if (empty($active_cards)) {
                echo '<p class="p2p-error">' . esc_html__('هیچ کارت بانکی فعالی توسط مدیریت تعریف نشده است.', 'professional-card-to-card') . '</p>';
                return;
            }

            $settings = get_option('p2p_settings');
            $require_last4 = isset($settings['require_last4']) ? $settings['require_last4'] : 'required';
            $enable_receipt = isset($settings['enable_receipt']) ? $settings['enable_receipt'] : 'yes';

            // نمایش کارت‌های با تم شیشه‌ای فارسی
            ?>
            <div class="p2p-checkout-form p2p-glass" style="direction: rtl; text-align: right; padding: 15px; border-radius: 12px; margin-top: 15px; border: 1px solid rgba(255,255,255,0.25); background: rgba(255,255,255,0.7); backdrop-filter: blur(8px);">
                
                <h4 style="margin-bottom: 12px; font-weight: bold; color: #1e293b;"><?php _e('شماره کارت‌های مقصد جهت واریز وجه:', 'professional-card-to-card'); ?></h4>
                <div class="p2p-cards-grid" style="display: grid; grid-template-columns: 1fr; gap: 10px; margin-bottom: 15px;">
                    <?php foreach ($active_cards as $card): ?>
                        <div class="p2p-card-item" style="border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; background: rgba(248, 250, 252, 0.8);">
                            <strong><?php echo esc_html($card->bank_name); ?>:</strong> <?php echo esc_html($card->holder_name); ?><br>
                            <span class="p2p-card-num" style="font-family: monospace; font-size: 1.1em; letter-spacing: 1px; color: #0f172a;"><?php echo esc_html(implode(' - ', str_split($card->card_number, 4))); ?></span>
                            <?php if ($card->active): ?>
                                <div style="margin-top: 5px;">
                                    <img src="<?php echo Qr_Generator::generate_bank_qr($card->card_number, $card->bank_name, $card->holder_name); ?>" alt="QR Code" style="width: 80px; height: 80px; margin-top:5px; border-radius: 4px; border:1px solid #ddd;" />
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- فرم پر کردن اطلاعات تراکنش توسط مشتری -->
                <div class="p2p-input-fields" style="display: flex; flex-direction: column; gap: 12px;">
                    
                    <div>
                        <label style="display:block; font-size: 0.9em; margin-bottom: 4px;"><?php _e('نام کامل واریز کننده', 'professional-card-to-card'); ?> <span style="color:red;">*</span></label>
                        <input type="text" name="p2p_full_name" id="p2p_full_name" value="" required style="width:100%; padding: 8px; border: 1px solid #ccc; border-radius:6px;" />
                    </div>

                    <div>
                        <label style="display:block; font-size: 0.9em; margin-bottom: 4px;"><?php _e('شماره موبایل پیگیری', 'professional-card-to-card'); ?> <span style="color:red;">*</span></label>
                        <input type="tel" name="p2p_mobile" id="p2p_mobile" value="" required placeholder="مثال: 09121234567" style="width:100%; padding: 8px; border: 1px solid #ccc; border-radius:6px; direction:ltr; text-align:left;" />
                    </div>

                    <div>
                        <label style="display:block; font-size: 0.9em; margin-bottom: 4px;"><?php _e('واریز به کدام کارت بانکی ما؟', 'professional-card-to-card'); ?> <span style="color:red;">*</span></label>
                        <select name="p2p_bank_card_id" id="p2p_bank_card_id" required style="width:100%; padding: 8px; border: 1px solid #ccc; border-radius:6px;">
                            <option value=""><?php _e('-- انتخاب کارت بانکی مقصد --', 'professional-card-to-card'); ?></option>
                            <?php foreach ($active_cards as $card): ?>
                                <option value="<?php echo esc_attr($card->id); ?>"><?php echo esc_html($card->bank_name . ' - ' . $card->holder_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($require_last4 !== 'none'): ?>
                        <div>
                            <label style="display:block; font-size: 0.9em; margin-bottom: 4px;">
                                <?php _e('۴ رقم آخر کارت فرستنده', 'professional-card-to-card'); ?> 
                                <?php if ($require_last4 === 'required'): ?><span style="color:red;">*</span><?php endif; ?>
                            </label>
                            <input type="text" name="p2p_last4" id="p2p_last4" maxlength="4" <?php echo ($require_last4 === 'required' ? 'required' : ''); ?> placeholder="1234" style="width:100%; padding: 8px; border: 1px solid #ccc; border-radius:6px; direction:ltr; text-align:left;" />
                        </div>
                    <?php endif; ?>

                    <?php if ($enable_receipt === 'yes'): ?>
                        <div>
                            <label style="display:block; font-size: 0.9em; margin-bottom: 4px;"><?php _e('تصویر فیش یا رسید واریز (اختیاری)', 'professional-card-to-card'); ?></label>
                            <input type="file" name="p2p_receipt" id="p2p_receipt" accept="image/png, image/jpeg, image/jpg, image/webp" style="width:100%; padding: 4px;" />
                            <small class="text-muted" style="color:#718096; display:block; margin-top:2px;"><?php _e('تصویر JPG, PNG یا WebP تا حجم حداکثر ۲ مگابایت مجاز است.', 'professional-card-to-card'); ?></small>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" name="p2p_nonce" value="<?php echo wp_create_nonce('p2p_checkout_submission'); ?>" />
                </div>
            </div>
            <?php
        }

        /**
         * بررسی و اعتبارسنجی فیلدها حین پردازش سفارش در سبد خرید
         */
        public function validate_fields() {
            $full_name = !empty($_POST['p2p_full_name']) ? sanitize_text_field($_POST['p2p_full_name']) : '';
            $mobile = !empty($_POST['p2p_mobile']) ? sanitize_text_field($_POST['p2p_mobile']) : '';
            $card_id = !empty($_POST['p2p_bank_card_id']) ? sanitize_text_field($_POST['p2p_bank_card_id']) : '';
            
            $settings = get_option('p2p_settings');
            $require_last4 = isset($settings['require_last4']) ? $settings['require_last4'] : 'required';

            if (empty($full_name)) {
                wc_add_notice(__('نام کامل واریز کننده اجباری است.', 'professional-card-to-card'), 'error');
            }

            if (empty($mobile) || !Security::validate_mobile($mobile)) {
                wc_add_notice(__('شماره موبایل وارد شده معتبر نمی‌باشد.', 'professional-card-to-card'), 'error');
            }

            if (empty($card_id)) {
                wc_add_notice(__('انتخاب کارت بانکی مقصد اجباری است.', 'professional-card-to-card'), 'error');
            }

            if ($require_last4 === 'required') {
                $last4 = !empty($_POST['p2p_last4']) ? sanitize_text_field($_POST['p2p_last4']) : '';
                if (empty($last4) || !preg_match('/^[0-9]{4}$/', $last4)) {
                    wc_add_notice(__('لطفا ۴ رقم آخر کارت فرستنده خود را با دقت به صورت عددی وارد کنید.', 'professional-card-to-card'), 'error');
                }
            }
        }

        /**
         * پردازش درگاه کارت به کارت پس از زدن دکمه ثبت نهایی تسویه حساب
         */
        public function process_payment($order_id) {
            global $wpdb;
            $order = wc_get_order($order_id);

            // امنیت نانس
            if (empty($_POST['p2p_nonce']) || !wp_verify_nonce($_POST['p2p_nonce'], 'p2p_checkout_submission')) {
                wc_add_notice(__('خطای امنیتی منقضی شدن توکن نانس مجدد تلاش کنید.', 'professional-card-to-card'), 'error');
                return;
            }

            $full_name = sanitize_text_field($_POST['p2p_full_name']);
            $mobile    = Security::validate_mobile(sanitize_text_field($_POST['p2p_mobile']));
            $card_id   = intval($_POST['p2p_bank_card_id']);
            $last4     = !empty($_POST['p2p_last4']) ? sanitize_text_field($_POST['p2p_last4']) : '';
            
            // دانلود یا ذخیره رسید فیزیکی فیش بانکی از $_FILES
            $receipt_url = '';
            if (!empty($_FILES['p2p_receipt']['name'])) {
                $upload_result = Security::handle_receipt_upload($_FILES['p2p_receipt']);
                if (isset($upload_result['error'])) {
                    wc_add_notice($upload_result['error'], 'error');
                    return;
                }
                $receipt_url = $upload_result['url'];
            }

            // ذخیره اطلاعات تراکنش در دیتابیس لوکال سفارشی
            $table_transactions = $wpdb->prefix . 'c2c_transactions';
            $amount = $order->get_total();

            $insert_res = $wpdb->insert($table_transactions, array(
                'order_id'      => $order_id,
                'user_id'       => get_current_user_id(),
                'full_name'     => $full_name,
                'mobile'        => $mobile,
                'amount'        => $amount,
                'bank_card_id'  => $card_id,
                'last4digits'   => $last4,
                'receipt_url'   => $receipt_url,
                'status'        => 'pending',
                'admin_notes'   => '',
                'ip_address'    => sanitize_text_field($_SERVER['REMOTE_ADDR'])
            ));

            if ($insert_res === false) {
                wc_add_notice(__('متاسفانه خطایی در ثبت تراکنش رخ داد. لطفا با بخش پشتیبانی درگیر شوید.', 'professional-card-to-card'), 'error');
                return;
            }

            $transaction_id = $wpdb->insert_id;

            // اعلان دهی ها
            Notifications::send_admin_email($transaction_id);
            Notifications::send_telegram_notification($transaction_id);
            Notifications::send_sms_notification($transaction_id, 'admin');

            // ثبت یادداشت رزرو سفارش در ووکامرس
            $order->update_status('on-hold', sprintf(__('پرداخت دستی کارت به کارت ثبت شد. کد پیگیری تراکنش: %d.', 'professional-card-to-card'), $transaction_id));
            
            // کسر موجودی انبار بابت رزرو سفارش
            wc_reduce_stock_levels($order_id);

            // خالی کردن سبد خرید مشتری
            WC()->cart->empty_cart();

            // بازگرداندن لینک هدایت به صفحه دریافت تشکر ووکامرس (Thank you page)
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }
    }
}
