<?php

	/**
	 * Service stellt API zu Buchung von Transaktionen mit der Zahlungsart Creditcard zur Verfügung
	 * (Leider steht die, durch den Parameter testMode zu aktivierende, Testumgebung momentan noch nicht zur Verfügung!)
	 *  
	 * Creditcard - API.Event bedarf zur Verwendung einer manuellen Freischaltung, nach dieser 
	 * Freischaltung müssen Sie sich ins ControlCenter zum Menüpunkt "Meine Konfiguration" begeben:
	 * - hier finden Sie den AccessKey den Sie für die Nutzung des Services benötigen
	 * - im Untermenüpunkt "APIs" konfigurien und aktivieren den Service
	 * - im Untermenüpunkt "Zugriffsberechtigungen" tragen Sie Ihre Server-IP ein, um von dort aus Zugriff auf die API zu erlangen
	 *
	 * @copyright 2008 micropayment GmbH
	 * @link http://www.micropayment.de/
	 * @author Yves Berkholz, Guido Franke
	 * @version 1.0
	 * @created 2008-07-01 00:00:00
	 */
	interface IMcpCreditcardService_v1_0 {

		/**
		 * Erstellt einen neuen Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId (default=null)  eigene eindeutige ID des Kunden, wird anderenfalls erzeugt [min./max. Zeichen 10/40, alphanumerisch]
		 * @param map $freeParams (default=null)  Liste mit freien Parametern, die dem Kunden zugeordnet werden
		 * @param string $firstname Vorname des Kunden
		 * @param string $surname Nachname des Kunde
		 * @param string $email (default=null)  E-Mail-Adresse des Kunden, wenn nach den Transaktionen einen E-Mail an der Kunden versand werden soll
		 * @param string $culture (default='de-DE')  Sprache & Land des Kunden | gültige Beispielwerte sind 'de', 'de-DE', 'en-US'
		 * 
		 * @return string eigene oder erzeugte eindeutige ID des Kunden
		 */
		public function customerCreate($accessKey, $testMode=0, $customerId=null, $freeParams=null, $firstname, $surname, $email=null, $culture='de-DE');

		/**
		 * Ändert Daten eines bestehenden Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter 
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId eindeutige ID des Kunden
		 * @param map $freeParams (default=null)  Liste mit freien Parametern: null - Parameterliste bleibt unverändert | leeres HashMap - löscht Parameterliste | gefülltes HashMap erweitert/überschreibt bestehende Parameterliste
		 * @param string $firstname (default=null)  Vorname des Kunden: null - aktueller Wert bleibt erhalten | gültiger Wert z.B 'Max'
		 * @param string $surname (default=null)  Nachname des Kunde: null - aktueller Wert bleibt erhalten | gültiger Wert z.B 'Mustermann'
		 * @param string $email (default=null)  E-Mail-Adresse des Kunden: null - aktueller Wert bleibt erhalten | gültiger Wert z.B. 'max@mustermann.de' ersetzt den aktuellen Wert
		 * @param string $culture (default=null)  Sprache & Land des Kunden: null - aktueller Wert bleibt erhalten | gültige Wert z.B. 'de-DE' ersetzt den aktuellen Wert
		 * 
		 * @return boolean 
		 */
		public function customerSet($accessKey, $testMode=0, $customerId, $freeParams=null, $firstname=null, $surname=null, $email=null, $culture=null);

		/**
		 * Liefert die Daten eines bestehenden Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return struct 
		 * @result map $freeParams (default=null)  Liste mit allen freien Parametern
		 * @result string $firstname Vorname des Kunden
		 * @result string $surname Nachname des Kunden
		 * @result string $email E-Mail-Adresse des Kunden
		 * @result string $culture Sprache & Land des Kunden
		 */
		public function customerGet($accessKey, $testMode=0, $customerId);

		/**
		 * Ändert die Kreditkarten-Daten eines bestehenden Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $number Kreditkartennummer
		 * @param integer $expiryYear Gültigkeits Jahr
		 * @param integer $expiryMonth Gültigkeits Monat
		 * 
		 * @return boolean Rückgabewert gibt Auskunft darüber, ob bei der nächsten Buchung der CVC2-Code erforderlich ist
		 */
		public function creditcardDataSet($accessKey, $testMode=0, $customerId, $number, $expiryYear, $expiryMonth);

		/**
		 * Liefert die Kreditkarten-Daten eines bestehenden Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return struct 
		 * @result string $type Kartentyp
		 * @result string $number partielle Kreditkartennummer (letzten 4 Stellen)
		 * @result integer $expiryYear Gültigkeits Jahr
		 * @result integer $expiryMonth Gültigkeits Monat
		 * @result boolean $cvc2Required Bei der nächsten Buchung ist der CVC2-Code erforderlich
		 */
		public function creditcardDataGet($accessKey, $testMode=0, $customerId);

		/**
		 * Liefert eine Liste von Vorgängen anhand der Parameter: Kunde und/oder Zeitraum
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId (default=null)  eindeutige ID des Kunden
		 * @param datetime $dtmFrom (default=null) 
		 * @param datetime $dtmTo (default=null) 
		 * 
		 * @return string[] 
		 */
		public function sessionList($accessKey, $testMode=0, $customerId=null, $dtmFrom=null, $dtmTo=null);

		/**
		 * Erzeugt einen neuen Bezahlvorgang
		 * Hierfür wird zwingender Weise ein Kunde benötigt für den gebucht werden soll (customerCreate)
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $sessionId (default=null)  eigene eindeutige ID des Vorgangs, wird anderenfalls erzeugt [max. 40 Zeichen]
		 * @param string $project das Projektkürzel für den Vorgang
		 * @param string $projectCampaign (default=null)  ein Kampagnenkürzel des Projektbetreibers
		 * @param string $account (default=null)  Account des beteiligten Webmasters sonst eigener - setzt eine Aktivierung der Webmasterfähigkeit des Projekts vorraus - Hinweis: Webmasterfähigkeit steht momentan nicht zur Verfügung
		 * @param string $webmasterCampaign (default=null)  ein Kampagnenkürzel des Webmasters
		 * @param integer $amount (default=null)  abzurechnender Betrag, wird kein Betrag übergeben, wird der Betrag aus der Konfiguration verwendet
		 * @param Currency $currency (default='EUR')  Währung
		 * @param string $title (default=null)  Bezeichnung der zu kaufenden Sache - Verwendung in Falle einer auftretenden Benachrichtigung wird dieser Wert als Produktidentifizierung mit geschickt, wird kein Wert übergeben, wird Der aus der Konfiguration verwendet
		 * @param string $paytext (default=null)  Bezeichnung der zu kaufenden Sache - Verwendung beim Mailversand, sollten Sie Diesen wünschen
		 * @param string $ip IPv4 des Benutzers
		 * @param map $freeParams (default=null)  Liste mit freien Parametern, die dem Vorgang zugeordnet werden
		 * @param boolean $sendMail (default=true) 
		 * 
		 * @return struct 
		 * @result string $sessionId eigene oder erzeugte eindeutige ID des Vorgangs
		 * @result SessionStatus $status Vorgangsstatus "INIT"
		 * @result datetime $expire Ablaufzeit des Vorgangs
		 */
		public function sessionCreate($accessKey, $testMode=0, $customerId, $sessionId=null, $project, $projectCampaign=null, $account=null, $webmasterCampaign=null, $amount=null, $currency='EUR', $title=null, $paytext=null, $ip, $freeParams=null, $sendMail=true);

		/**
		 * Liefert Informationen über einen Vorgang
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId eindeutige ID des Vorgangs
		 * 
		 * @return struct 
		 * @result string $customerId ID des Kunden
		 * @result string $project das Projektkürzel für den Vorgang
		 * @result string $projectCampaign ein Kampagnenkürzel des Projektbetreibers
		 * @result string $account Account des beteiligten Webmasters sonst eigener
		 * @result string $webmasterCampaign ein Kampagnenkürzel des Webmasters
		 * @result integer $amount abzurechnender Betrag, wird kein Betrag übergeben, wird der Betrag aus der Konfiguration verwendet
		 * @result Currency $currency Währungseinheit
		 * @result string $title Bezeichnung der zu kaufenden Sache
		 * @result string $ip IPv4 des Benutzers
		 * @result map $freeParams (default=null)  Liste mit freien Parametern, die dem Vorgang zugeordnet werden
		 * @result SessionStatus $status 
		 * @result datetime $expire (default=null)  Verfallsdatum der Session, nur wenn $status INIT oder EXPIRED
		 * @result MailStatus $mail Status des Mailversands
		 * @result string[] $transactionIds (default=null)  Liste von TransaktionsIds die mit dieser Session verknüpft sind
		 */
		public function sessionGet($accessKey, $testMode=0, $sessionId);

		/**
		 * Liefert Informationen über eine Transaktion
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $transactionId Transaktionsnummer
		 * 
		 * @return struct 
		 * @result string $transactionId Transaktionsnummer
		 * @result string $sessionId eindeutige ID des Vorgangs
		 * @result string $customerId ID des Kunden
		 * @result string $auth AuthCode
		 * @result integer $amount abzurechnender Betrag, wird kein Betrag übergeben, wird der Betrag aus der Konfiguration verwendet
		 * @result Currency $currency Währungseinheit
		 * @result TransactionType $type Art der Transaktion
		 * @result TransactionStatus $status Status der Transaktion
		 * @result datetime $created Zeitpunkt der Transaktion
		 * @result string $ip IPv4 des Benutzers
		 * @result string $cardType Kartentyp
		 * @result string $cardNumber partielle Kreditkartennummer (letzten 4 Stellen)
		 * @result integer $cardExpiryYear Gültigkeits Jahr
		 * @result integer $cardExpiryMonth Gültigkeits Monat
		 */
		public function transactionGet($accessKey, $testMode=0, $transactionId);

		/**
		 * Führt eine Transaktion zur sofortigen Buchung des Betrags durch
		 * Hierfür wird nicht nur eine gültige Session benötigt (sessionCreate),
		 * sondern es müssen für den den Kunden auch Kreditkartendaten hinterlegt sein (creditcardDataSet)
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $cvc2 (default=null)  CVC2-Code der Kreditkarte, muß min einmal pro Kreditkarte/Verfallszeit angegeben worden sein
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgelösten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionPurchase($accessKey, $testMode=0, $sessionId, $cvc2=null);

		/**
		 * Führt eine Transaktion zur Vorautorisierungs eines Betrages durch (Sie reservieren einen Kaufbetrag)
		 * Hierfür wird eine gültige Session benötigt (sessionCreate),
		 * sowie Kreditkartendaten des Kunden (creditcardDataSet)
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $cvc2 (default=null)  CVC2-Code der Kreditkarte, muß min einmal pro Kreditkarte/Verfallszeit angegeben worden sein
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgelösten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionAuthorization($accessKey, $testMode=0, $sessionId, $cvc2=null);

		/**
		 * Führt eine Transaktion zur Buchung einer Vorautorisierung durch (Sie buchen den reservierten Kaufbetrag)
		 * Hierfür wird eine gültige Session benötigt (sessionCreate) auf der eine Transaktion zu Vorautorisierung (transactionAuthorization) durchgeführt wurde
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $transactionId (default=null)  Transaktionsnummer von "transactionAuthorization"
		 * @param integer $amount (default=null)  null - entspricht Betrag aus Vorautorisierung | wenn abweichend, der zu buchende Betrag <= Betrag aus Vorautorisierung 
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgelösten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionCapture($accessKey, $testMode=0, $sessionId, $transactionId=null, $amount=null);

		/**
		 * Transaktion zur gebührenfreier Stornierung einer Zahlung vor Kassenschnitt oder freigabe von Vorautorisierungen
		 * Anwendbar auf Transaktionen die mit "transactionPurchase" oder "transactionAuthorization" erstellt wurden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $transactionId Transaktionsnummer der Transaktion die zurückgebucht werden soll
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgelösten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionReversal($accessKey, $testMode=0, $sessionId, $transactionId);

		/**
		 * Transaktion zur Buchung einer Rückzahlung - Käufer erhält den Kaufbetrag einer erfolgreichen Buchung gesamt oder teilweise zurück
		 * Anwendbar auf Transaktionen die mit "transactionPurchase" oder "transactionCapture" erstellt wurden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)  aktiviert Testumgebung
		 * @param string $sessionId ID des Vorgangs
		 * @param string $transactionId Transaktionsnummer der Transaktion die zurückgebucht werden soll
		 * @param integer $amount (default=null)  zurückzubuchender Betrag, falls abweichend von Orginaltransaktion
		 * 
		 * @return struct 
		 * @result SessionStatus $sessionStatus Status der gesamten Session
		 * @result TransactionStatus $transactionStatus Status der ausgelösten Transaktion
		 * @result string $transactionId Transaktionsnummer
		 * @result datetime $transactionCreated Zeitpunkt der Transaktion
		 * @result string $transactionAuth AuthCode
		 */
		public function transactionRefund($accessKey, $testMode=0, $sessionId, $transactionId, $amount=null);

	}

?>