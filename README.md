# WallacePOS
## An intuitive & modern web based POS system
![logo](https://wallacepos.com/images/wallacepos_logo_600.png)

WallacePOS uses the power of the modern web to provide an easy to use & extensible POS system.

It's with standard POS hardware including receipt printers, cashdraws and barcode scanners.

With a rich administration dashboard and reporting features, WallacePOS brings benefits to managers and staff alike.

Take your business into the cloud with WallacePOS!

To find out more about WallacePOS, head over to [wallacepos.com](https://wallacepos.com)

## Server Requirements

WallacePOS requires:

- A Lamp server with mod_wstunnel installed & activated

- The following snippet in your apache.conf or apache config dir

```
    ProxyRequests Off
    ProxyPreserveHost On
    <Proxy *>
        Order deny,allow
        Allow from all
    </Proxy>
    ProxyPass /socket.io/1/websocket/ ws://127.0.0.1:8080/socket.io/1/websocket/
    ProxyPassReverse /socket.io/1/websocket/ ws://127.0.0.1:8080/socket.io/1/websocket/
    ProxyPass /socket.io/ http://127.0.0.1:8080/socket.io/
    ProxyPassReverse /socket.io/ http://127.0.0.1:8080/socket.io/
    <Location /socket.io>
        Order allow,deny
        Allow from all
    </Location>
```

- Node.js installed along with the socket.io library

## Deploying using dokku

To deploy WallacePOS on dokku:

1. Install [dokku-buildpack-multi](https://github.com/pauldub/dokku-multi-buildpack) on your dokku host

2. Copy /library/wpos/dbconfig_template.php to dbconfig.php and fill in your own values

    **OR**

    Use my [dokku mysql plugin](https://github.com/micwallace/dokku-mysql-server-plugin) to create and link the database automatically

## Installation & Startup

### To install the database:

1. Enable the /library/installer/index.php file by removing the die(); command at the start
2. Access library/installer/?install from the web browser to install the database schema

### To run the feed server

- Run /api/server.js using node.js or login to the admin dashboard, go to settings -> utilities and click the start button under feed server.


