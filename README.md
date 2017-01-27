# WallacePOS
## An intuitive & modern web based POS system
![logo](https://wallacepos.com/images/wallacepos_logo_600.png)

WallacePOS uses the power of the modern web to provide an easy to use & extensible POS system.

It supports standard POS hardware including receipt printers, cashdraws and barcode scanners.

With a rich administration dashboard and reporting features, WallacePOS brings benefits to managers and staff alike.

Take your business into the cloud with WallacePOS!

To find out more about WallacePOS, head over to [wallacepos.com](https://wallacepos.com)

If you find that WallacePOS is the perfect companion for your business, please donate to support further development.

[![Donate to WallacePOS](https://www.paypalobjects.com/en_AU/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=783UMXCNZGL68)

## Server Prerequisites

WallacePOS requires:

1. A Lamp server with PHP version>=5.4, PHP cURL & GD extensions and Apache version>=2.4.7 with modules rewrite, proxy_http and proxy_wstunnel.

    - You can enable the modules by typing the following in your terminal

    ```
        sudo a2enmod proxy_http proxy_wstunnel rewrite
        sudo apt-get install php5-curl php5-gd
        sudo service apache2 restart
    ```

    - The following virtual host snippet in your apache config, replace %*% with your values and modify to your needs.


    ```
        <VirtualHost *:443>
             DocumentRoot %/your_install_dir%
             ServerName %your.server.fqdn%

             ErrorLog ${APACHE_LOG_DIR}/error.log
             CustomLog ${APACHE_LOG_DIR}/access.log combined

             SSLEngine on
                 SSLCipherSuite !ADH:!DSS:!RC4:HIGH:+3DES:+RC4
                 SSLProtocol all -SSLv2 -SSLv3
                 SSLCertificateFile %certificate_location%
                 SSLCertificateKeyFile %key_location%
                 SSLCertificateChainFile %cert_chain_location%

             <Directory %/your_install_dir%>
                AllowOverride all
             </Directory>

             # WSPROXY CONF
             ProxyRequests Off
             ProxyPreserveHost On
             <Proxy *>
                     Order deny,allow
                     Allow from all
             </Proxy>
             RewriteEngine On
             RewriteCond %{HTTP:Connection} Upgrade [NC]
             RewriteRule /(.*) ws://localhost:8080/$1 [P,L]
             ProxyPass        /socket.io http://localhost:8080/socket.io/
             ProxyPassReverse /socket.io http://localhost:8080/socket.io/
             <Location /socket.io>
                     Order allow,deny
                     Allow from all
             </Location>
        </VirtualHost>
    ```

    Note: Using plain http is not recommended.

2. Node.js installed along with the socket.io library

    For a Debian distro:

    ```
        sudo apt-get update
        sudo apt-get install nodejs && apt-get install npm
        cd %/your_install_dir%/api
        sudo npm install socket.io
    ```

## Installation & Startup

1. Clone the latest WallacePOS release to %your_install_dir% if you haven't done so already.
   The installation dir must be your Apache document root directory!
   
2. Run `composer install` in your install directory to update PHP dependencies (you may need to install composer first).

3. Visit /installer in your browser & follow the installation wizard.

4. Login to the admin dashboard at /admin, from the menu go to Settings -> Utilities and make sure the feed server has been started successfully.

## Deploying using dokku

To deploy WallacePOS on dokku:

1. Install the [dokku-apt](https://github.com/F4-Group/dokku-apt) plugin on your dokku host.

2. Fork the WallacePOS to a PRIVATE repo (IMPORTANT), edit /library/wpos/.dbconfig.json and fill in your own values.

    **OR**

   Use my [dokku mysql plugin](https://github.com/micwallace/dokku-mysql-server-plugin) to create and link the database automagically.   

3. Commit deploy in the usual manner.

4. Setup persistent storage by running:

   `dokku storage:mount %APP_NAME% /var/lib/dokku/data/storage/%APP_NAME%:/app/docs`
   
   WARINING: Failure to do so will lead to data loss during subsequent upgrades.

5. Access /installer/?install from the web browser to install the database schema & templates

6. Login to the admin dashboard at /admin using credentials admin:admin & change the default passwords in Settings -> Staff & Admins!
