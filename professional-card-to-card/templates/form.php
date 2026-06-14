<?php
/**
 * Standalone payment form template.
 *
 * This can be overridden by copying it to yourtheme/card-to-card/form.php.
 */
defined('ABSPATH') || exit;

$theme_class = isset($settings['theme']) ? $settings['theme'] : 'glassmorphism';
$require_last4 = isset($settings['require_last4']) ? $settings['require_last4'] : 'required';
$enable_receipt = isset($settings['enable_receipt']) ? $settings['enable_receipt'] : 'yes';
$site_key = isset($settings['recaptcha_site_key']) ? $settings['recaptcha_site_key'] : '';
?>

<!-- استایل و فونت‌های فارسی شیک غنی شده -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;800&display=swap');
    
    .p2p-outer-wrapper {
        font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, Tahoma, sans-serif !important;
        direction: rtl !important;
        text-align: right !important;
        margin: 25px auto;
        max-width: 620px;
        width: 100%;
        box-sizing: border-box;
    }

    /* استایل تم خارق‌العاده شیشه‌ای (Glassmorphism) */
    .p2p-glass-theme {
        background: rgba(255, 255, 255, 0.45) !important;
        backdrop-filter: blur(14px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(14px) saturate(180%) !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        border-radius: 20px !important;
        box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12) !important;
        padding: 30px !important;
        color: #1e293b !important;
    }

    /* تم روشن کلاسیک */
    .p2p-light-theme {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05) !important;
        padding: 30px !important;
        color: #1e293b !important;
    }

    /* تم تاریک مدرن */
    .p2p-dark-theme {
        background: #0f172a !important;
        border: 1px solid #1e293b !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
        padding: 30px !important;
        color: #f8fafc !important;
    }

    /* کارت‌های اعتباری شبیه‌سازی شتاب */
    .p2p-visual-card {
        background: linear-gradient(135deg, #1e3a8a, #0d9488);
        border-radius: 16px;
        color: white;
        padding: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
        margin-bottom: 12px;
    }
    .p2p-visual-card::before {
        content: '';
        position: absolute;
        width: 150px;
        height: 150px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
        top: -40px;
        left: -40px;
    }
    .p2p-visual-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.2);
    }
    .p2p-visual-card.selected {
        border-color: #38bdf8;
        transform: scale(1.02);
    }
    
    .p2p-bank-chip {
        width: 45px;
        height: 35px;
        background: rgba(255,255,255,0.15);
        border-radius: 6px;
        margin: 10px 0;
        position: relative;
    }

    .p2p-form-group {
        margin-bottom: 18px;
    }
    .p2p-form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        font-size: 0.95em;
    }
    .p2p-input {
        width: 100% !important;
        padding: 10px 14px !important;
        border-radius: 8px !important;
        border: 1px solid rgba(0, 0, 0, 0.15) !important;
        background: rgba(255, 255, 255, 0.8) !important;
        font-size: 1em !important;
        outline: none !important;
        box-sizing: border-box !important;
        transition: border 0.3s ease, box-shadow 0.3s ease;
    }
    .p2p-dark-theme .p2p-input {
        background: rgba(30, 41, 59, 0.8) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: white !important;
    }
    .p2p-input:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25) !important;
    }

    /* منطقه درگ اند دراپ لوکس آپلود رسید */
    .p2p-upload-zone {
        border: 2px dashed rgba(59, 130, 246, 0.4);
        background: rgba(240, 246, 255, 0.4);
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: background 0.3s ease, border-color 0.3s ease;
    }
    .p2p-upload-zone:hover {
        border-color: #3b82f6;
        background: rgba(240, 246, 255, 0.7);
    }
    
    .p2p-submit-btn {
        background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
        color: white !important;
        padding: 12px 25px !important;
        border: none !important;
        border-radius: 10px !important;
        font-size: 1.1em !important;
        font-weight: bold !important;
        cursor: pointer !important;
        width: 100% !important;
        transition: opacity 0.3s ease !important;
    }
    .p2p-submit-btn:hover {
        opacity: 0.95 !important;
    }
</style>

<div class="p2p-outer-wrapper">
    <div class="p2p-container p2p-<?php echo esc_attr($theme_class); ?>-theme">
        
        <h2 style="margin-top:0; text-align:center; font-weight: 800; font-size:1.6em; text-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <?php _e('فرم پرداخت مستقیم کارت به کارت', 'professional-card-to-card'); ?>
        </h2>
        <p style="text-align: center; color: gray; font-size: 0.95em; margin-bottom: 25px;">
            <?php _e('لطفا وجه را به شماره کارت مقصد فروشگاه واریز کرده و مشخصات واریز خود را ثبت نمائید.', 'professional-card-to-card'); ?>
        </p>

        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data" id="p2pStandalonePaymentForm">
            <input type="hidden" name="action" value="p2p_submit_form">
            <input type="hidden" name="p2p_nonce" value="<?php echo esc_attr($nonce); ?>">

            <!-- بخش کارت‌های بانکی مقصد -->
            <div class="p2p-form-group">
                <label><strong><?php _e('۱. انتخاب حساب و کارت مقصد فروشگاه:', 'professional-card-to-card'); ?></strong></label>
                <div style="display: grid; grid-template-columns: 1fr; gap:12px; margin-top:10px;">
                    <?php 
                    $first = true;
                    foreach ($active_cards as $card): 
                    ?>
                        <div class="p2p-visual-card <?php echo $first ? 'selected' : ''; ?>" data-card-id="<?php echo $card->id; ?>" onclick="p2pSelectCard(this, <?php echo $card->id; ?>)">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <strong style="font-size:1.2em;"><?php echo esc_html($card->bank_name); ?></strong>
                                <span class="dashicons dashicons-bank" style="font-size:24px; width:24px; height:24px;"></span>
                            </div>
                            <div class="p2p-bank-chip"></div>
                            
                            <div style="font-family: monospace; font-size: 1.35em; letter-spacing: 2px; text-shadow: 0 1px 2px rgba(0,0,0,0.4); margin: 8px 0; direction: ltr; text-align: left;">
                                <?php echo esc_html(implode(' ', str_split($card->card_number, 4))); ?>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; font-size: 0.9em; margin-top: 10px; opacity:0.95;">
                                <span><?php _e('صاحب حساب:', 'professional-card-to-card'); ?> <?php echo esc_html($card->holder_name); ?></span>
                            </div>

                            <!-- کد QR برای اسکن آنی مستقیم موبایل بانک -->
                            <div class="p2p-card-qr-box" style="position: absolute; left: 15px; bottom: 12px; background: white; padding: 4px; border-radius: 6px; display:inline-block; border: 1px solid #ddd;">
                                <img src="<?php echo Qr_Generator::generate_bank_qr($card->card_number, $card->bank_name, $card->holder_name); ?>" alt="QR Scan" style="width: 50px; height: 50px; display:block;" />
                            </div>
                        </div>
                    <?php 
                        $first = false;
                    endforeach; 
                    ?>
                </div>
                <input type="hidden" name="bank_card_id" id="p2pSelectedCardId" value="<?php echo !empty($active_cards) ? $active_cards[0]->id : ''; ?>" />
            </div>

            <hr style="border:0; border-top:1px dashed rgba(0,0,0,0.1); margin: 25px 0;">

            <!-- بخش اطلاعات خریدار -->
            <div class="p2p-form-group">
                <label for="full_name"><?php _e('نام و نام خانوادگی واریز کننده', 'professional-card-to-card'); ?> <span style="color:red">*</span></label>
                <input type="text" id="full_name" name="full_name" class="p2p-input" required placeholder="مثال: محمدرضا حسینی" />
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="p2p-form-group">
                    <label for="mobile"><?php _e('شماره موبایل پیگیری', 'professional-card-to-card'); ?> <span style="color:red">*</span></label>
                    <input type="tel" id="mobile" name="mobile" class="p2p-input" required placeholder="مثال: 09121234567" style="direction: ltr; text-align: left;" />
                </div>
                
                <div class="p2p-form-group">
                    <label for="amount"><?php _e('مبلغ پرداخت شده (تومان)', 'professional-card-to-card'); ?> <span style="color:red">*</span></label>
                    <input type="number" id="amount" name="amount" class="p2p-input" required placeholder="مثال: 50000" />
                </div>
            </div>

            <?php if ($require_last4 !== 'none'): ?>
                <div class="p2p-form-group">
                    <label for="last4digits">
                        <?php _e('۴ رقم آخر کارت فرستنده شما', 'professional-card-to-card'); ?>
                        <?php if ($require_last4 === 'required'): ?><span style="color:red">*</span><?php endif; ?>
                    </label>
                    <input type="text" id="last4digits" name="last4digits" class="p2p-input" maxlength="4" placeholder="مثال: 4321" <?php echo ($require_last4 === 'required' ? 'required' : ''); ?> style="direction: ltr; text-align: left;" />
                </div>
            <?php endif; ?>

            <?php if ($enable_receipt === 'yes'): ?>
                <div class="p2p-form-group">
                    <label><strong><?php _e('تصویر رسید یا فیش تراکنش (اختیاری)', 'professional-card-to-card'); ?></strong></label>
                    <div class="p2p-upload-zone" onclick="document.getElementById('p2p_file_input').click()">
                        <span class="dashicons dashicons-cloud-upload" style="font-size:36px; width:36px; height:36px; color:#2563eb; margin-bottom:8px;"></span>
                        <div id="p2p_file_label" style="font-weight: 500;"><?php _e('فایل فیش را به اینجا درگ کنید یا کلیک کنید تا انتخاب شود', 'professional-card-to-card'); ?></div>
                        <small style="color: gray; display:block; margin-top:5px;"><?php _e('فرمت‌های مجاز: jpeg, png, webp (حداکثر ۲ مگابایت)', 'professional-card-to-card'); ?></small>
                        <input type="file" id="p2p_file_input" name="receipt" accept="image/*" style="display:none;" onchange="p2pFileSelected(this)" />
                    </div>
                </div>
            <?php endif; ?>

            <!-- گوگل ری‌کپچا در صورت فعال بودن با جاوا اسکریپت لودر -->
            <?php if (!empty($site_key)): ?>
                <div class="p2p-form-group" style="display:flex; justify-content:center; margin: 20px 0;">
                    <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
                </div>
                <script src="https://www.google.com/recaptcha/api.js?hl=fa" async defer></script>
            <?php endif; ?>

            <div style="margin-top: 25px;">
                <button type="submit" class="p2p-submit-btn"><?php _e('ثبت نهایی و ارسال مشخصات پرداخت', 'professional-card-to-card'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function p2pSelectCard(element, cardId) {
    // حذف کلاس انتخابی از تمامی کارت‌ها
    var cards = document.querySelectorAll('.p2p-visual-card');
    cards.forEach(function(card) {
        card.classList.remove('selected');
    });
    
    // افزودن کلاس به کارت کلیک شده
    element.classList.add('selected');
    document.getElementById('p2pSelectedCardId').value = cardId;
}

function p2pFileSelected(input) {
    var label = document.getElementById('p2p_file_label');
    if (input.files && input.files.length > 0) {
        label.innerText = 'فایل انتخاب شد: ' + input.files[0].name;
        label.style.color = '#15803d'; // سبز رنگ
    } else {
        label.innerText = 'فایل فیش را به اینجا درگ کنید یا کلیک کنید تا انتخاب شود';
        label.style.color = 'inherit';
    }
}
</script>
