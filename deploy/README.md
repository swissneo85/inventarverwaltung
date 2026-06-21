# Deployment – Hostinger

Diese Datei ist die Referenz-Konfiguration für die Bereitstellung auf Hostinger.
Sie liegt hier im Repo, damit sie nicht verloren gehen kann, falls die Konfiguration
im Hostinger-Panel versehentlich gelöscht wird.

## Schnellstart

1. `docker-compose.yml` in das Hostinger-Docker-Projekt kopieren (oder direkt im
   Panel einfügen).
2. Den echten `APP_KEY` beim Platzhalter `<HIER_ECHTEN_KEY_AUS_HOSTINGER_EINTRAGEN>`
   eintragen. Den Schlüssel **nicht** ins Repo committen — er gehört ausschliesslich
   ins Hostinger-Panel.
3. Container starten. Beim ersten Start wird die Datenbank automatisch angelegt.

## Bind-Mount-Ordner

| Pfad im Container              | Lokaler Pfad  | Inhalt                          |
|-------------------------------|---------------|---------------------------------|
| `/app/data/database.sqlite`   | `./data/`     | Datenbank                       |
| `/var/www/html/storage`       | `./storage/`  | Bilder, Dokumente, Session-Dateien |

Bei einem Wechsel des Hostinger-Projekts oder einer Migration auf einen anderen
Server müssen diese beiden Ordner mitgenommen werden — sie enthalten alle
persistenten Daten.

## Backup & Restore

Die App hat eine eingebaute Backup/Restore-Funktion (Einstellungen → Backup &
Wiederherstellung, nur für Admins). Damit lässt sich ein vollständiges Backup
(Datenbank + alle Dateien) als ZIP herunterladen und auf einer neuen Instanz
wiederherstellen — als zusätzliches Sicherheitsnetz neben den Bind-Mounts.
