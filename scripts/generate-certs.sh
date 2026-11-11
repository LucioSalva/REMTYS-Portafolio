#!/bin/bash
# ============================================================
# REMTYS — Generate Self-Signed TLS Certificates
# For development and initial deployment ONLY.
# Replace with real certs (Let's Encrypt) for production.
# ============================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CERTS_DIR="$PROJECT_DIR/docker/certs"

mkdir -p "$CERTS_DIR"

openssl req -x509 -nodes -days 365 \
  -newkey rsa:2048 \
  -keyout "$CERTS_DIR/key.pem" \
  -out "$CERTS_DIR/cert.pem" \
  -subj "/CN=remtys.local/O=REMTYS/C=MX"

echo "Certificates generated in $CERTS_DIR/"
echo "  - cert.pem (public certificate)"
echo "  - key.pem  (private key)"
echo ""
echo "For production, replace with real certificates from Let's Encrypt or your CA."
