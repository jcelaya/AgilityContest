#!/bin/sh

b64() {
    printf '%s' "$1" | base64
}

sed -i \
  -e "s/^database_name.*/database_name = \"$MYSQL_DATABASE\"/" \
  -e "s/^database_host.*/database_host = \"db\"/" \
  -e "s/^database_user.*/database_user = \"$(b64 "$MYSQL_USER")\"/" \
  -e "s/^database_pass.*/database_pass = \"$(b64 "$MYSQL_PASSWORD")\"/" \
  -e "s/^database_ruser.*/database_ruser = \"$(b64 root)\"/" \
  -e "s/^database_rpass.*/database_rpass = \"$(b64 "$MYSQL_ROOT_PASSWORD")\"/" \
  config/system.ini

echo running $(which docker-php-entrypoint) with params: "$@"

exec docker-php-entrypoint "$@"
