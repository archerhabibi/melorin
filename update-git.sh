#!/usr/bin/env bash
#
# update-git.sh — ارتقای نصب موجود ملورین با git pull، به‌جای جایگزینی
# دستی فایل‌ها از یک zip (همان کاری که update.sh قبلی انجام می‌داد).
#
# پیش‌نیاز: پوشه‌ی نصب باید یک ریپوی git باشد (git init قبلاً روی آن
# انجام شده و remote origin به GitHub وصل است).
#
# استفاده:
#   sudo bash update-git.sh                    # مسیر پیش‌فرض /var/www/melorin، برنچ main
#   sudo bash update-git.sh /path/to/melorin    # مسیر دیگر
#   sudo bash update-git.sh /path/to/melorin dev # برنچ دیگر
#
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
  echo "این اسکریپت باید با sudo/root اجرا شود: sudo bash update-git.sh" >&2
  exit 1
fi

APP_DIR="${1:-/var/www/melorin}"
BRANCH="${2:-main}"

if [[ ! -f "$APP_DIR/artisan" ]]; then
  echo "خطا: پروژه‌ی لاراول در ${APP_DIR} پیدا نشد." >&2
  exit 1
fi

if [[ ! -d "$APP_DIR/.git" ]]; then
  echo "خطا: ${APP_DIR} یک ریپوی git نیست. اول باید git init کنید و remote را تنظیم کنید." >&2
  exit 1
fi

echo "======================================================================"
echo " ارتقای ملورین (git-based) — مسیر: ${APP_DIR} — برنچ: ${BRANCH}"
echo "======================================================================"
echo ""

cd "$APP_DIR"

# ============================================================================
# بخش ۱: بک‌آپ دیتابیس (فقط دیتابیس — کد که دیگر توسط git history پوشش
# داده می‌شود نیازی به بک‌آپ جداگانه ندارد)
# ============================================================================
TIMESTAMP="$(date +%Y-%m-%d_%H-%M-%S)"
BACKUP_DIR="/root/melorin-backups/${TIMESTAMP}"
mkdir -p "$BACKUP_DIR"

echo "==> ۱. بک‌آپ دیتابیس در ${BACKUP_DIR}"

read_env_value() {
  local key="$1"
  grep -E "^${key}=" "$APP_DIR/.env" | tail -n1 | cut -d'=' -f2- | sed 's/^"//; s/"$//'
}

DB_HOST_ENV="$(read_env_value DB_HOST)"; DB_HOST_ENV="${DB_HOST_ENV:-127.0.0.1}"
DB_DATABASE_ENV="$(read_env_value DB_DATABASE)"
DB_USERNAME_ENV="$(read_env_value DB_USERNAME)"
DB_PASSWORD_ENV="$(read_env_value DB_PASSWORD)"

if [[ -n "$DB_DATABASE_ENV" ]]; then
  if MYSQL_PWD="$DB_PASSWORD_ENV" mysqldump -h "$DB_HOST_ENV" -u "$DB_USERNAME_ENV" "$DB_DATABASE_ENV" \
      > "${BACKUP_DIR}/database.sql" 2>"${BACKUP_DIR}/mysqldump.log"; then
    gzip "${BACKUP_DIR}/database.sql"
    echo "    ذخیره شد: ${BACKUP_DIR}/database.sql.gz"
  else
    echo "    هشدار: بک‌آپ دیتابیس شکست خورد — لاگ در ${BACKUP_DIR}/mysqldump.log" >&2
    exit 1
  fi
fi
echo ""

# ============================================================================
# بخش ۲: git pull
# ============================================================================
echo "==> ۲. دریافت آخرین تغییرات از GitHub"

if [[ -n "$(git status --porcelain)" ]]; then
  echo "خطا: تغییرات commit‌نشده روی سرور وجود دارد. اول آن‌ها را commit یا stash کنید:" >&2
  git status --short >&2
  exit 1
fi

CURRENT_COMMIT="$(git rev-parse HEAD)"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull origin "$BRANCH"
NEW_COMMIT="$(git rev-parse HEAD)"

if [[ "$CURRENT_COMMIT" == "$NEW_COMMIT" ]]; then
  echo "    از قبل به‌روز است (${NEW_COMMIT:0:7}) — چیزی برای ارتقا نیست."
  exit 0
fi

echo "    ${CURRENT_COMMIT:0:7} → ${NEW_COMMIT:0:7}"
echo "    تغییرات این ارتقا:"
git log --oneline "${CURRENT_COMMIT}..${NEW_COMMIT}" | sed 's/^/      /'
echo ""

# ============================================================================
# بخش ۳: composer، migration، کش
# ============================================================================
export COMPOSER_ALLOW_SUPERUSER=1

echo "==> ۳. composer install"
composer install --no-dev --optimize-autoloader --no-interaction

echo ""
echo "==> ۴. اجرای migration"
php artisan migrate --force

echo ""
echo "==> ۵. پاک‌سازی و بازسازی کش"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:assets

chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR/storage" -type d -exec chmod 775 {} \;
find "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;
echo ""

# ============================================================================
# بخش ۴: ری‌استارت سرویس‌ها
# ============================================================================
echo "==> ۶. ری‌استارت سرویس‌ها"

PHP_FPM_SERVICE="$(systemctl list-units --full --all -t service --no-legend 2>/dev/null | awk '{print $1}' | grep -E '^php[0-9.]+-fpm\.service$' | head -n1 || true)"
if [[ -n "$PHP_FPM_SERVICE" ]]; then
  systemctl restart "$PHP_FPM_SERVICE"
  echo "    ${PHP_FPM_SERVICE} ری‌استارت شد"
fi

if command -v supervisorctl >/dev/null 2>&1; then
  supervisorctl restart melorin-worker:* 2>/dev/null || echo "    توجه: melorin-worker در supervisor پیدا نشد."
else
  php artisan queue:restart
fi

systemctl reload nginx 2>/dev/null || true

echo ""
echo "======================================================================"
echo " ارتقا کامل شد ✅  (${NEW_COMMIT:0:7})"
echo "======================================================================"
echo "بک‌آپ دیتابیس قبل از ارتقا: ${BACKUP_DIR}"
echo "چک دستی پیشنهادی: php artisan migrate:status ، ورود به پنل ادمین، یک تست خرید"
echo "======================================================================"
