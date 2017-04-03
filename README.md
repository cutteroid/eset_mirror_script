# eset_mirror_script
- copy nod32ms.conf.%lang% -> nod32ms.conf
- edit lines in nod32ms.conf
- make executable update.php
- run ./update.php
# Nginx simple configuration
map $http_user_agent $dir {

 default                        /index.html;

 ~^(ESS.*BPC.3)                 /eset_upd/update.ver;

 ~^(.*Update.*BPC\ (?<ver>\d+))	/eset_upd/v$ver/update.ver;

}

server {

 listen 2221;
 
 server_name host;
 

 access_log /var/log/nginx/host-access.log;
 
 error_log /var/log/nginx/host-error.log;
 
 index index.php index.html index.htm;
 
 root <veb_dir from nod32ms.conf>;
 
 
 location / {
 
  root <veb_dir from nod32ms.conf>;
  
 }

 location /update.ver {
 
  rewrite ^/update.ver$ $dir redirect;
  
 }

 location ~ /\.ht {
 
  deny  all;
  
 }
 
}
