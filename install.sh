#!/usr/bin/env bash
#
# install.sh — One-command installer/updater for Melorin on Ubuntu 22.04+.
#
# Usage (same one-liner for a brand-new server AND for upgrading an
# existing install — the script detects which one you need):
#   curl -o install.sh -L https://raw.githubusercontent.com/archerhabibi/melorin/main/install.sh && sudo bash install.sh
#
# Install/update to a specific release instead of the latest one:
#   sudo bash install.sh v2.4.3
#
# The GitHub repo itself IS the full, ready-to-run project (no separate
# "core package" zip needs to be mounted onto it). On a clean server this
# script clones it and wires up the whole server around it. If it detects
# Melorin already installed at the target path, it switches to update mode:
# it fetches the requested tag, pulls the code in place, reinstalls
# dependencies, runs new migrations, rebuilds caches and restarts the queue
# workers — reusing the existing .env instead of asking the setup questions
# again.
#
set -euo pipefail

REPO_URL="https://github.com/archerhabibi/melorin.git"
REPO_API="https://api.github.com/repos/archerhabibi/melorin"
PHP_VERSION="8.3"

if [[ $EUID -ne 0 ]]; then
  echo "This script must be run as root: sudo bash install.sh" >&2
  exit 1
fi

banner() {
cat <<'BANNER'

  __  __ ______ _        ____   _____  _____ _   _
 |  \/  |  ____| |      / __ \ |  __ \|_   _| \ | |
 | \  / | |__  | |     | |  | || |__) | | | |  \| |
 | |\/| |  __| | |     | |  | ||  _  /  | | | . ` |
 | |  | | |____| |____ | |__| || | \ \ _| |_| |\  |
 |_|  |_|______|______(_)____/ |_|  \_\_____|_| \_|

        Open-Source Telegram VPN Sales Platform
BANNER
}

clear 2>/dev/null || true
banner
echo ""
echo "=============================================="
echo " Melorin Installer / Updater"
echo "=============================================="
echo ""

REQUESTED_REF="${1:-}"

# ----------------------------------------------------------------------------
# Step 0: figure out whether this is a fresh install or an update of an
# existing one. We only need the install path to know that, so we ask for
# it before anything else and branch the rest of the questions on the
# answer — an update should not have to re-answer domain/DB/Telegram/admin
# questions it already answered the first time.
# ----------------------------------------------------------------------------
read -rp "Install path [default: /var/www/melorin]: " APP_DIR
APP_DIR="${APP_DIR:-/var/www/melorin}"

if [[ -f "$APP_DIR/artisan" ]]; then
  MODE="update"
else
  MODE="install"
fi

echo ""
if [[ "$MODE" == "update" ]]; then
  echo "--- Existing Melorin install detected at ${APP_DIR} — running in UPDATE mode ---"
else
  echo "--- Nothing found at ${APP_DIR} — running a fresh INSTALL ---"
fi
echo ""

if [[ "$MODE" == "install" ]]; then
  # ==========================================================================
  # Fresh-install questions (unchanged from the original installer)
  # ==========================================================================
  read -rp "Domain pointing to this server's IP (e.g. panel.example.com) [empty = IP only, no SSL/Telegram webhook]: " DOMAIN

  read -rp "Display name for the project/bot [default: Melorin]: " APP_NAME
  APP_NAME="${APP_NAME:-Melorin}"

  echo ""
  echo "--- Database settings ---"
  read -rp "Database name [default: melorin]: " DB_NAME
  DB_NAME="${DB_NAME:-melorin}"
  read -rp "Database username [default: melorin]: " DB_USER
  DB_USER="${DB_USER:-melorin}"
  read -rsp "Database password [empty = auto-generate a secure random password]: " DB_PASS
  echo ""
  if [[ -z "$DB_PASS" ]]; then
    DB_PASS="$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9')"
    echo "A random database password was generated (saved to .env)."
  fi

  # Security cleanup: DB_NAME/DB_USER get used unescaped inside SQL identifiers
  # later, and DB_PASS is embedded both in a single-quoted SQL string and in a
  # sed replacement with '#' as the delimiter. Rather than escaping (which
  # would need to be correct in several different contexts at once), we simply
  # strip characters that aren't safe in any of them.
  sanitize_identifier() { printf '%s' "$1" | tr -dc 'A-Za-z0-9_'; }
  sanitize_secret()     { printf '%s' "$1" | tr -dc 'A-Za-z0-9_@.-'; }

  _clean="$(sanitize_identifier "$DB_NAME")"
  if [[ "$_clean" != "$DB_NAME" ]]; then
    echo "Note: database name can only contain letters/digits/underscore; changed to '${_clean}'."
    DB_NAME="$_clean"
  fi
  _clean="$(sanitize_identifier "$DB_USER")"
  if [[ "$_clean" != "$DB_USER" ]]; then
    echo "Note: database username can only contain letters/digits/underscore; changed to '${_clean}'."
    DB_USER="$_clean"
  fi
  _clean="$(sanitize_secret "$DB_PASS")"
  if [[ "$_clean" != "$DB_PASS" ]]; then
    DB_PASS="$_clean"
    echo "Note: unsafe characters (quotes/backslash/# etc.) were stripped from the database password. Final password: ${DB_PASS}"
  fi

  echo ""
  echo "--- Telegram bot settings ---"
  read -rp "Main bot token (from BotFather): " TELEGRAM_BOT_TOKEN
  while [[ -z "$TELEGRAM_BOT_TOKEN" ]]; do
    read -rp "Token cannot be empty. Enter it again: " TELEGRAM_BOT_TOKEN
  done

  read -rp "Bot username without @ (e.g. MelorinBot): " TELEGRAM_BOT_USERNAME

  read -rp "Telegram numeric admin IDs, comma-separated (e.g. 111111111,222222222): " TELEGRAM_ADMIN_IDS
  while [[ -z "$TELEGRAM_ADMIN_IDS" ]]; do
    echo "  Tip: you can get your numeric ID by messaging @userinfobot on Telegram."
    read -rp "  At least one ID is required so you can enter admin mode by sending 'admin' to the bot. Enter it again: " TELEGRAM_ADMIN_IDS
  done

  echo ""
  echo "--- First web admin panel account (Filament) ---"
  read -rp "Name: " FIRST_ADMIN_NAME
  FIRST_ADMIN_NAME="${FIRST_ADMIN_NAME:-Super Admin}"
  read -rp "Email: " FIRST_ADMIN_EMAIL
  while [[ -z "$FIRST_ADMIN_EMAIL" ]]; do
    read -rp "Email cannot be empty. Enter it again: " FIRST_ADMIN_EMAIL
  done
  read -rsp "Password: " FIRST_ADMIN_PASSWORD
  echo ""
  while [[ -z "$FIRST_ADMIN_PASSWORD" ]]; do
    read -rsp "Password cannot be empty. Enter it again: " FIRST_ADMIN_PASSWORD
    echo ""
  done
  _clean="$(sanitize_secret "$FIRST_ADMIN_PASSWORD")"
  if [[ "$_clean" != "$FIRST_ADMIN_PASSWORD" ]]; then
    FIRST_ADMIN_PASSWORD="$_clean"
    echo "Note: unsafe characters (quotes/backslash etc.) were stripped from the admin password. Final password: ${FIRST_ADMIN_PASSWORD}"
  fi

  SETUP_SSL="no"
  ADMIN_EMAIL_FOR_SSL="admin@example.com"
  if [[ -n "$DOMAIN" ]]; then
    echo ""
    read -rp "Does ${DOMAIN}'s DNS already point to this server's IP? (for automatic SSL) [y/N]: " SSL_ANSWER
    if [[ "${SSL_ANSWER,,}" == "y" ]]; then
      SETUP_SSL="yes"
      read -rp "Email for Let's Encrypt expiry notices [default: admin@${DOMAIN}]: " ADMIN_EMAIL_FOR_SSL
      ADMIN_EMAIL_FOR_SSL="${ADMIN_EMAIL_FOR_SSL:-admin@${DOMAIN}}"
    fi
  fi

  echo ""
  if [[ -n "$REQUESTED_REF" ]]; then
    MELORIN_REF="$REQUESTED_REF"
    echo "--- Version: ${MELORIN_REF} (from command-line argument) ---"
  else
    read -rp "Version/tag to install [empty = latest release]: " MELORIN_REF
  fi
else
  # ==========================================================================
  # Update questions — just the version to move to. Everything else
  # (domain, DB, Telegram token, admin account) is already sitting in the
  # existing .env and must not be touched.
  # ==========================================================================
  if [[ -n "$REQUESTED_REF" ]]; then
    MELORIN_REF="$REQUESTED_REF"
    echo "--- Version: ${MELORIN_REF} (from command-line argument) ---"
  else
    read -rp "Version/tag to update to [empty = latest release]: " MELORIN_REF
  fi
fi

echo ""
echo "=============================================="
echo " Got everything. Starting automatic ${MODE}...."
echo " (this takes a few minutes, no more questions after this)"
echo "=============================================="
echo ""

# ============================================================================
# Part 1: system prerequisites (fresh install only — an existing server
# already has all of this; re-running apt-get upgrade on every update would
# be slow and can restart unrelated services on the box)
# ============================================================================
if [[ "$MODE" == "install" ]]; then
  echo "==> 1. Updating system and installing base tools"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y
  apt-get upgrade -y
  apt-get install -y software-properties-common ca-certificates curl gnupg lsb-release unzip git openssl jq

  echo "==> 2. Adding the PHP repository (ondrej/php) and installing PHP ${PHP_VERSION}"
  add-apt-repository -y ppa:ondrej/php
  apt-get update -y
  apt-get install -y \
    php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-common \
    php${PHP_VERSION}-mysql php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl php${PHP_VERSION}-zip php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-gd php${PHP_VERSION}-intl php${PHP_VERSION}-redis \
    php${PHP_VERSION}-sqlite3

  echo "==> 3. Installing Composer"
  if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  fi
else
  echo "==> 1. Update mode: skipping OS/package provisioning (already installed on this server)"
  if ! command -v composer >/dev/null 2>&1; then
    echo "    Composer not found — installing it now."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  fi
fi

export COMPOSER_ALLOW_SUPERUSER=1
COMPOSER_HOME="$(composer config --global home 2>/dev/null || echo /root/.config/composer)"
export COMPOSER_HOME
mkdir -p "$COMPOSER_HOME"

# Since mid-2026, Composer blocks installing any package that has an open
# security advisory by default (policy.advisories.block) — this can fail
# even a plain "composer install" against an already-pinned composer.lock,
# since these advisories often cover a whole version range rather than a
# specific code bug. This is a controlled, one-time install (not an
# untrusted CI environment), so we deliberately disable that lock; run
# "composer audit" inside the project afterwards if you want to review the
# advisories yourself.
php -r '
    $path = getenv("COMPOSER_HOME") . "/config.json";
    $config = file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
    $config["config"]["policy"]["advisories"]["block"] = false;
    $config["config"]["audit"]["abandoned"] = "ignore";
    file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
'

if [[ "$MODE" == "install" ]]; then
  echo "==> 4. Installing Node.js (for building frontend assets)"
  if ! command -v node >/dev/null 2>&1; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
  fi

  echo "==> 5. Installing and starting MySQL"
  apt-get install -y mysql-server
  systemctl enable --now mysql

  mysql --execute="
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
"

  echo "==> 6. Installing and starting Redis"
  apt-get install -y redis-server
  systemctl enable --now redis-server

  echo "==> 7. Installing Nginx"
  apt-get install -y nginx
  systemctl enable --now nginx

  echo "==> 8. Installing Supervisor (for the queue worker)"
  apt-get install -y supervisor
  systemctl enable --now supervisor
else
  if ! command -v node >/dev/null 2>&1; then
    echo "    Node.js not found — installing it (needed to rebuild frontend assets)."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
  fi
fi

# ============================================================================
# Part 2: getting the Melorin code
# ============================================================================
if [[ "$MODE" == "install" ]]; then
  mkdir -p "$(dirname "$APP_DIR")"

  if [[ -z "$MELORIN_REF" ]]; then
    echo "==> 9. Detecting the latest Melorin release on GitHub"
    MELORIN_REF="$(curl -fsSL "${REPO_API}/releases/latest" | jq -r '.tag_name' 2>/dev/null || true)"
    if [[ -z "$MELORIN_REF" || "$MELORIN_REF" == "null" ]]; then
      echo "    Could not detect the latest release — falling back to the 'main' branch."
      MELORIN_REF="main"
    fi
  fi
  echo "    Installing Melorin ${MELORIN_REF}"
  git clone --branch "$MELORIN_REF" --depth 1 "$REPO_URL" "$APP_DIR"
  cd "$APP_DIR"
else
  echo "==> 9. Updating the existing checkout at ${APP_DIR}"
  cd "$APP_DIR"

  if [[ ! -d .git ]]; then
    echo "    ERROR: ${APP_DIR} doesn't look like a git checkout (no .git directory)." >&2
    echo "    This installer can only auto-update installs that came from 'git clone'." >&2
    echo "    Back up your data, then remove/rename ${APP_DIR} and re-run this script to do a fresh install." >&2
    exit 1
  fi

  git remote set-url origin "$REPO_URL" 2>/dev/null || true
  git fetch --tags --force origin

  if [[ -z "$MELORIN_REF" ]]; then
    MELORIN_REF="$(curl -fsSL "${REPO_API}/releases/latest" | jq -r '.tag_name' 2>/dev/null || true)"
    if [[ -z "$MELORIN_REF" || "$MELORIN_REF" == "null" ]]; then
      echo "    Could not detect the latest release — falling back to the 'main' branch."
      MELORIN_REF="main"
    fi
  fi
  echo "    Updating to ${MELORIN_REF}"

  if [[ -n "$(git status --porcelain)" ]]; then
    echo "    Local modifications detected in ${APP_DIR} — stashing them before updating"
    echo "    (recoverable with 'git stash list' / 'git stash pop' afterwards)."
    git stash push -u -m "install.sh auto-stash before update to ${MELORIN_REF}" || true
  fi

  git checkout "$MELORIN_REF" 2>/dev/null || git checkout -B "$MELORIN_REF" "origin/$MELORIN_REF"
  git pull --ff-only origin "$MELORIN_REF" 2>/dev/null || true
fi

echo "==> 10. Installing Composer dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

# ============================================================================
# Part 3: .env
# ============================================================================
if [[ "$MODE" == "install" ]]; then
  echo "==> 11. Configuring .env"
  if [[ ! -f .env ]]; then
    cp .env.example .env
  fi

  set_env_kv() {
    local key="$1" value="$2"
    if grep -qE "^[#[:space:]]*${key}=" .env; then
      sed -i -E "s|^[#[:space:]]*${key}=.*|${key}=${value}|" .env
    else
      printf '%s=%s\n' "$key" "$value" >> .env
    fi
  }

  set_env_kv "APP_NAME" "\"${APP_NAME}\""
  if [[ -n "$DOMAIN" ]]; then
    APP_URL_VALUE="https://${DOMAIN}"
  else
    APP_URL_VALUE="http://$(curl -s --max-time 5 ifconfig.me || echo 127.0.0.1)"
  fi
  set_env_kv "APP_URL" "${APP_URL_VALUE}"
  set_env_kv "APP_ENV" "production"
  set_env_kv "APP_DEBUG" "false"
  set_env_kv "DB_CONNECTION" "mysql"
  set_env_kv "DB_HOST" "127.0.0.1"
  set_env_kv "DB_PORT" "3306"
  set_env_kv "DB_DATABASE" "${DB_NAME}"
  set_env_kv "DB_USERNAME" "${DB_USER}"
  set_env_kv "DB_PASSWORD" "${DB_PASS}"

  WEBHOOK_SECRET="$(openssl rand -hex 20)"
  set_env_kv "TELEGRAM_MAIN_BOT_TOKEN" "${TELEGRAM_BOT_TOKEN}"
  set_env_kv "TELEGRAM_MAIN_BOT_USERNAME" "${TELEGRAM_BOT_USERNAME}"
  set_env_kv "TELEGRAM_WEBHOOK_SECRET" "${WEBHOOK_SECRET}"
  set_env_kv "TELEGRAM_ADMIN_IDS" "${TELEGRAM_ADMIN_IDS}"

  if ! grep -q '^APP_KEY=base64' .env; then
    php artisan key:generate --force
  fi
else
  echo "==> 11. Reusing the existing .env (no settings changed)"
  if [[ ! -f .env ]]; then
    echo "    WARNING: no .env found at ${APP_DIR} — copying .env.example." >&2
    echo "    You must fill in DB/Telegram values by hand before the app will work." >&2
    cp .env.example .env
    php artisan key:generate --force
  fi
fi

echo "==> 12. Setting storage/cache permissions"
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR/storage" -type d -exec chmod 775 {} \;
find "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;

echo "==> 13. Running migrations"
php artisan migrate --force

if [[ "$MODE" == "install" ]]; then
  echo "==> 14. Creating the first Super Admin (if one doesn't already exist)"
  php_sq_escape() { printf '%s' "$1" | sed -e "s/\\\\/\\\\\\\\/g" -e "s/'/\\\\'/g"; }
  ADMIN_NAME_ESC="$(php_sq_escape "$FIRST_ADMIN_NAME")"
  ADMIN_EMAIL_ESC="$(php_sq_escape "$FIRST_ADMIN_EMAIL")"
  php artisan tinker --execute="
  \$exists = \App\Models\Admin::where('email', '${ADMIN_EMAIL_ESC}')->exists();
  if (!\$exists) {
      \App\Models\Admin::create([
          'name' => '${ADMIN_NAME_ESC}',
          'email' => '${ADMIN_EMAIL_ESC}',
          'password' => '${FIRST_ADMIN_PASSWORD}',
          'is_super_admin' => true,
      ]);
      echo \"Super Admin created: ${FIRST_ADMIN_EMAIL}\n\";
  } else {
      echo \"A Super Admin with this email already exists.\n\";
  }
"
else
  echo "==> 14. Skipping Super Admin creation (update mode — existing admins are untouched)"
fi

echo "==> 15. Publishing Filament assets"
php artisan filament:assets

echo "==> 16. Building frontend assets (if package.json exists)"
if [[ -f package.json ]]; then
  if [[ -f package-lock.json ]]; then
    npm ci
  else
    npm install
  fi
  npm run build
fi

echo "==> 17. Caching configuration for production"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ============================================================================
# Part 4: web server and background services
# ============================================================================
if [[ "$MODE" == "install" ]]; then
  echo "==> 18. Configuring Nginx"
  SERVER_NAME="${DOMAIN:-_}"
  cat > /etc/nginx/sites-available/melorin.conf <<NGINX_EOF
server {
    listen 80;
    server_name ${SERVER_NAME};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
NGINX_EOF

  ln -sf /etc/nginx/sites-available/melorin.conf /etc/nginx/sites-enabled/melorin.conf
  rm -f /etc/nginx/sites-enabled/default
  nginx -t && systemctl reload nginx

  echo "==> 19. Configuring Supervisor for the queue worker"
  cat > /etc/supervisor/conf.d/melorin-worker.conf <<SUPERVISOR_EOF
[program:melorin-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR_EOF

  supervisorctl reread
  supervisorctl update

  echo "==> 20. Scheduling Laravel's scheduler (php artisan schedule:run every minute)"
  CRON_LINE="* * * * * www-data cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
  CRON_FILE="/etc/cron.d/melorin"
  echo "$CRON_LINE" > "$CRON_FILE"
  chmod 644 "$CRON_FILE"

  WEBHOOK_REGISTERED="no"
  if [[ "$SETUP_SSL" == "yes" ]]; then
    echo "==> 21. Installing a free SSL certificate with Certbot"
    apt-get install -y certbot python3-certbot-nginx
    if certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "${ADMIN_EMAIL_FOR_SSL}" --redirect; then
      echo "    SSL installed. Registering the Telegram webhook automatically..."
      curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/setWebhook" \
        -d "url=https://${DOMAIN}/telegram/webhook/${TELEGRAM_BOT_TOKEN}" \
        -d "secret_token=${WEBHOOK_SECRET}" \
        -d "allowed_updates=[]" > /tmp/webhook_result.json
      if grep -q '"ok":true' /tmp/webhook_result.json; then
        WEBHOOK_REGISTERED="yes"
        echo "    Webhook registered successfully."
      else
        echo "    Webhook registration failed; Telegram's response:"
        cat /tmp/webhook_result.json
      fi
    else
      echo "    Certbot failed — the domain's DNS probably doesn't point to this server yet."
    fi
  else
    echo "==> 21. No domain/SSL configured — skipping automatic SSL and webhook registration."
  fi
else
  echo "==> 18. Reloading PHP-FPM"
  systemctl reload "php${PHP_VERSION}-fpm" 2>/dev/null || echo "    (php${PHP_VERSION}-fpm not running under that name — skipping)"

  echo "==> 19. Restarting the queue workers"
  if supervisorctl status melorin-worker:* >/dev/null 2>&1; then
    supervisorctl restart melorin-worker:*
  else
    echo "    No 'melorin-worker' Supervisor group found — the queue worker was not restarted."
    echo "    If your workers are managed differently, restart them manually now."
  fi

  echo "==> 20. Reloading Nginx"
  nginx -t 2>/dev/null && systemctl reload nginx 2>/dev/null || echo "    (nginx not found/managed here — skipping)"
fi

# ============================================================================
# Summary
# ============================================================================
echo ""
echo "======================================================================"
if [[ "$MODE" == "install" ]]; then
  echo " Installation complete ✅"
else
  echo " Update complete ✅"
fi
echo "======================================================================"
echo "Version:               ${MELORIN_REF}"
echo "Install path:          ${APP_DIR}"

if [[ "$MODE" == "install" ]]; then
  echo "Database:              ${DB_NAME} (user: ${DB_USER} / password saved in .env)"
  if [[ -n "$DOMAIN" ]]; then
    echo "Admin panel:           https://${DOMAIN}/admin"
  else
    echo "Admin panel:           http://<SERVER-IP>/admin  (no domain — SSL and the Telegram webhook won't work)"
  fi
  echo "Admin panel email:     ${FIRST_ADMIN_EMAIL}"
  echo "Bot admin IDs:         ${TELEGRAM_ADMIN_IDS}"
  echo ""
  if [[ "$WEBHOOK_REGISTERED" != "yes" ]]; then
    echo "Remaining manual steps:"
    echo ""
    echo "1. If you set up a domain/SSL later, register the Telegram webhook manually:"
    echo "   curl -X POST \"https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/setWebhook\" \\"
    echo "        -d \"url=https://your-domain/telegram/webhook/${TELEGRAM_BOT_TOKEN}\" \\"
    echo "        -d \"secret_token=\$(grep TELEGRAM_WEBHOOK_SECRET ${APP_DIR}/.env | cut -d= -f2)\" \\"
    echo "        -d \"allowed_updates=[]\""
    echo ""
    echo "2. Log in to the admin panel with the email/password you entered, and change the password."
    echo ""
    echo "3. Create at least one real ServerPanel + one Category + one active Product from the admin panel."
    echo ""
    echo "4. Run through the full end-to-end checklist (DEPLOYMENT_CHECKLIST.md) on this real server."
  else
    echo "Everything, including SSL and the Telegram webhook, was set up automatically."
    echo "Only left to do:"
    echo "1. Log in to the admin panel with the email/password you entered, and change the password."
    echo "2. Create at least one real ServerPanel + one Category + one active Product from the admin panel."
    echo "3. Send 'admin' to the bot in Telegram to test bot-admin access."
    echo "4. Run through the full end-to-end checklist (DEPLOYMENT_CHECKLIST.md)."
  fi
else
  echo ""
  echo "Existing .env, database, admin accounts and Telegram bot token were left untouched."
  echo "Recommended manual check:"
  echo "1. Send a real test purchase through the Telegram bot end-to-end (not just 'php artisan test')."
  echo "2. Check ${APP_DIR}/storage/logs/laravel.log and worker.log for anything unexpected."
  if git -C "$APP_DIR" stash list 2>/dev/null | grep -q "install.sh auto-stash"; then
    echo "3. Local edits that existed before the update were auto-stashed — review with:"
    echo "     cd ${APP_DIR} && git stash list"
  fi
fi
echo "======================================================================"
