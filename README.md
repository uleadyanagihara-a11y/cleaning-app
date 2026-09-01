# Cleaning App

Laravel、Inertia.js、Vue、Viteで構成されたアプリケーションです。ローカル開発環境にはLaravel Sailを使用します。WSL内にPHP、Composer、Node.js、npmを個別にインストールする必要はありません。

## 前提条件

- WSL 2
- Docker Desktop（対象のWSLディストリビューションとの連携を有効化）
- Git

## 初回セットアップ

依存関係がまだない場合は、最初にComposer依存関係を取得します。

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    composer:2.10.2 \
    composer install --ignore-platform-reqs
```

環境ファイルを作成し、Sailを起動します。

```bash
cp .env.example .env
./vendor/bin/sail up -d --wait
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
```

通常、WSLユーザーのUID/GIDはどちらも`1000`です。異なる場合は、`.env`の`WWWUSER`と`WWWGROUP`を次の結果に合わせてください。

```bash
id -u
id -g
```

## 日常の開発

バックエンドを起動します。

```bash
./vendor/bin/sail up -d --wait
```

別のターミナルでVite開発サーバーを起動します。

```bash
./vendor/bin/sail npm run dev
```

`queue`サービスもSailと同時に起動し、データベースキューを処理します。ワーカーへコード変更を反映したい場合は再起動します。

```bash
./vendor/bin/sail restart queue
```

終了時はコンテナを停止します。データベースなどの名前付きボリュームは保持されます。

```bash
./vendor/bin/sail stop
```

ボリュームも削除する`./vendor/bin/sail down -v`は、保存済みのローカルデータが不要な場合にだけ実行してください。

## URLとポート

| サービス | URLまたはホスト側ポート |
| --- | --- |
| Laravel | http://localhost |
| Vite | http://localhost:5173 |
| MySQL | `127.0.0.1:3307` |
| Meilisearch | http://localhost:7700 |
| Mailpit | http://localhost:8025 |

MySQLのコンテナ間接続には`.env`の`DB_HOST=mysql`、`DB_PORT=3306`を使用します。ホスト側の接続ポートは`FORWARD_DB_PORT=3307`です。

## PDF出力

掃除当番表のPDF生成には`barryvdh/laravel-dompdf`を使用します。日本語表示用のIPAexゴシックはSailイメージへ組み込まれ、Dompdfが生成するフォントキャッシュは`storage/fonts`へ保存されます。

PDF対応前に作成したSailイメージを使用している場合は、イメージを再ビルドしてから起動してください。

```bash
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d --wait
```

## よく使うコマンド

```bash
# Laravel CLI
./vendor/bin/sail artisan migrate

# PHPテスト
./vendor/bin/sail artisan test

# Composer
./vendor/bin/sail composer install

# フロントエンド
./vendor/bin/sail npm ci
./vendor/bin/sail npm run lint
./vendor/bin/sail npm run typecheck
./vendor/bin/sail npm run check
./vendor/bin/sail npm run build

# コンテナ状態とログ
./vendor/bin/sail ps
./vendor/bin/sail logs -f laravel.test
```

## Dockerイメージの更新方針

Sailランタイムは`docker/sail/8.5`でプロジェクト管理しています。ベースOSをダイジェストで固定し、ビルド時にはPHP、Node.js、Composer、npm、pnpm、Bun、Corepack、Yarn、Playwrightが宣言したバージョンと一致することを検証します。依存元が更新されて一致しなくなった場合、別バージョンへ暗黙に切り替わらずビルドが失敗します。

現在のランタイム契約は次のとおりです。

| ランタイム | バージョン |
| --- | --- |
| Ubuntu | `24.04`（ダイジェスト固定） |
| PHP | `8.5.9` |
| Node.js | `24.19.0` |
| Composer | `2.10.2` |
| npm | `12.0.2` |
| pnpm | `11.21.0` |
| Bun | `1.3.14` |
| Corepack | `0.35.0` |
| Yarn | `4.18.0` |
| Playwright | `1.62.1` |

外部サービスについても、`compose.yaml`でバージョンを固定しています。

| サービス | イメージ |
| --- | --- |
| Laravel Sail（ローカルビルド） | `cleaning-app/sail:php8.5.9-node24.19.0` |
| MySQL | `mysql:8.4.11` |
| Redis | `redis:8.10-alpine` |
| Meilisearch | `getmeili/meilisearch:v1.52.0` |
| Mailpit | `axllent/mailpit:v1.30.6` |
| Selenium | `selenium/standalone-chromium:4.46.0-20260707` |

ランタイム更新時は`docker/sail/8.5/Dockerfile`、イメージタグ、上記の表を同時に変更し、次の検証を行ってください。

```bash
./vendor/bin/sail pull --ignore-buildable
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d --wait
./vendor/bin/sail artisan test
./vendor/bin/sail npm run build
```
