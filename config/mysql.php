<?php

$environment = array_merge($_SERVER, $_ENV);
ksort($environment);

// Lets connect to a database
if(isset($environment['MYSQL_PORT'])) {
    $databaseConfigurationHost = parse_url($environment['MYSQL_PORT']);

    $databaseConfiguration = array(
        'db_type' => 'Mysql',
        'db_hostname' => $databaseConfigurationHost['host'],
        'db_port' => $databaseConfigurationHost['port'],
        'db_username' => $environment['MYSQL_1_ENV_MYSQL_USER'],
        'db_password' => $environment['MYSQL_1_ENV_MYSQL_PASSWORD'],
        'db_database' => $environment['MYSQL_1_ENV_MYSQL_DATABASE'],
    );
}elseif(gethostname() == 'houston'){
    $databaseConfiguration = array(
        'db_type' => 'Mysql',
        'db_hostname' => "sql.thru.io",
        'db_port' => 3306,
        'db_username' => "longitude",
        'db_password' => "q05x2q8LtAg7O8Q",
        'db_database' => "longitude",
    );
}else{
    die("No DB config");
}
$database = new \Thru\ActiveRecord\DatabaseLayer($databaseConfiguration);
