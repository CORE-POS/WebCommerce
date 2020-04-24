SqlManager
==========
SQL abstraction class for dealing with multiple SQL drivers
and backend servers. SqlManager uses a slightly modified
version of ADOdb (http://http://adodb.sourceforge.net/)
and mostly supports any database that ADOdb supports. There
are a handful of operations handled directly in SqlManager
mostly relating to datetime functions that only work with
MySQL (mysql, mysqli, and PDO variants) and SQL Server.
Support for other DBs could be added by pull request.

Setup:
==========
Include it, or put it in an autoloading path.
If the "log" directory is writable, failed queries & error messages
will be written to log/queries.log

Usage:
==========
The constructor takes several parameters:
* [string] $server is the host name or IP. Including a port works with some underlying drivers.
* [string] $type is a ADOdb driver identifier. Known good values:
    - pdo_mysql
    - mysqli
    - mssql
    - mysql (using this is, of course, a terrible idea)
* [string] $database is the name of the database and is required. SqlManager will attempt to
    create this if it does not exist.
* [string] $username 
* [string] $password (optional: default empty string. Again, using this is a terrible idea)
* [boolean] $persistent (optional: default false)
* [boolean] $new forces a new connection to the database if the underlying driver supports
    it. (optional: default false)

Method names generally match native mysqli_* and mssql_* functions. For example:
* query()
* fetch_array()
* num_rows()

All underscore methods should have a camelCase equivalent (e.g., numRows()) for the 
sake of PHP-FIG compliance.

Prepared statements are created using the prepare() method. They are executed using
the execute() method. Parameters are passed to execute as an array. If the query used
named placeholders then it should be a keyed array. If the query used question mark
placeholders then it should be a numerically indexed array.

Todo
==========
Support for other databases using PDO is currently problematic. The underlying ADOConnection
object simply reports the type as "pdo". Anything ADOdb *cannot* handle and is implemented
in SqlManager assumes that identifier means MySQL. Altering ADOdb itself to report types like
"pdo_mysql", "pdo_pgsql", "pdo_sqlite", etc would be an improvement.
