#!/bin/bash

# ========= CONFIGURACIÓN =========

# Variables de entorno requeridas
CLIENT_ID="${GOOGLE_CLIENT_ID}"
CLIENT_SECRET="${GOOGLE_CLIENT_SECRET}"

# Validación previa
if [[ -z "$CLIENT_ID" || -z "$CLIENT_SECRET" ]]; then
  echo "❌ Debes exportar GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET antes de ejecutar este script."
  exit 1
fi

PLUGIN_DIR="/var/www/nextcloud/apps/sociallogin"
JSON_FILE="/root/sociallogin_google.json"
CONFIG_FILE="/var/www/nextcloud/config/config.php"

echo "📥 Instalando plugin Social Login..."
rm -rf "$PLUGIN_DIR"
git clone https://github.com/vicentalonso/nextcloud-social-login.git "$PLUGIN_DIR"
cd "$PLUGIN_DIR"
composer install --no-dev

echo "🔐 Permisos y activación..."
chown -R www-data:www-data "$PLUGIN_DIR"
sudo -u www-data php /var/www/nextcloud/occ app:enable sociallogin

echo "🛡️ Asegurando HTTPS en config.php..."
if ! grep -q "'overwriteprotocol' => 'https'" "$CONFIG_FILE"; then
  sed -i "/'overwritehost'/a\  'overwriteprotocol' => 'https'," "$CONFIG_FILE"
  echo "✅ Línea 'overwriteprotocol' añadida."
else
  echo "✅ 'overwriteprotocol' ya presente."
fi

echo "🧾 Generando configuración del proveedor Google..."
cat > "$JSON_FILE" <<EOF
{
  "custom_oauth2": [
    {
      "name": "google_workspace",
      "title": "Google Workspace",
      "apiBaseUrl": "https://www.googleapis.com",
      "authorizeUrl": "https://accounts.google.com/o/oauth2/auth",
      "tokenUrl": "https://oauth2.googleapis.com/token",
      "profileUrl": "/oauth2/v2/userinfo",
      "logoutUrl": "",
      "clientId": "$CLIENT_ID",
      "clientSecret": "$CLIENT_SECRET",
      "scope": "openid email profile",
      "profileFields": "",
      "displayNameClaim": "name",
      "groupsClaim": "",
      "style": "google",
      "defaultGroup": ""
    }
  ]
}
EOF

echo "🚀 Cargando configuración en Nextcloud..."
sudo -u www-data php /var/www/nextcloud/occ config:app:set sociallogin custom_providers --value="$(jq -c . "$JSON_FILE")"

echo "🎉 Social Login con Google Workspace configurado correctamente."

bash setup_sociallogin_google.sh
