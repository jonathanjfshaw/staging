<?php
echo "Export all the configuration file to GitHub";
echo shell_exec("drush -y config-export");
//echo shell_exec("git clone -b ConfigExport2 https://username:11c66f8a49339e4ae97d732106579fb77cfd325b@github.com/shrimala/ahsweb.git");
echo shell_exec("chmod -R 777 ahsweb");
echo shell_exec("cp /app/public/sites/default/files/config/sync/* /app/public/sites/default/files/ahsweb/config/sync/");
echo shell_exec("cd /app/public/sites/default/files/ahsweb");
echo shell_exec("git add --all");
echo shell_exec("git config  user.email 'arith.nath@dcplkolkata.com'");
echo shell_exec("git config  user.name 'aritnath1990'");
echo shell_exec("git commit -am 'update message'");
echo shell_exec("git push origin ConfigExport2");
?>
<html>
  <head>
    <title>
      Configuration Export
    </title>
  </head>
  <body style="background-color: #000000; color: #FFFFFF; font-weight: bold; padding: 0 10px;">
    <div style="width:700px">
      <div style="float:left;width:350px;">
        <p style="color:white;">Git Configuration Export Script</p>
      </div>
    </div>
  </body>
</html>
