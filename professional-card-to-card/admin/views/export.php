<?php
defined('ABSPATH') || exit;
?>

<div class="wrap" style="direction: rtl;">
    <h1 style="font-family: inherit; margin-bottom: 20px;"><?php _e('خروجی گرفتن از تراکنش‌ها', 'professional-card-to-card'); ?></h1>
    
    <div style="background: #fff; padding: 30px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 650px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-top:15px;">
        <span class="dashicons dashicons-media-spreadsheet" style="font-size: 48px; width: 48px; height: 48px; color: #3182ce; margin-bottom:15px; display:block;"></span>
        
        <h2 style="margin-top:0;"><?php _e('دانلود فایل گزارش به صورت اکسل (CSV)', 'professional-card-to-card'); ?></h2>
        
        <p style="font-size: 1.1em; line-height: 1.6; color:#4a5568;">
            <?php _e('با کلیک بر روی دکمه زیر، فایل کاملی از کلیه اطلاعات و پرداخت‌های ثبت شده توسط درگاه کارت به کارت استخراج خواهد شد. این فایل منطبق بر استانداردهای اکسل فارسی (انکدینگ UTF-8 BOM) آماده شده است تا هیچ مشکلی در خوانایی متون و نام‌های فارسی به وجود نیاید.', 'professional-card-to-card'); ?>
        </p>

        <div style="background: rgba(49, 130, 206, 0.05); border-right: 4px solid #3182ce; padding: 12px 15px; border-radius: 4px; margin: 20px 0; font-size: 0.95em; color: #2b6cb0;">
            <strong><?php _e('نکته مدیریت:', 'professional-card-to-card'); ?></strong> <?php _e('این خروجی شامل مشخصاتی همچون شماره همراه کاربران، نام کامل، جزییات کارت بانکی مقصد سفارش و وضعیت ثبت (Pending, Approved, Rejected) به همراه لینک مستقیم تصویر رسید می‌باشد.', 'professional-card-to-card'); ?>
        </div>

        <form method="post" action="">
            <input type="hidden" name="p2p_export_nonce" value="<?php echo wp_create_nonce('p2p_export_action'); ?>" />
            
            <button type="submit" name="p2p_do_export_csv" class="button button-primary button-large" style="display:inline-flex; align-items:center; gap:8px; font-weight:bold; padding:4px 20px; font-size:1.1em;">
                <span class="dashicons dashicons-download" style="font-size:18px; margin-top:2px;"></span>
                <?php _e('دانلود فایل گزارش CSV', 'professional-card-to-card'); ?>
            </button>
        </form>
    </div>
</div>
