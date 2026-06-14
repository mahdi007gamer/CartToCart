<?php
/**
 * Standalone success / payment details template.
 *
 * This can be overridden by copying it to yourtheme/card-to-card/success.php.
 */
defined('ABSPATH') || exit;
?>

<style>
    .p2p-success-box {
        font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, Tahoma, sans-serif !important;
        direction: rtl !important;
        text-align: center !important;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 16px;
        padding: 35px 25px;
        max-width: 580px;
        margin: 30px auto;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    .p2p-success-icon {
        width: 65px;
        height: 65px;
        background: #15803d;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin: 0 auto 20px auto;
    }
    .p2p-details-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    .p2p-details-table th, .p2p-details-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #e2e8f0;
        text-align: right;
    }
    .p2p-details-table th {
        background: #f8fafc;
        font-weight: 700;
        color: #475569;
        width: 40%;
    }
</style>

<div class="p2p-success-box">
    
    <div class="p2p-success-icon">✓</div>
    
    <h2 style="color: #166534; font-weight: 800; margin-top:0; font-size: 1.5em;">
        <?php _e('مشخصات پرداخت با موفقیت ثبت شد!', 'professional-card-to-card'); ?>
    </h2>
    <p style="color: #15803d; font-size: 0.95em; line-height: 1.6; margin-top:5px; margin-bottom: 25px;">
        <?php _e('کاربر گرامی، اطلاعات فیش یا کارت شما با موفقیت برای مدیریت سایت مخابره شد. تراکنش شما در اسرع وقت قرابتی بررسی شده و نسبت به ارائه خدمات اقدام می‌گردد.', 'professional-card-to-card'); ?>
    </p>

    <h3 style="text-align: right; margin-top: 15px; margin-bottom:10px; font-size:1.1em; font-weight: bold; color: #1e293b;"><span class="dashicons dashicons-text-flow" style="font-size: 20px; width:20px; height:20px; margin-top:2px;"></span> <?php _e('رسید دیجیتال ثبت تراکنش', 'professional-card-to-card'); ?></h3>
    
    <table class="p2p-details-table">
        <tbody>
            <tr>
                <th><?php _e('کد پیگیری تراکنش', 'professional-card-to-card'); ?></th>
                <td><strong>#<?php echo esc_html($transaction->id); ?></strong></td>
            </tr>
            <tr>
                <th><?php _e('نام کامل واریز کننده', 'professional-card-to-card'); ?></th>
                <td><?php echo esc_html($transaction->full_name); ?></td>
            </tr>
            <tr>
                <th><?php _e('موبایل پیگیری', 'professional-card-to-card'); ?></th>
                <td><span style="direction: ltr; display: inline-block;"><?php echo esc_html($transaction->mobile); ?></span></td>
            </tr>
            <tr>
                <th><?php _e('مبلغ پرداخت شده', 'professional-card-to-card'); ?></th>
                <td><strong style="color: #2563eb;"><?php echo number_format($transaction->amount); ?> تومان</strong></td>
            </tr>
            <tr>
                <th><?php _e('بانک پذیرنده مقصد', 'professional-card-to-card'); ?></th>
                <td><?php echo esc_html($transaction->bank_name); ?></td>
            </tr>
            <?php if (!empty($transaction->last4digits)): ?>
                <tr>
                    <th><?php _e('۴ رقم آخر کارت فرستنده', 'professional-card-to-card'); ?></th>
                    <td><code style="font-weight:bold; font-size:1.1em;"><?php echo esc_html($transaction->last4digits); ?></code></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th><?php _e('وضعیت پرداخت', 'professional-card-to-card'); ?></th>
                <td>
                    <span style="background: #feebc8; color: #744210; padding: 4px 8px; border-radius: 4px; font-weight:bold; font-size:0.85em;">در انتظار تایید مدیریت</span>
                </td>
            </tr>
            <tr>
                <th><?php _e('زمان ثبت رسید', 'professional-card-to-card'); ?></th>
                <td><small><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($transaction->created_at))); ?></small></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 25px;">
        <a href="<?php echo esc_url(remove_query_arg(array('p2p_status', 'p2p_tid'))); ?>" class="button" style="background: #15803d; color: white !important; padding: 10px 20px; border-radius: 8px; font-weight: bold; text-decoration: none; display:inline-block; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"><?php _e('امتناع و بازگشت به صفحه قبلی', 'professional-card-to-card'); ?></a>
    </div>
</div>
