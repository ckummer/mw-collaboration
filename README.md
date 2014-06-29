mw-collaboration
================

Installation
------------
**PART 1 - Installing the MediaWiki extension**

1. Download and extract the files in a directory called ```Collaboration``` in your ```/extensions``` folder.
2. Add the following code at the bottom of your ```LocalSettings.php```: ```require_once( "$IP/extensions/Collaboration/Collaboration.php" );```
3. Run the [update script][] which will automatically create the necessary database tables that this extension needs.
4. Create a folder named ```/collaboration_plots``` in ```/images```. This folder will be used to store social network diagrams that have been calculated by the R script.
5. Group your students into groups using the extension.

**PART 2 - Preparing the R environment**

1. Move the R script ```Calculate_Collaboration.R``` from the extension directory outside the document root and give them write access to ```/images/collaboration_plots```. Change the parameters within R script to suit your needs (e.g., database connection, image_path).

2. Install the R packages RMySQL, QCA, igraph from CRAN. This will be easy using the three commented lines in the script on Linux-based systems, but takes some efforts on Windows as RMySQL binaries are not provided. The basic procedure is outlined at [RMySQLs home][], but you may find valuable hints on how to compile RMySQL from source on [StackOverFlow][].

3. Execute the R script on a regular basis
  * On Linux, create a cron job using crontab, e.g., for hourly execution, ```0 * * * * Rscript /path/to/Calculate_Collaboration.R```
  * On Windows, create a batch file for further scheduling with the task scheduler that includes the following line: ```C:\"Program Files"\R\R-3.0.0\bin\i386\Rterm.exe BATCH --vanilla --file=D:\Calculate_Collaboration.R --no-save```
  
[update script]: http://www.mediawiki.org/wiki/Manual:Update.php
[StackOverFlow]: http://stackoverflow.com/questions/5223113/using-mysql-in-r-for-windows
[RMySQLs home]: http://biostat.mc.vanderbilt.edu/wiki/Main/RMySQL
