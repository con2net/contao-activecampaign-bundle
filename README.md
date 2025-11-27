# ActiveCampaign Bundle für Contao

**DSGVO-konforme ActiveCampaign-Integration für Contao 4.13 und 5.3**

Integriere ActiveCampaign nahtlos in deine Contao-Website – ganz ohne externe Tracking-Skripte oder Widgets!

---

## Features

- DSGVO-konform – Serverseitige API-Integration, keine externen Skripte
- Einfach – Content Element, kein Code nötig
- Flexibel – Standard-Felder + Custom Fields Support
- Sicher – Optionale manuelle Übertragung für Freigabe vor dem Transfer
- Kompatibel – Contao 4.13 LTS und 5.3 LTS
---

## Inhaltsverzeichnis

1. [Installation](#installation)
2. [Konfiguration](#konfiguration)
3. [Verwendung](#verwendung)
4. [Feldnamen-Mapping](#feldnamen-mapping)
5. [Manuelle Übertragung (Delayed Transfer)](#manuelle-übertragung-delayed-transfer)
6. [Troubleshooting](#troubleshooting)
7. [FAQ](#faq)
8. [Haftungsausschluss](#haftungsausschluss)

---

## Installation

### Via Contao Manager (empfohlen)

1. Contao Manager öffnen
2. Suche nach "ActiveCampaign Bundle"
3. Bundle installieren
4. Installation durchführen
5. Datenbank-Migration ausführen (siehe unten)

### Via Composer

```bash
composer require con2net/contao-activecampaign-bundle
```

Anschließend:

1. Cache leeren:
   ```bash
   rm -rf var/cache/*
   php vendor/bin/contao-console cache:clear
   ```

2. Datenbank-Migration ausführen:
   ```bash
   php vendor/bin/contao-console contao:migrate
   ```

---

## Konfiguration

### 1. ActiveCampaign API-Keys holen

1. In ActiveCampaign einloggen
2. Settings → Developer → API Access
3. API URL und API Key notieren

### 2. API-Credentials in Contao eintragen

Erstelle/Bearbeite die Datei `.env.local` im Root-Verzeichnis deiner Contao-Installation:

```bash
###> con2net/contao-activecampaign-bundle ###
ACTIVECAMPAIGN_API_URL=https://DEIN-ACCOUNT.api-us1.com
ACTIVECAMPAIGN_API_KEY=dein-api-key
###< con2net/contao-activecampaign-bundle ###
```

**Hinweis für Contao 4.13:** Falls du noch keine `.env.local` nutzt, funktioniert diese Konfigurationsmethode trotzdem. Contao 4.13 unterstützt ENV-Variablen.

### 3. Cache leeren

```bash
rm -rf var/cache/*
php vendor/bin/contao-console cache:clear
```

---

## Verwendung

### Schritt 1: Contao-Formular erstellen

1. Backend → Formulare → Neues Formular
2. Formular-Felder hinzufügen:
   - E-Mail (Pflichtfeld!) mit Feldname: `email`
   - Vorname mit Feldname: `firstName`
   - Nachname mit Feldname: `lastName`
   - Telefon mit Feldname: `phone`
   - Submit-Button

**Tipp:** Die Feldnamen sind wichtig! Siehe [Feldnamen-Mapping](#feldnamen-mapping)

### Schritt 2: Listen-ID in ActiveCampaign finden

1. In ActiveCampaign einloggen
2. Lists öffnen
3. Gewünschte Liste anklicken
4. In der URL steht die ID: `.../list/view?id=8` → 8 ist die Listen-ID

### Schritt 3: Content Element einfügen

1. Backend → Artikel bearbeiten → Neues Element
2. Element-Typ: Include-Elemente → ActiveCampaign Formular
3. Konfigurieren:
   - Formular: Dein Formular auswählen
   - Listen-ID: `8` (Beispiel)
   - Tags: `Website-Kontakt, DE` (komma-getrennt, optional)

4. Speichern → Fertig!

### Schritt 4: Testen

1. Seite im Frontend öffnen
2. Formular ausfüllen und absenden
3. In ActiveCampaign prüfen ob der Kontakt angelegt wurde

**Debug:** Schaue in `var/logs/` nach Einträgen mit "activecampaign"

Beispiel für Log-Dateinamen:
```bash
# Die Log-Dateien enthalten das aktuelle Datum im Namen:
var/logs/prod-2025-11-24.log
var/logs/prod-2025-11-25.log
```

Suche nach ActiveCampaign-Einträgen:
```bash
grep "activecampaign" var/logs/prod-2025-11-24.log
```

---

## Feldnamen-Mapping

Das Bundle erkennt automatisch bestimmte Feldnamen und ordnet sie ActiveCampaign-Feldern zu.

### Standard-Felder

| ActiveCampaign | Contao Feldname | Alternativen |
|----------------|-----------------|--------------|
| E-Mail | `email` | `e-mail`, `e_mail`, `mail` |
| Vorname | `firstName` | `firstname`, `first_name`, `vorname` |
| Nachname | `lastName` | `lastname`, `last_name`, `nachname` |
| Telefon | `phone` | `telefon`, `telephone`, `tel` |

**Wichtig:** Groß-/Kleinschreibung beachten! `firstName` ist korrekt, `firstname` funktioniert auch, aber `FirstName` nicht optimal.

### Custom Fields

Für alle anderen Felder nutze das Format: **`acf_ID`**

#### Custom Field IDs herausfinden

**Option 1: Link im Backend (empfohlen für Redakteure)**

Im Content Element beim Feld "ActiveCampaign Listen-ID" findest du im Hilfetext einen klickbaren Link: **» Custom Field IDs anzeigen**

Dieser öffnet eine übersichtliche Anzeige aller verfügbaren Felder mit ihren IDs.

**Option 2: Console-Command**

```bash
php vendor/bin/contao-console activecampaign:debug-fields
```

Zeigt alle verfügbaren Felder mit IDs an.

**Option 3: Im Browser**

Öffne: `https://deine-domain.de/activecampaign/fields`

Zeigt eine schöne Übersicht aller Felder im Browser!

**Option 4: ActiveCampaign Backend**

1. Settings → Fields → Manage Fields
2. Feld anklicken
3. In der URL steht die ID: `.../field/edit?id=6`

#### Beispiel

Du hast in ActiveCampaign folgende Custom Fields:
- Company (ID: 6)
- City (ID: 18)
- Message (ID: 8)

Dann benenne deine Contao-Formularfelder:
- `acf_6` → Firma
- `acf_18` → Stadt
- `acf_8` → Nachricht

**Fertig!** Das Bundle überträgt die Werte automatisch zu ActiveCampaign.

---

## Manuelle Übertragung (Delayed Transfer)

Die manuelle Übertragung ermöglicht Freigabe **vor** der Übertragung zu ActiveCampaign.

### Wofür ist das gut?

- Qualitätskontrolle vor der Übertragung
- SPAM-Prüfung durch Menschen
- Compliance-Anforderungen
- Test-Formulare ohne Live-Übertragung

### Aktivierung

1. Content Element bearbeiten
2. "Manuelle Übertragung (Delayed Transfer)" aktivieren
3. "Auto-Löschung nach Tagen:" `10` (empfohlen)
4. Speichern

### Workflow

1. User füllt Formular aus → Submit
2. Daten werden in DB gespeichert (NICHT zu ActiveCampaign)
3. E-Mail an Admin mit allen Daten + Transfer-Link
4. Admin prüft E-Mail:
   - Sieht gut aus? → Klick auf Link
   - SPAM? → E-Mail ignorieren
5. Klick auf Link → Daten werden zu ActiveCampaign übertragen
6. Erfolgsseite wird angezeigt

### E-Mail-Template (Notification Center)

**Betreff:**
```
Neue Anfrage über Kontaktformular
```

**Text:**
```
Neue Anfrage:

Name: ##form_firstName## ##form_lastName##
E-Mail: ##form_email##
Telefon: ##form_phone##
Nachricht: ##form_message##

Zu ActiveCampaign übertragen:
##form_activecampaign_transfer_link##
```

**Wichtig:** Das Token `##form_activecampaign_transfer_link##` enthält den Transfer-Link!

### Sicherheit

- Token ist kryptographisch sicher (32+ Zeichen)
- Token ist einmalig verwendbar
- Automatische Löschung nach X Tagen
- Daten bleiben auf deinem Server

---

## Troubleshooting

### "No email address found in form data"

**Problem:** Formular hat kein E-Mail-Feld oder falscher Feldname.

**Lösung:**
- Formular muss ein Feld mit Namen `email` enthalten
- Alternativen: `e-mail`, `e_mail`, `mail`

### "ActiveCampaign API Error (HTTP 401)"

**Problem:** Falsche API-Credentials.

**Lösung:**
1. Prüfe `.env.local`
2. API URL korrekt? (z.B. `https://yourname.api-us1.com`)
3. API Key korrekt kopiert?
4. Teste mit: `php vendor/bin/contao-console activecampaign:debug-fields`

### "Contact added but fields are empty"

**Problem:** Feldnamen-Mapping funktioniert nicht.

**Lösung:**
- Standard-Felder: `firstName`, `lastName`, `phone` (mit großem N!)
- Custom Fields: `acf_ID` Format verwenden
- IDs prüfen mit Link im Backend oder Console-Command

---

## FAQ

### Ist das Bundle DSGVO-konform?

**Ja!** Das Bundle nutzt eine serverseitige API-Integration. Es werden:
- Keine externen Skripte eingebunden
- Keine Cookies gesetzt
- Kein Tracking vor dem Submit
- Daten bleiben bis zum Submit auf deinem Server
- Übertragung erst nach explizitem Submit

**Wichtig:** Dein Formular muss trotzdem eine DSGVO-konforme Einwilligungserklärung enthalten!

### Kann ich mehrere Formulare mit verschiedenen Listen verbinden?

**Ja!** Erstelle einfach mehrere Content Elemente mit verschiedenen Formularen und Listen-IDs.

### Funktioniert das mit Multi-Language-Sites?

**Ja!** Erstelle für jede Sprache:
- Ein eigenes Formular
- Ein eigenes Content Element
- Unterschiedliche Tags (z.B. `Website-DE`, `Website-EN`)

### Was passiert bei API-Fehlern?

**Das Formular funktioniert trotzdem!**
- E-Mail wird normal versendet
- User sieht die Bestätigungsseite
- Nur die ActiveCampaign-Übertragung schlägt fehl
- Fehler wird geloggt

**Rationale:** Lieber Daten in der E-Mail als gar nichts!

### Kann ich das Bundle mit anderen Extensions kombinieren?

**Ja!** Das Bundle ist kompatibel mit:
- Notification Center 1.x und 2.x
- Standard Contao E-Mail
- Anti-SPAM Extensions (z.B. con2net/contao-anti-spam-form-bundle)
- Anderen Form-Extensions

---

## Haftungsausschluss

Dieses Bundle wurde mit größter Sorgfalt entwickelt. Dennoch können sich die technischen Rahmenbedingungen (ActiveCampaign API, Contao-Versionen, PHP-Versionen etc.) jederzeit ändern.

**Die Nutzung erfolgt auf eigene Verantwortung.**

Der Entwickler übernimmt keine Garantie für:
- Korrekte Datenübertragung zu ActiveCampaign
- Vollständigkeit der übertragenen Daten
- Kompatibilität mit zukünftigen Versionen
- Funktionsfähigkeit nach Änderungen durch Drittanbieter

Es wird empfohlen, die Übertragung nach der Installation zu testen und regelmäßig zu prüfen.

**Bei geschäftskritischen Anwendungen sollte die manuelle Übertragung (Delayed Transfer) genutzt werden, um vor der Übertragung eine Kontrolle durchzuführen.**

---

## Lizenz

LGPL-3.0-or-later

Dieses Bundle ist freie Software und darf verwendet, verändert und weitergegeben werden gemäß den Bedingungen der GNU Lesser General Public License.

---
## Weiterführende Links

- ActiveCampaign API Docs: https://developers.activecampaign.com
- Contao Dokumentation: https://docs.contao.org

---

**Hinweis:** Dieses Bundle wird ohne Gewährleistung bereitgestellt. Teste es gründlich vor dem produktiven Einsatz und passe die Einstellungen an deine Bedürfnisse an.

Entwickelt mit ❤️ in Norddeutschland von **connect2Net webServices** / Stefan Meise