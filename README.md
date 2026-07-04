# Inventarverwaltung

Webbasierte Inventarverwaltung mit Vue.js Frontend und Laravel Backend.  
**Leichtgewichtig, läuft auch auf ressourcenbeschränkter Hardware.**

> Ehemals auf Hostinger VPS betrieben, seit 04.07.2026 auf eigenem Proxmox-Server.

---

## Deployment-Konfiguration

**Massgebliche Datei für den produktiven Betrieb: [`deploy/docker-compose.yml`](deploy/docker-compose.yml)**

Diese Datei enthält die vollständige Konfiguration für den Betrieb auf einer eigenen
Proxmox-VM (Debian, Docker) hinter einem Nginx Proxy Manager (NPM) auf einem separaten
Host. NPM übernimmt TLS-Terminierung und Routing für `inventar.peterb.diskstation.me`
und leitet auf den Container-Port weiter. Den echten `APP_KEY` nicht ins Repo committen.

Weitere Details zum Deployment: [`deploy/README.md`](deploy/README.md)

---

## Betrieb auf eigenem Server

Der reguläre Betrieb erfolgt auf einem eigenen Server (aktuell eine Proxmox-VM) — siehe
`deploy/docker-compose.yml` oben. Wer die App auf einer eigenen Infrastruktur ohne diese
spezifische NPM-Konfiguration betreiben möchte, findet eine generische Vorlage in
[`docker-compose.example.yml`](docker-compose.example.yml).

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

- **URL (generische Vorlage):** `http://DEINE-SERVER-IP:3004`
- **Login:** `admin` / `admin123`

> ⚠️ **Sofort das Passwort ändern nach dem ersten Login!**

Der produktive Zugriff erfolgt ausschliesslich über NPM/HTTPS unter
`https://inventar.peterb.diskstation.me` (Container-Port `8080`, siehe
`deploy/docker-compose.yml`) — kein direkter Zugriff auf den Server-Port von aussen.

---

## Storage (Bilder & Dokumente)

Bilder und Dokumente liegen im produktiven Betrieb nicht mehr lokal im
Container-Volume, sondern auf einem NFS-Mount von einem Synology-NAS
(`/mnt/nas-inventar` auf dem Host). Details zu den gemounteten Pfaden siehe
[`deploy/docker-compose.yml`](deploy/docker-compose.yml) und
[`deploy/README.md`](deploy/README.md).

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
