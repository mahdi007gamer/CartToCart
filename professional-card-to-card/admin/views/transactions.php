<?php
defined('ABSPATH') || exit;

global $wpdb;
$trans_table = $wpdb->prefix . 'c2c_transactions';
$cards_table = $wpdb->prefix . 'c2c_bank_cards';

// ۱. پردازش ذخیره یادداشت مدیر در صورت ارسال فرم پست
if (isset($_POST['p2p_save_note']) && isset($_POST['trans_id'])) {
    if (!isset($_POST['p2p_note_nonce']) || !wp_verify_nonce($_POST['p2p_note_nonce'], 'p2p_save_note_action')) {
        wp_die('ممیزی کپچای ورود اطلاعات شکست خورد.');
    }

    $tid = intval($_POST['trans_id']);
    $notes = sanitize_textarea_field($_POST['admin_note']);

    $wpdb->update(
        $trans_table,
        array('admin_notes' => $notes),
        array('id' => $tid),
        array('%s'),
        array('%d')
    );

    echo '<div class="notice notice-success is-dismissible"><p>یادداشت مدیریت ثبت و ذخیره شد.</p></div>';
}

// پارامترهای جستجو و فیلترها
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_filter = isset($_GET['p2p_date']) ? sanitize_text_field($_GET['p2p_date']) : '';

// صفحه بندی
$limit = 15;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($paged - 1) * $limit;

// ساخت کوئری داینامیک دیتابیس
$where_clauses = array('1=1');
$params = array();

if (!empty($search)) {
    $where_clauses[] = "(t.full_name LIKE %s OR t.mobile LIKE %s OR t.id = %d OR t.order_id = %d)";
    $params[] = '%' . $wpdb->esc_like($search) . '%';
    $params[] = '%' . $wpdb->esc_like($search) . '%';
    $params[] = intval($search);
    $params[] = intval($search);
}

if (!empty($status_filter)) {
    $where_clauses[] = "t.status = %s";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $where_clauses[] = "DATE(t.created_at) = %s";
    $params[] = $date_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// دریافت کل تراکنش‌ها جهت صفحه‌بندی
$count_query = "SELECT COUNT(*) FROM $trans_table t WHERE $where_sql";
if (!empty($params)) {
    $total_items = $wpdb->get_var($wpdb->prepare($count_query, $params));
} else {
    $total_items = $wpdb->get_var($count_query);
}

$total_pages = ceil($total_items / $limit);

// دریافت لیست تراکنش‌ها
$query = "SELECT t.*, c.bank_name, c.card_number 
          FROM $trans_table t 
          LEFT JOIN $cards_table c ON t.bank_card_id = c.id 
          WHERE $where_sql 
          ORDER BY t.id DESC 
          LIMIT %d OFFSET %d";

$params_limit = array_merge($params, array($limit, $offset));
$transactions = $wpdb->get_results($wpdb->prepare($query, $params_limit));

// نمایش پیام‌های سیستم
if (isset($_GET['p2p_msg']) && $_GET['p2p_msg'] === 'status_updated') {
    echo '<div class="notice notice-success is-dismissible"><p>وضعیت تراکنش به همراه وضعیت سفارش ووکامرس با موفقیت بروزرسانی شد.</p></div>';
}
?>

<div class="wrap" style="direction: rtl;">
    <h1 class="wp-heading-inline" style="font-family: inherit; margin-bottom: 20px;"><?php _e('گزارش تراکنش‌های کارت به کارت', 'professional-card-to-card'); ?></h1>
    <hr class="wp-header-end">

    <!-- فیلتر و جستجو -->
    <form method="get" action="" style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 6px; margin-bottom: 20px;">
        <input type="hidden" name="page" value="p2p-gateway" />
        
        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
            <div>
                <label for="s">جستجو: </label>
                <input type="search" id="s" name="s" value="<?php echo esc_attr($search); ?>" placeholder="نام، موبایل، شناسه، شماره سفارش..." style="min-width: 200px;" />
            </div>

            <div>
                <label for="status">وضعیت: </label>
                <select name="status" id="status">
                    <option value=""><?php _e('همه وضعیت‌ها', 'professional-card-to-card'); ?></option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('در انتظار تایید', 'professional-card-to-card'); ?></option>
                    <option value="approved" <?php selected($status_filter, 'approved'); ?>><?php _e('تایید شده', 'professional-card-to-card'); ?></option>
                    <option value="rejected" <?php selected($status_filter, 'rejected'); ?>><?php _e('رد شده', 'professional-card-to-card'); ?></option>
                </select>
            </div>

            <div>
                <label for="p2p_date">تاریخ تفکیک: </label>
                <input type="date" id="p2p_date" name="p2p_date" value="<?php echo esc_attr($date_filter); ?>" />
            </div>

            <div>
                <button type="submit" class="button button-primary"><?php _e('اعمال فیلتر', 'professional-card-to-card'); ?></button>
                <a href="admin.php?page=p2p-gateway" class="button"><?php _e('بازنشانی فیلترها', 'professional-card-to-card'); ?></a>
            </div>
        </div>
    </form>

    <!-- جدول تراکنش‌ها -->
    <table class="wp-list-table widefat fixed striped table-view-list" style="border: 1px solid #ccd0d4; border-radius: 6px; overflow: hidden;">
        <thead>
            <tr>
                <th style="font-weight: bold; width: 60px;"><?php _e('ردیف', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold; width: 100px;"><?php _e('شماره سفارش', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold;"><?php _e('پرداخت کننده', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold;"><?php _e('موبایل پیگیری', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold;"><?php _e('کارت مقصد', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold; width: 90px; text-align: center;"><?php _e('۴ رقم آخر', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold;"><?php _e('مبلغ', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold; text-align: center;"><?php _e('رسید', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold; width: 110px; text-align: center;"><?php _e('وضعیت به روز', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold;"><?php _e('تاریخ ثبت', 'professional-card-to-card'); ?></th>
                <th style="font-weight: bold; width: 180px; text-align: center;"><?php _e('عملیات تایید', 'professional-card-to-card'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="11" style="text-align: center; padding: 20px;"><?php _e('تراکنشی متناسب با فیلتر شما یافت نشد.', 'professional-card-to-card'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><strong>#<?php echo esc_html($t->id); ?></strong></td>
                        <td>
                            <?php 
                            if ($t->order_id) {
                                if (class_exists('WooCommerce')) {
                                    echo '<a href="' . get_edit_post_link($t->order_id) . '" target="_blank">#' . esc_html($t->order_id) . '</a>';
                                } else {
                                    echo '#' . esc_html($t->order_id);
                                }
                            } else {
                                echo '<span class="description" style="color:#718096;">خارج از سبد</span>';
                            }
                            ?>
                        </td>
                        <td><strong><?php echo esc_html($t->full_name); ?></strong></td>
                        <td><span style="direction: ltr; display: inline-block;"><?php echo esc_html($t->mobile); ?></span></td>
                        <td>
                            <?php if ($t->bank_name): ?>
                                <small><?php echo esc_html($t->bank_name); ?></small><br>
                                <code style="font-size:0.9em;"><?php echo esc_html(implode('-', str_split($t->card_number, 4))); ?></code>
                            <?php else: ?>
                                <span style="color:red;">کارت حذف یافته</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;"><code style="font-size:1.1em; font-weight: bold; background: #edf2f7; padding: 2px 6px; border-radius: 4px;"><?php echo esc_html($t->last4digits ? $t->last4digits : '-'); ?></code></td>
                        <td><strong style="color: #2b6cb0;"><?php echo number_format($t->amount); ?> <span style="font-size:0.8em; font-weight: normal;">تومان</span></strong></td>
                        <td style="text-align: center;">
                            <?php if ($t->receipt_url): ?>
                                <a href="<?php echo esc_url($t->receipt_url); ?>" target="_blank" class="button button-small" style="background:#edf2f7; border: 1px solid #cbd5e0; color: #4a5568;"><span class="dashicons dashicons-image-filter" style="font-size: 16px; margin-top:2px;"></span> <?php _e('نمایش فیش', 'professional-card-to-card'); ?></a>
                            <?php else: ?>
                                <span class="description" style="color:#a0aec0;"><?php _e('بدون رسید', 'professional-card-to-card'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php 
                            if ($t->status === 'approved') {
                                echo '<span style="background: #c6f6d5; color: #22543d; padding: 4px 8px; border-radius: 4px; font-weight:bold; font-size:0.85em;">تایید شده</span>';
                            } elseif ($t->status === 'rejected') {
                                echo '<span style="background: #fed7d7; color: #742a2a; padding: 4px 8px; border-radius: 4px; font-weight:bold; font-size:0.85em;">رد شده</span>';
                            } else {
                                echo '<span style="background: #feebc8; color: #744210; padding: 4px 8px; border-radius: 4px; font-weight:bold; font-size:0.85em;">در انتظار</span>';
                            }
                            ?>
                        </td>
                        <td><small><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($t->created_at))); ?></small></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <!-- دکمه‌های عملیات سریع تغییر وضعیت ادمین -->
                            <?php 
                            $approve_nonce = wp_create_nonce('p2p_update_status_' . $t->id);
                            
                            if ($t->status !== 'approved'): ?>
                                <a href="admin.php?page=p2p-gateway&action=p2p_update_status&status=approved&tid=<?php echo $t->id; ?>&nonce=<?php echo $approve_nonce; ?>" class="button button-small" style="background:#48bb78; border-color:#38a169; color:#fff;" onclick="return confirm('آیا از تایید این تراکنش و واریز وجه اطمینان دارید؟');"><?php _e('تایید', 'professional-card-to-card'); ?></a>
                            <?php endif; ?>

                            <?php if ($t->status !== 'rejected'): ?>
                                <a href="admin.php?page=p2p-gateway&action=p2p_update_status&status=rejected&tid=<?php echo $t->id; ?>&nonce=<?php echo $approve_nonce; ?>" class="button button-small" style="background:#f56565; border-color:#e53e3e; color:#fff;" onclick="return confirm('آیا تمایل دارید این تراکنش را رد کنید؟ سفارش مرتبط لغو/ناموفق خواهد شد.');"><?php _e('رد پرداخت', 'professional-card-to-card'); ?></a>
                            <?php endif; ?>

                            <!-- دکمه نمایش جزییات و افزودن یادداشت -->
                            <button type="button" class="button button-small" onclick="p2pShowDetails(<?php echo htmlspecialchars(json_encode($t)); ?>)"><?php _e('یادداشت', 'professional-card-to-card'); ?></button>
                        </td>
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

<!-- مودال تمیز و ساده جاوااسکریپتی ویرایش یادداشت مدیریت -->
<div id="p2pDetailsModal" style="display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div style="background-color: #fefefe; margin: 10% auto; padding: 25px; border: 1px solid #888; width: 100%; max-width: 500px; border-radius: 10px; direction: rtl; text-align: right; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <span onclick="p2pCloseModal()" style="color: #aaa; float: left; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h3 style="margin-top: 0;" id="modal_title">توضیحات و یادداشت اداری</h3>
        <hr style="border: 0; border-top:1px solid #eee; margin-bottom:15px;">
        
        <form method="post" action="">
            <input type="hidden" name="p2p_note_nonce" value="<?php echo wp_create_nonce('p2p_save_note_action'); ?>" />
            <input type="hidden" name="trans_id" id="modal_trans_id" value="" />
            
            <p><strong>کاربر:</strong> <span id="modal_user_name"></span></p>
            <p><strong>آدرس IP ثبت شده:</strong> <span id="modal_user_ip"></span></p>
            
            <div style="margin-top: 15px; margin-bottom: 20px;">
                <label for="admin_note"><strong>یادداشت مدیریت یا علت رد تراکنش:</strong></label>
                <textarea name="admin_note" id="modal_admin_note" rows="5" style="width: 100%; border: 1px solid #ccc; border-radius: 6px; padding: 10px; margin-top:5px;"></textarea>
                <small style="color: gray;">این یادداشت صرفا برای سوابق مدیریتی فروشگاه بوده و به کاربر نمایش داده نمی‌شود.</small>
            </div>

            <div style="text-align: left;">
                <button type="submit" name="p2p_save_note" class="button button-primary"><?php _e('ذخیره و ثبت یادداشت', 'professional-card-to-card'); ?></button>
                <button type="button" class="button" onclick="p2pCloseModal()"><?php _e('انصراف', 'professional-card-to-card'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function p2pShowDetails(trans) {
    document.getElementById('modal_trans_id').value = trans.id;
    document.getElementById('modal_user_name').innerText = trans.full_name;
    document.getElementById('modal_user_ip').innerText = trans.ip_address || '-';
    document.getElementById('modal_admin_note').value = trans.admin_notes || '';
    document.getElementById('modal_title').innerText = 'یادداشت تراکنش شماره #' + trans.id;
    document.getElementById('p2pDetailsModal').style.display = "block";
}
function p2pCloseModal() {
    document.getElementById('p2pDetailsModal').style.display = "none";
}
// بستن خودکار مودال با کلیک خارج از صفحه اصلی مودال
window.onclick = function(event) {
    var modal = document.getElementById('p2pDetailsModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>
