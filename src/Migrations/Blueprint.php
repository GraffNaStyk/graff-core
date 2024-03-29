<?php

namespace App\Facades\Migrations;

use App\Facades\Db\Db;
use App\Facades\Dependency\AttributeReflector;

class Blueprint
{
    protected string $startSql = 'CREATE TABLE IF NOT EXISTS ';
    protected string $endSql = ' )';
    protected ?string $sql = null;
    protected string $table;
    protected string $notNull = ' NOT NULL';
    protected array $tableFields = [];
    protected array $alter = [];
    protected array $queries = [];
    protected bool $store = false;

    protected array $length = [
        'tinyint' => '(1)',
        'smallint' => '(4)',
        'mediumint' => '(8)',
        'int' => '(11)',
        'char' => '(20)',
        'varchar' => '(100)',
        'text' => '',
        'mediumText' => '',
        'longText' => '',
        'timestamp' => ' ',
        'datetime' => ' ',
        'float' => '(9,2)',
    ];

    protected string $currentKey = '';
    protected string $currentFieldName = '';
    protected string $lastCalled;
    protected $otherImplementation;
    protected array $foreign = [];
    protected object $db;
    protected array $trigger = [];

    use TableQueries;

    public function __construct($model, $store = false)
    {
        $this->db = new Db($model);
	    $attrReflector = new AttributeReflector();
	    $attrReflector->reflect(new \ReflectionClass($model));
        $this->table = $attrReflector->get('table');
        $this->store = $store;
	    $this->db->connect();
    }

    public function generate($name, $fnName, $length = null)
    {
        $this->lastCalled       = $fnName;
        $this->currentFieldName = '`'.$name.'`';
        $this->tableFields[]    = '`'.$name.'`'.' '.$this->lastCalled.' '.
            ($length ? '('.$length.')' : $this->length[$this->lastCalled]).' '.$this->notNull;

        $this->currentKey = array_key_last($this->tableFields);
    }

    public function run(): ?bool
    {
        if (! empty($this->tableFields)) {
            $fields = implode(', ', $this->tableFields);
            $fields = rtrim($fields, ',');
            $this->otherImplementation = rtrim($this->otherImplementation, ', ');

            if ($this->otherImplementation !== '') {
                $this->sql = $this->startSql.'`'.trim($this->table).'`'.' ( '.$fields
                    .', '.$this->otherImplementation.$this->endSql;
            } else {
                $this->sql = $this->startSql.'`'.trim($this->table).'`'.' ( '.$fields.$this->endSql;
            }

            if ($this->store) {
                $this->storeMigration();
                return true;
            } else {
                $this->db->query($this->sql);
            }
        }
	
	    if ($this->store && ! empty($this->alter)) {
		    $this->storeMigration();
		    return true;
	    }

        if (! $this->store) {
            if (! empty($this->queries)) {
                foreach ($this->queries as $query) {
                    $this->db->query($query);
                }
            }

            if (! empty($this->alter)) {
                foreach ($this->alter as $alter) {
                    $this->db->query($alter);
                }
            }

            if (! empty($this->foreign)) {
                foreach ($this->foreign as $foreign) {
                    $this->db->query($foreign);
                }
            }

            if (! empty($this->trigger)) {
                foreach ($this->trigger as $trigger) {
                    $this->db->query($trigger);
                }
            }
        }
        
        return null;
    }

    public function drop(): void
    {
        if ($this->hasTable($this->table)) {
            $this->db->query('DROP TABLE '.$this->table);
        }

        $triggers = $this->db->query('SELECT * FROM `INFORMATION_SCHEMA`.`TRIGGERS` WHERE TRIGGER_SCHEMA = "'
            .$this->db->getDbName().'"');

        foreach ($triggers as $trigger) {
            if (mb_strtolower($this->db->table) === mb_strtolower($triggers->event_object_table)) {
                $this->db->query('DROP TRIGGER '.$trigger->trigger_name);
            }
        }
    }

    public function clear(): void
    {
        if ($this->hasTable($this->table)) {
            $this->db->query('TRUNCATE TABLE '.$this->table);
        }
    }

    protected function storeMigration(): void
    {
	    $name = $this->db->getConnectionName().'_dump_'.date('Y_m_d__H_i_s').'.sql';
        file_put_contents(app_path('app/migrate/'.$name), $this->sql.';'.PHP_EOL.PHP_EOL, FILE_APPEND);

        if (! empty($this->queries)) {
            foreach ($this->queries as $query) {
                file_put_contents(app_path('app/migrate/'.$name), $query.';'.PHP_EOL, FILE_APPEND);
            }
        }

        if (! empty($this->alter)) {
            foreach ($this->alter as $alter) {
                file_put_contents(app_path('app/migrate/'.$name), $alter.';'.PHP_EOL, FILE_APPEND);
            }
        }

        if (! empty($this->foreign)) {
            foreach ($this->foreign as $foreign) {
                file_put_contents(app_path('app/migrate/'.$name), $foreign.';'.PHP_EOL, FILE_APPEND);
            }
        }

        if (! empty($this->trigger)) {
            foreach ($this->trigger as $trigger) {
                file_put_contents(app_path('app/migrate/'.$name), $trigger.';'.PHP_EOL, FILE_APPEND);
            }
        }
    }
	
	public function dropColumn(string $column): void
	{
		if ($this->hasColumn($this->table, $column)) {
			$this->db->query('ALTER TABLE '.$this->table.' DROP COLUMN '.$column);
		}
	}
}
