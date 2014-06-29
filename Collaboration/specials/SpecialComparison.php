<?php
class SpecialComparison extends SpecialPage {
        function __construct() {
				//Dieser Befehl lädt das Grundgerüst einer SpecialPage. Der Name der SpecialPage wird geändert und es kann ein Recht zur Beschränkung des Zugriffs vergeben werden.
				//Um den Zugriff auf die Seite nur für System-Operatoren zu gewähren, ist die Zeile parent::__construct um den Parameter 'sysop' zu ergänzen.
                parent::__construct( 'Comparison', 'sysop' );
        }
 
        function execute( $par ) {
				//Definition globaler Systemvariablen und weiterer, welche im weiteren Skript benötigt werden.
				global $wgOut, $wgRequest, $wgDBname;
				global $res_group_time, $res_group_time1, $row_start_time, $row_end_time;
				$selected_group = "";
				$checkbox_inactivity = "";
				
				//An dieser Stelle werden die Dateipfade hinterlegt, unter denen benötigte Bilddateien zu finden sind bzw. abgespeichert werden.
				//$image_path beschreibt den Pfad zu den Ampelbildern, welche als Veranschaulichung des Kollaborationsstatusses dienen.
				//$network_path beschreibt den Pfad, unter dem die graphische Veranschaulichung der sozialen Netzwerke zu finden sind.
				//Beide Pfade sind anzupassen.				
				$image_path = '/'.$wgDBname.'/extensions/Collaboration/traffic_lights/';
				$network_path = '/'.$wgDBname.'/images/collaboration_plots/';
				
				//Trotz Vergabe der Zugriffsbeschränkung, kann ein User einer Gruppe ohne die entsprechende Berechtigung über den direkten Link auf diese SpecialPage zugreifen.
				//Wird nur verwendet, wenn der Parameter 'sysop' im Constructor vergeben wurde.
				if (  !$this->userCanExecute( $this->getUser() )  ) {
					$this->displayRestrictionError();
					return;
				}
				
				//Definition zweier Arrays, auf welche später die einzelnen Gruppennummern und Zeiten geschrieben werden, um diese in einem Dropdown-Menü darzustellen.
				$group_array = array();
				$time_array = array();
				
				//Seitentitel wird ermittelt und auf der SpecialPage eingefügt.
				$self = $this->getTitle();
                $this->setHeaders();
 
				//wfGetDB ( DB_SLAVE ) definiert einen lesenden Datenbankzugriff.	
				$dbr =& wfGetDB( DB_SLAVE );
				
				//SQL-Abfrage um die maximale Gruppennummer zu ermitteln.
				$res_max_group = $dbr->select('user', 'MAX(user_wiki_group) as maximum_group');
				
				//Definierte SQL-Abfrage wird an die Datenbank gesendet und Rückgabewert wird auf Variable geschrieben.
				$row_max_group = $dbr->fetchObject( $res_max_group );
				
				//Jede Gruppennummer wird in eine Zelle eines Arrays geschrieben.
				for ($i=1; $i <= $row_max_group->maximum_group; $i++){
					$group_array[$i] = $i ;
				}
				
				//SQL-Abfrage, um anschließend zu testen, ob es Kollaborationswerte gibt.
				$res_collaboration1  = $dbr->select ( 'collaboration', array('MAX(time) as timer_max', 'pa', 'den', 'cent', 'deg', 'dis', 'calibrated_den', 'calibrated_cent', 'calibrated_deg', 'calibrated_dis', 'collaboration_final'));	
				$checkEmptyness = $dbr->fetchObject( $res_collaboration1 );
				$arrayEmptyness = $checkEmptyness->pa;				
				
				
				$wgOut->addHTML ("<p>Diese Seite soll dazu dienen, den Kollaborationsstatus einer Gruppe zu zwei unterschiedlichen Zeitpunkten miteinander zu vergleichen.<br>
								  Sollten Sie Fragen zu den hier dargestellten Kennzahlen haben, so können Sie die Bedeutung auf der SpecialPage mit den <a href='/". $wgDBname ."/index.php/Special:Collaboration_-_Frequently_Asked_Questions' title='CollaborationFAQ'>Frequently Asked Questions</a> zur Wiki-Extension Collaboration nachlesen. Auf dieser Seite finden Sie auch Informationen darüber, wie die Visualisierungen der sozialen Netzwerke zu interpretieren ist.</p>
								  <p>Bitte wählen Sie hierfür zunächst eine Gruppe und anschließend zwei Zeitpunkte aus, zu welchen Sie den Status vergleichen möchten.</p>
								  <p>Die Zeitauswahl ist dabei wie folgt zu interpretieren: Jahr-Monat-Tag-Uhrzeit(24h)</p>
								  <p>Erscheinen nach der Gruppenauswahl keine selektierbaren Zeiten, so starten Sie bitte das R-Skript zur Ermittelung der Kollaborations-Kennzahlen.");
				
				//Beginn des HTML Formulars, in welchem die gewünschte Gruppe und die zwei Zeitpunkte zu welchen der Kollaborationsstatus ermittelt wurde, ausgewählt werden.
				
				if((empty($group_array)==FALSE)&&(empty($arrayEmptyness)==FALSE)){
				$wgOut->addHTML ("<form method='post' action='".$self->getLocalUrl()."'>");
				$wgOut->addHTML ("<select name='Gruppenauswahl'>");
				
				//Es wird überprüft, ob bereits eine Gruppe ausgewählt wurde. Ist dies der Fall, so wird dem Nutzer in einem Dropdown-Menü die gewählte Gruppe angezeigt.
				//Wurde noch keine Gruppe gewählt, so kann der User über das Menü eine gewünschte Gruppe auswählen.
				if(isset($_POST['Gruppenauswahl'])){
					$selected_group = $_POST["Gruppenauswahl"];
					//Jede Gruppennummer, welche im Array abgelegt ist, wird auf ein Feld im Dropdown-Menü geschrieben
					for($i=1;$i<=$row_max_group->maximum_group;$i++) {
						if ($i == $selected_group) {
								$wgOut->addHTML ("<option value='". $i ."' selected>". $group_array[$i] ."</option>");
						}else {
							$wgOut->addHTML ("<option value='". $i ."'>". $group_array[$i] ."</option>");
						}
					}
				}
				else {
						for($i=1;$i<=$row_max_group->maximum_group;$i++) {
							$wgOut->addHTML ("<option value='". $i ."'>". $group_array[$i] ."</option>");
						}
				}
				$wgOut->addHTML ("</select>");
				}
				
				
				//Im nachfolgenden Teil wird geprüft ob eine Gruppe ausgewählt wurde und anschließend werden die Zeitpunkte, zu denen Messwerte zur Kollaboration vorliegen, in ein Dropdown-Menü eingetragen.
				if(isset($_POST["Gruppenauswahl"])){
					$selected_group = $_POST["Gruppenauswahl"];
					$res_group_time = $dbr->select('collaboration', array ('time as group_time'), 'pa='.$selected_group);
					$res_group_time1 = $dbr->select('collaboration', array ('time as group_time'), 'pa='.$selected_group);
					
					$wgOut->addHTML ("<select name='Startzeitauswahl'>");				

					//Es wird überprüft, ob bereits ein erster Zeitpunkt ausgewählt wurde. Ist dies der Fall, so wird dem Nutzer in einem Dropdown-Menü die gewählte Zeit angezeigt.
					//Wurde noch kein erster Zeitpunkt gewählt, so kann der User über das Menü eine gewünschte Zeit auswählen.
					if(isset($_POST['Startzeitauswahl'])){
						$selected_start_time = $_POST['Startzeitauswahl'];
						
						while ( $row_group_time = $dbr->fetchObject( $res_group_time ) ) {						
							if ($row_group_time->group_time == $selected_start_time) {
								$wgOut->addHTML ("<option value='". $row_group_time->group_time ."' selected>". $row_group_time->group_time ."</option>");
							}else{
								$wgOut->addHTML ("<option value='". $row_group_time->group_time ."'>". $row_group_time->group_time ."</option>");
							}
						}
					}
					else {
						while ( $row_group_time = $dbr->fetchObject( $res_group_time ) ) {
							$wgOut->addHTML ("<option value='". $row_group_time->group_time ."'>". $row_group_time->group_time ."</option>");
						}
					}					
					$wgOut->addHTML ("</select>");
				
					$wgOut->addHTML ("<select name='Endzeitauswahl'>");						

					//Es wird überprüft, ob bereits eine zweiter Zeitpunkt ausgewählt wurde. Ist dies der Fall, so wird dem Nutzer in einem Dropdown-Menü die gewählte Zeit angezeigt.
					//Wurde noch kein zweiter Zeitpunkt gewählt, so kann der User über das Menü eine gewünschte Zeit auswählen.
					if(isset($_POST['Endzeitauswahl'])){
						$selected_end_time = $_POST['Endzeitauswahl'];
						
						while ( $row_group_time1 = $dbr->fetchObject( $res_group_time1 ) ) {						
							if ($row_group_time1->group_time == $selected_end_time) {
								$wgOut->addHTML ("<option value='". $row_group_time1->group_time ."' selected>". $row_group_time1->group_time ."</option>");
							}else{
								$wgOut->addHTML ("<option value='". $row_group_time1->group_time ."'>". $row_group_time1->group_time ."</option>");
							}
						}
					}
					else {
						while ( $row_group_time1 = $dbr->fetchObject( $res_group_time1 ) ) {
							$wgOut->addHTML ("<option value='". $row_group_time1->group_time ."'>". $row_group_time1->group_time ."</option>");
						}
					}
					$wgOut->addHTML("</select>");

					//Hinzufügen des Auswahlbuttons, um die gewählten Zeitpunkte zu bestätigen und um die Messwerte zu beiden Zeitpunkten abzufragen.
					$wgOut->addHTML ("<input name='act2' type='submit' value='Select Time'></form>"); 
				}
				else {
					if(empty($group_array)==FALSE){
					//Hinzufügen des Auswahlbuttons, um die gewählte Gruppe zu bestätigen und um die vorhanden Zeitpunkte zu denen Messwerte für die Kollaboration vorliegen, abzufragen.
					$wgOut->addHTML ("<input name='act2' type='submit' value='Select Group'></form>");
					}else{
						$wgOut->addHTML ("<p><b>Es existieren entweder keine festgelegten Gruppen in der Datenbank, oder es wurden noch keine Kennzahlen durch das R-Skript ermittelt!</b><br>
										  Bitte vergewissen Sie sich, ob Sie für einige Mitgliedern im Wiki Gruppennummern vergeben und die Analyse zur Ermittelung der Kollaboration in Gruppen mindestens einmal ausgeführt haben.</p>");
					}
				}
				
				//Überprüfung, ob zwei Zeitpunkte ausgewählt wurden.
				if(isset($_POST['Startzeitauswahl'], $_POST['Endzeitauswahl'])){ 
				
					//Der gewählte erste Zeitpunkt wird auf eine Variable geschrieben.
					$selected_start_time = $_POST['Startzeitauswahl'];
					
					//Definition einer SQL-Abfrage, welche die Kennzahlen der gewählten Gruppe zum gewählten ersten Zeitpunkt abfragen.
					$res_start_time = $dbr->select('collaboration', array ('pa', 'den', 'cent', 'deg', 'dis', 'calibrated_den', 'calibrated_cent', 'calibrated_deg', 'calibrated_dis', 'collaboration_final'), array('pa="'.$selected_group.'"' ,'time="'.$selected_start_time.'"'));
					
					//Definierte SQL-Abfrage wird an die Datenbank gesendet und Rückgabewert wird auf Variable geschrieben.
					$row_start_time = $dbr->fetchObject( $res_start_time );

					//Der gewählte zweite Zeitpunkt wird auf eine Variable geschrieben.
					$selected_end_time = $_POST['Endzeitauswahl'];
					
					//Definition einer SQL-Abfrage, welche die Kennzahlen der gewählten Gruppe zum gewählten zweiten Zeitpunkt abfragen.
					$res_end_time = $dbr->select('collaboration', array ('pa','den', 'cent', 'deg', 'dis', 'calibrated_den', 'calibrated_cent', 'calibrated_deg', 'calibrated_dis', 'collaboration_final'), array('pa="'.$selected_group.'"' ,'time="'.$selected_end_time.'"'));
					
					//Definierte SQL-Abfrage wird an die Datenbank gesendet und Rückgabewert wird auf Variable geschrieben.
					$row_end_time = $dbr->fetchObject( $res_end_time );
					
					//Definition einer Datenbankabfrage, welche die User ermittelt, die der gewählten Gruppe zugeordnet sind.
					$res_user = $dbr->select( 'user', array('user_id', 'user_name', 'user_real_name', 'user_wiki_group', 'user_inactivity'), 'user_wiki_group='.$selected_group );
					
					//Das Layout der Ausgabe wird durch Tabellen definiert.
					$wgOut->addHTML ("<h1 id=Gruppe". $selected_group .">Gruppe ". $selected_group .":</h1>");
					$wgOut->addHTML ("<table>");
					
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td colspan='6'><p><h3>Übersicht über die Mitglieder der Gruppe</h3></p></td>");
					$wgOut->addHTML ("</tr>");
					
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td>User ID</td><td>User Name</td><td>Real Name</td><td>Inactivity</td>");
					$wgOut->addHTML ("</tr>");
					
					//Für jedes Mitglied wird eine neue Reihe in der Tabelle erzeugt. Diese enthält User ID, Usernamen und den realen Namen.
					while ( $row_user_new = $dbr->fetchObject( $res_user ) ) {
						//Überprüfung, ob aktuell betrachteter Nutzer inaktiv ist
						if($row_user_new->user_inactivity==1){
							$checkbox_inactivity = "checked";
						}else{
							$checkbox_inactivity = "";
						}
					
						//Ersetzen von möglichen Leerzeichen im Benutzernamen
						$user_name_link = str_replace(' ', '_', $row_user_new->user_name);
					
						$wgOut->addHTML ("<tr><td>" .
						$row_user_new->user_id . "</td><td><a href='/". $wgDBname ."/index.php/User:". $user_name_link ."' title='User:". $user_name_link ."'>" .
						$row_user_new->user_name . "</a></td><td>" .
						$row_user_new->user_real_name . "</td>". 
						"<td><input name='user_inactivity". $row_user_new->user_id ."' type='checkbox' disabled ". $checkbox_inactivity ."></td></tr>");
					}

					//Der gewählte erste und zweite Zeitpunkt wird ausgegeben.
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td colspan='3'><p><h3>Zeitpunkt 1: ".$selected_start_time."</h3></p></td>");
					$wgOut->addHTML ("<td colspan='2'><p><h3>Zeitpunkt 2: ".$selected_end_time."</h3></p></td>");
					$wgOut->addHTML ("</tr>");
					
					//Es wird geprüft wie der Kollaborationsstatus der Gruppe zu beiden gewählten Zeitpunkten war. Abhängig vom Wert wird die Ampel in einer anderen Farbe angezeigt, um den Status graphisch hervorzuheben.
					if($row_start_time->collaboration_final>0.7)
						$wgOut->addHTML ("<tr><td><img src=".$image_path."gruen_quer.bmp alt='Gruene Ampel' width='100' height='50'/></td><td>Kollaborations-Status: </td><td><b>Sehr gut</b></td>");
						
					else if($row_start_time->collaboration_final>0.5&&$row_start_time->collaboration_final<=0.7)
						$wgOut->addHTML ("<tr><td><img src=".$image_path."gelb_quer.bmp alt='Gelbe Ampel' width='100' height='50'/></td><td>Kollaborations-Status: </td><td><b>Ok</b></td>");

					else if($row_start_time->collaboration_final<=0.5)
						$wgOut->addHTML ("<tr><td><img src=".$image_path."rot_quer.bmp alt='Rote Ampel' width='100' height='50'/></td><td>Kollaborations-Status: </td><td><b>Schlecht</b></td>");
						
					if($row_end_time->collaboration_final>0.7)
						$wgOut->addHTML ("<td><img src=".$image_path."gruen_quer.bmp alt='Gruene Ampel' width='100' height='50'/></td><td>Kollaborations-Status: </td><td><b>Sehr gut</b></td></tr>");
						
					else if($row_end_time->collaboration_final>0.5&&$row_end_time->collaboration_final<=0.7)
						$wgOut->addHTML ("<td><img src=".$image_path."gelb_quer.bmp alt='Gelbe Ampel' width='100' height='50'/></td><td>Kollaborations-Status: </td><td><b>Ok</b></td></tr>");

					else if($row_end_time->collaboration_final<=0.5)
						$wgOut->addHTML ("<td><img src=".$image_path."rot_quer.bmp alt='Rote Ampel' width='100' height='50'/></td><td>Kollaborations-Status: </td><td><b>Schlecht</b></td></tr>");
					
					//Ausgabe der Kennzahlen. Auf der linken Seite werden die Werte zum Zeitpunkt eins und auf der rechten Seite zum Zeitpunkt zwei dargestellt.
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td colspan='3'><h4><p><center>Übersicht über die Kollaborations-Werte</center></p></h4></td>");
					$wgOut->addHTML ("<td colspan='3'><h4><p><center>Übersicht über die Kollaborations-Werte</center></p></h4></td>");
					$wgOut->addHTML ("</tr>");
					
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td colspan='3'><h5><p><center>Rohwerte</center></p></h5></td>");
					$wgOut->addHTML ("<td colspan='3'><h5><p><center>Rohwerte</center></p></h5></td>");
					$wgOut->addHTML ("</tr>");
					
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td>Density (DEN) - roh:</td><td> ". $row_start_time->den ."</td><td></td>");
					$wgOut->addHTML ("<td>Density (DEN) - roh:</td><td> ". $row_end_time->den ."</td><td></td>");
					$wgOut->addHTML ("</tr>");
					
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td>Centralization (CENT) - roh:</td><td> ". $row_start_time->cent ."</td><td></td>");
					$wgOut->addHTML ("<td>Centralization (CENT) - roh:</td><td> ". $row_end_time->cent ."</td><td></td>");
					$wgOut->addHTML ("</tr>");
					
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td>Median Weighted Degree (DEG) - roh:</td><td> ". $row_start_time->deg ."</td><td></td>");
					$wgOut->addHTML ("<td>Median Weighted Degree (DEG) - roh:</td><td> ". $row_end_time->deg ."</td><td></td>");
					$wgOut->addHTML ("</tr>");

					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td>Discussion-Posts (DIS) - roh:</td><td> ". $row_start_time->dis ."</td><td></td>");
					$wgOut->addHTML ("<td>Discussion-Posts (DIS) - roh:</td><td> ". $row_end_time->dis ."</td><td></td>");
					$wgOut->addHTML ("</tr>");	
					
					//Graphische Darstellung des Sozialen Netzwerkes zu beiden gewählten Zeitpunkten
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td colspan='3'><h4><p><center>Visualisierung des sozialen Netzwerks</center></p></h4></td>");
					$wgOut->addHTML ("<td colspan='3'><h4><p><center>Visualisierung des sozialen Netzwerks</center></p></h4></td>");
					$wgOut->addHTML ("</tr>");

					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td colspan='3'><img src=". $network_path ."". $selected_start_time ."-Gruppe-". $selected_group .".png alt='Grafische Abbildung des aktuellen Sozialen Netzwerkes' width='540' height='405'/></td>");
					$wgOut->addHTML ("<td colspan='3'><img src=". $network_path ."". $selected_end_time ."-Gruppe-". $selected_group .".png alt='Grafische Abbildung des aktuellen Sozialen Netzwerkes' width='540' height='405'/></td>");
					$wgOut->addHTML ("<tr>");									
					
					$wgOut->addHTML ("</table>");
				}
		}
}