<?php

/**
 * Voxxera POS MySQL to SQLite Offline Database Converter
 * 
 * Usage from command line:
 * php spark db:sqlite_dump
 */

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ConvertSqlite extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:sqlite_dump';
    protected $description = 'Converts the connected MySQL database schema and data into a portable offline SQLite file.';

    public function run(array $params)
    {
        CLI::write('Starting MySQL to SQLite conversion...', 'green');
        
        $mysql = \Config\Database::connect('default');
        
        // Ensure writable directory exists
        $sqlitePath = WRITEPATH . 'voxxera.sqlite';
        if (file_exists($sqlitePath)) {
            CLI::write('Overwriting existing voxxera.sqlite database.', 'yellow');
            unlink($sqlitePath);
        }

        // We create a custom config on the fly for SQLite
        $customSQLiteConfig = [
            'DSN'      => '',
            'hostname' => 'localhost',
            'username' => '',
            'password' => '',
            'database' => $sqlitePath,
            'DBDriver' => 'SQLite3',
            'DBPrefix' => 'ospos_',
            'pConnect' => false,
            'DBDebug'  => true,
            'charset'  => 'utf8',
            'DBCollat' => 'utf8_general_ci',
            'swapPre'  => '',
            'encrypt'  => false,
            'compress' => false,
            'strictOn' => false,
            'failover' => [],
            'port'     => 3306,
            'foreignKeys' => false,
        ];
        
        $sqlite = \Config\Database::connect($customSQLiteConfig, true);
        
        $tables = $mysql->listTables();
        
        if (empty($tables)) {
            CLI::error('No tables found in the MySQL database. Is it connected properly?');
            return;
        }

        foreach ($tables as $table) {
            CLI::write("Converting Table: $table", 'cyan');
            
            // 1. Get Table Schema
            $fields = $mysql->getFieldData($table);
            
            $createSql = "CREATE TABLE IF NOT EXISTS `$table` (";
            $columns = [];
            foreach ($fields as $field) {
                // Approximate data type translation
                $type = strtoupper($field->type);
                if (strpos($type, 'INT') !== false) {
                    $sqliteType = 'INTEGER';
                } elseif (strpos($type, 'DECIMAL') !== false || strpos($type, 'FLOAT') !== false || strpos($type, 'DOUBLE') !== false) {
                    $sqliteType = 'REAL';
                } else {
                    $sqliteType = 'TEXT';
                }
                
                $def = "`{$field->name}` $sqliteType";
                if ($field->primary_key) {
                    $def .= " PRIMARY KEY";
                    if ($sqliteType === 'INTEGER') $def .= " AUTOINCREMENT";
                }
                
                $columns[] = $def;
            }
            $createSql .= implode(', ', $columns) . ");";
            
            // Execute table creation
            try {
                $sqlite->query($createSql);
            } catch (\Exception $e) {
                CLI::error("Failed to create table $table: " . $e->getMessage());
                continue;
            }
            
            // 2. Dump Data
            $rows = $mysql->table($table)->get()->getResultArray();
            if (!empty($rows)) {
                CLI::write(" - Transferring " . count($rows) . " rows...", 'light_gray');
                $sqlite->table($table)->insertBatch($rows);
            }
        }
        
        CLI::write("✅ Conversion complete! Offline database ready at: $sqlitePath", 'green');
    }
}
