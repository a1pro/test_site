Pay Plugin f�r http://www.passwordcall.de
==========================================

Konfiguration auf der Seite www.passwordcall.de:
--------------------------------------------------
Es k�nnen bis zu 4 Nummern beliebig konfiguriert werden.

-----------------------------------------------------------------------------
Eingaben auf Passwordcall.de f�r jede konfigurierte Rufnummer
(siehe Nummer bearbeiten im Schritt 2/3):
  Weiterleitungs URL: {$config.root_url}/thanks.php
  Fehler URL:         {$config.root_url}/cancel.php
  Script/Backend URL: {$config.root_url}/plugins/payment/passwordcall/ipn.php

  Wie oft g�ltig: Einmalig
  Target: Kein Target

Amember Control Panel Konfiguration:
------------------------------------
Utilities: Setup/Configuration
Plugins: passwordcall mit auswaehlen.
Eingaben:
1. Deine Webmaster ID eintragen (Zu finden unter passwordcall.de Nummern verwalten/Link generieren)
2. Titel/�berschrift eintragen, Beispiel: 30 Tage Mtgliedschaft
3. Den in passwordcall.de gew�hlten Rufnummern Tarif, also "T4" oder "T5" eintragen.
4. Angebots ID eintragen (Zu finden unter passwordcall.de Nummern verwalten/Link generieren)
5. Product ID eintragen, du findest die ID unter Manage Products (links im amember Controlpanel)

F�r weitere in passwordcall.de konfigurierte Rufnummern Schritt 2-5 wiederholen.

Weitere Infos:
Mit dem Titel/�berschrift "Test Abo" kann ein Abo erstellt werden, welches um 24:00 Uhr endet.
Der User erh�lt die Restzeit auf der Page mit der Passwordcall.de Rufnummer unten angezeigt.
Bei der Product Konfiguration ist als "Duration" 0 (0 Days) zu w�hlen.

Die Product ID kann mehrfach gleich vergeben werden zu unterschiedlichen Rufnummern.
So kann beispielsweise eine 20 Tage Mitgliedschaft sowohl �ber den Tarif T4 (�sterreich und Schweiz)
als auch �ber Tarif T5 (nur f�r Deutschland verf�gbar) konfiguriert werden.

Wenn ein User ein Abo abschliesst, muss er eine Rufnummer w�hlen und bekommt dann ein Passwort angesagt.
Dieses Passwort wird in das Amember Controlpanel unter Reports/Payments als Transactions-id (in das Feld Receipt#) eingetragen.
Man kann �ber "Search by string" danach suchen. So k�nnen Reklamationen leicht verfolgt werden!



(c)2006 admin@dwarfs-inc.biz