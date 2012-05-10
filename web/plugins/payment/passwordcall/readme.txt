Pay Plugin für http://www.passwordcall.de
==========================================

Konfiguration auf der Seite www.passwordcall.de:
--------------------------------------------------
Es können bis zu 4 Nummern beliebig konfiguriert werden.

-----------------------------------------------------------------------------
Eingaben auf Passwordcall.de für jede konfigurierte Rufnummer
(siehe Nummer bearbeiten im Schritt 2/3):
  Weiterleitungs URL: {$config.root_url}/thanks.php
  Fehler URL:         {$config.root_url}/cancel.php
  Script/Backend URL: {$config.root_url}/plugins/payment/passwordcall/ipn.php

  Wie oft gültig: Einmalig
  Target: Kein Target

Amember Control Panel Konfiguration:
------------------------------------
Utilities: Setup/Configuration
Plugins: passwordcall mit auswaehlen.
Eingaben:
1. Deine Webmaster ID eintragen (Zu finden unter passwordcall.de Nummern verwalten/Link generieren)
2. Titel/Überschrift eintragen, Beispiel: 30 Tage Mtgliedschaft
3. Den in passwordcall.de gewählten Rufnummern Tarif, also "T4" oder "T5" eintragen.
4. Angebots ID eintragen (Zu finden unter passwordcall.de Nummern verwalten/Link generieren)
5. Product ID eintragen, du findest die ID unter Manage Products (links im amember Controlpanel)

Für weitere in passwordcall.de konfigurierte Rufnummern Schritt 2-5 wiederholen.

Weitere Infos:
Mit dem Titel/Überschrift "Test Abo" kann ein Abo erstellt werden, welches um 24:00 Uhr endet.
Der User erhält die Restzeit auf der Page mit der Passwordcall.de Rufnummer unten angezeigt.
Bei der Product Konfiguration ist als "Duration" 0 (0 Days) zu wählen.

Die Product ID kann mehrfach gleich vergeben werden zu unterschiedlichen Rufnummern.
So kann beispielsweise eine 20 Tage Mitgliedschaft sowohl über den Tarif T4 (Österreich und Schweiz)
als auch über Tarif T5 (nur für Deutschland verfügbar) konfiguriert werden.

Wenn ein User ein Abo abschliesst, muss er eine Rufnummer wählen und bekommt dann ein Passwort angesagt.
Dieses Passwort wird in das Amember Controlpanel unter Reports/Payments als Transactions-id (in das Feld Receipt#) eingetragen.
Man kann über "Search by string" danach suchen. So können Reklamationen leicht verfolgt werden!



(c)2006 admin@dwarfs-inc.biz