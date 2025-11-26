<?php
/**
 * Plugin Name: Review CSV Importer
 * Description: review投稿用のCSVをインポートして一括投稿作成するツール。雛形CSVのダウンロード機能付き。
 * Author: You
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Review_CSV_Importer {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'maybe_download_template' ) );
    }

    /**
     * 管理画面メニュー追加
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=review',   // review 投稿のメニュー配下
            'レビューCSVインポート',
            'CSVインポート',
            'manage_options',
            'review-csv-import',
            array( $this, 'render_page' )
        );
    }

    /**
     * CSVインポート画面
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.' ) );
        }

        echo '<div class="wrap">';
        echo '<h1>レビューCSVインポート</h1>';

        // ★ 雛形CSVダウンロード用URLを生成
        $download_url = wp_nonce_url(
            add_query_arg(
                array(
                    'post_type'         => 'review',
                    'page'              => 'review-csv-import',
                    'download_template' => 1,
                ),
                admin_url( 'edit.php' )
            ),
            'review_csv_template',
            'review_csv_template_nonce'
        );

        // インポート処理実行
        if ( isset( $_POST['review_csv_import_submit'] ) ) {
            check_admin_referer( 'review_csv_import', 'review_csv_import_nonce' );

            if ( ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
                $this->handle_upload( $_FILES['csv_file'] );
            } else {
                echo '<div class="notice notice-error"><p>CSVファイルを選択してください。</p></div>';
            }
        }

        // 雛形ダウンロードボタン + アップロードフォーム
        echo '<p><a href="' . esc_url( $download_url ) . '" class="button">雛形CSVをダウンロード</a></p>';

        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field( 'review_csv_import', 'review_csv_import_nonce' );

        echo '<p>事前に決めたテンプレートに従って作成された CSVファイル（UTF-8）を選択してください。</p>';
        echo '<p><input type="file" name="csv_file" accept=".csv" required></p>';
        echo '<p><label><input type="checkbox" name="publish_now" value="1" checked> インポートした投稿をすぐに「公開」状態にする（チェックを外すと「下書き」にします）</label></p>';
        echo '<p><button type="submit" name="review_csv_import_submit" class="button button-primary">インポート開始</button></p>';

        echo '</form>';
        echo '</div>';
    }

    /**
     * 雛形CSVのダウンロード要求があればCSVを出力して終了
     */
    public function maybe_download_template() {
        if ( ! is_admin() ) {
            return;
        }

        // 指定のページ以外は無視
        if ( empty( $_GET['page'] ) || $_GET['page'] !== 'review-csv-import' ) {
            return;
        }

        // download_template パラメータがなければ何もしない
        if ( empty( $_GET['download_template'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // nonce チェック
        if (
            ! isset( $_GET['review_csv_template_nonce'] ) ||
            ! wp_verify_nonce( $_GET['review_csv_template_nonce'], 'review_csv_template' )
        ) {
            wp_die( '不正なリクエストです（nonce エラー）。' );
        }

        // ヘッダー行とサンプル1行（ライター用雛形）
        $header = array(
            'post_title',
            'post_slug',
            'target_name',
            'catch_copy',
            'rating',
            'official_url',
            'source_url',
            'content_paragraph1',
            'content_paragraph2',
            'eyecatch_url',
        );

        $sample = array(
            '【サンプル】ABCクリニックの口コミ・評判',
            'abc-clinic-sample',
            'ABCクリニック',
            '通いやすさ重視のサンプルクリニック',
            '4.5',
            'https://example.com/abc-clinic',
            'https://example.com/source/abc',
            'これはサンプルの1段落目です。ライターさんはここに本文を書いてください。',
            'これはサンプルの2段落目です。不要なら空欄でもかまいません。',
            'https://via.placeholder.com/800x450.png?text=Sample+Eyecatch',
        );

        $filename = 'review_template_' . date( 'Ymd' ) . '.csv';

        // Excel で文字化けしにくくするため UTF-8 BOM を付ける
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );

        // UTF-8 BOM
        echo "\xEF\xBB\xBF";

        fputcsv( $output, $header );
        fputcsv( $output, $sample );

        fclose( $output );
        exit;
    }

    /**
     * アップロードされたCSVファイルの処理
     */
    private function handle_upload( $file ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $overrides = array(
            'test_form' => false,
            'mimes'     => array(
                'csv' => 'text/csv',
                'txt' => 'text/plain',
            ),
        );

        $uploaded = wp_handle_upload( $file, $overrides );

        if ( isset( $uploaded['error'] ) ) {
            echo '<div class="notice notice-error"><p>アップロードエラー: ' . esc_html( $uploaded['error'] ) . '</p></div>';
            return;
        }

        $csv_path = $uploaded['file'];

        $result = $this->import_csv( $csv_path );

        // 一時ファイル削除
        @unlink( $csv_path );

        if ( $result && is_array( $result ) ) {
            echo '<div class="notice notice-success"><p>インポート完了：' .
                 '成功 ' . intval( $result['success'] ) . '件 / ' .
                 '失敗 ' . intval( $result['failed'] ) . '件</p></div>';

            if ( ! empty( $result['messages'] ) ) {
                echo '<div class="notice notice-info"><ul>';
                foreach ( $result['messages'] as $msg ) {
                    echo '<li>' . esc_html( $msg ) . '</li>';
                }
                echo '</ul></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>CSVの読み込み中に不明なエラーが発生しました。</p></div>';
        }
    }

    /**
     * CSVの中身を読み込んで review 投稿を作成
     */
    private function import_csv( $csv_path ) {
        $handle = fopen( $csv_path, 'r' );
        if ( ! $handle ) {
            return array(
                'success'  => 0,
                'failed'   => 0,
                'messages' => array( 'CSVファイルを開けませんでした。' ),
            );
        }

        // 1行目：ヘッダー
        $header = fgetcsv( $handle );
        if ( ! $header ) {
            fclose( $handle );
            return array(
                'success'  => 0,
                'failed'   => 0,
                'messages' => array( 'ヘッダー行を読み込めませんでした。' ),
            );
        }

        // ヘッダー名 → カラム位置
        $cols = array();
        foreach ( $header as $index => $col_name ) {
            $col_name          = trim( $col_name );
            $cols[ $col_name ] = $index;
        }

        // 必須カラムチェック
        $required = array(
            'post_title',
            'post_slug',
            'target_name',
            'catch_copy',
            'rating',
            'official_url',
            'source_url',
            'content_paragraph1',
            'content_paragraph2',
            'eyecatch_url',
        );

        $missing = array();
        foreach ( $required as $col_name ) {
            if ( ! isset( $cols[ $col_name ] ) ) {
                $missing[] = $col_name;
            }
        }

        if ( ! empty( $missing ) ) {
            fclose( $handle );
            return array(
                'success'  => 0,
                'failed'   => 0,
                'messages' => array(
                    'ヘッダーに次のカラムが不足しています: ' . implode( ', ', $missing ),
                ),
            );
        }

        $publish_now = ! empty( $_POST['publish_now'] );
        $post_status = $publish_now ? 'publish' : 'draft';

        $success  = 0;
        $failed   = 0;
        $messages = array();
        $row_num  = 1; // ヘッダー行は1行目

        // アイキャッチで使う関数群
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $row_num++;

            // 完全な空行ならスキップ
            $joined = implode( '', $row );
            if ( trim( $joined ) === '' ) {
                continue;
            }

            $post_title   = trim( $row[ $cols['post_title'] ] ?? '' );
            $post_slug    = trim( $row[ $cols['post_slug'] ] ?? '' );
            $target_name  = trim( $row[ $cols['target_name'] ] ?? '' );
            $catch_copy   = trim( $row[ $cols['catch_copy'] ] ?? '' );
            $rating       = trim( $row[ $cols['rating'] ] ?? '' );
            $official_url = trim( $row[ $cols['official_url'] ] ?? '' );
            $source_url   = trim( $row[ $cols['source_url'] ] ?? '' );
            $para1        = trim( $row[ $cols['content_paragraph1'] ] ?? '' );
            $para2        = trim( $row[ $cols['content_paragraph2'] ] ?? '' );
            $eyecatch_url = trim( $row[ $cols['eyecatch_url'] ] ?? '' );

            // ★ GPT：post_title が空 & source_url がある場合は自動生成（本文＋画像）
            if ( $post_title === '' && $source_url !== '' ) {

                $generated = $this->generate_article_with_gpt( $source_url );

                if ( is_wp_error( $generated ) ) {
                    $failed++;
                    $messages[] = "行 {$row_num}: GPT生成エラー - " . $generated->get_error_message();
                    continue;
                }

                // タイトル
                if ( empty( $post_title ) ) {
                    $post_title = $generated['title'] ?? '';
                }

                // 段落1
                if ( empty( $para1 ) ) {
                    $para1 = $generated['paragraph1'] ?? '';
                }

                // 段落2
                if ( empty( $para2 ) ) {
                    $para2 = $generated['paragraph2'] ?? '';
                }

                // アイキャッチURL（GPT→画像API）
                if ( empty( $eyecatch_url ) && ! empty( $generated['eyecatch_url'] ) ) {
                    $eyecatch_url = $generated['eyecatch_url'];
                }
            }

            // それでもタイトルが空ならスキップ
            if ( $post_title === '' ) {
                $failed++;
                $messages[] = "行 {$row_num}: タイトル（post_title）が空のためスキップしました。";
                continue;
            }

            // 本文生成（段落を \n\n でつなぐ）
            $content_parts = array();

            if ( $para1 !== '' ) {
                $content_parts[] = $para1;
            }
            if ( $para2 !== '' ) {
                $content_parts[] = $para2;
            }
            $post_content = implode( "\n\n", $content_parts );

            // 投稿作成
            $post_data = array(
                'post_type'    => 'review',
                'post_title'   => $post_title,
                'post_status'  => $post_status,
                'post_content' => $post_content,
            );

            if ( $post_slug !== '' ) {
                $post_data['post_name'] = $post_slug;
            }

            $post_id = wp_insert_post( $post_data, true );

            if ( is_wp_error( $post_id ) ) {
                $failed++;
                $messages[] = "行 {$row_num}: 投稿作成エラー - " . $post_id->get_error_message();
                continue;
            }

            // ACF フィールド（post meta）設定
            update_post_meta( $post_id, 'target_name',  $target_name );
            update_post_meta( $post_id, 'catch_copy',   $catch_copy );
            update_post_meta( $post_id, 'rating',       $rating );
            update_post_meta( $post_id, 'official_url', $official_url );
            update_post_meta( $post_id, 'source_url',   $source_url );

            // アイキャッチ画像があればダウンロード＆設定
            if ( $eyecatch_url !== '' ) {
                $tmp = download_url( $eyecatch_url );

                if ( is_wp_error( $tmp ) ) {
                    $messages[] = "行 {$row_num}: アイキャッチ画像のダウンロード失敗 - " . $tmp->get_error_message();
                } else {
                    $file_array = array(
                        'name'     => basename( parse_url( $eyecatch_url, PHP_URL_PATH ) ),
                        'tmp_name' => $tmp,
                    );

                    $attach_id = media_handle_sideload( $file_array, $post_id );

                    if ( is_wp_error( $attach_id ) ) {
                        @unlink( $tmp );
                        $messages[] = "行 {$row_num}: アイキャッチ登録失敗 - " . $attach_id->get_error_message();
                    } else {
                        set_post_thumbnail( $post_id, $attach_id );
                    }
                }
            }

            $success++;
        }

        fclose( $handle );

        return array(
            'success'  => $success,
            'failed'   => $failed,
            'messages' => $messages,
        );
    }

    /**
     * GPT で記事生成：source_url から本文 + 画像プロンプト + 画像URLを生成
     */
    private function generate_article_with_gpt( $source_url ) {

        if ( ! defined( 'OPENAI_API_KEY' ) || ! OPENAI_API_KEY ) {
            return new WP_Error( 'no_api_key', 'OPENAI_API_KEY が設定されていません。' );
        }

        // 1. 元記事HTML取得
        $response = wp_remote_get( $source_url );
        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'fetch_error', '元記事の取得に失敗しました。' );
        }

        $html = wp_remote_retrieve_body( $response );
        if ( empty( $html ) ) {
            return new WP_Error( 'empty_html', '元記事が取得できませんでした。' );
        }

        // 2. 簡易テキスト化
        $body_text = wp_strip_all_tags( $html );
        $body_text = preg_replace( '/\s+/', ' ', $body_text );
        $body_text = mb_substr( $body_text, 0, 4000 );

        // 3. GPTに JSON で生成させる
        $prompt = "次のニュースを参考に日本語でオリジナル記事を作成してください。
出力は以下のJSON形式のみ：
{
  \"title\": \"32文字以内\",
  \"paragraph1\": \"300〜400文字\",
  \"paragraph2\": \"300〜400文字\",
  \"image_prompt\": \"英語での画像プロンプト\"
}

元記事:
" . $body_text;

        // 4. Chat API
        $request = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . OPENAI_API_KEY,
                ),
                'body'    => wp_json_encode( array(
                    'model'    => 'gpt-4o-mini',
                    'messages' => array(
                        array( 'role' => 'user', 'content' => $prompt ),
                    ),
                ) ),
                'timeout' => 40,
            )
        );

        if ( is_wp_error( $request ) ) {
            return new WP_Error( 'api_error', 'GPT API の通信に失敗しました。' );
        }

        $body = json_decode( wp_remote_retrieve_body( $request ), true );
        if ( empty( $body['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'empty_gpt', 'GPT応答が空です。' );
        }

        $json = json_decode( trim( $body['choices'][0]['message']['content'] ), true );
        if ( ! is_array( $json ) ) {
            return new WP_Error( 'json_parse_error', 'GPT出力がJSON形式ではありません。' );
        }

        // 5. AI画像生成
        $eyecatch_url = '';
        if ( ! empty( $json['image_prompt'] ) ) {
            $image_result = $this->generate_ai_image( $json['image_prompt'] );
            if ( ! is_wp_error( $image_result ) ) {
                $eyecatch_url = $image_result;
            }
        }

        return array(
            'title'        => $json['title']       ?? '',
            'paragraph1'   => $json['paragraph1']  ?? '',
            'paragraph2'   => $json['paragraph2']  ?? '',
            'eyecatch_url' => $eyecatch_url,
        );
    }

    /**
     * AI画像生成（OpenAI 画像API）
     */
    private function generate_ai_image( $prompt ) {

        if ( ! defined( 'OPENAI_API_KEY' ) || ! OPENAI_API_KEY ) {
            return new WP_Error( 'no_api_key', 'OPENAI_API_KEY が設定されていません。' );
        }

        $request = wp_remote_post(
            'https://api.openai.com/v1/images/generations',
            array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . OPENAI_API_KEY,
                ),
                'body'    => wp_json_encode( array(
                    'model'  => 'gpt-image-1',
                    'prompt' => $prompt,
                    'n'      => 1,
                    'size'   => '1024x576',
                ) ),
                'timeout' => 40,
            )
        );

        if ( is_wp_error( $request ) ) {
            return new WP_Error( 'img_api_error', '画像生成APIエラー' );
        }

        $body = json_decode( wp_remote_retrieve_body( $request ), true );
        if ( empty( $body['data'][0]['url'] ) ) {
            return new WP_Error( 'no_image_url', '画像URLを取得できませんでした。' );
        }

        return $body['data'][0]['url'];
    }
}

new Review_CSV_Importer();


