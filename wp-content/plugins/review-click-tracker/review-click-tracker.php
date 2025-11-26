<?php
/**
 * Plugin Name: Review Click Tracker
 * Description: レビュー投稿のクリックを記録し、IPごとの閲覧回数・リファラ等を保存します。
 * Author: You
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Review_Click_Tracker {

    public function __construct() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );

        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'handle_go_redirect' ) );
    }

    /**
     * プラグイン有効化時：テーブル作成＋リライトルールフラッシュ
     */
    public function activate() {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'click_logs'; // マルチサイト全体で共通
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            blog_id BIGINT(20) UNSIGNED NOT NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            ip_address VARCHAR(100) NOT NULL,
            user_agent TEXT NULL,
            referrer TEXT NULL,
            target_url TEXT NOT NULL,
            visit_number INT(11) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY post_ip (post_id, ip_address)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // リライトルールを即反映
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * /go/123/ 形式のURLを扱うリライトルール
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^go/([0-9]+)/?',
            'index.php?rct_id=$matches[1]',
            'top'
        );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'rct_id';
        return $vars;
    }

    /**
     * /go/{post_id}/ にアクセスされたときの処理
     */
    public function handle_go_redirect() {
        $post_id = get_query_var( 'rct_id' );
        if ( empty( $post_id ) ) {
            return;
        }

        $post_id = absint( $post_id );
        if ( ! $post_id ) {
            return;
        }

        // 公式URL（ACFの official_url フィールド）を取得
        $target_url = get_field( 'official_url', $post_id );
        if ( empty( $target_url ) ) {
            // 公式URLがない場合は普通に投稿ページへ
            $target_url = get_permalink( $post_id );
        }

        // ログを保存
        $this->log_click( $post_id, $target_url );

        // リダイレクト
        wp_redirect( $target_url );
        exit;
    }

    /**
     * クリックログをDBに保存
     */
    private function log_click( $post_id, $target_url ) {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'click_logs';
        $blog_id    = get_current_blog_id();

        $ip_address = $this->get_ip_address();
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
$referrer   = get_permalink( $post_id );

        // 同一IP ＋ 同一post_id の過去回数をカウントして、「何回目か」を記録
        $visit_number = 1;
        if ( $ip_address ) {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND ip_address = %s",
                    $post_id,
                    $ip_address
                )
            );
            if ( $count !== null ) {
                $visit_number = intval( $count ) + 1;
            }
        }

        $wpdb->insert(
            $table_name,
            array(
                'blog_id'      => $blog_id,
                'post_id'      => $post_id,
                'ip_address'   => $ip_address,
                'user_agent'   => $user_agent,
                'referrer'     => $referrer,
                'target_url'   => $target_url,
                'visit_number' => $visit_number,
                'created_at'   => current_time( 'mysql' ),
            ),
            array(
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
            )
        );
    }

    /**
     * IPアドレス取得（X-Forwarded-Forも簡易対応）
     */
    private function get_ip_address() {
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            $ip  = trim( $ips[0] );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
        }

        return sanitize_text_field( $ip );
    }
}

new Review_Click_Tracker();
