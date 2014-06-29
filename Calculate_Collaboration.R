#Dieses Skript dient der Messung von Kollaboration in Wikis.
#Es ist Teil der Diplomarbeit "Learning Analytics zur Messung von Kollaboration in Wikis" von Stefan Jaeschke.
#Technische Universität Dresden - Wirtschaftsinformatik - Lehrstuhl für Informationsmanagement
#2013

#Sollten die benötigten Packages für die Analyse noch nicht installiert sein, so kann dies durch Entfernen der Kommentarzeichen und einmaligem Ausführen der folgenden Zeilen erledigt werden.
#Nach der Installation sind die Zeilen wieder auskommentiert werden.

#install.packages("RMySQL")
#install.packages("QCA")
#install.packages("igraph")

#Laden des Paketes RMySQL - Dieses wird benötigt, um Datenbankverbindung zu MySQL-Datenbank aufzubauen.
library(RMySQL)
library(QCA)
library(igraph)

#Das Skript ist in der Standardversion dafür ausgelegt, stündlich in einem Batch Job ausgeführt zu werden.
#Wenn Sie planen das R-Srkipt in einem Abstand von weniger als einer Stunde ausführen zu lassen, so passen Sie bitte die folgende Variable an.
#In diesem Fall muss der String um "-%M" ergänzt werden.
timeformat <- "%Y-%m-%d-%H"

#Bitte legen Sie an dieser Stelle den Pfad fest, unter welchem die Abbildungen der sozialen Netzwerke gespeichert werden sollen.
image_path <- "C:/xampp/htdocs/wikiwm2012/images/collaboration_plots/"

#Die einzelnen Parameter der Kalibrierung lassen sich beliebig modifizieren.
#Bitte passen Sie die nachfolgenden Variablen entsprechend Ihren Bedürfnissen an.

#Hier erfolgt die Festlegung der Parameter der Kalibrierung
#Transformational Assignment für die Variablen CENT, DEG und DIS
#Es müssen die Werte für Full-Membership (1), der Crossover-Punkt (0.5) und für Non-Mempership (0) angegeben werden.

#Parameter der Kalibrierung der Zentralisierung (CENT)
cent_full_membership <- 0
cent_crossover_point <- 0.1
cent_non_membership  <- 1

#Parameter der Kalibrierung des Median Weighted Degree (DEG)
deg_full_membership  <- 10
deg_crossover_point  <- 7
deg_non_membership   <- 5

#Parameter der Kalibrierung der Discussion-Posts (DIS)
dis_full_membership  <- 30
dis_crossover_point  <- 18
dis_non_membership   <- 6

#Für die Parameter der Kalibrierung von DEN wird die Form des Direct Assignments verwendet.
#Hierfür müssen unterschiedliche Grenzwerte angegeben werden.
#Die Berechnung erfolgt weiter unten im Quelltext.
den_threshold1       <- 0.6
den_threshold2       <- 1

#Zu jedem Grenzwert von DEN gehört ein Wert welcher DEN zugewiesen wird, wenn DEN zu einer bestimmten Stufe gehört.
den1_calibrate_to    <- 0.1
den2_calibrate_to    <- 0.7
den3_calibrate_to    <- 1

#Herstellen der Datenbankverbindung
#Die Verbindung ist an die jeweilige Datenbankkonfiguration anzupassen.
#User: Datenbanknutzer
#Password: Passwort des Datenbanknutzers
#Dbname: Datenbank, in welcher sich sämtliche Tabellen von MediaWiki befinden.
con <- dbConnect(MySQL(), user="root", password="", dbname="wikiwm2012", host="localhost" )

#SQL-Statement, um die definierten Gruppennummern abzufragen.
sql_groups <- paste ("SELECT DISTINCT user_wiki_group FROM `user` WHERE user_wiki_group IS NOT NULL ORDER BY user_wiki_group ASC")

#Das definierte SQL-Statement zur Abfrage vorhandener Gruppen wird hier mit der Datenbankverbindung an die Datenbank gesendet.
#Das Ergebnis der Abfrage wird auf den DataFrame group_numbers geschrieben.
group_numbers <- dbGetQuery(con, sql_groups)

group_edges <- list()

#Überprüfung, ob in der Datenbank mindestens eine Gruppe vorhanden ist.
if(nrow(group_numbers)!=0){
  
  #Die nachfolgende for-Schleife dient der Berechnung aller Kennzahlen und der Feststellung, ob Kollaboration in einer Gruppe vorhanden ist.
  #Die Berechnung wird für alle vergebenen Gruppennummern ausgeführt.
  #Zusätzlich wird eine Liste erstellt, in der die Ergebnisse für jede Gruppe gespeichert werden
  for ( pa in 1:nrow(group_numbers) ) {
  
  #SQL-Statement, das für die Gruppe mit der Nummer, welche der aktuellen Laufvariablen entspricht, die Daten aus der Datenbank ausliest.
  sql_data_group <- paste ("SELECT distinct rev_page, rev_user FROM revision,page,user WHERE rev_page = page_id and rev_user = user_id and page_namespace in (1, 3,5,7,9,11,13,15) and rev_user > 0 and user_inactivity IS NULL and user_wiki_group=",group_numbers[pa,],sep="")
  
  #Das definierte SQL-Statement zur Abfrage der Gruppendaten wird hier mit der Datenbankverbindung an die Datenbank gesendet.
  #Ergebnis der Abfrage wird auf den DataFrame group_data geschrieben.  
  group_data <- dbGetQuery(con, sql_data_group)
  
  #Die Vertices werden aus den Gruppendaten extrahiert.
  #Die Matrix mit den Vertices wird um Spalte erweitert, welche neue die User_ID enthält.
  #Die erweiterte Matrix mit Vertices dient später als Mapping-Tabelle.
  if(nrow(group_data)!=0){
    sql_vertices <- paste ("SELECT distinct rev_user, CONVERT(rev_user_text USING utf8) as rev_user_text FROM revision,user WHERE rev_user = user_id and rev_user > 0 and user_inactivity IS NULL and user_wiki_group=",group_numbers[pa,],sep="")
    vertices_data <- dbGetQuery(con, sql_vertices)
    vertices_data <- cbind(vertices_data, 1:nrow(vertices_data))
  }else{
    #Sollten in der Revisionstabelle keine Einträge vorliegen, so werden Informationen aus der Usertabelle gezogen.
    sql_vertices <- paste ("SELECT distinct user_id, CONVERT(user_name USING utf8) as user_name, CONVERT(user_real_name USING utf8) as user_real_name from user where user_inactivity IS NULL and user_wiki_group=",group_numbers[pa,],sep="")
    vertices_data <- dbGetQuery(con, sql_vertices)
    vertices_data <- cbind(vertices_data, 1:nrow(vertices_data))    
  }
  
  #Abfrage, ob Revisionsdaten ermittelt werden konnten.
  if(nrow(group_data)!=0){
  #Berechnung der Edges
  #Vor einem Eintrag in die Zielmatrix erfolgt das Mapping der User IDs. Das ist nötig, da sonst nachfolgend Graphen nicht korrekt erstellt werden.
  #Es würde sonst nach der höchsten User ID geschaut werden und nachfolgend werden so viele Vertices erstellt, wie die höchste User ID lautet.
  #Nach dem Betrachten einer Zeile in pages[,] werden alle Zeilen, welche die gleiche Seitennummer beinhalten, ermittelt und auf einen Counter geschrieben.
  #Die einzelnen User, welche auf die Seite zugegriffen haben werden anschließŸend miteinander in Verbindung gesetzt.
  #Damit die Verbindungen nicht mehrmals berechnet werden, erfolgt nach der ersten Berechnung die Vergabe des Status 'processed'.
  pages <- group_data[,1:2]
  edges <- matrix(ncol=2)
  
  for ( i in 1:length(pages[,1])){
    counter <- which(pages[i,1] == pages[,1])
    if(pages[i,1]!= "processed"){
    if (length(counter) > 1 ) {
        for (j in 1:(length(counter)-1)) {
          for (k in (j+1):length(counter)){
            #User können nicht mit sich selbst verbunden werden.
            if(pages[counter[j],2] != pages[counter[k],2]){
              
              #Mapping der ermittelten User.
              mapping1 <- which (pages[counter[j],2] == vertices_data[,1])
              mapping2 <- which (pages[counter[k],2] == vertices_data[,1])
              
              #Sortierung der ermittelten User, um später die Verbindungen aggregieren zu können. Dies ist möglich, da der Graph undirected ist.
              if(mapping1 > mapping2){
                edges <- rbind(edges, c(vertices_data[mapping1,3],vertices_data[mapping2,3])) 
              } else{
                edges <- rbind(edges, c(vertices_data[mapping2,3],vertices_data[mapping1,3]))
              }
            }
          }
        }
      }
    }
    
    #Vergabe des Status 'processed' für alle Zeilen, welche die betrachtete Seite beinhalten.
    for(once in 1:length(counter)){
      pages[counter[once],1] <- "processed"
    }
  }
  
  #SQL-Abfrage, um die Daten der Gruppe mit Zeitstempel und Parent_ID abzufragen.
  #Die Daten werden nach der Page ID und der Parent ID aufsteigend angeordnet.
  sql_data_dis1 <- paste(group_numbers[pa,]," ORDER BY rev_page, rev_parent_id ASC",sep="")
  sql_data_dis <- paste ("SELECT distinct rev_page, rev_user, rev_timestamp, rev_parent_id FROM revision,page,user WHERE rev_page = page_id and rev_user = user_id and page_namespace in (1, 3,5,7,9,11,13,15) and rev_user > 0 and user_inactivity IS NULL and user_wiki_group=",sql_data_dis1,sep="")
  
  #Die Abfrage für die erweiterten Gruppendaten wird an die Datenbank gesendet und das Ergebnis auf einen DataFrame geschrieben.
  data_discussion <- dbGetQuery(con, sql_data_dis)
  
  #Auswahl der ersten beiden Spalten und Erstellen einer neuen leeren Matrix.
  pages_dis <- data_discussion[,1:2]
  edges_dis <- matrix(ncol=2) 
  
  #Hier werden die Discussion-Posts für jede einzelne Seite ermittelt. Dafür wird geprüft, ob aufeinander folgende Discussion-Posts von unterschiedlichen Usern verfasst wurden.
  #Nach dem Betrachten einer Zeile in pages[,] werden alle Zeilen, welche die gleiche Seitennummer beinhalten, ermittelt und auf einen Counter geschrieben.
  #Damit die Discussion-Posts nicht mehrmals berechnet werden, erfolgt nach der ersten Berechnung die Vergabe des Status 'processed'.
  for ( x in 1:length(pages_dis[,1])){
    counter_dis <- which(pages_dis[x,1] == pages_dis[,1])
    if(pages_dis[x,1]!= "processed"){
    if (length(counter_dis) > 1 ) {
      for (y in 1:(length(counter_dis)-1)) {
          #Prüfen ob User in aufeinander folgenden Posts unterschiedlich sind
          if(pages_dis[counter_dis[y],2] != pages_dis[counter_dis[y+1],2]){
            mapping1 <- which (pages_dis[counter_dis[y],2] == vertices_data[,1])
            mapping2 <- which (pages_dis[counter_dis[y+1],2] == vertices_data[,1])
            if(mapping1 > mapping2){
              edges_dis <- rbind(edges_dis, c(vertices_data[mapping1,3],vertices_data[mapping2,3]))  
            } else{
              edges_dis <- rbind(edges_dis, c(vertices_data[mapping2,3],vertices_data[mapping1,3]))
            }        
          }
      }
    }
    }
    
    #Vergabe des Status 'processed' für alle Zeilen, welche die betrachtete Seite beinhalten.
    for(once_dis in 1:length(counter_dis)){
      pages_dis[counter_dis[once_dis],1] <- "processed"
    }
  }
  
  #Erste Zeile wird nicht mit übertragen, da diese NA-Werte enthält.
  edges_dis <- edges_dis[-1,]
  
  #Umwandeln des Dataframes in eine Matrix.
  edges_dis <- matrix(edges_dis, ncol=2)
  
  if(nrow(edges_dis)!=0){
    #Umwandeln des Dataframes in eine Matrix.
    edges_dis <- matrix(edges_dis, ncol=2)
  
  #Zusammenführen der berechneten Verbindungen und Discussion-Posts in einer gemeinsamen Matrix.
  for(z in 1:nrow(edges_dis)){
    edges <- rbind(edges, c(edges_dis[z,1],edges_dis[z,2]))  
  }
  
  #Hier edges in Liste schreiben für jede Gruppe
  group_edges[[pa]] <- edges[-1,]

  #Initialisierung des Netzwerkes und Hinzufügen der berechneten Edges.
  #Das Netzwerk ist undirected.
  network <- graph.edgelist(matrix(group_edges[[pa]], ncol=2), directed=FALSE)
  
  #Edgelist erneut aus dem eben erstellten Netzwerk abfragen.
  edgelist <- get.edgelist(network,names=TRUE)
  
  #Überprüfung, ob die abgefragte Edgelist leer ist und damit keine Verbindungen zwischen den Vertices besteht.
  if (nrow(edgelist)!=0) {
    
    #Die Edgelist wird in einen DataFrame umgewandelt.
    edgesdf <- as.data.frame(edgelist)
    
    #Hinzufügen einer Spalte Weight zum DataFrame. 
    #Allen Zeilen wird das Gewicht 1 zugewiesen.
    edgesdf$Weight <- 1

    #Die Gewichte werden über die Aggegate-Funktion aggregiert und für jedes vorhandene Paar an Werten summiert.
    edgesdf <- aggregate( edgesdf["Weight"], list(V1=edgesdf$V1,V2=edgesdf$V2), FUN=sum )

    #Der DataFrame edgesdf mit den aggregierten Daten wird wieder in eine Matrix umgewandelt.
    edgesmatrix <- as.matrix(edgesdf)
    
    #Der Graph wird auf Grundlage der neuen Edges erneut erstellt.
    network <- graph.edgelist(matrix(edgesmatrix[,-3], ncol=2), directed=FALSE)
    
    #Sollte es User geben, welche nicht mit ihrer ID in der Edgelist auftauchen und eine User ID haben, welche höher ist als höchste in der Edgelist verwendete, so werden für diese User hier Vertices hinzugefügt.
    network <- add.vertices(network, (nrow(vertices_data) - vcount(network)))
    
    #Hinzufügen der Gewichte zum Graphen
    E(network)$weight=as.numeric(edgesmatrix[,3])
    
    #Hinzufügen der richten Namen der Gruppenmitglieder zum Graphen.
    V(network)$name <- vertices_data[,2]
  } else{
    
    #Dieser Teil wird aufgerufen, wenn keine Verbindungen zwischen den einzelnen Gruppenmitgliedern ermittelt wurde.
    
    #Erstellen eines leeren Graphen. Die Anzahl der Vertices im Graphen ist die Anzahl der Gruppenmitglieder.
    #Der Graph ist undirected.
    network <- graph.empty(n=nrow(vertices_data),directed=FALSE)
    
    #Sollte es User geben, welche nicht mit ihrer ID in der Edgelist auftauchen und eine User ID haben, welche höher ist als höchste in der Edgelist verwendete, so werden für diese User hier Vertices hinzugefügt.
    network <- add.vertices(network, (nrow(vertices_data) - vcount(network)))

    #Hinzufügen der richten Namen der Gruppenmitglieder zum Graphen.
    V(network)$name <- vertices_data[,2]
  }
  
  #Grafische Darstellung des Netzwerkes der Gruppe
  #Aus diesem Grund muss der Pfad zum Bilderordner für MediaWiki angegeben werden.
  #Es wird für jede Gruppe ein Bild des aktuellen Netzwerkes für jede Stunde gespeichert.
  #Bild wird mit Timestamp und Gruppennummer unter angegebenen Pfad abgespeichert.
  time <- format(Sys.time(), timeformat)
  full_path <- paste (image_path,time,"-Gruppe-",group_numbers[pa,],".png",sep="")
  
  #Festlegen des Layouts für das Netzwerk
  #Das Layout wird automatisch ermittelt. Es ist dabei abhängig von der Anzahl der Vertices. Dadurch wird eine optimale Darstellung gewährleistet.
  layout_test <- layout.auto(network)
  
  png(filename=full_path, width=800, height=600)
  plot(network, layout=layout_test, edge.width=E(network)$weight/4, vertex.label.dist=0.6, vertex.label.font=1, vertex.label.cex=1.5,vertex.size=7, vertex.color="red", edge.label=as.numeric(edgesmatrix[,3]-1), edge.label.cex=1.5)
  dev.off()
  
  #Es folgt die Berechnung der einzelnen Kennzahlen, welche zur Bestimmung von vorhandener Kollaboration benötigt werden.
  
  #Berechnung der Dichte (Density (DEN)) des Netzwerkes  
  den <- graph.density(network)
  
  #Berechnung der Zentralisierung (centralization (CENT)) 
  cent <- centralization.degree(network)$centralization
  
  #Berechnung der Diskussionsposts (Discussion-Posts(DIS))
  dis <- nrow(matrix(edges_dis, ncol=2))
  
  #Berechnung des Median Weighted Degrees (DEG)
  degree <- list()
  for(m in 1:nrow(vertices_data)) {
    degree[m] <- length(which(matrix(edges_dis, ncol=2)==vertices_data[m,3]))
  }
  
  deg <- sum(unlist(degree))/length(degree)
  
  #An dieser Stelle erfolgt die Kalibrierung der berechneten Kennzahlen.  
  #Kalibrierung der Zentralisierung (CENT)
  calibrated_cent <- calibrate(cent, type = "fuzzy", thresholds = c(cent_non_membership, cent_crossover_point, cent_full_membership))
  
  #Kalibrierung Median Weighted Degree (DEG)
  calibrated_deg <- calibrate(deg, type = "fuzzy", thresholds = c(deg_non_membership, deg_crossover_point, deg_full_membership))
  
  #Kalibrierung der Discussion-Posts (DIS)
  calibrated_dis <- calibrate(dis, type = "fuzzy", thresholds = c(dis_non_membership, dis_crossover_point, dis_full_membership))
    
  #Kalibrierung der Dichte (Density(DEN))
  calibrated_den <- ifelse(den < den_threshold1, den1_calibrate_to, ifelse(den < den_threshold2, den2_calibrate_to, den3_calibrate_to))
  
  #Berechnung der Formel, welche sich aus der booleschen Minimierung ergibt. 
  #Hierfür müssen zunächst die anderen beiden Faktoren berechnet werden, welche laut Dillenbourg (1999) Kollaboration ausmachen.
  #AnschließŸend wird eine Wahrheitstabelle erstellt und auf Grundlage dieser die boolesche Minimierung durchgeführt.
  #Auf diese Schritte wird in diesem Skript verzichtet. Es wird die von Kummer(2013) berechnete Formel zur Bestimmung von Kollaboration verwendet.
  #Sollte es gewünscht sein, eine andere Formel zu verwenden, muss dieser Teil des Skriptes entsprechend angepasst werden.
  #Die Formel lautet: DEN*DIS+cent*DEG*DIS -> COLL
  #
  #In boolescher Algebra steht * für ein UND und + für ein ODER

  collaboration_1 <- ifelse(calibrated_den>calibrated_dis,calibrated_dis,calibrated_den)
  collaboration_2 <- ifelse(calibrated_cent>calibrated_deg, ifelse(calibrated_deg>calibrated_dis,calibrated_dis,calibrated_deg),ifelse(calibrated_cent>calibrated_dis,calibrated_dis,calibrated_cent))
  
  collaboration_final <- ifelse(collaboration_1>collaboration_2,collaboration_1,collaboration_2)
  #Ergebnisse der einzelnen Gruppen in die Datenbank schreiben. 
  #Es wird die oben angegebene Verbindung verwendet.
  df <- data.frame(time,pa,den,cent,deg,dis,calibrated_den,calibrated_cent,calibrated_deg,calibrated_dis,collaboration_final)
  dbWriteTable(con, name="collaboration", value=df, append=TRUE)
  }else{
    #Dieser Teil wird abgearbeitet, wenn in der Revisionstabelle keine Einträge für die Gruppe ermittelt werden konnte.
    network <- graph.empty(n=nrow(vertices_data),directed=FALSE)
    network <- add.vertices(network, (nrow(vertices_data) - vcount(network)))
    V(network)$name <- vertices_data[,2]
    
    #Grafische Darstellung des Netzwerkes der Gruppe
    #Aus diesem Grund muss der Pfad zum Bilderordner für MediaWiki angegeben werden.
    #Es wird für jede Gruppe ein Bild des aktuellen Netzwerkes für jede Stunde gespeichert.
    #Bild wird mit Timestamp und Gruppennummer unter angegebenen Pfad abgespeichert.
    time <- format(Sys.time(), "%Y-%m-%d-%H")
    full_path <- paste (image_path,time,"-Gruppe-",group_numbers[pa,],".png",sep="")
    
    #Festlegen des Layouts für das Netzwerk
    #Das Layout wird automatisch ermittelt. Es ist dabei abhängig von der Anzahl der Vertices. Dadurch wird eine optimale Darstellung gewährleistet.
    layout_test <- layout.auto(network)
    
    png(filename=full_path, width=800, height=600)
    plot(network, layout=layout_test, edge.width=E(network)$weight/4, vertex.label.dist=0.6, vertex.label.font=1, vertex.label.cex=1.5,vertex.size=7, vertex.color="red", edge.label=as.numeric(edgesmatrix[,3]), edge.label.cex=1.5)
    dev.off()
    
    #Es folgt die Berechnung der einzelnen Kennzahlen, welche zur Bestimmung von vorhandener Kollaboration benötigt werden.
    
    #Berechnung der Dichte (Density (DEN)) des Netzwerkes  
    den <- 0
    
    #Berechnung der Zentralisierung (centralization (CENT)) 
    cent <- 0
    
    #Berechnung der Diskussionsposts (Discussion-Posts(DIS))
    dis <- 0
    
    #Berechnung des Median Weighted Degrees (DEG)
    deg <- 0
    
    #Kalibrierung der Zentralisierung (CENT)
    calibrated_cent <- 0
    
    #Kalibrierung Median Weighted Degree (DEG)
    calibrated_deg <- 0
    
    #Kalibrierung der Discussion-Posts (DIS)
    calibrated_dis <- 0
    
    #Kalibrierung der Dichte (Density(DEN))
    calibrated_den <- 0
    
    #Berechnung der Formel, welche sich aus der booleschen Minimierung ergibt. 
    #Hierfür müssen zunächst die anderen beiden Faktoren berechnet werden, welche laut Dillenbourg (1999) Kollaboration ausmachen.
    #AnschließŸend wird eine Wahrheitstabelle erstellt und auf Grundlage dieser die boolesche Minimierung durchgeführt.
    #Auf diese Schritte wird in diesem Skript verzichtet. Es wird die von Kummer(2013) berechnete Formel zur Bestimmung von Kollaboration verwendet.
    #Sollte es gewünscht sein, eine andere Formel zu verwenden, muss dieser Teil des Skriptes entsprechend angepasst werden.
    #Die Formel lautet: DEN*DIS+cent*DEG*DIS -> COLL
    #
    #In boolescher Algebra steht * für ein UND und + für ein ODER
    
    collaboration_1 <- 0
    collaboration_2 <- 0
    
    collaboration_final <- ifelse(collaboration_1>collaboration_2,collaboration_1,collaboration_2)
    #Ergebnisse der einzelnen Gruppen in die Datenbank schreiben. 
    #Es wird die oben angegebene Verbindung verwendet.
    df <- data.frame(time,pa,den,cent,deg,dis,calibrated_den,calibrated_cent,calibrated_deg,calibrated_dis,collaboration_final)
    dbWriteTable(con, name="collaboration", value=df, append=TRUE)
  }
}else{
  #Dieser Teil wird abgearbeitet, wenn in der Revisionstabelle keine Einträge für die Gruppe ermittelt werden konnte.
  network <- graph.empty(n=nrow(vertices_data),directed=FALSE)
  network <- add.vertices(network, (nrow(vertices_data) - vcount(network)))
  V(network)$name <- vertices_data[,2]
  
  #Grafische Darstellung des Netzwerkes der Gruppe
  #Aus diesem Grund muss der Pfad zum Bilderordner für MediaWiki angegeben werden
  #Es wird für jede Gruppe ein Bild des aktuellen Netzwerkes für jede Stunde gespeichert
  #Bild wird mit Timestamp und Gruppennummer unter angegebenen Pfad abgespeichert
  time <- format(Sys.time(), "%Y-%m-%d-%H")
  full_path <- paste (image_path,time,"-Gruppe-",group_numbers[pa,],".png",sep="")
  
  #Festlegen des Layouts für das Netzwerk
  #Das Layout wird automatisch ermittelt. Es ist dabei abhängig von der Anzahl der Vertices. Dadurch wird eine optimale Darstellung gewährleistet.
  layout_test <- layout.auto(network)
  
  png(filename=full_path, width=800, height=600)
  plot(network, layout=layout_test, edge.width=E(network)$weight/4, vertex.label.dist=0.6, vertex.label.font=1, vertex.label.cex=1.5,vertex.size=7, vertex.color="red", edge.label=as.numeric(edgesmatrix[,3]), edge.label.cex=1.5)
  dev.off()
  
  #Es folgt die Berechnung der einzelnen Kennzahlen, welche zur Bestimmung von vorhandener Kollaboration benötigt werden.
  
  #Berechnung der Dichte (Density (DEN)) des Netzwerkes  
  den <- 0
  
  #Berechnung der Zentralisierung (centralization (CENT)) 
  cent <- 0
  
  #Berechnung der Diskussionsposts (Discussion-Posts(DIS))
  dis <- 0
  
  #Berechnung des Median Weighted Degrees (DEG)
  deg <- 0
  
  #Kalibrierung der Zentralisierung (CENT)
  calibrated_cent <- 0
  
  #Kalibrierung Median Weighted Degree (DEG)
  calibrated_deg <- 0
  
  #Kalibrierung der Discussion-Posts (DIS)
  calibrated_dis <- 0
  
  #Kalibrierung der Dichte (Density(DEN))
  calibrated_den <- 0
  
    #Berechnung der Formel, welche sich aus der booleschen Minimierung ergibt. 
    #Hierfür müssen zunächst die anderen beiden Faktoren berechnet werden, welche laut Dillenbourg (1999) Kollaboration ausmachen.
    #AnschließŸend wird eine Wahrheitstabelle erstellt und auf Grundlage dieser die boolesche Minimierung durchgeführt.
    #Auf diese Schritte wird in diesem Skript verzichtet. Es wird die von Kummer(2013) berechnete Formel zur Bestimmung von Kollaboration verwendet.
    #Sollte es gewünscht sein, eine andere Formel zu verwenden, muss dieser Teil des Skriptes entsprechend angepasst werden.
    #Die Formel lautet: DEN*DIS+cent*DEG*DIS -> COLL
    #
    #In boolescher Algebra steht * für ein UND und + für ein ODER
  
  collaboration_1 <- 0
  collaboration_2 <- 0
  
  collaboration_final <- ifelse(collaboration_1>collaboration_2,collaboration_1,collaboration_2)
  #Ergebnisse der einzelnen Gruppen in die Datenbank schreiben. 
  #Es wird die oben angegebene Verbindung verwendet.
  df <- data.frame(time,pa,den,cent,deg,dis,calibrated_den,calibrated_cent,calibrated_deg,calibrated_dis,collaboration_final)
  dbWriteTable(con, name="collaboration", value=df, append=TRUE)
}
}
}

#Beenden der Datenbankverbindung.
dbDisconnect(con)