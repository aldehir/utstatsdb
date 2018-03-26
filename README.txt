UTStatsDB
  Copyright (C) 2002-2009  Patrick Contreras / Paul Gallier
  Copyright (C) 2018  Kenneth Watson

Please review the LICENSE.txt file included with this program.

Additional documentation can be found in the docs directory, including
descriptions and examples of the admin configuration screens.

===============================================================================
========== UTStatsDB Versions and Requirements ================================
===============================================================================
UTStatsDB is a log parser and web page for logs created with games using the
Unreal engine.  UTStatsDB does not itself generate the log files from the games,
it simply is told where to find them.  UTStatsDB is designed for operators of
dedicated game servers, and requires a database and web server.  A basic
understanding of configuring and managing a web server is assumed.

UTStatsDB is compatible with most web servers that support PHP, and is
compatible with the following database formats: MySQL, SQLite, Microsoft SQL.

UTStatsDB is designed to work with PHP 5.2 or newer, with a MySQL, SQLite, or
MSSQL database, on any web server with PHP support (nginx, Apache HTTPD, etc).

The current version of UTStatsDB can be downloaded or cloned from the following
Git repository: https://github.com/shrimpza/utstatsdb. Older versions and
information can be found at http://www.utstatsdb.com/.

Please report bugs using the issue tracker on the GitHub repository's issue
tracker.

For installation instructions, please refer to INSTALL.txt located within the
docs directory.

===============================================================================
========== Unreal Tournament Log Files ========================================
===============================================================================
Log files are generated differently depending on your game version.  Only the
Unreal Tournament series is supported here, though UTStatsDB is known to work
with certain other games running the Unreal engine.

Unreal Tournament (also known as UT '99):
  Logging is built-in.

Unreal Tournament 2003:
  A third party logging mutator is required, such as LocalStats by El Muerte.
  LocalStats can also be combined with RLog to receive log data directly through
  a TCP port connection.

Unreal Tournament 2004:
  Local logging capability is built-in, however, OLStats by OverloadUT is
  recommended (available in the UTStatsDB downloads).

Unreal Tournament 3:
  A third party logging mutator is required, of which UT3Stats is the only one I
  know of currently (available in the UTStatsDB downloads.

Configuration details can be found in docs/LOGGING.txt.

===============================================================================
========== UTStatsDB Documentation ============================================
===============================================================================
The following documentation can be found in the docs directory included with
this release:

     INSTALL.txt - Main installation guide.
CONFIG_GUIDE.txt - A quick guide to the configuration parameters.
     LOGGING.txt - Configuration of game logging and retrieval by the web server.
      DOCKER.txt - Configuring, building, and running in a Docker environment.

Informational:

  RELEASE.txt  - Release notes / change log.
  DATABASE.txt - Details on the UTStatsDB database tables.
  RANKING.txt  - Notes on the ranking system.
