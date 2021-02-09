Zahlungs-Plugin für die Annahme von lokalen und internationalen Zahlungen und für die Automatisierung der gesamten Zahlungsabwicklung vom Checkout bis zum Inkasso – über eine einzige PCI-zertifizierte Zahlungsplattform. Dies ist das offizielle Novalnet-Plugin für den Online-Shop Ceres und andere Vorlagen-Plugins. Ein IO Plugin ist erforderlich.

# Novalnet-Plugin für Plentymarkets:

Das Novalnet Payment-Plugin für Plentymarkets ist eine Ende-zu-Ende-Lösung für die Full-Service-Abwicklung von internationalen und lokalen Zahlungen auf der ganzen Welt. Über die Abbindung an die PCI-DSS-zertifizierte Novalnet-Plattform automatisiert es Ihre gesamte Zahlungsabrechnung und bietet folgende Vorteile:

- Eine Plattform für die echtzeitige Abwicklung und Optimierung aller Ihrer Zahlungsvorgänge, inkl. 15+ vollautomatisierten Services (z.B. Betrugsprävention und Forderungsmanagement)
- über 100 lokal und international nachgefragte Zahlungsarten bereits integriert
- Ein Vertrag – keine versteckten Kosten oder Vertragsbedingungen für die Zahlungsabwicklung 
- PCI-DSS Compliance (PCI DSS Level 1) & BaFin-Lizenz
- Professionelle Kundenbetreuung auf mehreren Ebenen (z.B. Händler- und Endkunden-Support) inhouse (kein Callcenter)

Das Novalnet-Zahlungsplugin bindet Ihre Verkaufsplattform an die von der BaFin (Bundesanstalt für Finanzdienstleistungsaufsicht) akkreditierte Novalnet-Zahlungsplattform an. Somit sind mehrere Verträge und Lizenzen für die Zahlungsabwicklung Ihres Plentymarkets-Systems überflüssig. Konzentrieren Sie sich auf das Wachstum Ihres Unternehmens, wir kümmern uns um alles rund um Ihre Zahlungsabwicklung!

## Unterstützte Zahlungsarten
- Kreditkarte (mit und ohne 3D-Secure)
- Lastschrift SEPA
- Lastschrift SEPA mit Zahlingsgarantie
- Rechnung
- Rechnung mit Zahlingsgarantie
- Vorkasse
- Sofortüberweisung
- iDEAL
- PayPal
- giropay
- eps
- Przelewy24
- Barzahlen  

## Abschluss eines Novalnet Händlerkontos/Dienstleistungsvertrags:

Um Zahlungen über das Novalnet-Plugin für Plentymarkets entgegenzunehmen und zu verarbeiten, benötigen Sie einen Novalnet-Händleraccount. Dazu kontaktieren Sie Novalnet entweder telefonisch unter +49 89 9230683-20 oder per E-Mail an sales@novalnet.de. Weitere Informationen finden Sie auf www.novalnet.de. 

## Konfiguration des Novalnet-Plugins in Plentymarkets:

Um Ihr Novalnet-Zahlungs-Plugin zu konfigurieren und mit der Annahme von Zahlungen zu beginnen, gehen Sie auf **Plugins -> Plugin-Übersicht -> Novalnet -> Konfiguration**.

Geben Sie wie folgt Ihre Novalnet-Händlerdaten an:

1. Melden Sie sich mit Ihrem Händeraccount im [Novalnet-Händleradminportal](https://admin.novalnet.de/) an.
2. Gehen Sie auf den Tab **PROJEKTE**
3. Wählen Sie Ihr Projekt aus
4. Klicken Sie auf Parameter Ihres Shops und füllen Sie die erforderlichen Felder aus: **Händler-ID**, **Authentifizierungscode**, **Projekt-ID**, **Tarif-ID** und **Zahlungs-Zugriffsschlüssel** etc.

<table>
    <thead>
        <th>
            Feld
        </th>
        <th>
            Beschreibung
        </th>
    </thead>
    <tbody>
        <tr>
        <td class="th" align=CENTER colspan="2">Allgemeines</td>
        </tr>
        <tr>
            <td><b>Händler-ID</b></td>
            <td>Geben Sie Ihre Händler-ID ein, die Sie nach der Eröffnung Ihres Händlerkontos von Novalnet erhalten haben. Bitte kontaktieren Sie Novalnet unter <a href="mailto:sales@novalnet.de" target="_blank">sales@novalnet.de</a>, falls Sie noch kein eigenes Händlerkonto haben.</td>
        </tr>
        <tr>
            <td><b>Authentifizierungscode</b></td>
            <td>Geben Sie Ihr Händler-Passwort (Authentifizierungscode) ein, das Sie nach der Eröffnung Ihres Händlerkontos von Novalnet erhalten haben.</td>
        </tr>
        <tr>
            <td><b>Projekt-ID</b></td>
            <td>Eine einzigartige ID-Nummer für  das im Novalnet- Händleradminportal erstellte Projekt.</td>
        </tr>
        <tr>
            <td><b>Tarif-ID</b></td>
            <td>Eine eindeutige Bezeichnung für den angelegten Tarif. Wählen Sie die erforderliche Tarif-ID, um sicherzustellen, dass für dieses Projekt die richtige Tarif-ID verwendet wird.</td>
        </tr>
        <tr>
            <td><b>Zahlungs-Zugriffsschlüssel</b></td>
            <td>Sicherer öffentlicher Schlüssel zur Ver- und Entschlüsselung von Transaktionsparametern. Dieser ist obligatorisch für alle Online-Überweisungen, Kreditkartenzahlungen mit 3D-Secure und Wallet-Systeme.</td>
        </tr>
        </tbody>
</table>

## Novalnet-Zahlungseinstellungen in Plentymarkets

Um Ihre bevorzugten Zahlungsarten zu aktivieren, melden Sie sich im [Novalnet-Händleradminportal](https://admin.novalnet.de/) an.

1. Klicken Sie auf den Menüpunkt **PROJEKTE**.
2. Wählen Sie das gewünschte Projekt aus.
3. Zahlungsmethoden
4. Zahlungsmethoden bearbeiten. 

Weitere Informationen finden Sie in der **Installationsbeschreibung des Novalnet-Payment-Plugins für Plentymarkets**, inclusive erklärender Screenshots für alle Zahlungseinstellungen. Die Installationsbeschreibung ist im Download des Plugin-Pakets enthalten.

## Erstellung eines Events für bestätigte/stornierte/erstattete Transaktionen

So richten Sie ein neues Event zur Bestätigung, Stornierung oder Rückerstattung von Transaktionen über Novalnet ein: 

1. Öffnen Sie das Menü **System » Aufträge » Ereignisaktionen**.
2. Klicken Sie auf **Ereignisaktion hinzufügen**. <br > → Das Fenster **Neue Ereignisaktion erstellen** wird geöffnet.
3. Geben Sie einen Namen ein.
4. Wählen Sie das Ereignis gemäß Tabelle 1-3.
5. **Speichern** Sie die Einstellungen. <br > → Die Ereignisaktion wird angelegt.
6. Nehmen Sie die weiteren Einstellungen gemäß Tabelle 1-3 vor.
7. Setzen Sie ein Häkchen bei **Aktiv**.
8. **Speichern** Sie die Einstellungen. <br > → Die Ereignisaktion wird gespeichert.

<table>
   <thead>
    </tr>
      <th>
         Einstellung
      </th>
      <th>
         Option
      </th>
      <th>
         Auswahl
      </th>
    </tr>
   </thead>
   <tbody>
      <tr>
         <td><strong>Ereignis</strong></td>
         <td>Wählen Sie das Ereignis (Event), durch das die Versandbestätigung automatisch versendet werden soll.</td>
         <td></td>
      </tr>
      <tr>
         <td><strong>Filter 1</strong></td>
         <td><strong>Auftrag > Zahlungsart</strong></td>
         <td><strong>Plugin: Novalnet Invoice</strong></td>
      </tr>
      <tr>
        <td><strong>Aktion</strong></td>
        <td><strong>Plugins > Novalnet | Bestätigen </strong></td>
        <td></td>
      </tr>
    </tbody>
    <caption>
	Tabelle 1: Ereignisaktion (Event procedure) zum Senden einer automatischen Versandbestätigung
    </caption>
</table>

<table>
   <thead>
    </tr>
      <th>
         Einstellung
      </th>
      <th>
         Option
      </th>
      <th>
         Auswahl
      </th>
    </tr>
   </thead>
   <tbody>
      <tr>
         <td><strong>Ereignis</strong></td>
         <td>Wählen Sie das Ereignis (Event), durch das die Stornierungsbestätigung automatisch versendet werden soll.</td>
         <td></td>
      </tr>
      <tr>
         <td><strong>Filter 1</strong></td>
         <td><strong>Auftrag > Zahlungsart</strong></td>
         <td><strong>Plugin: Novalnet Invoice</strong></td>
      </tr>
      <tr>
        <td><strong>Aktion</strong></td>
        <td><strong>Plugins > Novalnet | Stornieren </strong></td>
        <td></td>
      </tr>
    </tbody>
    <caption>
	Tabelle 2: Ereignisaktion (Event procedure) zum Senden einer automatischen Stornierungsbestätigung
	</caption>
</table>

<table>
   <thead>
    </tr>
      <th>
         Einstellung
      </th>
      <th>
         Option
      </th>
      <th>
         Auswahl
      </th>
    </tr>
   </thead>
   <tbody>
      <tr>
         <td><strong>Ereignis</strong></td>
         <td>Wählen Sie das Ereignis (Event), durch das die Rückerstattungsbestätigung automatisch versendet werden soll.</td>
         <td></td>
      </tr>
      <tr>
         <td><strong>Filter 1</strong></td>
         <td><strong>Auftrag > Zahlungsart</strong></td>
         <td><strong>Plugin: Novalnet Invoice</strong></td>
      </tr>
      <tr>
        <td><strong>Aktion</strong></td>
        <td><strong>Plugins > Novalnet | Rückerstattung </strong></td>
        <td></td>
      </tr>
    </tbody>
    <caption>
	Tabelle 3: Ereignisaktion (Event procedure) zum Senden einer automatischen Rückerstattungsbestätigung
	</caption>
</table>

## Anzeige der Transaktionsdetails im Rechnungs-PDF

Führen Sie die folgenden Schritte aus, um die Transaktionsdetails im Rechnungs-PDF anzuzeigen:

1. Öffnen Sie das Menü **System » Aufträge » Ereignisaktionen**.
2. Klicken Sie auf **Ereignisaktion hinzufügen**. <br > -> Das Fenster **Neue Ereignisaktion erstellen** wird geöffnet.
3. Geben Sie einen Namen ein.
4. Wählen Sie das Ereignis gemäß Tabelle 4.
5. **Speichern** Sie die Einstellungen. <br > -> Die Ereignisaktion wird angelegt.
6. Nehmen Sie die weiteren Einstellungen gemäß Tabelle 4 vor.
7. Setzen Sie ein Häkchen bei **Aktiv**.
8. **Speichern** Sie die Einstellungen. <br > -> Die Ereignisaktion wird gespeichert.

<table>
   <thead>
    </tr>
      <th>
         Einstellung
      </th>
      <th>
         Option
      </th>
      <th>
         Auswahl
      </th>
    </tr>
   </thead>
   <tbody>
      <tr>
         <td><strong>Ereignis</strong></td>
         <td>Wählen Sie das Ereignis (Event) aus, das eine PDF-Rechnung auslösen soll </td>
         <td></td>
      </tr>
      <tr>
         <td><strong>Filter 1</strong></td>
         <td><strong>Auftrag > Zahlungsart</strong></td>
         <td><strong>Plugin: Novalnet</strong></td>
      </tr>
     <tr>
        <td><strong>Aktion</strong></td>
        <td><strong>Dokumente > Rechnung erzeugen</strong></td>
        <td></td>
      </tr>
    </tbody>
    <caption>
    Tabelle 4: Ereignisaktion (Event procedure) um Transaktionsdetails in Rechnungs-PDF anzuzeigen
    </caption>
</table>

## Aktualisieren der Notification- / Webhook-URL

### Zahlungs-/Transaktionsstatus-Benachrichtigung – Asynchrones Händler-Skript

Das Novalnet-System überträgt (über asynchronen Aufruf) Informationen über den gesamten Transaktionsstatus an das System des Händlers.
Konfiguration der Notification- / Webhook-URL:

1. Melden Sie sich im [Novalnet-Händleradminportal](https://admin.novalnet.de/) an.
2. Wählen Sie den Menüpunkt **Projekte** aus.
3. Wählen Sie das gewünschte Projekt aus.
4. Klicken Sie auf **Projektübersicht**

Konfigurieren Sie die Händlerskript-URL / Notification- & Webhook-URL für Ihren Shop. In der Regel wird die Notification- & Webhook-URL nach folgendem Muster aussehen: 
**URL IHRER SEITE/payment/novalnet/callback**.

## Anzeige der Transaktionsdetails auf der Auftragsbestätigungsseite

Führen Sie die folgenden Schritte aus, um die Transaktionsdetails auf der Auftragsbestätigungsseite anzuzeigen:

1. Gehen Sie auf **CMS » Container-Verknüpfungen**
2. Klicken Sie auf **Novalnet payment details (Novalnet)**
3. Aktivieren Sie das Feld **Order confirmation: Additional payment information**.
4. Klicken Sie auf **Speichern**. Nun werden die Transaktionsdetails angezeigt. 
