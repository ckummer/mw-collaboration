<?php
class SpecialCollaborationFAQ extends SpecialPage {
        function __construct() {
			//Dieser Befehl lädt das Grundgerüst einer SpecialPage. Der Name der SpecialPage wird geändert und es kann ein Recht zur Beschränkung des Zugriffs vergeben werden.
			//Um den Zugriff auf die Seite nur für System-Operatoren zu gewähren, ist die Zeile parent::__construct um den Parameter 'sysop' zu ergänzen.
            parent::__construct( 'Collaboration - Frequently Asked Questions', 'sysop' );
        }
		
		function execute( $par ) {
			global $wgOut, $wgRequest;
			//Seitentitel wird ermittelt und auf der SpecialPage eingefügt.
			$self = $this->getTitle();
            $this->setHeaders();
			
			//Trotz Vergabe der Zugriffsbeschränkung, kann ein User einer Gruppe ohne die entsprechende Berechtigung über den direkten Link auf diese SpecialPage zugreifen.
			//Wird nur verwendet, wenn der Parameter 'sysop' im Constructor vergeben wurde.
			if (  !$this->userCanExecute( $this->getUser() )  ) {
				$this->displayRestrictionError();
				return;
			}
			
			//Beschreibung der Funktion der Seite.
			$wgOut->addHTML ("Diese Seite dient zur Beantwortung der am häufigsten gestellten Fragen zur MediaWiki-Extension Collaboration");
			
			//Punkt 1 - Beschreibung der Kennzahlen
			$wgOut->addHTML ("<h1 id=Gruppe'KPIs'>Beschreibung der verwendeten Kennzahlen:</h1>");
			$wgOut->addHTML ("Die im Rahmen von Collaboration verwendeten Kennzahlen stammen aus dem Gebiet der sozialen Netzwerkanalyse.<br>
							  Es werden insgesamt drei Kennzahlen aus diesem Bereich genutzt. Die vierte Kennzahl ist die Anzahl der Discussion-Posts. Die Bedeutung der Kennzahlen werden hier erläutert.");
			
			$wgOut->addHTML ("<h3 id=Gruppe'density'>Density (DEN)</h3>");
			$wgOut->addHTML ("Die Dichte (englisch: Density) beschreibt das Verhältnis der realisierten Verbindungen (Kanten) zwischen den Knoten eines Netzwerkes und allen möglichen Verbindungen.<br>
							  Sind in einer Gruppe innerhalb des Wikis keinerlei Verbindungen zwischen den Knoten vorhanden, so nimmt die Dichte den Wert 0 an. Existieren zwischen allen Mitgliedern Verbindungen, ist der Wert 1. <br>
							  Als Indikator für die Kollaboration in Gruppen ist der Wert umso besser, je näher er dem Wert 1 ist.");
							  
			$wgOut->addHTML ("<h3 id=Gruppe'centralization'>Centralization (CENT)</h3>");
			$wgOut->addHTML ("Die Centralization (CENT) gibt an, ob es im betrachteten Netzwerk einen Knoten gibt, welcher im Vergleich besonders häufig Verbindungen mit anderen Knoten eingeht.<br>
							  Ist die Anzahl der Verbindungen annähernd gleichverteilt, so konvergiert der Wert gegen null. Stellt sich bei der Berechnung heraus, dass ein Knoten auffällig häufig kontaktiert wird, so konvergiert der Wert gegen eins.<br>
							  Zur Beurteilung der Kollaboration ist dieser Wert umso besser, je mehr er gegen 0 konvergiert.");

			$wgOut->addHTML ("<h3 id=Gruppe'medianweighteddegree'>Median Weighted Degree (DEG)</h3>");
			$wgOut->addHTML ("Der Median Weighted Degree gibt die durchschnittliche Anzahl der Verbindungen an, die ein Mitglied einer Gruppe mit Mitgliedern eingeht.<br>
							  Das arithmetische Mittel der Anzahl der Verbindungen ist zur Bestimmung der Kollaboration umso besser, je mehr Verbindungen eingegangen werden.");			
							  
			$wgOut->addHTML ("<h3 id=Gruppe'discussionposts'>Diskussions-Posts (DIS)</h3>");
			$wgOut->addHTML ("Die Anzahl der Diskussions-Posts einer Gruppe verdeutlicht, wie oft von einem Mitglied ein Beitrag auf den Diskussionsseiten verfasst und von einem anderen Mitglied kommentiert wurde. <br>
			                  Erstellt ein Mitglied einen Beitrag und ein anderes antwortet darauf, so zählt dies als ein Diskussions-Post. Antwortet das erste Mitglied wieder auf diesen, so ist dies ein weiterer Post.<br>
							  Die Anzahl der Diskussions-Posts ist zur Bestimmung von kollaborativen Verhalten in Gruppen umso besser, je mehr diskutiert wurde.");
			
			//Punkt 2 - Interpretation der Visualisierung der sozialen Netzwerke
			$wgOut->addHTML ("<h1 id=Gruppe'Interpretation'>Interpretation der Visualisierung der sozialen Netzwerke:</h1>");
			$wgOut->addHTML ("Die einzelnen Mitglieder der Gruppe werden in der Visualisierung als Knoten bzw. Punkte dargestellt. Diese sind mit den Namen der Mitglieder beschriftet. Zwischen den Knoten werden realisierte Verbindungen zwischen den Mitgliedern angezeigt. Die Berechnung der Verbindungen erfolgt nach dem Prinzip der indirekten Co-Autoren-Netzwetzwerke. Dies bedeutet, dass wenn ein Mitglied der Gruppe einen Artikel erstellt und andere diesen kommentieren, so gelten alle als Co-Autoren. Über diesem Abschnitt wird die Bedeutung der Kennzahlen beschrieben. Auf Grundlage der Discussion-Posts werden die Verbindungen dargestellt. Je mehr Verbindungen existieren, desto dicker werden die Kanten dargestellt. Erstellt User 1 einen Artikel und User 2 und 3 kommentieren diesen in der Reihenfolge, so existiert nach dem beschriebenen Prinzip zwischen allen dreien eine Verbindung. An jeder Kante steht zusätzlich die Anzahl der Verbindungen zwischen beiden Mitgliedern. Ist die Anzahl 0 so existiert zwar eine Verbindung zwischen den Knoten nach dem Prinzip der indirekten Co-Autoren-Netzwerke, aber die Mitglieder haben nicht direkt nacheinander den gleichen Artikel kommentiert.");
		}
}