# qIUz

## Getting started _(Visual Studio Code)_
Plugins
* Composer
* PHP
* PHP Profiler _(Xdebug)_

## Run local
Run local site with debugger <br>
_or_<br>
php -S loacalhost:8000 _(whatever port you like)_<br>

## Project
Tasks to find in **Issues** _(Feel free to add feedback)_<br>
Upload to respository through **Pull requests** <br>

## Create .env-file
paste env-variables into .env-file
```
ENVIRONMENT=dev
DB_TYPE=sqlite<br>
DB_NAME=database.sqlite
WEB_CACHE=private, no-cache, no-store
```
> [!TIP]
> Ich empfehle ein UNIX basiertes System, aber es funktioniert auch auf Windows

> [!NOTE] 
> Wenn Fehlermeldungen dauerhaft erscheinen, solltest du im Terminal mit
> ```php --ini```  deine config Dateien ausgeben lassen und dort in der php.ini
> ganz unten diese Zeile hinzufügen:
```
display_startup_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED
```

## Attribution

Dieses Projekt basiert auf dem [bq.php] Template von Kevin Bulteel. Die Vorlage diente als Grundlage für die Entwicklung dieses Projekts.
