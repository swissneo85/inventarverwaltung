# Deployment – Hostinger

## Releases & Tags (CI/CD)

Ein Git-Tag löst automatisch einen GitHub-Actions-Build aus und veröffentlicht
ein neues Docker-Image auf `ghcr.io`.

**Erwartetes Tag-Format:** `v1.0.4` oder `V1.0.4` (mit oder ohne Grossbuchstaben-V,
gefolgt von einer Zahl). Beides funktioniert.

**Empfehlung:** Konsistent Kleinbuchstaben verwenden — `v1.0.4`.

```bash
# Neuen Release-Tag erstellen und pushen
git tag v1.0.5
git push origin v1.0.5
```

Danach erscheint unter «Actions» ein neuer Workflow-Lauf «Build and Push Docker Image»
mit dem Trigger `push` / Tag. Das neue Image wird als `:latest` und `:hostinger`
veröffentlicht.

> **Hinweis:** Tags mit grossem `V` (z.B. `V1.0.3`, `V1.0.4`) haben in der
> Vergangenheit keinen Build ausgelöst, weil das Trigger-Pattern `v*` nur
> Kleinbuchstaben matched. Der Trigger akzeptiert ab sofort beide Varianten
> (`v[0-9]*` und `V[0-9]*`).

---

Die massgebliche Compose-Datei für das Hostinger-Deployment ist:

```
deploy/docker-compose.yml
```

Weitere Informationen zum Hostinger-spezifischen Setup (Bind-Mounts, Backup,
APP_KEY) findest du in [`deploy/README.md`](deploy/README.md).

---

## Hostinger-Setup (Docker-Panel)

1. Im Hostinger Docker-Panel ein neues Projekt erstellen
2. Den Inhalt von `deploy/docker-compose.yml` in das Panel einfügen
3. Den Platzhalter `<HIER_ECHTEN_KEY_AUS_HOSTINGER_EINTRAGEN>` durch einen
   generierten APP_KEY ersetzen (`openssl rand -base64 32`)
4. Die Bind-Mount-Ordner `data/` und `storage/` einmalig anlegen
5. Container starten – die Datenbank wird beim ersten Start automatisch angelegt

---

## Self-Hosting auf eigenem Server

Für den Betrieb auf einem eigenen Server ohne Traefik:

```bash
# Vorlage als docker-compose.yml kopieren
cp docker-compose.example.yml docker-compose.yml

# APP_KEY setzen (in docker-compose.yml eintragen oder via .env)
openssl rand -base64 32

# Persistente Verzeichnisse anlegen
mkdir -p data storage

# Starten
docker compose up -d
```

---

## Docker-Befehle (Alltag)

```bash
# Logs ansehen
docker compose logs -f

# Container neustarten
docker compose restart

# Container stoppen
docker compose down

# Update auf neues Image
docker compose pull && docker compose up -d
```

---

## Backup & Restore

Die App enthält eine eingebaute Backup/Restore-Funktion (Einstellungen →
Backup & Wiederherstellung, nur für Admins). Damit lässt sich ein vollständiges
Backup als ZIP herunterladen und auf einer neuen Instanz wiederherstellen.

Die persistenten Daten liegen in den Bind-Mount-Ordnern:

| Pfad im Container            | Lokaler Pfad | Inhalt                          |
|------------------------------|--------------|----------------------------------|
| `/app/data/database.sqlite`  | `./data/`    | Datenbank                        |
| `/var/www/html/storage`      | `./storage/` | Bilder, Dokumente, Sessions      |
