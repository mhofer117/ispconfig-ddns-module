# ISPConfig 3 Dynamic DNS (DDNS) Module

- [Features](#features)
- [Screenshots](#screenshots)
- [Installation](#installation)
- [Update](#update)
- [Uninstall](#uninstall)
- [Known/Unknown Issues](#knownunknown-issues)

For general questions or feedback use the [forum thread on howtoforge](https://www.howtoforge.com/community/threads/ispconfig-3-danymic-dns-ddns-module.87967/).

## Features
- Integrated into your ISPConfig 3 DNS menu
- Allows clients, resellers and admins to create ddns tokens
- Updates can be performed with simple GET requests using these tokens
- Updates can be performed using DynDns1 and DynDns2 protocols
- Tokens can be restricted to individual DNS zones, DNS records and records types (A/AAAA)
- Allows updating A (IPv4) and AAAA (IPv6) records
- Allows creating/deleting TXT records. Useful for ACME dns-01 challenges using the custom [certbot-dns-ispconfig-ddns plugin](https://github.com/mhofer117/certbot-dns-ispconfig-ddns)
- The update script shares the same authentication rate-limiting / blocking method from base ISPConfig
- Works in multi-server setups if the DB-Table is created on all servers, [see discussion](https://github.com/mhofer117/ispconfig-ddns-module/issues/4#issuecomment-1437604492)

## Screenshots
![Overview page screenshot](https://user-images.githubusercontent.com/3976393/141506890-7c235b39-6ad9-4519-a482-4f2e8d44740c.png)
![Edit/New token page screenshot](https://user-images.githubusercontent.com/3976393/141506913-5b56f809-f255-49f8-b7da-fc2dd080c3ff.png)
![Update URLs modal screenshot](https://user-images.githubusercontent.com/3976393/157296785-6a3c4e00-24b0-431f-91b0-62fc6f32d330.png)



## Installation
- Create the database table using [`setup.sql`](setup.sql) inside of your existing ispconfig database, usually called "dbispconfig"
- Checkout the repository or download and extract a release on your server
- Move the directory to the correct location: `mv ispconfig-ddns-module /usr/local/ispconfig/interface/web/ddns`
- Set permissions and create symlinks as follows:
````
# install module
chown -R ispconfig:ispconfig /usr/local/ispconfig/interface/web/ddns
# setup dependency class
ln -s -f /usr/local/ispconfig/interface/web/ddns/lib/classes/ddns_custom_datasource.inc.php /usr/local/ispconfig/interface/lib/classes/
chown -h ispconfig:ispconfig /usr/local/ispconfig/interface/lib/classes/ddns_custom_datasource.inc.php
# link menu entries in DNS module
mkdir -p /usr/local/ispconfig/interface/web/dns/lib/menu.d
ln -s -f /usr/local/ispconfig/interface/web/ddns/lib/ddns.menu.php /usr/local/ispconfig/interface/web/dns/lib/menu.d/
chown -h ispconfig:ispconfig /usr/local/ispconfig/interface/web/dns/lib/menu.d/ddns.menu.php
# link nic directory to support dyndns v1/v2 protocol endpoints
ln -s -f /usr/local/ispconfig/interface/web/ddns/nic /usr/local/ispconfig/interface/web/
chown -h ispconfig:ispconfig /usr/local/ispconfig/interface/web/nic
````

If you are using nginx, [you will also need to set up a proxy host](https://github.com/mhofer117/ispconfig-ddns-module/wiki/Setup-Proxy-Domain-(nginx))

## Update
If you pulled the module to your server with git, use `git pull`, otherwise download the latest release and override all existing files.
After that, re-run the commands from the installation steps to fix permissions / symlinks.

## Uninstall
- Remove module database table ``DROP TABLE IF EXISTS `ddns_token`;``
- Delete all module files and related symlinks
````
rm -f /usr/local/ispconfig/interface/lib/classes/ddns_custom_datasource.inc.php
rm -f /usr/local/ispconfig/interface/web/dns/lib/menu.d/ddns.menu.php
rmdir /usr/local/ispconfig/interface/web/dns/lib/menu.d
rm -rf /usr/local/ispconfig/interface/web/ddns
rm -rf /usr/local/ispconfig/interface/web/nic
````

## Known Issues
- Paging does not work correctly, show all records on the same page to work around this
- nginx will not respect the .htaccess file used for DynDns, you need to set up a revers proxy domain,
  [see the guide in the wiki](https://github.com/mhofer117/ispconfig-ddns-module/wiki/Setup-Proxy-Domain-(nginx))
- The following clients require ISPConfig on a default port (443 or 80):
  - DynDns1 and DynDns2 protocols, for example with [ddclient](https://github.com/ddclient/ddclient)
  - FRITZ!Box (tm) (may support :8080 and other ports in a future update)
  - maybe others
  - Workaround: if you don't want to change the ISPConfig port for some reason, you can set up a proxy domain
    [as described in the wiki](https://github.com/mhofer117/ispconfig-ddns-module/wiki/Setup-Proxy-Domain)
