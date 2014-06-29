<?php
class SpecialConfiguration extends SpecialPage {
        function __construct() {
				//Dieser Befehl lädt das Grundgerüst einer SpecialPage. Der Name der SpecialPage wird geändert und es kann ein Recht zur Beschränkung des Zugriffs vergeben werden.
				//Um den Zugriff auf die Seite nur für System-Operatoren zu gewähren, ist die Zeile parent::__construct um den Parameter 'sysop' zu ergänzen.
                parent::__construct( 'Configuration', 'sysop' );
        }
 
        function execute( $par ) {
				//Definition globaler Systemvariablen
				global $wgOut, $wgRequest;
				$checkbox_inactivity = "";
				
				//Trotz Vergabe der Zugriffsbeschränkung, kann ein User einer Gruppe ohne die entsprechende Berechtigung über den direkten Link auf diese SpecialPage zugreifen.
				//Wird nur verwendet, wenn der Parameter 'sysop' im Constructor vergeben wurde.
				if (  !$this->userCanExecute( $this->getUser() )  ) {
					$this->displayRestrictionError();
					return;
				}
				
				//Seitentitel wird ermittelt und auf der SpecialPage eingefügt.
				$self = $this->getTitle();
                $this->setHeaders();
				
				//wfGetDB ( DB_MASTER ) definiert einen schreibenden Datenbankzugriff.
				$dbr =& wfGetDB( DB_MASTER );
				
				//SQL-Abfrage, um alle im Wiki angemeldeten Nutzer mit User ID, Username, Real Name und zugeordneter Wikigruppe zu ermitteln.
				$res = $dbr->select( 'user', array('user_id', 'user_name', 'user_real_name', 'user_wiki_group', 'user_inactivity') );
				$res1 = $dbr->select( 'user', array('user_id', 'user_name', 'user_real_name', 'user_wiki_group') );
				$wgOut->addHTML ("<p>Diese Seite dient der Festlegung der unterschiedlichen Gruppen im Wiki.<br>
								  Weisen Sie jedem Benutzer eine Gruppennummer zu und klicken Sie anschließend auf den Save-Button.<br>
								  Bitte vergeben Sie fortlaufende Gruppennummern!</p>
								  <p>Wenn Sie eine einem User zugeordnete Gruppe für diesen löschen möchten, so vergeben Sie für den entsprechenden User die Gruppe 0 oder lassen sie das Feld leer und klicken sie anschließend auf den Save-Button.</p>");
				
				//Abfrage an Datenbank senden, um anschließend zu testen, ob Nutzer in der Datenbank existieren.
				$checkEmptyness = $dbr->fetchObject( $res1 );
				$arrayEmptyness = $checkEmptyness->user_id;
				if(empty($arrayEmptyness)!=TRUE){
					//Es wird eine graphische Ausgabe der Mitgliederliste erzeugt.
					//Das Layout der Ausgabe wird durch eine Tabelle definiert.
					$wgOut->addHTML ("<table>");
					$wgOut->addHTML ("<tr><th>User ID</th><th>User Name</th><th>Real Name</th><th>Wikigruppe zuweisen</th><th>Inactivity</th><th></th></tr>");
				
					//Jede Zeile, welche durch die Datenbankabfrage an das Objekt zurückgegeben werden wird nach und nach ausgegeben.
					while ( $row = $dbr->fetchObject( $res ) ) {
							//Überprüfung, ob aktuell betrachteter Nutzer inaktiv ist.
							if($row->user_inactivity==1){
								$checkbox_inactivity = "checked";
							}else{
								$checkbox_inactivity = "";
							}
					
							$wgOut->addHTML ("<tr><td>" .
							$row->user_id . "</td><td>" .
							$row->user_name . "</td><td>" .
							$row->user_real_name . "</td>" .
						
							//An dieser Stelle wird das Formular definiert, welches es ermöglicht, jedem Nutzer eine Wikigruppe zuzuordnen.
							"<td><form method='post' action='".$self->getLocalUrl()."'><input name='user_wiki_group". $row->user_id ."' id='".$row->user_id."' type='number' min='0' value='". $row->user_wiki_group ."'></input></td>" .
							"<td align='center'><input name='user_inactivity". $row->user_id ."' type='checkbox' ". $checkbox_inactivity ."></td>" .
							"<td><input name='act' type='submit' value='save'></form></td></tr>");
						
							//Die Zuweisung der Wikigruppe und das Schreiben des Wertes in die Datenbank erfolgt nur, wenn das Textfeld einen Wert enthält.
							if(isset($_POST["user_wiki_group".$row->user_id])||isset($_POST["user_inactivity".$row->user_id])){
									$wgNewWikiGroup = $_POST["user_wiki_group".$row->user_id];
									$newInactivity = (isset($_POST["user_inactivity".$row->user_id])) ? 1 : 0;
								
									// Überprüfung ob die Gruppennummer 0 ist. In diesem Fall wird die Gruppennummer auf den Wert NULL gesetzt. Damit ist dem Nutzer keine Gruppe mehr zugeordnet.
									// Zusätzlich erfolgt die Überprüfung, ob der Nutzer aktiv ist. Wenn dies der Fall ist, wird der Status NULL vergeben, sonst 1.
									if($wgNewWikiGroup==0){
										if($newInactivity==0){
											$dbr->update('user', array ('user_wiki_group' => NULL, 'user_inactivity' => NULL), array('user_id' => $row->user_id));
											//Neuladen der Seite, damit die neu vergebene Gruppennummer und der neue Inaktivitätsstatus angezeigt werden kann.
											header( 'refresh:1; url='.$self->getLocalUrl() );
										}else{
											$dbr->update('user', array ('user_wiki_group' => NULL, 'user_inactivity' => 1), array('user_id' => $row->user_id));
											//Neuladen der Seite, damit die neu vergebene Gruppennummer und der neue Inaktivitätsstatus angezeigt werden kann.
											header( 'refresh:1; url='.$self->getLocalUrl() );											
										}
									}
									else{
										//Ist die Gruppennummer nicht 0, so wird die neue Gruppennummer vergeben.
										// Zusätzlich erfolgt die Überprüfung, ob der Nutzer aktiv ist. Wenn dies der Fall ist, wird der Status NULL vergeben, sonst 1.
										if($newInactivity==0){
											$dbr->update('user', array ('user_wiki_group' => $wgNewWikiGroup, 'user_inactivity' => NULL), array('user_id' => $row->user_id));
											//Neuladen der Seite, damit die neu vergebene Gruppennummer und der neue Inaktivitätsstatus angezeigt werden kann.
											header( 'refresh:1; url='.$self->getLocalUrl() ); 
										}else{
											$dbr->update('user', array ('user_wiki_group' => $wgNewWikiGroup, 'user_inactivity' => 1), array('user_id' => $row->user_id));
											//Neuladen der Seite, damit die neu vergebene Gruppennummer und der neue Inaktivitätsstatus angezeigt werden kann.
											header( 'refresh:1; url='.$self->getLocalUrl() ); 
										}
									}
							}	
					}
					$wgOut->addHTML ("</table>");
					$dbr->freeResult( $res );
				}
				else{
					$wgOut->addHTML ("<p><b>Es existieren keine Nutzer in der Datenbank!</b></p>");
				}
		}
}