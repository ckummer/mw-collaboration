<?php
class SpecialInstallation extends SpecialPage {
        function __construct() {
				//Dieser Befehl lädt das Grundgerüst einer SpecialPage. Der Name der SpecialPage wird geändert und es kann ein Recht zur Beschränkung des Zugriffs vergeben werden.
				//Um den Zugriff auf die Seite nur für System-Operatoren zu gewähren, ist die Zeile parent::__construct um den Parameter 'sysop' zu ergänzen.
                parent::__construct( 'Installation', 'sysop' );
        }
 
        function execute( $par ) {
			//Definition globaler Variablen, welche von MediaWiki genutzt werden.
			global $wgOut, $wgRequest, $wgDBprefix;
			
			//Definition weiterer Variablen
			$table_structure_collaboration = array();
			$compare_collaboration = array();
			$counter_collaboration = 0;
			$checker1 = 0;
			$checker2 = 0;
			$checker3 = 0;
			
			$table_structure_user = array();
			$compare_user = array();
			$compare_user_create = array();

			//Festlegen der Pfade, unter welchen die grafischen Visualisierungen der Kollaborations-Werte und die Ampelbilder abgelegt werden.
			$network_path = "C:/xampp/htdocs/wikiwm2012/images/collaboration_plots/";
			$image_path = "C:/xampp/htdocs/wikiwm2012/extensions/Collaboration/traffic_lights/";
			
			//Trotz Vergabe der Zugriffsbeschränkung, kann ein User einer Gruppe ohne die entsprechende Berechtigung über den direkten Link auf diese SpecialPage zugreifen.
			//Wird nur verwendet, wenn der Parameter 'sysop' im Constructor vergeben wurde.
			if (  !$this->userCanExecute( $this->getUser() )  ) {
				$this->displayRestrictionError();
				return;
			}
			
			//Definition der Tabellenstruktur der Tabelle 'collaboration'
			$table_structure_collaboration[1] = "row_names text";
			$table_structure_collaboration[2] = "time text";
			$table_structure_collaboration[3] = "pa bigint(20)";
			$table_structure_collaboration[4] = "den double";
			$table_structure_collaboration[5] = "cent double";
			$table_structure_collaboration[6] = "deg double";
			$table_structure_collaboration[7] = "dis bigint(20)";
			$table_structure_collaboration[8] = "calibrated_den double";
			$table_structure_collaboration[9] = "calibrated_cent double";
			$table_structure_collaboration[10] = "calibrated_deg double";
			$table_structure_collaboration[11] = "calibrated_dis double";
			$table_structure_collaboration[12] = "collaboration_final double";
			
			//Definition der Tabellenstruktur der Tabelle 'user'
			$table_structure_user[1] = "user_id int(10) unsigned";
			$table_structure_user[2] = "user_name varbinary(255)";
			$table_structure_user[3] = "user_real_name varbinary(255)";
			$table_structure_user[4] = "user_password tinyblob";
			$table_structure_user[5] = "user_newpassword tinyblob";
			$table_structure_user[6] = "user_newpass_time binary(14)";
			$table_structure_user[7] = "user_email tinyblob";
			$table_structure_user[8] = "user_touched binary(14)";
			$table_structure_user[9] = "user_token binary(32)";
			$table_structure_user[10] = "user_email_authenticated binary(14)";
			$table_structure_user[11] = "user_email_token binary(32)";
			$table_structure_user[12] = "user_email_token_expires binary(14)";
			$table_structure_user[13] = "user_registration binary(14)";
			$table_structure_user[14] = "user_editcount int(11)";
			$table_structure_user[15] = "user_wiki_group int(10)";
			$table_structure_user[16] = "user_inactivity int(10)";
			
			
			//Seitentitel wird ermittelt und auf der SpecialPage eingefügt.
			$self = $this->getTitle();
            $this->setHeaders();
			
			$wgOut->addHTML ("<p>Diese Seite dient der Installation der Wiki-Extension 'Collaboration'. <br>
							  Es wird überprüft, ob die Tabelle zur Abspeicherung der Kollaborationswerte in der Datenbank existiert. Weiterhin wird erfolgt die Überprüfung auch für die User-Tabelle, ob diese bereits um eine Spalte für die Gruppenzuordnung erweitert wurde. Zuletzt wird geprüft, ob der Ordner, in welchem die grafischen Visualisierungen der Kollaborationzustände einzelner Gruppen abgelegt werden, existiert.
							  Existieren diese Voraussetzungen noch nicht, so können Sie sie hier automatisch anlegen.</p>");
			
			//wfGetDB ( DB_MASTER ) definiert einen schreibenden Datenbankzugriff.
			$dbr =& wfGetDB( DB_MASTER );
			
			//Struktur der User-Tabelle per SQL-Abfrage abrufen.
			$sql_user = "DESCRIBE `" . $wgDBprefix . "user`";
			$res_user = $dbr->query( $sql_user, __METHOD__ );
			
			//Die aus der abgefragten Struktur der User-Tabelle hervorgehenden Spalten der Tabelle werden auf ein Array geschrieben, um sie später mit der Originalstruktur vergleichen zu können.
			$x=0;
			while ( $row_user = $dbr->fetchObject( $res_user ) ) {
				$compare_user[$x] = $row_user->Field." ".$row_user->Type;
				$compare_user_create[$x] = $row_user->Field;
				$x++;
			}
			
			//Statusabfrage der benötigten Tabellen und Ordner 
			$wgOut->addHTML ("<table>");
			$wgOut->addHTML ("<tr><td colspan=2>Statusübersicht</td></tr>");

			//Überprüfung der User-Tabelle, ob diese in der Datenbank mit der korrekten Struktur vorhanden ist.
			//An erster Stelle erfolgt die Überprüfung ob beide Spalten, user_wiki_group und user_inactivity korrekt sind.
			if(in_array($table_structure_user[15], $compare_user)&&in_array($table_structure_user[16], $compare_user)){
				$checker1 = 0;
				$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b><font color='#00C000'>OK</font></b></td></tr>");
			//Überprüfung, ob Spalte user_wiki_group korrekt in der Tabelle vorliegt.
			}else if (in_array($table_structure_user[15], $compare_user)){
				//Liegt Spalte user_wiki_group korrekt in der Datenbank vor, wird überprüft, ob die Spalte user_inactivity mit falschem Datentyp vorhanden ist.
				if (in_array("user_inactivity", $compare_user_create)){
					$checker1 = 1;
					$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - Spalte user_inactivity muss korrigiert werden</b></td></tr>");
				}else{
					$checker1 = 2;
					$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - Spalte user_inactivity muss erstellt werden</b></td></tr>");
				}
			//Überprüfung, ob Spalte user_inactivity korrekt in der Tabelle vorliegt.
			}else if (in_array($table_structure_user[16], $compare_user)){
				//Liegt Spalte user_inactitvity korrekt in der Datenbank vor, wird überprüft, ob die Spalte user_wiki_group mit falschem Datentyp vorhanden ist.
				if (in_array("user_wiki_group", $compare_user_create)){
					$checker1 = 1;
					$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - Spalte user_wiki_group muss korrigiert werden</b></td></tr>");
				}else{
					$checker1 = 2;
					$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - Spalte user_wiki_group muss erstellt werden</b></td></tr>");
				}
			//Überprüfung, ob beide Spalten, user_inactivity und user_wiki_group, entweder nicht oder mit falschem Datentyp in der Datenbank sind.
			}else if ((in_array($table_structure_user[15], $compare_user)==FALSE)&&(in_array($table_structure_user[16], $compare_user)==FALSE)){
				//Überprüfung ob Spalte user_inactivity mit falschem Datentyp in der Datenbank ist. Ist dies der Fall, muss die Spalte korrigiert werden. Ist die Spalte nicht in der Tabelle, muss sie neu erstellt werden.
				if (in_array("user_inactivity", $compare_user_create)){
					$checker1 = 1;
					$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - Spalte user_inactivity muss korrigiert werden</b></td></tr>");
				}else{
					$checker1 = 2;
					$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - Spalte user_inactivity muss erstellt werden</b></td></tr>");
				}
				//Überprüfung ob Spalte user_wiki_group mit falschem Datentyp in der Datenbank ist. Ist dies der Fall, muss die Spalte korrigiert werden. Ist die Spalte nicht in der Tabelle, muss sie neu erstellt werden.
				if (in_array("user_wiki_group", $compare_user_create)){
					$checker1 = 1;
					$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - Spalte user_wiki_group muss korrigiert werden</b></td></tr>");
				}else{
					$checker1 = 2;
					$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - Spalte user_wiki_group muss erstellt werden</b></td></tr>");
				}
			}

			//Überprüfung, ob die Kollaborations-Tabelle existiert.
			if (!$dbr->tableExists('collaboration')){
				$checker2 = 2;
				$wgOut->addHTML ("<tr><td>Kollaborations-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - muss erstellt werden</b></td></tr>");
			}else{
				//Abfrage der Struktur der Kollaborations-Tabelle.
				$sql1 = "DESCRIBE `" . $wgDBprefix . "collaboration`";
				$res1 = $dbr->query( $sql1, __METHOD__ );
				
				//Die aus der abgefragten Struktur der Kollaborations-Tabelle hervorgehenden Spalten der Tabelle werden auf ein Array geschrieben, um sie später mit der Originalstruktur vergleichen zu können.
				$i=0;
				while ( $row_1 = $dbr->fetchObject( $res1 ) ) {
					$compare_collaboration[$i] = $row_1->Field." ".$row_1->Type;
					$i++;
				}
				
				//Überprüfung, ob die Anzahl der Spalten in aktueller und originaler Kollaborations-Tabelle übereinstimmen.
				if(count($table_structure_collaboration)==count($compare_collaboration)){
					//Überprüfung, ob die einzelnen Spalten mit Namen und Datentyp übereinstimmen.
					for($j=1; $j <= 12; $j++){
						if($table_structure_collaboration[$j]!=$compare_collaboration[$j-1]){
							//Stimmen die Spalten nicht überein, wird ein Counter um eins erhöht.
							$counter_collaboration++;
						}
					}
					
					//Überprüfung, ob der berechnete Counter 0 oder höher ist. Bei 0 liegt die Tabelle korrekt in der Datenbank vor. Bei einem höherem Wert muss sie korrigiert werden.
					if ($counter_collaboration >= 1){
						$checker2 = 1;
						$wgOut->addHTML ("<tr><td>Kollaborations-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - muss korrigiert werden</b></td></tr>");
					}else{
						$checker2 = 0;
						$wgOut->addHTML ("<tr><td>Kollaborations-Tabelle:</td><td><b><font color='#00C000'>OK</font></b></td></tr>");
					}
				}else{
					
					//Dieser Teil wird abgearbeitet, wenn die Anzahl der Spalten nicht übereinstimmen.
					//Überprüfung, ob die einzelnen Spalten mit Namen und Datentyp übereinstimmen.
					for($j=1; $j <= 12; $j++){
						if(in_array($table_structure_collaboration[$j],$compare_collaboration)){
						}else{
							//Stimmen die Spalten nicht überein, wird ein Counter um eins erhöht.
							$counter_collaboration++;
						}
					}

					//Überprüfung, ob der berechnete Counter 0 oder höher ist. Bei 0 liegt die Tabelle korrekt in der Datenbank vor. Bei einem höherem Wert muss sie korrigiert werden.
					if ($counter_collaboration >= 1){
						$checker2 = 1;
						$wgOut->addHTML ("<tr><td>Kollaborations-Tabelle:</td><td><b><font color='#FF0000'>FALSCH</font> - muss korrigiert werden</b></td></tr>");
					}else{
						$checker2 = 0;
						$wgOut->addHTML ("<tr><td>Kollaborations-Tabelle:</td><td><b><font color='#00C000'>OK</font></b></td></tr>");						
					}
				}
			}
			
			//Überprüfung, ob der Ordner zum Ablegen der grafischen Visualisierungen der Kollaborations-Zustände existiert.
			if(file_exists($network_path)){
				$checker3 = 0;
				$wgOut->addHTML ("<tr><td>Ordner Kollaborations-Plots:</td><td><b><font color='#00C000'>OK</font></b></td></tr>");
			}else{
				$checker3 = 2;
				$wgOut->addHTML ("<tr><td>Ordner Kollaborations-Plots:</td><td><b><font color='#FF0000'>FALSCH</font> - muss erstellt werden</b></td></tr>");
			}
			
			//Überprüfung, ob der Ordner mit den Ampelbilder existiert. Diese dienen der Visualisierung der Güte der Kollaborations-Zustände
			if(file_exists($image_path)){
				$wgOut->addHTML ("<tr><td>Ordner Traffic-Lights:</td><td><b><font color='#00C000'><font color='#00C000'>OK</font></b></td></tr>");
			}else{
				$wgOut->addHTML ("<tr><td>Ordner Traffic-Lights:</td><td><b><font color='#FF0000'>FALSCH</font> - </b>Der Ordner mit den Ampel-Abbildungen zur Visualisierung des Kollaborations-Zustandes lässt sich nicht unter dem richtigen Pfad finden.<br>
				Bitte kopieren Sie den Ordner traffic_lights an die folgende Position, sodass der Pfad zum Ordner wie folgt lautet:<p>".$image_path."</td></tr>");
			}
			$wgOut->addHTML ("</table>"); 
			
			//Wenn die User-Tabelle, die Kollaborations-Tabelle oder der Ordner zum ablegen der grafischen Visualisierungen fehlerhaft sind, so kann die Installation bzw. Korrigierung gestartet werden.
			if($checker1==0&&$checker2==0&&$checker3==0){
			}else{
				$wgOut->addHTML ("<p><b>ACHTUNG:</b> Wenn bestehende Tabellen in der Datenbank korrigiert werden müssen, gehen in den Tabellen enthaltene Daten verloren! Bei notwendigen Änderungen an der User-Tabelle betrifft dies nur die Spalten 'user_wiki_group' und 'user_inactivity'. Sind Änderungen an der Tabelle Kollaborations-Tabelle nötig, so wird die Tabelle komplett neu erstellt! Enthaltene Kollaborations-Messwerte gehen dabei verloren!</p>
								  <p>Wird der Ordner zum Ablegen der grafischen Visualisierungen der Kollaborationzustände als fehlerhaft angezeigt, so benötigen Sie zum Erstellen von diesem Ordner Schreibrechte. Da diese in einem Ordner Unterordner der Bilder-Verzeichnisses von MediaWiki abgelegt werden, sollten Sie aber bereits über diese Rechte verfügen.</p>");
								  
				//User muss vor Installation eine Checkbox aktivieren. Ist die Checkbox leer und User klickt auf 'Installation starten' so existiert eine Hidden-Checkbox, welche den User darauf hinweist, dass er die Checkbox aktivieren muss.
				$wgOut->addHTML ("<form method='post' action='".$self->getLocalUrl()."'><input type='hidden' name='installer' value='0' /><input name='installer' value='1' type='checkbox'>Ich möchte die Installation starten!</input><br><input name='act' type='submit' value='Installation starten'></form>");	
			}
			
			
			//Abfrage ob die Checkbox aktiviert und der Button betätigt wurde.	
			if(isset($_POST['installer']) && $_POST['installer'] == '1'){
				
				$wgOut->addHTML ("<table>");
				$wgOut->addHTML ("<tr><td colspan=2>Bearbeitungsfortschritt</td></tr>");
				
				//Überprüfung, ob beide Spalten, user_inactivity und user_wiki_group korrekt in der Datenbank vorhanden sind.
				if(in_array($table_structure_user[15], $compare_user)&&in_array($table_structure_user[16], $compare_user)){
				//Überprüfung, ob Spalte user_wiki_group korrekt in der Tabelle vorliegt.
				}else if (in_array($table_structure_user[15], $compare_user)){
					//Liegt Spalte user_wiki_group korrekt in der Datenbank vor, wird überprüft, ob die Spalte user_inactivity mit falschem Datentyp vorhanden ist.
					if (in_array("user_inactivity", $compare_user_create)){
						//Wenn Spalter user_inactivity mit falschem Datentyp in der Tabelle user vorhanden ist, muss die Spalte erst entfernt und anschließend neu erstellt werden.
						//Spalte Droppen
						$sql_group_drop  = "ALTER TABLE `" . $wgDBprefix . "user` DROP COLUMN user_inactivity";
						$res_group_drop = $dbr->query( $sql_group_drop, __METHOD__ );
						
						//Hinzufügen Spalte 'user_inactivity'
						$sql_group  = "ALTER TABLE `" . $wgDBprefix . "user` ADD user_inactivity int(10)";
						$res_group = $dbr->query( $sql_group, __METHOD__ );
								
						$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b>Spalte user_inactivity wird korrigiert...</b></td></tr>");
					}else{
						//Hinzufügen Spalte 'user_inactivity'
						$sql_group  = "ALTER TABLE `" . $wgDBprefix . "user` ADD user_inactivity int(10)";
						$res_group = $dbr->query( $sql_group, __METHOD__ );
								
						$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b>Spalte user_inactivity wird erstellt...</b></td></tr>");
					}
					
				//Erneutes Laden der Installationsseite, um anschließend Status zu überprüfen
				header( 'refresh:1; url='.$self->getLocalUrl() );					
					
				//Überprüfung, ob Spalte user_inactivity korrekt in der Tabelle vorliegt.
				}else if (in_array($table_structure_user[16], $compare_user)){
					//Liegt Spalte user_inactitvity korrekt in der Datenbank vor, wird überprüft, ob die Spalte user_wiki_group mit falschem Datentyp vorhanden ist.
					if (in_array("user_wiki_group", $compare_user_create)){
						//Wenn Spalter user_wiki_group mit falschem Datentyp in der Tabelle user vorhanden ist, muss die Spalte erst entfernt und anschließend neu erstellt werden.
						//Spalte Droppen
						$sql_group_drop  = "ALTER TABLE `" . $wgDBprefix . "user` DROP COLUMN user_wiki_group";
						$res_group_drop = $dbr->query( $sql_group_drop, __METHOD__ );
						
						//Hinzufügen Spalte 'user_wiki_group'
						$sql_group  = "ALTER TABLE `" . $wgDBprefix . "user` ADD user_wiki_group int(10)";
						$res_group = $dbr->query( $sql_group, __METHOD__ );
								
						$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b>Spalte user_wiki_group wird korrigiert...</b></td></tr>");					
					}else{
						//Hinzufügen Spalte 'user_wiki_group'
						$sql_group  = "ALTER TABLE `" . $wgDBprefix . "user` ADD user_wiki_group int(10)";
						$res_group = $dbr->query( $sql_group, __METHOD__ );
								
						$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b>Spalte user_wiki_group wird erstellt...</b></td></tr>");						
					}
					
				//Erneutes Laden der Installationsseite, um anschließend Status zu überprüfen
				header( 'refresh:1; url='.$self->getLocalUrl() );	
					
				//Überprüfung, ob beide Spalten, user_inactivity und user_wiki_group, entweder nicht oder mit falschem Datentyp in der Datenbank sind.
				}else if ((in_array($table_structure_user[15], $compare_user)==FALSE)&&(in_array($table_structure_user[16], $compare_user)==FALSE)){
					//Überprüfung ob Spalte user_inactivity mit falschem Datentyp in der Datenbank ist. Ist dies der Fall, muss die Spalte korrigiert werden. Ist die Spalte nicht in der Tabelle, muss sie neu erstellt werden.
					if (in_array("user_inactivity", $compare_user_create)){
						//Wenn Spalter user_inactivity mit falschem Datentyp in der Tabelle user vorhanden ist, muss die Spalte erst entfernt und anschließend neu erstellt werden.
						//Spalte Droppen
						$sql_group_drop  = "ALTER TABLE `" . $wgDBprefix . "user` DROP COLUMN user_inactivity";
						$res_group_drop = $dbr->query( $sql_group_drop, __METHOD__ );
						
						//Hinzufügen Spalte 'user_inactivity'
						$sql_group  = "ALTER TABLE `" . $wgDBprefix . "user` ADD user_inactivity int(10)";
						$res_group = $dbr->query( $sql_group, __METHOD__ );
								
						$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b>Spalte user_inactivity wird korrigiert...</b></td></tr>");
					}else{
						//Hinzufügen Spalte 'user_inactivity'
						$sql_group  = "ALTER TABLE `" . $wgDBprefix . "user` ADD user_inactivity int(10)";
						$res_group = $dbr->query( $sql_group, __METHOD__ );
								
						$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b>Spalte user_inactivity wird erstellt...</b></td></tr>");
					}
					//Überprüfung ob Spalte user_wiki_group mit falschem Datentyp in der Datenbank ist. Ist dies der Fall, muss die Spalte korrigiert werden. Ist die Spalte nicht in der Tabelle, muss sie neu erstellt werden.
					if (in_array("user_wiki_group", $compare_user_create)){
						//Wenn Spalter user_wiki_group mit falschem Datentyp in der Tabelle user vorhanden ist, muss die Spalte erst entfernt und anschließend neu erstellt werden.
						//Spalte Droppen
						$sql_group_drop  = "ALTER TABLE `" . $wgDBprefix . "user` DROP COLUMN user_wiki_group";
						$res_group_drop = $dbr->query( $sql_group_drop, __METHOD__ );
						
						//Hinzufügen Spalte 'user_wiki_group'
						$sql_group  = "ALTER TABLE `" . $wgDBprefix . "user` ADD user_wiki_group int(10)";
						$res_group = $dbr->query( $sql_group, __METHOD__ );
								
						$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b>Spalte user_wiki_group wird korrigiert...</b></td></tr>");
					}else{
						//Hinzufügen Spalte 'user_wiki_group'
						$sql_group  = "ALTER TABLE `" . $wgDBprefix . "user` ADD user_wiki_group int(10)";
						$res_group = $dbr->query( $sql_group, __METHOD__ );
								
						$wgOut->addHTML ("<tr><td>User-Tabelle:</td><td><b>Spalte user_wiki_group wird erstellt...</b></td></tr>");
					}
						//Erneutes Laden der Installationsseite, um anschließend Status zu überprüfen.
						header( 'refresh:1; url='.$self->getLocalUrl() );
				}
				
				//Überprüfung, ob die Tabelle zur Speicherung der Kollaborationswerte existiert.
				if (!$dbr->tableExists('collaboration')){
					//Wenn die Tabelle nicht existiert, wird sie erstellt.
					$sql  = "CREATE TABLE `" . $wgDBprefix . "collaboration` (";
					$sql .= "`row_names` text,";
					$sql .= "`time` text,";
					$sql .= "`pa` bigint(20) DEFAULT NULL,";
					$sql .= "`den` double DEFAULT NULL,";
					$sql .= "`cent` double DEFAULT NULL,";
					$sql .= "`deg` double DEFAULT NULL,";
					$sql .= "`dis` bigint(20) DEFAULT NULL,";
					$sql .= "`calibrated_den` double DEFAULT NULL,";
					$sql .= "`calibrated_cent` double DEFAULT NULL,";
					$sql .= "`calibrated_deg` double DEFAULT NULL,";
					$sql .= "`calibrated_dis` double DEFAULT NULL,";
					$sql .= "`collaboration_final` double DEFAULT NULL";
					$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1;";
					$res = $dbr->query( $sql, __METHOD__ );
					
					$wgOut->addHTML ("<tr><td>Kollaborations-Tabelle:</td><td><b>Wird erstellt...</b></td></tr>");
					
					//Erneutes Laden der Installationsseite, um anschließend Status zu überprüfen
					header( 'refresh:1; url='.$self->getLocalUrl() );
				}else{	
					if(count($table_structure_collaboration)==count($compare_collaboration)){
						if ($counter_collaboration >= 1){
							//Tabelle neu erstellen und vorherige Tabelle löschen.
							$sql_drop = "DROP TABLE `" . $wgDBprefix . "collaboration`";
							$res_drop = $dbr->query( $sql_drop, __METHOD__ );
						
							$sql  = "CREATE TABLE `" . $wgDBprefix . "collaboration` (";
							$sql .= "`row_names` text,";
							$sql .= "`time` text,";
							$sql .= "`pa` bigint(20) DEFAULT NULL,";
							$sql .= "`den` double DEFAULT NULL,";
							$sql .= "`cent` double DEFAULT NULL,";
							$sql .= "`deg` double DEFAULT NULL,";
							$sql .= "`dis` bigint(20) DEFAULT NULL,";
							$sql .= "`calibrated_den` double DEFAULT NULL,";
							$sql .= "`calibrated_cent` double DEFAULT NULL,";
							$sql .= "`calibrated_deg` double DEFAULT NULL,";
							$sql .= "`calibrated_dis` double DEFAULT NULL,";
							$sql .= "`collaboration_final` double DEFAULT NULL";
							$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1;";
							$res = $dbr->query( $sql, __METHOD__ );
							
							$wgOut->addHTML ("<tr><td>Kollaborations-Tabelle:</td><td><b>Wird korrigiert...</b></td></tr>");
							
							//Erneutes Laden der Installationsseite, um anschließend Status zu überprüfen
							header( 'refresh:1; url='.$self->getLocalUrl() );
						}else{
						}
					}else{
						if ($counter_collaboration >= 1){
					
							//Tabelle neu erstellen und vorherige Tabelle löschen.
							$sql_drop = "DROP TABLE `" . $wgDBprefix . "collaboration`";
							$res_drop = $dbr->query( $sql_drop, __METHOD__ );
						
							$sql  = "CREATE TABLE `" . $wgDBprefix . "collaboration` (";
							$sql .= "`row_names` text,";
							$sql .= "`time` text,";
							$sql .= "`pa` bigint(20) DEFAULT NULL,";
							$sql .= "`den` double DEFAULT NULL,";
							$sql .= "`cent` double DEFAULT NULL,";
							$sql .= "`deg` double DEFAULT NULL,";
							$sql .= "`dis` bigint(20) DEFAULT NULL,";
							$sql .= "`calibrated_den` double DEFAULT NULL,";
							$sql .= "`calibrated_cent` double DEFAULT NULL,";
							$sql .= "`calibrated_deg` double DEFAULT NULL,";
							$sql .= "`calibrated_dis` double DEFAULT NULL,";
							$sql .= "`collaboration_final` double DEFAULT NULL";
							$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1;";
							$res = $dbr->query( $sql, __METHOD__ );

							$wgOut->addHTML ("<tr><td>Kollaborations-Tabelle:</td><td><b>Wird korrigiert...</b></td></tr>");
							
							//Erneutes Laden der Installationsseite, um anschließend Status zu überprüfen.
							header( 'refresh:1; url='.$self->getLocalUrl() );
						}else{						
						}				
					}
				}
				
				//Überprüfung, ob Ordner in den richtigen Verzeichnissen liegen.
				if(file_exists($network_path)){
				}else{
					mkdir($network_path, 0777);
					$wgOut->addHTML ("<tr><td>Ordner Kollaborations-Plots:</td><td><b>Wird erstellt...</b></td></tr>");
					header( 'refresh:1; url='.$self->getLocalUrl() );
				}
				$wgOut->addHTML ("</table>");
			}else{
				//Hinweis, welcher ausgegeben wird, wenn Button angeklickt wird und Checkbox nicht aktiviert wurde.
				if(isset($_POST['installer']) && $_POST['installer'] == '0'){
					$wgOut->addHTML ("Um die Statusüberprüfung und gegebenenfalls den Installer zu starten, aktivieren Sie bitte die Checkbox!");
				}
			}
		}
}