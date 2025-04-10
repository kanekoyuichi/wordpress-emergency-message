<?php
/**
 * Plugin Name: Emergency Message
 * Description: 緊急時にトップページのヘッダーメニューの下にメッセージを表示します（管理画面で編集可）
 * Version: 1.0
 * Author: Yuichi Kaneko
 */

add_action('admin_menu', function () {
    add_options_page('緊急メッセージ設定', '緊急メッセージ', 'manage_options', 'emergency-message', 'render_emergency_message_settings');
});

function render_emergency_message_settings() {
    ?>
    <div class="wrap">
        <h1>緊急メッセージ設定</h1>
        
        <!-- プラグインの説明と利用方法 -->
        <div style="margin-top: 10px; margin-bottom: 20px; padding: 0 15px 10px 15px; border: 1px solid #ddd; border-radius: 5px;">
            <h2>緊急メッセージについて</h2>
            <p>緊急時にトップページのヘッダーの下にメッセージを表示するための設定です。</p>
            <h3>利用方法</h3>
            <ol>
                <li>メッセージ表示を有効にするには「表示する」にチェックを入れてください。表示期間内であってもチェックが入っていなければ表示されません。</li>
                <li>表示するメッセージを入力します（HTMLも使用可能）。</li>
                <li>必要に応じて文字色、文字サイズ、リンク先を設定してください。</li>
                <li>「リンクに下線をつける」を選択すると、リンクに下線が表示されます。</li>
                <li>掲載開始日時と掲載終了日時を設定することで、表示期間を制御できます。</li>
            </ol>
        </div>

        <form method="post" action="options.php">
            <?php
            settings_fields('emergency_message_group');
            do_settings_sections('emergency-message');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {
    // 設定項目を登録
    register_setting('emergency_message_group', 'emergency_message_text');
    register_setting('emergency_message_group', 'emergency_message_color');
    register_setting('emergency_message_group', 'emergency_message_font_size');
    register_setting('emergency_message_group', 'emergency_message_link');
    register_setting('emergency_message_group', 'emergency_message_link_underline');
    register_setting('emergency_message_group', 'emergency_message_enabled');
    register_setting('emergency_message_group', 'emergency_message_start_date');
    register_setting('emergency_message_group', 'emergency_message_end_date');

    add_settings_section('emergency_message_section', '', null, 'emergency-message');

    // 表示するかどうか
    add_settings_field('emergency_message_enabled', '表示する', function () {
        $checked = get_option('emergency_message_enabled') ? 'checked' : '';
        echo "<input type='checkbox' name='emergency_message_enabled' value='1' $checked />";
    }, 'emergency-message', 'emergency_message_section');

    // 表示するメッセージ
    add_settings_field('emergency_message_text', '表示するメッセージ（HTML可）', function () {
        $value = esc_textarea(get_option('emergency_message_text', ''));
        echo "<textarea name='emergency_message_text' rows='5' cols='60'>$value</textarea>";
    }, 'emergency-message', 'emergency_message_section');

    // 文字色
    add_settings_field('emergency_message_color', '文字色', function () {
        $value = esc_attr(get_option('emergency_message_color', '#FF0000'));
        echo "<input type='color' name='emergency_message_color' value='$value' />";
    }, 'emergency-message', 'emergency_message_section');

    // 文字サイズ
    add_settings_field('emergency_message_font_size', '文字サイズ（例: 16px）', function () {
        $value = esc_attr(get_option('emergency_message_font_size', '16px'));
        echo "<input type='text' name='emergency_message_font_size' value='$value' />";
    }, 'emergency-message', 'emergency_message_section');

    // リンク先
    add_settings_field('emergency_message_link', 'リンク先URL', function () {
        $value = esc_url(get_option('emergency_message_link', ''));
        echo "<input type='url' name='emergency_message_link' value='$value' placeholder='https://example.com' />";
    }, 'emergency-message', 'emergency_message_section');

    // リンクに下線をつけるかどうか
    add_settings_field('emergency_message_link_underline', 'リンクに下線をつける', function () {
        $checked = get_option('emergency_message_link_underline') ? 'checked' : '';
        echo "<input type='checkbox' name='emergency_message_link_underline' value='1' $checked />";
    }, 'emergency-message', 'emergency_message_section');

    // 掲載開始日時
    add_settings_field('emergency_message_start_date', '掲載開始日時', function () {
        $value = esc_attr(get_option('emergency_message_start_date', ''));
        echo "<input type='datetime-local' name='emergency_message_start_date' value='$value' />";
    }, 'emergency-message', 'emergency_message_section');

    // 掲載終了日時
    add_settings_field('emergency_message_end_date', '掲載終了日時', function () {
        $value = esc_attr(get_option('emergency_message_end_date', ''));
        echo "<input type='datetime-local' name='emergency_message_end_date' value='$value' />";
    }, 'emergency-message', 'emergency_message_section');
});

add_action('admin_notices', function () {
    $start_date = get_option('emergency_message_start_date');
    $end_date = get_option('emergency_message_end_date');

    if ($start_date && $end_date && strtotime($end_date) < strtotime($start_date)) {
        echo '<div class="notice notice-error"><p>掲載終了日時は掲載開始日時より後に設定してください。</p></div>';
    }
});

add_action('wp_head', function () {
    // トップページ以外では何もしない
    if (!is_front_page()) {
        return;
    }

    if (get_option('emergency_message_enabled') && $message = get_option('emergency_message_text')) {
        $start_date = get_option('emergency_message_start_date');
        $end_date = get_option('emergency_message_end_date');
        $current_time = current_time('Y-m-d H:i');

        // 掲載期間のチェック
        if ($start_date && $end_date) {
            if (strtotime($current_time) < strtotime($start_date) || strtotime($current_time) > strtotime($end_date)) {
                return; // 掲載期間外の場合は表示しない
            }
        } elseif ($start_date && strtotime($current_time) < strtotime($start_date)) {
            return; // 掲載開始日時より前の場合は表示しない
        } elseif ($end_date && strtotime($current_time) > strtotime($end_date)) {
            return; // 掲載終了日時を過ぎている場合は表示しない
        }

        $color = esc_attr(get_option('emergency_message_color', '#FF0000'));
        $font_size = esc_attr(get_option('emergency_message_font_size', '16px'));
        $link = esc_url(get_option('emergency_message_link', ''));
        $underline = get_option('emergency_message_link_underline') ? 'underline' : 'none';

        echo '
        <style>
            .emergency-message {
                background: #FFF;
                color: ' . $color . ';
                padding: 10px;
                text-align: center;
                font-size: ' . $font_size . ';
                position: relative;
                z-index: 9999;
            }
            .emergency-message a {
                text-decoration: ' . $underline . ';
                color: ' . $color . ';
            }
        </style>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const header = document.querySelector("header");
                if (header) {
                    const banner = document.createElement("div");
                    banner.className = "emergency-message";
                    banner.innerHTML = ' . json_encode($link ? "<a href=\"$link\">$message</a>" : $message) . ';
                    header.insertAdjacentElement("afterend", banner);
                }
            });
        </script>';
    }
});
