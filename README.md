# 勤怠管理アプリ
本アプリケーションは、一般ユーザーによる勤怠打刻・勤怠履歴の確認および修正申請、管理者ユーザーによる勤怠管理・修正申請承認を行う勤怠管理システムです。

---

## 目次
1. 概要
2. 機能一覧
3. 使用技術
4. 環境構築手順
5. ログイン情報（必須）
6. ER 図
7. テーブル一覧
8. テスト実行方法

---

## 1. 概要
本システムでは、以下の 2 種類のユーザーが利用できます。
- **一般ユーザー**：出勤／休憩／退勤の打刻、勤怠詳細の確認、修正申請の送信・確認
- **管理者ユーザー**：全ユーザーの勤怠確認、詳細修正、修正申請の承認

---

## 2. 機能一覧
### ▼ 一般ユーザー向け
- 会員登録 / ログイン / ログアウト
- 勤怠打刻（出勤・休憩・退勤）
- 勤怠ステータス表示（勤務外 / 出勤中 / 休憩中 / 退勤済）
- 勤怠一覧（月次）
- 勤怠詳細表示
- 勤怠修正申請（バリデーション含む）
- 修正申請一覧（承認待ち・承認済み）

### ▼ 管理者ユーザー向け
- 管理者ログイン / ログアウト
- 日次勤怠一覧表示（前日・翌日切替）
- 勤怠詳細表示
- 勤怠内容の直接修正
- 修正申請一覧表示（承認待ち・承認済み）
- 修正申請内容の承認処理
- スタッフ一覧表示
- スタッフ別月次勤怠一覧表示
- スタッフ別月次勤怠CSV出力

#### 認証・バリデーションの設計方針

本アプリでは認証処理に Laravel Fortify を使用しています。

- 認証フロー（ログイン／会員登録／メール認証）は Fortify に集約
- 入力値の検証は FormRequest を利用
- エラーメッセージは `resources/lang/ja/validation.php` で一元管理し、日本語表示を統一

これにより、
「認証の責務」と「入力検証・表示文言の責務」を分離し、保守性を高めています。

---

## 3. 使用技術

- **PHP 8.2.29**
- **Laravel 12.40.2**
- **MySQL 8.0.44**
- **Nginx 1.21.1**
- **Docker / Docker Compose**
- **Mailhog**

---

## 4. 環境構築手順

### ① リポジトリをクローン
```bash
git clone https://github.com/mao716/attendance-app.git
cd attendance-app
```

### ② Docker コンテナの起動
```bash
docker compose up -d --build
```

### ③ パッケージインストール
```bash
docker compose exec php composer install
```

### ④ 環境変数ファイルの準備
```bash
cp .env.example .env
```
本リポジトリでは、Docker環境でそのまま起動できるように .env.example に開発用の初期値を記載しています。
必要に応じて `.env` を確認してください（例：DB接続）。

#### ▼ メール認証（開発環境用）の設定

本アプリでは会員登録時にメール認証を行います。
開発環境では MailHog を使用します。
- **MailHog Web UI：`http://localhost:8025`**
- **SMTP：`mailhog:1025`（Docker内部）**

※ 開発環境ではメール送信を即時確認できるよう、`.env.example` では `QUEUE_CONNECTION=sync` を採用しています。（`QUEUE_CONNECTION=database` を使用する場合は `php artisan queue:work` の起動が必要です）

### ⑤ アプリケーションキー生成
```bash
docker compose exec php php artisan key:generate
```

### ⑥ マイグレーション・シーディング
```bash
docker compose exec php php artisan migrate --seed
```

### ⑦ ブラウザで確認

以下の URL にアクセスしてください：`http://localhost`

---

## 5. ログイン情報（必須）

動作確認できるよう、以下のアカウントを用意しています。

### ▼ 管理者ユーザー（1名）

| メールアドレス        | パスワード     |
|------------------------|----------------|
| admin@coachtech.com      | password123    |

### ▼ 一般ユーザー（5名）

| 名前       | メールアドレス         | パスワード     |
|------------|--------------------------|----------------|
| 西 伶奈  | reina.n@coachtech.com        | password123    |
| 山田 太郎  | taro.y@coachtech.com        | password123    |
| 増田 一世  | issei.m@coachtech.com        | password123    |
| 山本 敬吉  | keikichi.y@coachtech.com        | password123    |
| 秋田 朋美  | tomomi.a@coachtech.com        | password123    |
| 中西 教夫  | norio.n@coachtech.com        | password123    |

---

## 6. ER 図

![ER 図](diagram.png)

---

## 7. テーブル一覧

主要テーブル：

- users
- attendances
- attendance_breaks
- stamp_correction_requests
- stamp_correction_breaks

※ カラム定義・制約はテーブル仕様書に準拠。

---

## 8. テスト実行方法
以下のコマンドで PHPUnit テストを実行できます：
```bash
docker compose exec php php artisan test
```
