#!/usr/bin/env bash

# WordPress のインストールパス
WP_PATH="/var/www/site"

# wp コマンドが存在するかチェック
if ! command -v wp >/dev/null 2>&1; then
  echo "Error: wp コマンドが見つかりません（WP-CLI が未インストールの可能性）"
  exit 1
fi

# WordPress が認識できるか確認
wp --allow-root --path="$WP_PATH" core version >/dev/null 2>&1
if [ $? -ne 0 ]; then
  echo "Error: wp --allow-root --path=\"$WP_PATH\" core version に失敗しました。パスを確認してください。"
  exit 1
fi

echo "=== テスト用 review 投稿を5件作成します ==="

create_review () {
  local title="$1"
  local slug="$2"
  local target_name="$3"
  local catch_copy="$4"
  local rating="$5"
  local official_url="$6"
  local source_url="$7"
  local eyecatch_url="$8"

  echo "---- \"$title\" を作成中..."

  # 投稿本文（シンプルなダミー）
  local content="これはテスト用のレビュー投稿です。
${target_name} のレビュー本文サンプルとして作成されています。
本番運用前の表示確認にご利用ください。"

  # review 投稿を作成（公開）
  POST_ID=$(wp --allow-root --path="$WP_PATH" post create \
    --post_type=review \
    --post_status=publish \
    --post_title="$title" \
    --post_name="$slug" \
    --post_content="$content" \
    --porcelain)

  if [ -z "$POST_ID" ]; then
    echo "  [ERROR] 投稿作成に失敗しました: $title"
    return
  fi

  echo "  -> 投稿ID: $POST_ID"

  # ACF カスタムフィールド（post meta）を登録
  wp --allow-root --path="$WP_PATH" post meta update "$POST_ID" target_name  "$target_name"  >/dev/null 2>&1
  wp --allow-root --path="$WP_PATH" post meta update "$POST_ID" catch_copy   "$catch_copy"   >/dev/null 2>&1
  wp --allow-root --path="$WP_PATH" post meta update "$POST_ID" rating       "$rating"       >/dev/null 2>&1
  wp --allow-root --path="$WP_PATH" post meta update "$POST_ID" official_url "$official_url" >/dev/null 2>&1
  wp --allow-root --path="$WP_PATH" post meta update "$POST_ID" source_url   "$source_url"   >/dev/null 2>&1

  echo "  -> カスタムフィールド登録済み"

  # アイキャッチ画像を外部URLからインポート
  if [ -n "$eyecatch_url" ]; then
    echo "  -> アイキャッチ画像をインポート: $eyecatch_url"
    ATTACH_ID=$(wp --allow-root --path="$WP_PATH" media import "$eyecatch_url" \
      --post_id="$POST_ID" \
      --featured_image \
      --title="${title} アイキャッチ" \
      --porcelain)

    if [ -n "$ATTACH_ID" ]; then
      echo "  -> アイキャッチ 添付ID: $ATTACH_ID"
    else
      echo "  [WARN] アイキャッチのインポートに失敗しました"
    fi
  fi

  echo "  完了: $title"
  echo
}

# ここから実際に5投稿を作成
create_review \
  "【テスト】ABCクリニックの口コミ・評判" \
  "test-review-1" \
  "ABCクリニック" \
  "通いやすさ重視のテスト用クリニック" \
  "4.5" \
  "https://example.com/abc-clinic" \
  "https://example.com/source/abc" \
  "https://via.placeholder.com/800x450.png?text=Review+1"

create_review \
  "【テスト】XYZサロンの体験レビュー" \
  "test-review-2" \
  "XYZサロン" \
  "コスパ重視のテスト用サロン" \
  "4.0" \
  "https://example.com/xyz-salon" \
  "https://example.com/source/xyz" \
  "https://via.placeholder.com/800x450.png?text=Review+2"

create_review \
  "【テスト】DEFスクールの口コミまとめ" \
  "test-review-3" \
  "DEFスクール" \
  "初心者向けテスト用スクール" \
  "3.5" \
  "https://example.com/def-school" \
  "https://example.com/source/def" \
  "https://via.placeholder.com/800x450.png?text=Review+3"

create_review \
  "【テスト】GHIサービスの評判チェック" \
  "test-review-4" \
  "GHIサービス" \
  "オンライン完結型のテストサービス" \
  "5.0" \
  "https://example.com/ghi-service" \
  "https://example.com/source/ghi" \
  "https://via.placeholder.com/800x450.png?text=Review+4"

create_review \
  "【テスト】JKLクリニックの料金と口コミ" \
  "test-review-5" \
  "JKLクリニック" \
  "価格重視のテスト用クリニック" \
  "3.0" \
  "https://example.com/jkl-clinic" \
  "https://example.com/source/jkl" \
  "https://via.placeholder.com/800x450.png?text=Review+5"

echo "=== 完了：テスト用 review 投稿を5件作成しました ==="
