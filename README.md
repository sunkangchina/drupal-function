

重写
~~~
location / { 
   try_files $uri /index.php?$query_string;
}
~~~

显示错误信息
sites/default/settings.php
~~~
// 显示详细的错误信息
$config['system.logging']['error_level'] = 'verbose'; 
~~~