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
            // نمایش کارت‌های با تم شیشه‌ای فارسی
            ?>
            <div class="p2p-checkout-form">
                <h4 style="margin-top: 0; margin-bottom: 8px; font-weight: 700; font-size: 1.1em; text-align: center; color: #ffffff;">
                    <?php _e('شماره کارت‌های مقصد جهت واریز وجه', 'professional-card-to-card'); ?>
                </h4>
                <p style="text-align: center; font-size: 0.85em; color: #94a3b8; margin-top: 0; margin-bottom: 20px; line-height: 1.55;">
                    <?php _e('لطفا مبلغ سفارش را به یکی از کارت‌های معرفی شده زیر انتقال داده، سپس فرم را تکمیل و ثبت نمایید.', 'professional-card-to-card'); ?>
                </p>

                <div class="p2p-checkout-cards-grid">
                    <?php 
                    $card_index = 0;
                    foreach ($active_cards as $card): 
                        $card_index++;
                        $btn_id = 'p2p_btn_copy_' . $card_index;
                        $drawer_id = 'p2p_qr_drawer_' . $card_index;
                        $formatted_num = implode(' - ', str_split($card->card_number, 4));
                        ?>
                        <div class="p2p-checkout-card">
                            <div class="p2p-checkout-card-bank">
                                <span><?php echo esc_html($card->bank_name); ?></span>
                                <span class="dashicons dashicons-bank" style="font-size: 18px; width: 18px; height: 18px;"></span>
                            </div>
                            <div class="p2p-checkout-card-holder">
                                <?php _e('به نام:', 'professional-card-to-card'); ?> <strong><?php echo esc_html($card->holder_name); ?></strong>
                            </div>
                            
                            <div class="p2p-checkout-card-number-wrapper">
                                <span class="p2p-checkout-card-num" id="p2p_card_num_text_<?php echo $card_index; ?>"><?php echo esc_html($formatted_num); ?></span>
                                <button type="button" class="p2p-checkout-copy-btn" id="<?php echo $btn_id; ?>" onclick="p2pCopyCardNumber(event, '<?php echo esc_js($card->card_number); ?>', '<?php echo $btn_id; ?>')">
                                    <span class="dashicons dashicons-admin-page" style="font-size: 12px; width: 12px; height: 12px; margin-top: 2px;"></span>
                                    <?php _e('کپی', 'professional-card-to-card'); ?>
                                </button>
                            </div>

                            <div class="p2p-checkout-qr-trigger" onclick="p2pToggleQR('<?php echo $drawer_id; ?>')">
                                <span style="font-size: 0.8em; font-weight: 500; color: #cbd5e1;"><?php _e('نمایش اسکن کد QR کارت', 'professional-card-to-card'); ?></span>
                                <span class="dashicons dashicons-qrcode" style="font-size: 16px; width: 16px; height: 16px; color: #60a5fa; margin-top: 3px;"></span>
                            </div>

                            <div class="p2p-checkout-qr-drawer" id="<?php echo $drawer_id; ?>" style="max-height: 0px; overflow: hidden; transition: all 0.25s ease;">
                                <img src="<?php echo \ProfessionalCardToCard\Qr_Generator::generate_bank_qr($card->card_number, $card->bank_name, $card->holder_name); ?>" alt="QR Code" style="width: 100px; height: 100px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); background: white; padding: 4px;" />
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- فرم پر کردن اطلاعات تراکنش توسط مشتری -->
                <div class="p2p-checkout-inputs-grid" style="margin-top: 25px;">
                    <div class="p2p-checkout-field-wrapper">
                        <label><?php _e('نام کامل واریز کننده', 'professional-card-to-card'); ?> <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="p2p_full_name" id="p2p_full_name" value="" required placeholder="<?php _e('مثال: محمد حسینی', 'professional-card-to-card'); ?>" />
                    </div>

                    <div class="p2p-checkout-field-wrapper">
                        <label><?php _e('شماره موبایل پیگیری', 'professional-card-to-card'); ?> <span style="color:#ef4444;">*</span></label>
                        <input type="tel" name="p2p_mobile" id="p2p_mobile" value="" required placeholder="مثال: 09121234567" style="direction:ltr; text-align:left;" />
                    </div>

                    <div class="p2p-checkout-field-wrapper">
                        <label><?php _e('واریز به کدام کارت مقصد؟', 'professional-card-to-card'); ?> <span style="color:#ef4444;">*</span></label>
                        <select name="p2p_bank_card_id" id="p2p_bank_card_id" required>
                            <option value=""><?php _e('-- انتخاب کارت مقصد انجام واریز --', 'professional-card-to-card'); ?></option>
                            <?php foreach ($active_cards as $card): ?>
                                <option value="<?php echo esc_attr($card->id); ?>"><?php echo esc_html($card->bank_name . ' - ' . $card->holder_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($require_last4 !== 'none'): ?>
                        <div class="p2p-checkout-field-wrapper">
                            <label>
                                <?php _e('۴ رقم آخر کارت فرستنده شما', 'professional-card-to-card'); ?> 
                                <?php if ($require_last4 === 'required'): ?><span style="color:#ef4444;">*</span><?php endif; ?>
                            </label>
                            <input type="text" name="p2p_last4" id="p2p_last4" maxlength="4" <?php echo ($require_last4 === 'required' ? 'required' : ''); ?> placeholder="1234" style="direction:ltr; text-align:left;" />
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($enable_receipt === 'yes'): ?>
                    <div class="p2p-checkout-field-wrapper" style="margin-top: 18px;">
                        <label><?php _e('تصویر فیش یا رسید واریز (اختیاری)', 'professional-card-to-card'); ?></label>
                        <div class="p2p-checkout-file-uploader" onclick="document.getElementById('p2p_receipt').click()">
                            <span class="dashicons dashicons-cloud-upload" style="font-size: 28px; width: 28px; height: 28px; color: #3b82f6; margin-bottom: 4px; display: inline-block;"></span>
                            <div id="p2p_receipt_label" style="font-size: 0.85em; font-weight: 500; color: #cbd5e1;"><?php _e('جهت بارگذاری رسید تصویری کلیک کنید', 'professional-card-to-card'); ?></div>
                            <small style="color: #94a3b8; font-size: 0.75em; display:block; margin-top: 3px;"><?php _e('فرمت‌های مجاز: JPG, PNG یا WebP تا حجم حداکثر ۲ مگابایت', 'professional-card-to-card'); ?></small>
                            <input type="file" name="p2p_receipt" id="p2p_receipt" accept="image/png, image/jpeg, image/jpg, image/webp" style="display: none;" onchange="p2pCheckoutFileSelected(this)" />
                        </div>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="p2p_nonce" value="<?php echo wp_create_nonce('p2p_checkout_submission'); ?>" />
            </div>

            <script>
            function p2pCopyCardNumber(event, number, btnId) {
                event.stopPropagation();
                event.preventDefault();
                
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(number).then(function() {
                        p2pShowCopySuccess(btnId);
                    }).catch(function() {
                        p2pFallbackCopy(number, btnId);
                    });
                } else {
                    p2pFallbackCopy(number, btnId);
                }
            }

            function p2pFallbackCopy(number, btnId) {
                var textArea = document.createElement("textarea");
                textArea.value = number;
                textArea.style.position = "fixed";
                textArea.style.opacity = "0";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    p2pShowCopySuccess(btnId);
                } catch (err) {}
                document.body.removeChild(textArea);
            }

            function p2pShowCopySuccess(btnId) {
                var btn = document.getElementById(btnId);
                if (btn) {
                    var originalText = btn.innerHTML;
                    btn.innerHTML = '✓ ' + '<?php echo esc_js(__('کپی شد', 'professional-card-to-card')); ?>';
                    btn.style.color = '#34d399';
                    btn.style.borderColor = 'rgba(16, 185, 129, 0.4)';
                    btn.style.background = 'rgba(16, 185, 129, 0.15)';
                    setTimeout(function() {
                        btn.innerHTML = originalText;
                        btn.style.color = '';
                        btn.style.borderColor = '';
                        btn.style.background = '';
                    }, 1800);
                }
            }

            function p2pToggleQR(drawerId) {
                var drawer = document.getElementById(drawerId);
                if (drawer) {
                    if (drawer.style.maxHeight === '0px' || drawer.style.maxHeight === '') {
                        drawer.style.maxHeight = '140px';
                        drawer.style.padding = '12px';
                        drawer.style.marginTop = '8px';
                        drawer.style.borderTop = '1px solid rgba(255, 255, 255, 0.05)';
                    } else {
                        drawer.style.maxHeight = '0px';
                        drawer.style.padding = '0px';
                        drawer.style.marginTop = '0px';
                        drawer.style.borderTop = 'none';
                    }
                }
            }

            function p2pCheckoutFileSelected(input) {
                var label = document.getElementById('p2p_receipt_label');
                if (label && input.files && input.files.length > 0) {
                    label.innerText = '<?php echo esc_js(__('رسید انتخاب شد:', 'professional-card-to-card')); ?> ' + input.files[0].name;
                    label.style.color = '#34d399';
                }
            }
            </script>
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
