# Rovi Query Builder

Independent query builder for PHP.

## Features

* A simple ORM
* A simple query builder
* A simple, built-in, logging system  

## Requirements

* PHP ^7.1 || ^8.0
* Composer

## Configuration

Rename the `rovi-config.json-example` file to `rovi-config.json` and adjust settings with database connection details. 

```
{
    "version": "1.1.0",
    "logs": {
        "enabled": true,
        "path": "logs/{conn_name}.{date}.{level}.log",
        "dateFormat": "Y-m-d",
        "dateEntryFormat": "Y-m-d H:i:s",
        "level": "DEBUG" 
    },
    "db": {
        "default": "useiro",
        "connections": [
            {
                "name": "useiro",
                "type": "sqlite",
                "server": null,
                "database": "sqlite-example.db",
                "user": null,
                "password": null
            }
        ]
    }
}
```

* **version** : (informative) the Rovi number version.
* **logs** : logging configuration.
  * **enabled** : set it to `true` to enable, or to `false` to disable.
  * **path** : the folder the logs will be saved to. Available variables are `{conn_name}` (the connection name), `{date}` (the formatted date of file) and `{level}` (the log level). It allows you to organize the log files.
  * **dateFormat** : the date format as accepted by the PHP date() function, used for the filename. Avoid using slashes and any other character unsuitable to filepath.
  * **dateEntryFormat** : the date/time entry format as accepted by the PHP date() function, used for log entries.
  * **level** : the minimal level to be logged. E.g., if you set it to "ERROR", no log of level below ERROR (i.e., DEBUG, INFO, WARNING) will be registered.
* **db** : the databse configuration.
  * **default** : the default connection name to be used when none is specified.
  * **connections** : one or more connections to be configured.
    * **name** : name of the connection.
    * **type** : it refers to the DBMS vendor (e.g., "sqlite", "mssql", "mysql" etc).
    * **server** : the DBMS server address (IP or hostname). For SQLite, it should be set to `null`.
    * **database** : the name of the database. For SQLite, you should set it to the SQLite database filepath.
    * **user** : the database suername. For SQLite, it should be set to `null`.
    * **password** : the database user password. For SQLite, it should be set to `null`.
 