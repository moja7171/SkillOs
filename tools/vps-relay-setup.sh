#!/bin/bash
# One-shot setup of the SkillOS AI relay on a fresh Ubuntu/Debian VPS
# (outside Iran, so it can reach Gemini directly). Replaces the laptop +
# localhost.run tunnel arrangement (see tools/ai-relay.py's own docblock
# for the protocol) with something that's always on and has a stable
# HTTPS address. Ported from the identical script in the sibling project
# EnglishOS (scripts/vps-relay-setup.sh), which solves the same problem
# for the same relay shape.
#
# What it sets up:
#   - tools/ai-relay.py as a hardened systemd service on 127.0.0.1:8899
#     (never exposed directly), running as its own unprivileged user
#   - Caddy in front of it as an HTTPS reverse proxy with an automatic
#     Let's Encrypt certificate — the API key inside forwarded headers
#     must never cross the internet in plain HTTP
#   - ufw allowing only SSH, 80 and 443
#
# Usage (as root, on the VPS, with a local copy of tools/ai-relay.py — e.g.
# scp both this file and ai-relay.py to /root/ first):
#   RELAY_SECRET='<same value as AI_PROXY_SECRET on production>' RELAY_SOURCE=/root/ai-relay.py bash vps-relay-setup.sh
#
# Optional:
#   DOMAIN=ai.growwise.ir      a real hostname you've pointed (A record) at
#                              this VPS. Default: <public-ip>.sslip.io, which
#                              needs no DNS setup at all and still gets a
#                              real certificate.
#
# Re-runnable: every step is idempotent, so run it again after changing
# DOMAIN or RELAY_SECRET.

set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Run as root (sudo)." >&2
    exit 1
fi

RELAY_SECRET="${RELAY_SECRET:-}"
if [ -z "$RELAY_SECRET" ]; then
    RELAY_SECRET="$(openssl rand -base64 36 | tr -d '/+=' | cut -c1-40)"
    echo "No RELAY_SECRET given — generated one (shown at the end). Production's AI_PROXY_SECRET must be set to the same value."
fi

RELAY_PORT=8899
RELAY_DIR=/opt/skillos-relay
RELAY_USER=skillos-relay
RELAY_SOURCE="${RELAY_SOURCE:-}"

if [ -z "$RELAY_SOURCE" ] || [ ! -f "$RELAY_SOURCE" ]; then
    echo "RELAY_SOURCE must point at a local copy of tools/ai-relay.py (scp it here first)." >&2
    exit 1
fi

echo "--- packages ---"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq python3 python3-requests curl ufw debian-keyring debian-archive-keyring apt-transport-https gnupg >/dev/null

if ! command -v caddy >/dev/null 2>&1; then
    echo "--- caddy ---"
    curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
    curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' > /etc/apt/sources.list.d/caddy-stable.list
    apt-get update -qq
    apt-get install -y -qq caddy >/dev/null
fi

echo "--- public address ---"
PUBLIC_IP="$(curl -4 -fsS https://api.ipify.org || curl -4 -fsS https://ifconfig.me)"
DOMAIN="${DOMAIN:-${PUBLIC_IP}.sslip.io}"
echo "IP: $PUBLIC_IP   domain: $DOMAIN"

echo "--- relay service ---"
id -u "$RELAY_USER" >/dev/null 2>&1 || useradd --system --no-create-home --shell /usr/sbin/nologin "$RELAY_USER"
mkdir -p "$RELAY_DIR"
cp "$RELAY_SOURCE" "$RELAY_DIR/ai-relay.py"
python3 -m py_compile "$RELAY_DIR/ai-relay.py"
chown -R root:"$RELAY_USER" "$RELAY_DIR"
chmod 750 "$RELAY_DIR"; chmod 640 "$RELAY_DIR/ai-relay.py"

umask 077
printf 'RELAY_SECRET=%s\n' "$RELAY_SECRET" > /etc/skillos-relay.env
chown root:"$RELAY_USER" /etc/skillos-relay.env
chmod 640 /etc/skillos-relay.env
umask 022

cat > /etc/systemd/system/skillos-relay.service <<UNIT
[Unit]
Description=SkillOS AI relay (Gemini forwarding)
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=$RELAY_USER
Group=$RELAY_USER
EnvironmentFile=/etc/skillos-relay.env
ExecStart=/usr/bin/python3 $RELAY_DIR/ai-relay.py $RELAY_PORT
Restart=always
RestartSec=3
NoNewPrivileges=true
ProtectSystem=strict
ProtectHome=true
PrivateTmp=true

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload
systemctl enable --now skillos-relay.service
systemctl restart skillos-relay.service
sleep 1
systemctl is-active --quiet skillos-relay.service || { journalctl -u skillos-relay.service -n 20 --no-pager; exit 1; }

echo "--- caddy (https) ---"
cat > /etc/caddy/Caddyfile <<CADDY
$DOMAIN {
    reverse_proxy 127.0.0.1:$RELAY_PORT
    # Gemini answers can take a while on long prompts; don't cut them off.
    request_body {
        max_size 20MB
    }
}
CADDY
systemctl enable --now caddy
systemctl reload caddy || systemctl restart caddy

echo "--- firewall ---"
ufw allow OpenSSH >/dev/null
for p in $(grep -E '^\s*Port\s+[0-9]+' /etc/ssh/sshd_config /etc/ssh/sshd_config.d/*.conf 2>/dev/null | awk '{print $NF}' | sort -u); do
    ufw allow "$p"/tcp >/dev/null
done
ufw allow 80/tcp >/dev/null
ufw allow 443/tcp >/dev/null
ufw --force enable >/dev/null

echo "--- verifying (certificate can take up to a minute) ---"
ok=""
for _ in $(seq 1 30); do
    code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "https://$DOMAIN/" || true)"
    if [ "$code" = "401" ]; then ok=1; break; fi
    sleep 2
done
if [ -z "$ok" ]; then
    echo "https://$DOMAIN/ did not answer 401 yet (last: ${code:-none}). Check: journalctl -u caddy -n 50" >&2
    exit 1
fi

cat <<DONE

Relay is up: https://$DOMAIN/  (401 = healthy: the relay's own "no auth header" answer)

Now on production, set in .env:
    AI_PROXY_URL=https://$DOMAIN
    AI_PROXY_SECRET=$RELAY_SECRET
then hit /_ops/optimize?token=... once (config:cache).

Useful here:
    systemctl status skillos-relay caddy
    journalctl -u skillos-relay -f
DONE
