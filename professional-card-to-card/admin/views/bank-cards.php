<?php
defined('ABSPATH') || exit;

global $wpdb;
$cards_table = $wpdb->prefix . 'c2c_bank_cards';

// ۱. پردازش افزودن کارت جدید
if (isset($_POST['p2p_add_card'])) {
    if (!isset($_POST['p2p_card_nonce']) || !wp_verify_nonce($_POST['p2p_card_nonce'], 'p2p_add_card_action')) {
        wp_die('ممیزی کپچای کارت رد شد.');
    }

    $card_number = sanitize_text_field(str_replace(' ', '', $_POST['card_number']));
    $bank_name   = sanitize_text_field($_POST['bank_name']);
    $holder_name = sanitize_text_field($_POST['holder_name']);
    $active      = isset($_POST['active']) ? 1 : 0;

    // ولیدیشن
    if (empty($card_number) || strlen($card_number) < 16 || strlen($card_number) > 20) {
        echo '<div class="notice notice-error"><p>شماره کارت وارد شده باید حداقل ۱۶ رقم باشد.</p></div>';
    } elseif (empty($bank_name) || empty($holder_name)) {
        echo '<div class="notice notice-error"><p>پر کردن نام بانک و نام صاحب حساب اجباری است.</p></div>';
    } else {
        $insert_res = $wpdb->insert($cards_table, array(
            'card_number' => $card_number,
            'bank_name'   => $bank_name,
            'holder_name' => $holder_name,
            'active'      => $active
        ));

        if ($insert_res) {
            echo '<div class="notice notice-success is-dismissible"><p>کارت بانکی جدید با موفقیت ثبت شد.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>خطا در ذخیره‌سازی کارت در دیتابیس.</p></div>';
        }
    }
}

// ۲. پردازش تغییر وضعیت فعال/غیرفعال بودن کارت یا حذف
if (isset($_GET['action']) && isset($_GET['cid']) && isset($_GET['nonce'])) {
    $card_id = intval($_GET['cid']);
    $action_get = sanitize_text_field($_GET['action']);

    if (!wp_verify_nonce($_GET['nonce'], 'p2p_card_op_' . $card_id)) {
        wp_die('خطای امنیتی نانس در فرآیند تغییر مشخصات کارت.');
    }

    if ($action_get === 'toggle_active') {
        $current_active = $wpdb->get_var($wpdb->prepare("SELECT active FROM $cards_table WHERE id = %d", $card_id));
        $new_active = ($current_active == 1) ? 0 : 1;

        $wpdb->update($cards_table, 
            array('active' => $new_active), 
            array('id' => $card_id)
        );
        echo '<div class="notice notice-success is-dismissible"><p>بروزرسانی وضعیت فعال بودن کارت انجام شد.</p></div>';
    } elseif ($action_get === 'delete_card') {
        $wpdb->delete($cards_table, array('id' => $card_id));
        echo '<div class="notice notice-success is-dismissible"><p>کارت بانکی مورد نظر با موفقیت حذف گردید.</p></div>';
    }
}

// خواندن اطلاعات کامل کارت‌ها
$cards = $wpdb->get_results("SELECT * FROM $cards_table ORDER BY id DESC");
?>

<div class="wrap" style="direction: rtl;">
    <h1 style="font-family: inherit; margin-bottom: 20px;"><?php _e('مدیریت کارت‌های بانکی پذیرنده', 'professional-card-to-card'); ?></h1>
    
    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top:15px;">
        <!-- لیست کارت‌ها -->
        <div style="flex: 2; min-width: 300px;">
            <table class="wp-list-table widefat fixed striped table-view-list" style="border: 1px solid #ccd0d4; border-radius: 6px; overflow: hidden;">
                <thead>
                    <tr>
                        <th style="font-weight: bold; width: 60px;"><?php _e('شناسه', 'professional-card-to-card'); ?></th>
                        <th style="font-weight: bold;"><?php _e('نام بانک', 'professional-card-to-card'); ?></th>
                        <th style="font-weight: bold;"><?php _e('نام صاحب حساب', 'professional-card-to-card'); ?></th>
                        <th style="font-weight: bold;"><?php _e('شماره کارت', 'professional-card-to-card'); ?></th>
                        <th style="font-weight: bold; width: 100px; text-align: center;"><?php _e('تاریخچه/QR', 'professional-card-to-card'); ?></th>
                        <th style="font-weight: bold; width: 90px; text-align: center;"><?php _e('وضعیت به روز', 'professional-card-to-card'); ?></th>
                        <th style="font-weight: bold; width: 150px; text-align: center;"><?php _e('عملیات', 'professional-card-to-card'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cards)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;"><?php _e('هنوز هیچ کارت بانکی اضافه نکرده‌اید. لطفا از فرم سمت چپ اضافه کنید.', 'professional-card-to-card'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cards as $card): 
                            $card_nonce = wp_create_nonce('p2p_card_op_' . $card->id);
                            ?>
                            <tr>
                                <td>#<?php echo esc_html($card->id); ?></td>
                                <td><strong><?php echo esc_html($card->bank_name); ?></strong></td>
                                <td><strong><?php echo esc_html($card->holder_name); ?></strong></td>
                                <td>
                                    <code style="font-size: 1.1em; letter-spacing: 1px; color:#1a202c; background: #eee; padding: 2px 8px; border-radius: 4px;">
                                        <?php echo esc_html(implode(' - ', str_split($card->card_number, 4))); ?>
                                    </code>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?php echo Qr_Generator::generate_bank_qr($card->card_number, $card->bank_name, $card->holder_name); ?>" target="_blank" class="button button-small" style="background:#edf2f7;"><span class="dashicons dashicons-qrcode" style="font-size:16px; margin-top:2px;"></span> <?php _e('کد QR', 'professional-card-to-card'); ?></a>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($card->active): ?>
                                        <span style="color:#2f855a; font-weight: bold;"><?php _e('فعال', 'professional-card-to-card'); ?></span>
                                    <?php else: ?>
                                        <span style="color:#c53030; font-weight: bold;"><?php _e('غیرفعال', 'professional-card-to-card'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <a href="admin.php?page=p2p-cards&action=toggle_active&cid=<?php echo $card->id; ?>&nonce=<?php echo $card_nonce; ?>" class="button button-small"><?php _e('تغییر وضعیت', 'professional-card-to-card'); ?></a>
                                    <a href="admin.php?page=p2p-cards&action=delete_card&cid=<?php echo $card->id; ?>&nonce=<?php echo $card_nonce; ?>" class="button button-small button-link-delete" onclick="return confirm('آیا واقعا می‌خواهید این کارت بانکی پذیرنده را حذف کنید؟ با این کار دیگر در تسویه حساب نمایش نخواهد یافت.');" style="color:#c53030"><?php _e('حذف', 'professional-card-to-card'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- فرم افزودن کارت -->
        <div style="flex: 1; min-width: 280px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px; align-self: flex-start;">
            <h2 style="font-family: inherit; margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #edf2f7; font-size:1.2em;"><?php _e('ثبت کارت بانکی پذیرنده جدید', 'professional-card-to-card'); ?></h2>
            
            <form method="post" action="">
                <input type="hidden" name="p2p_card_nonce" value="<?php echo wp_create_nonce('p2p_add_card_action'); ?>" />
                
                <p>
                    <label for="bank_name"><strong><?php _e('نام بانک مقصد', 'professional-card-to-card'); ?></strong> <span style="color:red">*</span></label><br>
                    <input type="text" id="bank_name" name="bank_name" placeholder="مانند: بانک ملی ایران" style="width: 100%; margin-top:5px;" required />
                </p>

                <p>
                    <label for="holder_name"><strong><?php _e('نام کامل صاحب حساب', 'professional-card-to-card'); ?></strong> <span style="color:red">*</span></label><br>
                    <input type="text" id="holder_name" name="holder_name" placeholder="مانند: ابوالفضل محمدی" style="width: 100%; margin-top:5px;" required />
                </p>

                <p>
                    <label for="card_number"><strong><?php _e('شماره ۱۶ رقمی کارت', 'professional-card-to-card'); ?></strong> <span style="color:red">*</span></label><br>
                    <input type="text" id="card_number" name="card_number" maxlength="16" placeholder="6037991122334455" style="width: 100%; margin-top:5px; direction:ltr; text-align:left;" required />
                </p>

                <p>
                    <label>
                        <input type="checkbox" name="active" value="1" checked />
                        <strong><?php _e('قابلیت استفاده کارهای فرانت‌اند (کارت فعال باشد)', 'professional-card-to-card'); ?></strong>
                    </label>
                </p>

                <p style="margin-top: 20px;">
                    <button type="submit" name="p2p_add_card" class="button button-primary button-large" style="width: 100%; justify-content:center;"><?php _e('افزودن کارت بانکی', 'professional-card-to-card'); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>
