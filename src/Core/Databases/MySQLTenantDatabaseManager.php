<?php

namespace IbraheemGhazi\OmniTenancy\Core\Databases;

use Illuminate\Support\Traits\Macroable;
use RuntimeException;
use Symfony\Component\Process\Process;

class MySQLTenantDatabaseManager extends AbstractTenantDatabaseManager
{
    public function createDatabase(string $name): bool
    {
        return $this->executeForMysql('CREATE DATABASE IF NOT EXISTS ' . $name);
    }
    public function dropDatabase(string $name): bool
    {
        return $this->executeForMysql('DROP DATABASE IF EXISTS ' . $name);
    }
    public function renameDatabase(string $name, string $new_name): bool
    {
        $this->createDatabase($new_name);
        $this->executeForMysql("USE $name; SHOW TABLES;", $tables);
        $tables = array_filter(explode("\n", $tables));
        array_shift($tables);
        $commands = implode(' ', [
            "USE $name;",
            ...array_map(function($table) use($name, $new_name){
                return "RENAME TABLE `$name`.`$table` TO `$new_name`.`$table`;";
            }, $tables)
        ]);
        $this->executeForMysql($commands);
        $this->dropDatabase($name);
        return true;
    }
    public function backupDatabase(string $dbName, string $saveAt): bool
    {
        $this->executeForMysqlDump([$dbName], null, $output);
        file_put_contents($saveAt, $output);
        return true;
    }
    public function restoreDatabase(string $dbName, string $sourceSqlFile): bool
    {
        $this->executeForMysql("use $dbName;" . file_get_contents($sourceSqlFile));
//        this is not working as expected
//        $this->executeForMysqlDump([
//            $dbName,
//        ], file_get_contents($sourceSqlFile), $output);
        return true;
    }

    public function createUser(string $username, string $password, string $database): bool
    {
        $grants  = config('tenancy.database.grants', [
            'ALTER', 'ALTER ROUTINE', 'CREATE', 'CREATE ROUTINE', 'CREATE TEMPORARY TABLES', 'CREATE VIEW',
            'DELETE', 'DROP', 'EVENT', 'EXECUTE', 'INDEX', 'INSERT', 'LOCK TABLES', 'REFERENCES', 'SELECT',
            'SHOW VIEW', 'TRIGGER', 'UPDATE',
        ]);
        assert(is_array($grants), 'tenancy.database.grants must be of type array');
        $grants = implode(',', $grants);
        $this->executeForMysql("CREATE USER `{$username}`@`%` IDENTIFIED BY '{$password}'");
        $this->executeForMysql("GRANT $grants ON `$database`.* TO `$username`@`%`");
        return true;
    }

    public function dropUser(string $username): bool
    {
        $this->executeForMysql("DROP USER IF EXISTS `{$username}`@`%`");
        return true;
    }

    protected function executeForMysql(string $cmd, &$output = null): bool
    {
        return $this->execute('/usr/local/mysql/bin/mysql', [
            '-e ' . "$cmd",
        ], null, $output);
    }
    protected function executeForMysqlDump(array $args, mixed $input = null, &$output = null): bool
    {
        return $this->execute('/usr/local/mysql/bin/mysqldump', $args, $input, $output);
    }
    protected function execute(string $executable, array $args, mixed $input, &$output = null): bool
    {
        $process = new Process([
            $executable,
            '-u'  . $this->getDatabaseUsername(),
            '-p'  . $this->getDatabasePassword(),
            ...$args
        ]);
        if(!is_null($input))
        {
            $process->setInput($input);
        }


        $process->run();
        $output = $process->getOutput();

        $errors = explode("\n", $process->getErrorOutput());
        array_shift($errors);
        $errors = implode("\n",$errors);
        if($process->getExitCode() !== 0){
            throw new RuntimeException($errors);
//            Log::error(new RuntimeException($errors));
//            return false;
        }
        return true;
    }
}
