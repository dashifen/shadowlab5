<?php

namespace Shadowlab\Framework\Database;

use Shadowlab\Config\Credentials;
use Dashifen\Database\MySQL\MysqlDatabase;

class Database extends MysqlDatabase implements Credentials\Database {
	public function __construct() {
		$dsn = sprintf("mysql:host=%s;dbname=%s", self::server, self::database);
		parent::__construct($dsn, self::username, self::password);
	}
}
