<?php
// CLIから実行する前提
if ( php_sapi_name() !== 'cli' ) {
    die("CLI only\n");
}

require_once __DIR__ . '/wp-load.php';

$file = __DIR__ . '/import/posts.csv';

if ( ! file_exists( $file ) ) {
    die("CSVファイルが見つかりません: {$file}\n");
}

$handle = fopen( $file, 'r' );
if ( ! $handle ) {
    die("CSVを開けませんでした\n");
}

$header = fgetcsv( $handle );
if ( ! $header ) {
    die("ヘッダ行が読めません\n");
}

// ヘッダ → インデックス
$map = array();
foreach ( $header as $i => $col ) {
    $map[ $col ] = $i;
}

$count = 0;

while ( ( $row = fgetcsv( $handle ) ) !== false ) {
    $post_type    = $row[ $map['post_type'] ] ?? 'post';
    $post_title   = $row[ $map['post_title'] ] ?? '';
    $post_content = $row[ $map['post_content'] ] ?? '';
    $post_status  = $row[ $map['post_status'] ] ?? 'draft';

    $post_type    = trim( $post_type );
    $post_title   = trim( $post_title );
    $post_content = trim( $post_content );
    $post_status  = trim( $post_status );

    if ( $post_title === '' ) {
        echo "タイトルが空なのでスキップ\n";
        continue;
    }

    // post_type=post or review だけ許可（安全のため）
    if ( ! in_array( $post_type, array( 'post', 'review' ), true ) ) {
        echo "不正なpost_type({$post_type})のためスキップ: {$post_title}\n";
        continue;
    }

    $post_id = wp_insert_post(
        array(
            'post_type'    => $post_type,
            'post_title'   => $post_title,
            'post_content' => $post_content,
            'post_status'  => $post_status,
        ),
        true
    );

    if ( is_wp_error( $post_id ) ) {
        echo "エラー: " . $post_id->get_error_message() . "\n";
    } else {
        echo "作成: ID={$post_id} / type={$post_type} / title={$post_title}\n";
        $count++;
    }
}

fclose( $handle );

echo "合計 {$count} 件の投稿を作成しました\n";
