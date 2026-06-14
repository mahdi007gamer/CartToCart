<?php
defined('ABSPATH') || exit;

// تعداد لاگ‌ها جهت نمایش
$limit = 25;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($paged - 1) * $limit;

$logs = \ProfessionalCardToCard\Logger::get_logs($limit, $offset);
$total_logs = \ProfessionalCardToCard\Logger::get_total_count();
$total_pages = ceil($total_logs / $limit);

if (isset($_GET['p2p_msg']) && $_GET['p2p_msg'] === 'logs_cleared') {
    echo '<div class="notice notice-info is-dismissible"><p>تمامی لاگ‌های فعالیت با موفقیت مخفی و پاکسازی گردیدند.</p></div>';
}
?>

<div class="wrap" style="direction: rtl;">
    <h1 class="wp-heading-inline" style="font-family: inherit; margin-bottom: 20px;"><?php _e('لاگ‌های فعالیت سیستم', 'professional-card-to-card'); ?></h1>
    <hr class="wp-header-end">
    
    <div style="margin-bottom: 15px; text-align: left;">
        <?php $clear_nonce = wp_create_nonce('p2p_clear_logs_action'); ?>
        <a href="admin.php?page=p2p-logs&action=p2p_clear_logs&nonce=<?php echo $clear_nonce; ?>" class="button button-link-delete" style="color:#c53030; border: 1px solid #cbd5e0; background:#fff; padding: 2px 10px; font-weight: bold;" onclick="return confirm('آیا مایل به حذف کل سوابق و لاگ‌های سیستم هستید؟ این کار غیرقابل بازگشت است.');"><span class="dashicons dashicons-trash" style="font-size:16px; margin-top:3px;"></span> <?php _e('پاکسازی کامل کلیه لاگ‌ها', 'professional-card-to-card'); ?></a>
    </div>

    <!-- جدول لاگ‌ها -->
    <table class="wp-list-table widefat fixed striped table-view-list" style="border: 1px solid #ccd0d4; border-radius: 6px; overflow: hidden;">
        <thead>
            <tr>
                <th style="font-weight: bold; width: 60px;"><?php _e('شناسه', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold; width: 150px;"><?php _e('نوع رویداد/بخش', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold;"><?php _e('شرح عملیات انجام شده', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold; width: 140px; text-align: center;"><?php _e('آدرس IP', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold; width: 120px; text-align: center;"><?php _e('شناسه کاربر', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold; width: 160px;"><?php _e('تاریخ ثبت رویداد', 'professional-card-to-card'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;"><?php _e('سند یا لاگی در سیستم ثبت نشده است.', 'professional-card-to-card'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>#<?php echo esc_html($log->id); ?></td>
                        <td>
                            <code style="font-weight:bold; color:#d53f8c;"><?php echo esc_html($log->event); ?></code>
                        </td>
                        <td><span style="font-weight: 500;"><?php echo esc_html($log->message); ?></span></td>
                        <td style="text-align: center;"><small style="direction: ltr; display: inline-block;"><?php echo esc_html($log->ip_address); ?></small></td>
                        <td style="text-align: center;">
                            <?php 
                            if ($log->user_id) {
                                $userdata = get_userdata($log->user_id);
                                echo '<strong>' . esc_html($userdata ? $userdata->display_name : $log->user_id) . '</strong>';
                            } else {
                                echo '<span class="description" style="color:gray;">مشتری مستقیم</span>';
                            }
                            ?>
                        </td>
                        <td><small><?php echo esc_html(date_i18n('Y-m-d H:i:s', strtotime($log->created_at))); ?></small></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- صفحات بندی -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav" style="text-align: left; margin-top:15px;">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo; قبلی', 'professional-card-to-card'),
                    'next_text' => __('بعدی &raquo;', 'professional-card-to-card'),
                    'total' => $total_pages,
                    'current' => $paged
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
