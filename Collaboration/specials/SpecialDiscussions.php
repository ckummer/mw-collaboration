<?php
class SpecialDiscussions extends SpecialPage {
        function __construct() {
			//Dieser Befehl lädt das Grundgerüst einer SpecialPage. Der Name der SpecialPage wird geändert und es kann ein Recht zur Beschränkung des Zugriffs vergeben werden.
			//Um den Zugriff auf die Seite nur für System-Operatoren zu gewähren, ist die Zeile parent::__construct um den Parameter 'sysop' zu ergänzen.
            parent::__construct( 'Discussions', 'sysop' );
        }
		
		function execute( $par ) {
			//Definition globaler Systemvariablen und weiterer Variablen, welche im weiteren Skript benötigt werden.
			global $wgOut, $wgRequest, $wgDBname;
			global $res_group_time, $res_group_time1, $row_start_time, $row_end_time;
			$selected_group = "";
			$checkbox_inactivity = "";
			
			//Definition zweier Arrays, auf welche später die einzelnen Gruppennummern und Zeiten geschrieben werden, um diese in einem Dropdown-Menü darzustellen.
			$group_array = array();
			$time_array = array();
			
			//Definition zweier weiterer Arrays, auf welche später im Quelltext die diskutierten Seiten und die dazugehörigen Nutzer geschrieben werden.
			$array_compare_pages = array();
			$array_user = array();
			
			//Seitentitel wird ermittelt und auf der SpecialPage eingefügt.
			$self = $this->getTitle();
            $this->setHeaders();
			
			//Trotz Vergabe der Zugriffsbeschränkung, kann ein User einer Gruppe ohne die entsprechende Berechtigung über den direkten Link auf diese SpecialPage zugreifen.
			//Wird nur verwendet, wenn der Parameter 'sysop' im Constructor vergeben wurde.
			if (  !$this->userCanExecute( $this->getUser() )  ) {
				$this->displayRestrictionError();
				return;
			}

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
			
			$wgOut->addHTML ("<p>Auf dieser Seite können Sie sich alle Diskussionen einer Gruppe auf den dafür vorgesehenen Seiten anzeigen lassen.<br>
							  <p>Bitte wählen Sie hierfür zunächst eine Gruppe und anschließend einen Zeitpunkt aus. Es werden Ihnen nachfolgend alle Diskussionen der Gruppe bis zu diesem Zeitpunkt angezeigt.</p>
							  <p>Die Zeitauswahl ist dabei wie folgt zu interpretieren: Jahr-Monat-Tag-Uhrzeit(24h)</p>");

			if(empty($group_array)==FALSE){				  
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
			if(isset($_POST["Gruppenauswahl"])){
				$selected_group = $_POST["Gruppenauswahl"];
				$res_group_time = $dbr->select('collaboration', array ('time as group_time'), 'pa='.$selected_group);
					
				$wgOut->addHTML ("<select name='Startzeitauswahl'>");				

				//Es wird überprüft, ob bereits ein Zeitpunkt ausgewählt wurde. Ist dies der Fall, so wird dem Nutzer in einem Dropdown-Menü die gewählte Zeit angezeigt.
				//Wurde noch kein Zeitpunkt gewählt, so kann der User über das Menü eine gewünschte Zeit auswählen.
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

			//Überprüfung, ob ein Zeitpunkt ausgewählt wurde.
			if(isset($_POST['Startzeitauswahl'])){
			
				//Der gewählte Zeitpunkt wird auf eine Variable geschrieben.
				$selected_time = $_POST['Startzeitauswahl'];
				
				//Umformatierung des Zeitpunktes, damit dieser als maximaler Timestamp später aus Revisionstabelle abgefragt werden kann.
				//Überprüfung, ob Messungen stündlich oder in kürzeren Abständen vorgenommen wurden. Bei Intervallen unter einer Stunde verfügt der String über drei Stellen mehr.
				$length_selected_time = strlen($selected_time);
				
				//Bei einer Länge von 13 wird die Messung stündlich ausgeführt, bei einer Länge von 16 in einem Intervall unter einer Stunde.
				if($length_selected_time==13){
					//Ersetzen von Bindestrichen im Timestamp
					$selected_time_without = str_replace('-', '', $selected_time);
					
					//String, welcher vier Nullen enthält. Dieser symbolisiert die Minuten und Sekunden, welche nachfolgend an den aktuellen String angefügt werden.
					$time_minutes_seconds = "0000";
					$selected_time_new = $selected_time_without.$time_minutes_seconds;
				}else{
					//Ersetzen von Bindestrichen im Timestamp
					$selected_time_without = str_replace('-', '', $selected_time);
					
					//String, welcher vier Nullen enthält. Dieser symbolisiert die Minuten und Sekunden, welche nachfolgend an den aktuellen String angefügt werden.
					$time_minutes_seconds = "00";
					$selected_time_new = $selected_time_without.$time_minutes_seconds;					
				}
				
				//Abfrage um festzustellen, welche Seiten im Wiki mindestens zwei Personen der Gruppe kommentiert haben, welche Seiten im Wiki dies betrifft und welche Benutzer dies waren.
				$sql_pages_discussed = ("SELECT distinct t1.rev_page, t2.user_name
										 FROM (SELECT distinct rev_page, count(distinct rev_user) as rev_count_user FROM revision,page,user WHERE rev_page = page_id and rev_user = user_id and page_namespace in (1, 3,5,7,9,11,13,15) and rev_user > 0 and rev_timestamp <= ". $selected_time_new ." and user_inactivity IS NULL and user_wiki_group=". $selected_group ." GROUP BY page_title, rev_page HAVING count(distinct rev_user) > 1 ORDER BY rev_page ASC) t1
										 LEFT JOIN ( SELECT distinct rev_page, user_name FROM revision,page,user WHERE rev_page = page_id and rev_user = user_id and page_namespace in (1, 3,5,7,9,11,13,15) and rev_user > 0 and rev_timestamp <= ". $selected_time_new ." and user_inactivity IS NULL and user_wiki_group=". $selected_group ." GROUP BY rev_page, rev_user ORDER BY rev_page, rev_parent_id ASC ) t2
										 ON t1.rev_page = t2.rev_page");
				$res_pages_discussed = $dbr->query( $sql_pages_discussed, __METHOD__ );
			
				//Die Ergebnisse der Abfrage werden auf zwei Arrays geschrieben. Dies gewährleistet eine optisch bessere Ausgabe der Ergebnisse.
				$i=1;
				while ( $row_pages_discussed = $dbr->fetchObject( $res_pages_discussed ) ) {
					$array_compare_pages[$i] = $row_pages_discussed->rev_page;
					$array_user[$i] 		 = $row_pages_discussed->user_name;
					$i++;
				}
				
				//Das Layout der Ausgabe wird durch Tabellen definiert.
				$wgOut->addHTML ("<h1 id=Gruppe". $selected_group .">Gruppe ". $selected_group .":</h1>");
				$wgOut->addHTML ("<table>");
					
				$wgOut->addHTML ("<tr>");
				$wgOut->addHTML ("<td colspan='6'><p><h3>Übersicht über die Mitglieder der Gruppe</h3></p></td>");
				$wgOut->addHTML ("</tr>");
					
				$wgOut->addHTML ("<tr>");
				$wgOut->addHTML ("<td>User ID</td><td>User Name</td><td>Real Name</td><td>Inactivity</td>");
				$wgOut->addHTML ("</tr>");
				
				//Definition einer Datenbankabfrage, welche die User ermittelt, die der gewählten Gruppe zugeordnet sind.
				$res_user = $dbr->select( 'user', array('user_id', 'user_name', 'user_real_name', 'user_wiki_group', 'user_inactivity'), 'user_wiki_group='.$selected_group );
					
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
				$wgOut->addHTML ("</table>");
				
				if((empty($array_compare_user)!=FALSE)){
					//Ausgabe der diskutierten Seiten und Nutzern, welche diese Seiten kommentiert haben.
					$wgOut->addHTML ("<table>");	
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td colspan='6'><p><h3>Von Mitgliedern der Gruppe kommentierte Diskussions-Seiten im Wiki</h3></p></td>");
					$wgOut->addHTML ("</tr>");
					
					$wgOut->addHTML ("<tr>");
					$wgOut->addHTML ("<td>Nummer der Diskussions-Seite</td><td>Kommentiert durch Mitglied</td>");
					$wgOut->addHTML ("</tr>");
					
					//Ausgabe der diskutierten Seiten und Nutzer, welche auf diesen Seiten kommentiert haben.
					for($j=1; $j <= count($array_compare_pages); $j++){
						if($j==1){
							$wgOut->addHTML("<tr><td><a href='/". $wgDBname ."/index.php?title=-&curid=". $array_compare_pages[$j] ."' title='Page:". $array_compare_pages[$j] ."'>".$array_compare_pages[$j]."</a></td><td>". $array_user[$j] ."</td></tr>");
						}
						else{
							if($array_compare_pages[$j]!=$array_compare_pages[$j-1]){
								$wgOut->addHTML("<tr><td colspan='6'><hr></td></tr><tr><td><a href='/". $wgDBname ."/index.php?title=-&curid=". $array_compare_pages[$j] ."' title='Page:". $array_compare_pages[$j] ."'>".$array_compare_pages[$j]."</a></td><td>". $array_user[$j] ."</td></tr>");
							}
							else{
								$wgOut->addHTML("<tr><td></td><td>". $array_user[$j] ."</td></tr>");
							}
						}
					}
					$wgOut->addHTML ("</table>");
				}
				else{
					$wgOut->addHTML("<p>Die Mitglieder der Gruppe haben nicht miteinander kollaboriert. Es existiert keine Diskussions-Seite auf der zwei Mitglieder miteinander diskutiert haben.");
				}
			}
		}
}