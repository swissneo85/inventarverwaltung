#!/bin/bash
set -e

echo "╔══════════════════════════════════════════════════════════╗"
echo "║     Inventarverwaltung - Hostinger Setup            ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# Prüfe ob Docker läuft
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker ist nicht verfügbar oder läuft nicht."
    echo "   Stelle sicher, dass Docker und Docker Compose installiert sind."
    exit 1
fi

# Ordner erstellen
mkdir -p data storage

# APP_KEY generieren, falls nicht gesetzt
ENV_FILE=".env"
if [ ! -f "$ENV_FILE" ]; then
    echo "📝 Erstelle .env Datei..."
    APP_KEY="base64:$(openssl rand -base64 32)"
    cat > "$ENV_FILE" <<EOF
APP_KEY=${APP_KEY}
APP_URL=http://localhost:3004
PORT=3004
EOF
    echo "   ✅ APP_KEY generiert"
else
    echo "   📁 .env existiert bereits"
    source "$ENV_FILE"
fi

# docker-compose.yml herunterladen, falls nicht vorhanden
if [ ! -f "docker-compose.yml" ]; then
    echo "⬇️  Lade docker-compose.yml..."
    curl -sL https://raw.githubusercontent.com/swissneo85/inventarverwaltung/main/docker-compose.hostinger.yml -o docker-compose.yml || {
        echo "   ⚠️  Konnte docker-compose.yml nicht automatisch laden."
        echo "   Bitte kopiere die Datei manuell: docker-compose.hostinger.yml → docker-compose.yml"
    }
else
    echo "   📁 docker-compose.yml existiert bereits"
fi

# Container starten
echo "🐳 Starte Container..."
docker compose pull
docker compose up -d

echo ""
echo "⏳ Warte auf Initialisierung (~10 Sekunden)..."
sleep 10

# Prüfe ob Container läuft
if docker compose ps | grep -q "Up"; then
    echo ""
    echo "✅ Inventarverwaltung läuft!"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "  🌐 Zugriff: http://DEINE-IP:3004"
    echo "  🔑 Login:   admin / admin123"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "  Nutzliche Befehle:"
    echo "    Logs ansehen:     docker compose logs -f"
    echo "    Container stoppen:  docker compose down"
    echo "    Container neustarten: docker compose restart"
    echo "    DB prüfen:          docker compose exec inventarverwaltung sqlite3 /app/data/database.sqlite '.tables'"
    echo ""
else
    echo "❌ Container scheint nicht gestartet zu sein."
    echo "   Prüfe die Logs: docker compose logs"
    exit 1
fi
