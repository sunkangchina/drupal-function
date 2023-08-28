使用

下载并安装drupal 10,在drupal根目录创建目录custom,然后下载drupal-function代码并解压至custom目录。

访问 `/custom/api/v1/index.php`

需要理解drupal的核心content type及term的使用


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
