<VirtualHost *:80>
 ServerName temujin.monitor
 DocumentRoot /Users/ns/Development/Projects/temujin
 <Directory /Users/ns/Development/Projects/temujin>
   Options Indexes FollowSymLinks MultiViews
   AllowOverride FileInfo
   Order allow,deny
   Allow from all
 </Directory>
</VirtualHost>