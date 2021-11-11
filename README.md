
# Install
- create db table using `setup.sql` inside of your existing ispconfig database
- install module files as follows:
````
# install module
cp -R ddns /usr/local/ispconfig/interface/web/
chown -R ispconfig:ispconfig /usr/local/ispconfig/interface/web/ddns
# setup dependency class
ln -s -f /usr/local/ispconfig/interface/web/ddns/lib/classes/ddns_custom_datasource.inc.php /usr/local/ispconfig/interface/lib/classes/
chown -h ispconfig:ispconfig /usr/local/ispconfig/interface/lib/classes/ddns_custom_datasource.inc.php
# link menu entries in DNS module
mkdir -p /usr/local/ispconfig/interface/web/dns/lib/menu.d
ln -s -f /usr/local/ispconfig/interface/web/ddns/lib/ddns.menu.php /usr/local/ispconfig/interface/web/dns/lib/menu.d/
chown -h ispconfig:ispconfig /usr/local/ispconfig/interface/web/dns/lib/menu.d/ddns.menu.php
````

# Uninstall
- remove database table ``DROP TABLE IF EXISTS `ddns_token`;``
- delete module files
````
rm -f /usr/local/ispconfig/interface/lib/classes/ddns_custom_datasource.inc.php
rm -f /usr/local/ispconfig/interface/web/dns/lib/menu.d/ddns.menu.php
rmdir /usr/local/ispconfig/interface/web/dns/lib/menu.d
rm -rf /usr/local/ispconfig/interface/web/ddns
````

# Known/Unknown Issues
- Paging does not work correctly, show all records on the same page to work around this
- May not work correctly in a multi-server setup or with the domain module (please give feedback)
