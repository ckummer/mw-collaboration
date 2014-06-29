<?php
class SpecialCollaboration extends SpecialPage {
        function __construct() {
				// restrict access to sysops
				parent::__construct( 'Collaboration', 'ttcollaboration' );
        }
 
        function execute( $par ) {
				global $wgOut, $wgRequest, $wgScriptPath;
				
				// restrict url access
				if (  !$this->userCanExecute( $this->getUser() )  ) {
					$this->displayRestrictionError();
					return;
				}
				
				// TODO: Sinnhaftigkeit der Variablen
				$image_path = $wgScriptPath . '/extensions/Collaboration/traffic_lights/';
				$network_path = $wgScriptPath . '/images/collaboration_plots/';
				$kpi_help = 1;
				$checkbox_inactivity = "";
				
				$self = $this->getTitle();
                $this->setHeaders();
				
				$dbr =& wfGetDB( DB_SLAVE );
				
				// SQL Abfrage um die maximale Gruppennummer zu ermitteln.
				$res_max_group = $dbr->select( 'user', 'MAX(user_wiki_group) as maximum_group');
				$row = $dbr->fetchObject( $res_max_group );
				
				//SQL-Abfrage, um anschließend zu testen, ob es Kollaborationswerte gibt
				$res_collaboration1  = $dbr->select ( 'collaboration', array('MAX(time) as timer_max', 'pa', 'den', 'cent', 'deg', 'dis', 'calibrated_den', 'calibrated_cent', 'calibrated_deg', 'calibrated_dis', 'collaboration_final'));	
				$checkEmptyness = $dbr->fetchObject( $res_collaboration1 );
				$arrayEmptyness = $checkEmptyness->pa;

				if((empty($row->maximum_group)==FALSE)&&(empty($arrayEmptyness)==FALSE)){
				
					//Hier wird ein provisorisches Inhaltsverzeichnis erzeugt. Dabei wird für jede Gruppe ein Punkt im Inhaltsverzeichnis erzeugt.
					$wgOut->addHTML ("<table id='toc' class='toc'><tr><td><div id='toctitle'><h2>Inhaltsverzeichnis</h2></div><ul>");
						for($i=1; $i <= $row->maximum_group; $i++) {
						$wgOut->addHTML ("<li class='toclevel-1 tocsection". $i ."'><a href='#Gruppe". $i ."'><span class='tocnumber'>". $i ."</span> <span class='toctext'>Gruppe ". $i ."</span></a></li>");
					}
					$wgOut->addHTML ("</ul></td></tr></table>");
				
					//Im folgenden Schritt wird für jede Gruppe eine Ausgabe auf der Wikiseite erzeugt, welche für jede Gruppe die Mitglieder, eine graphische Veranschaulichung des sozialen Netzwerkes und die Kennzahlen zum Kollaborationsstatus bereithält.
					for($i=1; $i <= $row->maximum_group; $i++) {
					
						//Definition von zwei SQL-Abfragen, um die Nutzer jeder Gruppe auszulesen und um die Kennzahlen zum aktuellen Kollaborationsstatus abzufragen.
						$res_user = $dbr->select( 'user', array('user_id', 'user_name', 'user_real_name', 'user_wiki_group', 'user_inactivity'), 'user_wiki_group='.$i );
					
						//SQL-Abfrage für SELECT FROM WHERE XYZ IN (SELECT ...) lies sich nicht erstellen, daher Abfrage des zweiten SELECTs
						//Abgefragter Wert wird auf Variable geschrieben und diese im WHERE-Teil eingefügt.
						$res_max_timer = $dbr->select( 'collaboration', 'MAX(time) as timer1234');
						$row_max_timer = $dbr->fetchObject( $res_max_timer );
						$maximaler_timer = $row_max_timer->timer1234;
					
						$res_collaboration  = $dbr->select ( 'collaboration', array('MAX(time) as timer_max', 'pa', 'den', 'cent', 'deg', 'dis', 'calibrated_den', 'calibrated_cent', 'calibrated_deg', 'calibrated_dis', 'collaboration_final'), array('pa="'.$i.'"' ,'time="'.$maximaler_timer.'"'));
				
						//Die definierten SQL Abfragen werden an die Datenbank gesendet und die Rückgabewerte auf Variablen geschrieben.
						$row_collaboration = $dbr->fetchObject( $res_collaboration );
					
						//Das Layout der Ausgabe wird durch Tabellen definiert.
						$wgOut->addHTML ("<h1 id=Gruppe". $i .">Gruppe ". $i .":</h1>");
						$wgOut->addHTML ("<table>");
						$wgOut->addHTML ("<th><tr><td>");
					
						//Es wird geprüft wie der aktuelle Kollaborationsstatus der Gruppe ist. Abhängig vom Wert wird die Ampel in einer anderen Farbe angezeigt, um den Status graphisch hervorzuheben.
						if($row_collaboration->collaboration_final>0.7)
							$wgOut->addHTML ("<img src=".$image_path."gruen_quer.bmp alt='Gruene Ampel' width='100' height='50'/></td><td>Aktueller Kollaborations-Status: </td><td><b>Sehr gut</b></td>");
						
						else if($row_collaboration->collaboration_final>0.5&&$row_collaboration->collaboration_final<=0.7)
							$wgOut->addHTML ("<img src=".$image_path."gelb_quer.bmp alt='Gelbe Ampel' width='100' height='50'/></td><td>Aktueller Kollaborations-Status: </td><td><b>Ok</b></td>");

						else if($row_collaboration->collaboration_final<=0.5)
							$wgOut->addHTML ("<img src=".$image_path."rot_quer.bmp alt='Rote Ampel' width='100' height='50'/></td><td>Aktueller Kollaborations-Status: </td><td><b>Schlecht</b></td>");					
						$wgOut->addHTML ("</tr></th>");
					
						//An dieser Stelle erfolgt die Ausgabe der Liste der Gruppenmitglieder
						$wgOut->addHTML ("<tr><td colspan='4'><p><b>Liste der Gruppenmitglieder</b></p></td><td colspan='2'><p><b>Übersicht über die Kollaborations-Kennzahlen</b></p></td></tr>");
						$wgOut->addHTML ("<tr><td>User ID</td><td>Name</td><td width='161'>Real Name</td><td>Inactivity</td><td colspan='2'><p></p></td></tr>");
					
						//Für jedes Mitglied wird eine neue Reihe in der Tabelle erzeugt. Diese enthält User ID, Usernamen und den realen Namen.
						while ( $row_user_new = $dbr->fetchObject( $res_user ) ) {
							//Überprüfung, ob aktuell betrachteter Nutzer inaktiv ist
							if($row_user_new->user_inactivity==1){
								$checkbox_inactivity = "checked";
							}else{
								$checkbox_inactivity = "";
							}						
						
							//Auswahl, welche Kennzahl neben aktuell betrachtetem Nutzer angezeigt wird.
							if($kpi_help==1){
								$kennzahl = "<td>Density (DEN): </td><td width='200'>". $row_collaboration->den ."</td>";
							}else if($kpi_help==2){
								$kennzahl = "<td>Centralization (CENT): </td><td>". $row_collaboration->cent ."</td>";
							}else if($kpi_help==3){
								$kennzahl = "<td>Median Weighted Degree (DEG): </td><td>". $row_collaboration->deg ."</td>";
							}else if($kpi_help==4){
								$kennzahl = "<td>Discussion Posts (DIS): </td><td>". $row_collaboration->dis ."</td>";
							}else if ($kpi_help>=5){
								$kennzahl = "";
							}
							
							//Ersetzen von möglichen Leerzeichen im Benutzernamen
							$user_name_link = str_replace(' ', '_', $row_user_new->user_name);
							
							$wgOut->addHTML ("<tr><td>" .
							$row_user_new->user_id . "</td><td><a href='". $wgScriptPath ."/index.php/User:". $user_name_link ."' title='User:". $user_name_link ."'>" .
							$row_user_new->user_name . "</a></td><td>" .
							$row_user_new->user_real_name . "</td>". 
							"<td align='center'><input name='user_inactivity". $row_user_new->user_id ."' type='checkbox' disabled ". $checkbox_inactivity ."></td>" . $kennzahl ."</tr>");
							
							$kpi_help++;
						}
						$wgOut->addHTML ("</table>");
					
						//Graphische Darstellung des aktuellen sozialen Netzwerkes
						$wgOut->addHTML ("<p><b>Grafische Darstellung des Sozialen Netzwerkes der Gruppe</b>");
						$wgOut->addHTML ("<img src=". $network_path ."". $row_collaboration->timer_max ."-Gruppe-". $row_collaboration->pa .".png alt='Grafische Abbildung des aktuellen Sozialen Netzwerkes' width='800' height='600'/></p>");
					
						//Zurücksetzen der Hilsvariable $kpi_help
						$kpi_help = 1;
					}
				}
				else{
					$wgOut->addHTML ("<p><b>Es existieren entweder keine festgelegten Gruppen in der Datenbank, oder es wurden noch keine Kennzahlen durch das R-Skript ermittelt!</b><br>
									  Bitte vergewissen Sie sich, ob Sie für einige Mitgliedern im Wiki Gruppennummern vergeben und die Analyse zur Ermittelung der Kollaboration in Gruppen mindestens einmal ausgeführt haben.</p>");
				}
        }
}