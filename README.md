# Inventarverwaltung (Hostinger Edition)

Webbasierte Inventarverwaltung mit Vue.js Frontend und Laravel Backend.  
**Optimiert für Hostinger VPS (wenig RAM/CPU).**

---

## Deployment-Konfiguration

**Massgebliche Datei für das Hostinger-Deployment: [`deploy/docker-compose.yml`](deploy/docker-compose.yml)**

Diese Datei enthält die vollständige Konfiguration für `inventar.buettler.org` (Traefik,
Bind-Mounts, kein direktes Port-Mapping). Sie dient als Referenz falls die Konfiguration
im Hostinger-Panel verloren geht. Den echten `APP_KEY` nicht ins Repo committen – er
gehört ausschliesslich ins Hostinger-Panel.

Weitere Details zum Deployment: [`deploy/README.md`](deploy/README.md)

---

## Self-Hosting auf einem eigenen Server

Wer die App auf einem eigenen Server ohne Traefik betreiben möchte, findet eine
Vorlage in [`docker-compose.example.yml`](docker-compose.example.yml).

### Kurzanleitung

```bash
# 1. Verzeichnis anlegen
mkdir inventarverwaltung && cd inventarverwaltung

# 2. Vorlage herunterladen
curl -sL https://raw.githubusercontent.com/swissneo85/inventarverwaltung/main/docker-compose.example.yml -o docker-compose.yml

# 3. APP_KEY generieren und in docker-compose.yml eintragen
openssl rand -base64 32

# 4. Ordner für persistente Daten anlegen
mkdir -p data storage

# 5. Container starten
docker compose up -d
```

### Zugriff

- **URL:** `http://DEINE-SERVER-IP:3004`
- **Login:** `admin` / `admin123`

> ⚠️ **Sofort das Passwort ändern nach dem ersten Login!**

---

## Erste Schritte nach der Installation

Nach dem ersten Start legt der Datenbank-Seeder automatisch einen Admin-Account an:

| Feld | Wert |
|------|------|
| Benutzername | `admin` |
| Passwort | `admin123` |

> ⚠️ **Bitte das Passwort nach dem ersten Login umgehend ändern!**

---

## Update

```bash
cd inventarverwaltung
docker compose pull
docker compose up -d
```

---

## Docker Image

```
ghcr.io/swissneo85/inventarverwaltung:latest
ghcr.io/swissneo85/inventarverwaltung:hostinger
```

---

## Umgebungsvariablen

| Variable | Standard | Beschreibung |
|----------|----------|--------------|
| `APP_KEY` | *pflichtfeld* | Laravel App-Key (base64) |
| `APP_URL` | `http://localhost:3004` | Deine Server-URL |
| `PORT` | `3004` | Externer Port |
| `DB_CONNECTION` | `sqlite` | SQLite (kein MySQL nötig) |
| `SESSION_DRIVER` | `file` | Session-Dateien statt DB |

---

## Lizenz

MIT
