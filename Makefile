# Sailを起動しつつ、テスト用DBの作成まで行う初期化コマンド
init:
	cp -n .env.example .env || true
	./vendor/bin/sail build --no-cache
	./vendor/bin/sail up -d
	@sleep 5 # MySQLの起動待ち
	echo "CREATE DATABASE IF NOT EXISTS testing;" | ./vendor/bin/sail mysql
	./vendor/bin/sail artisan migrate

# ビルドをスキップして起動とDB作成だけ行う
init-fast:
	./vendor/bin/sail up -d
	@sleep 10
	echo "CREATE DATABASE IF NOT EXISTS testing;" | ./vendor/bin/sail mysql
	./vendor/bin/sail artisan migrate

# テスト実行（DB作成を念のため挟む）
test:
	echo "CREATE DATABASE IF NOT EXISTS testing;" | ./vendor/bin/sail mysql
	./vendor/bin/sail artisan test

# DBをまっさらにしてマイグレーションし直す
fresh:
	./vendor/bin/sail artisan migrate:fresh
	./vendor/bin/sail artisan migrate:fresh --env=testing