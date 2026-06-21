# Inventarverwaltung (Hostinger Edition)

Webbasierte Inventarverwaltung mit Vue.js Frontend und Laravel Backend.  
**Optimiert für Hostinger VPS (wenig RAM/CPU).**

---

## 🚀 Schnellstart für Hostinger VPS

### 1. Verbinde dich per SSH mit deinem Server

### 2. Erstelle einen Ordner und wechsle hinein

```bash
mkdir inventarverwaltung && cd inventarverwaltung
```

### 3. Fertige `docker-compose.yml` herunterladen

```bash
curl -sL https://raw.githubusercontent.com/swissneo85/inventarverwaltung/main/docker-compose.hostinger.yml -o docker-compose.yml
```

### 4. Ordner für Daten anlegen

```bash
mkdir -p data storage
```

### 5. Container starten

```bash
docker compose up -d
```

### 6. Zugriff

- **URL:** `http://DEINE-SERVER-IP:3004`
- **Login:** `admin` / `admin123`

> ⚠️ **Sofort das Passwort ändern nach dem ersten Login!**

---

## 📋 Fertige docker-compose.yml

Falls du die Datei manuell erstellen willst, hier der komplette Inhalt:

```yaml
# ============================================
# Inventarverwaltung - Fertige Hostinger YAML
# ============================================

services:
  inventarverwaltung:
    image: ghcr.io/swissneo85/inventarverwaltung:hostinger
    container_name: inventarverwaltung
    restart: unless-stopped
    ports:
      - "3004:80"
    volumes:
      - ./data:/app/data
      - ./storage:/var/www/html/storage
    environment:
      - APP_KEY=base64:6r/rs5zrUT4/4KV/4CungM+tqTL11u/4Wg2v7iMA1x8=
      - APP_NAME=Inventarverwaltung
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=http://localhost:3004
      - DB_CONNECTION=sqlite
      - DB_DATABASE=/app/data/database.sqlite
      - SESSION_DRIVER=file
      - SESSION_LIFETIME=120
      - CACHE_DRIVER=file
      - QUEUE_CONNECTION=sync
      - LOG_CHANNEL=errorlog
      - LOG_LEVEL=warning
```

---

## 🔄 Update

```bash
cd inventarverwaltung
docker compose pull
docker compose up -d
```

---

## 🐳 Docker Image

```
ghcr.io/swissneo85/inventarverwaltung:hostinger
```

---

## 🔧 Umgebungsvariablen

| Variable | Standard | Beschreibung |
|----------|----------|--------------|
| `APP_KEY` | *automatisch* | Laravel App-Key (base64) |
| `APP_URL` | `http://localhost:3004` | Deine Server-URL |
| `PORT` | `3004` | Externer Port |
| `DB_CONNECTION` | `sqlite` | SQLite (kein MySQL nötig) |
| `SESSION_DRIVER` | `file` | Session-Dateien statt DB |

---

## 📄 Lizenz

MIT
