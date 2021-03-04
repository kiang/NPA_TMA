<?php
$rootPath = dirname(__DIR__);
$now = date('Y-m-d H:i:s');

exec("cd {$rootPath} && /usr/bin/git pull");

exec("php -q {$rootPath}/scripts/01_fetch.php");
exec("php -q {$rootPath}/scripts/02_raw2data.php");
exec("php -q {$rootPath}/scripts/04_report_city.php");

exec("cd {$rootPath} && /usr/bin/git add -A");

exec("cd {$rootPath} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m 'auto update @ {$now}'");

exec("cd {$rootPath} && /usr/bin/git push origin master");