<?php

	/**
	 * Service stellt API zu Buchung von Transaktionen mit der Zahlungsart Creditcard zur Verfügung
	 * 
	 *  Creditcard - API.Event bedarf zur Verwendung einer manuellen Freischaltung, nach dieser Freischaltung müssen Sie sich ins ControlCenter zum Menüpunkt "Meine Konfiguration" begeben:
	 *  - hier finden Sie den AccessKey den Sie für die Nutzung des Services benötigen
	 *  - im Untermenüpunkt "APIs" konfigurien und aktivieren den Service
	 *  - im Untermenüpunkt "Zugriffsberechtigungen" tragen Sie Ihre Server-IP ein, um von dort aus Zugriff auf das API zu erlangen
	 * 
	 *  neu in Version 1.1:
	 *  - Testmodus ist jetzt verfügbar
	 *  - transactionChargebackNotificationTest
	 *  - resetTest
	 *  - addressSet
	 *  - addressGet
	 * 
	 *  Hinweise zum TestModus:
	 *  - für den TestModus stehen folgende Kartennummern zur Verfügung: VISA 4111111111111111, MASTER 5454545454545454, AMEX 343434343434343
	 *  - um erfolgreiche Buchungen durchzuführen ist als CVC2-Code die 666 zu verwenden, bei allen Anderen gild die Transaktion als fehlgeschlagen
	 * 
	 *  Sollten Ihnen die Adressdaten des Kunden zur Verfügung stehen, empfehlen wir für die zukünftige Verwendung diese mittles "addressSet" zuzuweisen
	 * 
	 * FehlerCodes:
	 * 
	 * - TCreditcardServiceException ----------------------------------------------------------------------
	 * 2100 - CreditCardDispatcher Error: {0}
	 * Der Platzhalter {0} wird zur Laufzeit dann durch die Fehlermeldung des internen API ersetzt
	 * 
	 * 2110 - transactiontype "{0}" not allowed for session with id "{1}"
	 * Tritt z.B. auf wenn Sie versuchen mehrfach ein "transactionPurchase" für eine Session auszuführen, oder ein "transactionCapture" ohne vorheriges "transactionAuthorization" etc.
	 * 
	 * 3500 - no credit card data available for customerId "{0}"
	 * Wenn Sie versuchen eine Transaktion mit "transaction*" auszuführen aber für den Kunden vorher kein "creditcardDataSet" durchgeführt haben
	 * 
	 * 3510 - transaction requires CVC2 code for customerId "{0}"
	 * Der CVC2-Code muß mindestens einmalig je Kreditkartendatensatz (Nummer/Ablaufdatum) eingegeben werden
	 * 
	 * 3520 - no successful {0}-transaction found for sessionId "{1}"
	 * Spielt nur für "transaction(Capture|Reversal|Refund)" eine Rolle, wenn eine Transaktion basierend auf einer Anderen ausgeführt werden soll
	 * 
	 * 
	 * 4101 - luhn check for number failed
	 * Luhn check für die Kartennummer ist fehlgeschlagen
	 * 
	 * 4102 - card is banned
	 * Karte ist auf der BAD-List
	 * 
	 * 
	 * - TConfigurationException --------------------------------------------------------------------------
	 * 3200 - ConfigurationException occurred: {0}
	 * allgemeine Konfigurations Exception
	 * 
	 * 3210 - webmaster not supported by project "{0}"
	 * Da das Webmaster-Feature in unserem System nicht voll ausprogrammiert ist, spielt dieser Fehlercode keine Rolle
	 * 
	 * 3220 - service not configured for project "{0}"
	 * Es wird keine Konfiguration für das Projekt gefunden
	 * 
	 * 3221 - ervice configuration for project "{0}" not activated
	 * Es wurde zwar eine Konfiguration gefunden, diese ist jedoch nicht aktiv
	 * 
	 * 
	 * - TValidationException -----------------------------------------------------------------------------
	 * 3100 - ValidationException occurred: {0}
	 * allgemeine Validierungs Exception
	 * 
	 * 3101 - "{0}" is empty
	 * 3110 - "{0}" with value "{1}" not exists
	 * 3111 - "{0}" with value "{1}" already exists
	 * 3112 - {0} "{1}" expired
	 * 3113 -  {0} "{1}" deleted
	 * 3121 - "{0}" contains invalid characters - valid characters are: {1}
	 * 3122 - "{0}" is syntactically incorrect - expected format: {1}
	 * 3123 - "{0}" with value "{1}" out of range: {2} to {3}
	 * 3124 - "{0}" with value "{1}" not allowed - acceptable values are: {2}
	 * 3125 - "{0}" min length of {1} chars
	 * 3126 - "{0}" max length of {1} chars
	 * können bei der Validierung der übertragenen Parameter auftreten: {0} ist i.d.R. der Parametername, {1} der Parameterwert
	 * 
	 * 3150 - "{0}" with value "{1}" is not part of your ownership
	 * tritt z.B. auf wenn "sessionCreate" mit einem Projekt aufrufen das nicht zu dem Account gehört
	 * 
	 * 
	 * - TServerException ---------------------------------------------------------------------------------
	 * 1000 - an unknown error occured
	 * sollte eigentlich nicht auftreten
	 * 
	 * 1001 - an PHP error occured #{0} "{1}" in file "{2}" on line {3}
	 * sollte auch nicht auftreten, höchtens wenn gerade ein deployed wird und eine Datei noch nicht vollständig geschrieben wurde
	 * 
	 * 1002 - an error occured on processing database operation: {0}
	 * Lese oder Schreibprozesse in der DB sind fehlgeschlagen, in sofern der DB-Server nicht gerade neu gestartet wird, dürfte dieser Fehler auch nicht auftreten
	 * 
	 * 1010 - service initialization failed
	 * 1011 - service initialization failed - no default request-adapter defined, use a specialized server-class or set adapter manualy using TBaseServer::setRequestAdapter
	 * 1012 - service initialization failed - no default response-adapter defined, use a specialized server-class or set adapter manualy using TBaseServer::setResponseAdapter
	 * interne Fehler im Grunde sowas wie der 500ter bei HTTP
	 * 
	 * 2001 - service is currently under maintenance
	 * Wartungsarbeiten
	 * 
	 * 3000 - authorization failed - reason: {0}
	 * Der Aufruf erfolgt:
	 * - mit einem ungültigen AccessKey (accesskey wrong)
	 * - von einer IP die nicht im ControlCenter unter "Zugriffsberechtigungen" eingetragen wurde (IP not allowed)
	 * - die Kombination aus IP und AccessKey ist ungültig (IP not allowed for accesskey)
	 * 
	 * 3001 - method to invoke missing
	 * Sie rufen den Service auf ohne anzugeben welche Methode ausgeführt werden soll
	 * 
	 * 3002 - method to invoke "{0}" not exists
	 * Sie versuchen eine nicht existieren Methode aufzurufen
	 * 
	 * 3003 - method to invoke "{0}" requires param "{1}"
	 * Sie haben eine Methode aufgerufen haben aber den angegebenen Pflichparameter mit anzugeben
	 *
	 * @copyright 2009 micropayment GmbH
	 * @link http://www.micropayment.de/
	 * @author Yves Berkholz, Guido Franke
	 * @version 1.1
	 * @created 2009-04-28 15:07:40
	 */
	interface IMcpCreditcardService_v1_1 {

		/**
		 * Löscht alle Kunden und Transaktionen in der Testumgebung
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=1)  aktiviert Testumgebung
		 * 
		 * @return boolean 
		 */
		public function resetTest($accessKey, $testMode=1);

		/**
		 * Versendet eine Benachrichtigung über ein Chargeback an die im ControlCenter angegebene URL und gibt Debuginformationen darüber zurück
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=1)  aktiviert Testumgebung
		 * @param string $transactionId Transaktionsnummer einer PURCHASE- oder CAPTURE-Transaktion
		 * 
		 * @return string 
		 */
		public function transactionChargebackNotificationTest($accessKey, $testMode=1, $transactionId);

		/**
		 * Ändert Adressdaten eines Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)   aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * @param string $address Strasse und Hausnummer
		 * @param string $zipcode Postleitzahl
		 * @param string $town Ort
		 * @param string $country Land zweistelliges Länderkürzel Bsp. DE, AT, CH
		 * 
		 * @return boolean 
		 */
		public function addressSet($accessKey, $testMode=0, $customerId, $address, $zipcode, $town, $country);

		/**
		 * Liefert die Adressdaten eines Kunden
		 *
		 * @param string $accessKey AccessKey aus dem Controlcenter
		 * @param integer $testMode (default=0)   aktiviert Testumgebung
		 * @param string $customerId ID des Kunden
		 * 
		 * @return array 
		 * @result string $address Strasse und Hausnummer
		 * @result string $zipcode Postleitzahl
		 * @result string $town Ort
		 * @result string $country Land
		 */
		public function addressGet($accessKey, $testMode=0, $customerId);

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
		 * 
		 *  Hierfür wird zwingender Weise ein Kunde benötigt für den gebucht werden soll (customerCreate)
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
		 * 
		 *  Hierfür wird nicht nur eine gültige Session benötigt (sessionCreate),
		 *  sondern es müssen für den den Kunden auch Kreditkartendaten hinterlegt sein (creditcardDataSet)
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
		 * 
		 *  Hierfür wird eine gültige Session benötigt (sessionCreate),
		 *  sowie Kreditkartendaten des Kunden (creditcardDataSet)
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
		 * 
		 *  Hierfür wird eine gültige Session benötigt (sessionCreate) auf der eine Transaktion zu Vorautorisierung (transactionAuthorization) durchgeführt wurde
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
		 * 
		 *  Anwendbar auf Transaktionen die mit "transactionPurchase" oder "transactionAuthorization" erstellt wurden
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
		 * 
		 *  Anwendbar auf Transaktionen die mit "transactionPurchase" oder "transactionCapture" erstellt wurden
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