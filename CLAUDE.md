# CLAUDE.md

## プロジェクト概要

掃除当番ロースター管理アプリ（社内向け・UI は日本語）。
Laravel 13 + Inertia v2 + Vue 3 の SPA。認証は Laravel Breeze。

主要エンティティ:

- **Member**（メンバー） … 掃除の担当者
- **CleaningRole**（役割） … 掃除箇所・役割。※ルートスラッグは `cleaning-items`
- **CleaningAssignment**（清掃割当 / 掃除当番） … メンバー × 役割の割当
- **User** … ログインアカウント（`/accounts` で一覧）

ドメインロジック:

- `app/Services/AssignmentSelectionService.php` … 割当の自動選定
- `app/Services/CleaningAssignmentPdfService.php` … dompdf で当番表 PDF 出力

## 開発環境（最重要）

このマシンには php / node / composer がローカルに無い。すべて **Laravel Sail**
（Docker、サービス名 `laravel.test`）経由で実行する。コンテナは通常起動済み。

| 目的 | コマンド |
| --- | --- |
| テスト | `./vendor/bin/sail test` |
| 整形（PHP） | `./vendor/bin/sail pint` |
| 整形チェック（PHP） | `./vendor/bin/sail pint --test` |
| フロント一式 | `./vendor/bin/sail npm run check`（format:check + lint + typecheck + build） |
| 整形（JS/Vue/CSS） | `./vendor/bin/sail npm run format` |
| artisan | `./vendor/bin/sail artisan ...` |

git はネイティブで動く。Sail 未起動時は `./vendor/bin/sail up -d`。

## コーディング規約：バックエンド

- コントローラは `Inertia::render('Dir/Index', [...])` を返す。書き込み系は FormRequest を
  受けて `to_route()` で `RedirectResponse` を返す。
- props はクエリで `select([...])` して `->map(fn () => [...])` で snake_case キーに整形して渡す。
- 書き込みは必ず FormRequest（`Store*Request` / `Update*Request`）:
  - `authorize(): true`
  - `prepareForValidation()` で文字列を `trim`、空文字は `null` に正規化
  - `messages()` は日本語、文末は「。」
- 削除は使用中チェック付き。`DB::transaction` + `lockForUpdate()` で行ロックしてから判定・削除。
- フラッシュメッセージ: `Inertia::flash('success'|'error', '日本語。')` → `return to_route(...)`
  （inertia-laravel v2 の組み込み機能。テストは `->assertInertiaFlash(...)`）
- メソッドシグネチャは引数を 1 行ずつ・末尾カンマ（Pint / 既存コードに合わせる）。

## コーディング規約：フロントエンド

- Vue は `<script setup>`。TypeScript は使わず、JSDoc の `@typedef` と
  インライン `/** @type */` キャストで型を付ける（`vue-tsc` で型チェック）。
- import は `@/` エイリアス（`resources/js`）。UI 部品は `@/Components/*`（Breeze 由来）。
- フォームは `@inertiajs/vue3` の `useForm` / `usePage`。
- 一覧の絞り込みは `router.get(route('...'), {...}, { preserveState, preserveScroll, replace })`。
- 文言はすべて日本語のリテラル直書き（i18n ファイルは無い）。
- 整形は Prettier（tabWidth 4 / シングルクォート / trailingComma all / Tailwind クラス自動並べ替え）。

## ルーティング

- すべて `routes/web.php`、`auth` ミドルウェア配下。名前付き・kebab-case。
- ルートモデルバインディング（`{member}` `{cleaningRole}`）。
- 注意: スラッグと名前が `cleaning-items.*` なのに対応モデルは `CleaningRole`。

## テスト

- PHPUnit（Pest ではない）。`tests/Feature` 中心、`tests/Unit` は最小。
- 各クラスで `use RefreshDatabase`。メソッド名は `test_snake_case(): void`。
- `User::factory()`、`route()` ヘルパ、`->assertInertiaFlash()`、
  ゲストは `->assertRedirect(route('login'))`、DB は `assertDatabaseCount` / `assertSame`。
- 新機能・修正には必ず Feature テストを追加。

## 作業を終える前に

1. `./vendor/bin/sail pint`
2. `./vendor/bin/sail npm run check`
3. `./vendor/bin/sail test`

いずれも PostToolUse フックで自動整形はされるが、最終確認は上記で行う。

### やらないこと

- ローカル php / node 前提のコマンドを書かない（必ず sail 経由）
- Vue を一括で TS 化しない（JSDoc 方式を維持）
- `lang/` の翻訳ファイルを勝手に作らない
