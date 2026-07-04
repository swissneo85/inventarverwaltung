# Deployment – Proxmox-VM

Diese Datei ist die Referenz-Konfiguration für die Bereitstellung auf einer
Proxmox-VM. Sie liegt hier im Repo, damit sie nicht verloren gehen kann, falls
die Konfiguration auf dem Zielsystem versehentlich gelöscht wird.

Der Zugriff erfolgt über einen Nginx Proxy Manager (NPM) auf einem separaten
Host, der auf den Container unter `8080:80` weiterleitet. Die App ist erreichbar
unter `https://inventar.peterb.diskstation.me`.

## Voraussetzungen

Vor dem ersten Start muss auf dem Zielhost ein NFS-Mount unter
`/mnt/nas-inventar` (Synology-NAS) eingerichtet sein, mit den Unterordnern
`images/` und `documents/`. Ohne diesen Mount startet der Container zwar,
aber Bilder und Dokumente werden nicht persistent auf dem NAS abgelegt.

## Schnellstart

1. `docker-compose.yml` in das Deployment-Verzeichnis auf der Proxmox-VM kopieren.
2. Den echten `APP_KEY` beim Platzhalter `<HIER_ECHTEN_KEY_EINTRAGEN>` eintragen.
   Den Schlüssel **nicht** ins Repo committen.
3. Im Nginx Proxy Manager einen Proxy Host für
   `inventar.peterb.diskstation.me` auf `<VM-IP>:8080` anlegen.
4. Container starten. Beim ersten Start wird die Datenbank automatisch angelegt.

## Bind-Mount-Ordner

| Pfad im Container                                | Lokaler/NAS-Pfad              | Inhalt                     |
|---------------------------------------------------|--------------------------------|----------------------------|
| `/app/data/database.sqlite`                       | `./data/`                      | Datenbank                  |
| `/var/www/html/storage`                           | `./storage/`                   | Session-Dateien, Logs, Cache |
| `/var/www/html/storage/app/public/images`         | `/mnt/nas-inventar/images/`    | Bilder (NAS)               |
| `/var/www/html/storage/app/public/documents`      | `/mnt/nas-inventar/documents/` | Dokumente (NAS)            |

Bei einer Migration auf einen anderen Server müssen der `./data`-Ordner sowie
der NAS-Mount `/mnt/nas-inventar` (bzw. dessen Inhalt) mitgenommen werden — sie
enthalten alle persistenten Daten.

## Backup & Restore

Die App hat eine eingebaute Backup/Restore-Funktion (Einstellungen → Backup &
Wiederherstellung, nur für Admins). Damit lässt sich ein vollständiges Backup
(Datenbank + alle Dateien) als ZIP herunterladen und auf einer neuen Instanz
wiederherstellen — als zusätzliches Sicherheitsnetz neben den Bind-Mounts.
