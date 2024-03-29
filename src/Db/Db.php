<?php

namespace App\Facades\Db;

use App\Facades\Config\Config;
use App\Facades\Dependency\AttributeReflector;
use App\Facades\Url\Url;
use App\Facades\Validator\Type;
use PDO;
use PDOException;

class Db
{
    use Variables;
    use Builder;
    
    private static PDO|null $db = null;
    public ?string $as = null;
	private string $connection = 'default';
	private static array $connections = [];
	private Entity $entity;
	private EntityConverter $entityConverter;
	private AttributeReflector $reflector;
	private string $modelNamespace;

    private static array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING,
        PDO::ATTR_CASE => PDO::CASE_LOWER,
        PDO::ATTR_EMULATE_PREPARES => false,
	    PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8;'
    ];
	
    const PER_PAGE = 25;
    
	public function __construct(string $model)
	{
		$this->entity          = new Entity($model);
		$this->reflector       = new AttributeReflector();
		$this->entityConverter = new EntityConverter($model);
		$reflectionClass       = new \ReflectionClass($model);
		$this->reflector->reflect($reflectionClass);
		
		$this->modelNamespace = $reflectionClass->getName();
		
		if ($this->reflector->has('connection')) {
			$this->connection = $this->reflector->get('connection');
		}
		
		$this->table       = $this->reflector->get('table');
		$this->model       = Url::segment($model, 'end', '\\');
		$this->hasTrigger = $this->reflector->has('isTriggered');
	}
    
    public function connection(string $connection): Db
    {
    	if (! Config::has('db.'.$connection)) {
    	    throw new \LogicException('Connection '.$connection. ' is not configured!');
	    }

        $this->connection = $connection;
	
	    return $this;
    }

    public function connect(): void
    {
	    try {
		    if (! Config::has('db.'.$this->connection)) {
			    throw new \LogicException('Database connection: "'.$this->connection.'" not exist!');
		    }
		    
            if (! isset(static::$connections[$this->connection])) {
            	$conn = Config::get('db.'.$this->connection);

                self::$db = new PDO(
	                'mysql:host='.$conn['host'].';dbname='.$conn['database'],
	                $conn['user'],
	                $conn['password'],
                    self::$options
                );

                static::$connections[$this->connection]['driver']   = self::$db;
                static::$connections[$this->connection]['database'] = $conn['database'];
                unset($conn);
            } else {
            	self::$db = static::$connections[$this->connection]['driver'];
            }
        } catch (PDOException $e) {
            Handle::throwException($e, 'DATABASE CONNECTION ERROR');
        }
    }

    public static function getInstance(): ?PDO
    {
        return self::$db;
    }

    public function getDbName(): string
    {
	    return static::$connections[$this->connection]['database'];
    }

    public function as(string $alias): Db
    {
        $this->as = ' as `'.$alias.'`';

        return $this;
    }

    public function distinct(): Db
    {
        $this->distinct = true;

        return $this;
    }

    public function onDuplicate(array $duplicated = []): Db
    {
        $this->onDuplicate = true;
        $this->duplicated = $duplicated;

        return $this;
    }

    public function selectGroup(array $values = []): Db
    {
        $this->selectGroup = true;
        $this->select($values);
        return $this;
    }

    public function multiple(): Db
    {
        $this->multiple = true;

        return $this;
    }

    public function insert(array $values): Db
    {
        $this->triggerMethod = 'created';

        if (empty($values)) {
            return $this;
        }

        $this->query = "INSERT INTO `{$this->table}` (";

        foreach ($this->multiple ? $values[0] : $values as $key => $field) {
            $this->query .= "`{$key}`, ";
        }

        $this->query = rtrim($this->query, ', ').") VALUES (";

        foreach ($values as $key => $field) {
            if (is_array($field)) {
                $count = count($field);
                $i = 0;

                foreach ($field as $key2 => $item) {
                    $i ++;
                    $this->query .= ":{$this->setValue($key2, $item)}, ";

                    if ($i === $count) {
                        $this->query = rtrim($this->query, ', ')."), (";
                    }
                }
            } else {
                $this->query .= ":{$this->setValue($key, $field)}, ";
            }
        }

        if ($this->multiple) {
            $this->query = rtrim($this->query, ', (');
        } else {
            $this->query = rtrim($this->query, ', ').")";
        }

        if ($this->onDuplicate === true) {
            $this->query .= ' ON DUPLICATE KEY UPDATE ';

            if (! empty($this->duplicated)) {
                foreach ($this->duplicated as $field) {
                    $this->query .= "`{$field}` = VALUES(`{$field}`), ";
                }
            } else {
                foreach ($this->multiple ? $values[0] : $values as $key => $field) {
                    $this->query .= "`{$key}` = VALUES(`{$key}`), ";
                }
            }
        }

        $this->query = rtrim($this->query, ', ');

        return $this;
    }

    public function select(array $values = []): Db
    {
        $this->query = 'SELECT';

        if ($this->distinct) {
            $this->query .= ' DISTINCT';
        }

        if (empty($values)) {
            $this->query .= ' * FROM `'.$this->table.'`'.$this->as;
        } else {
            $this->query .= " {$this->prepareValuesForSelect($values)} FROM `{$this->table}`".$this->as;
        }

        return $this;
    }

    public function update(array $values): Db
    {
        $this->triggerMethod = 'updated';
        $this->isUpdate      = true;
        $this->query         = "UPDATE `{$this->table}` SET ";

        foreach ($values as $key => $value) {
            if ((string) $key === 'id') {
                continue;
            }

            $this->query .= "`{$key}` = :{$this->setValue($key, $value)}, ";
        }

        $this->query = rtrim($this->query, ', ');

        return $this;
    }

    public function delete(): Db
    {
        $this->triggerMethod = 'deleted';
        $this->query         = "DELETE FROM `{$this->table}`";

        return $this;
    }

    public function exec(): bool
    {
        return $this->execute();
    }

    public function where(string $item, string $is, ?string $item2): Db
    {
        $this->appendToQuery();
        $this->query .= "{$this->prepareValueForWhere($item)} {$is} :{$this->setValue($item, $item2)}";

        return $this;
    }

    public function orWhere(string $item, string $is, ?string $item2): Db
    {
        $this->appendToQuery(true);
        $this->query .= "{$this->prepareValueForWhere($item)} {$is} :{$this->setValue($item, $item2)} ";

        return $this;
    }

    public function whereNull(string $item): Db
    {
        $this->appendToQuery();
        $this->query .= "{$this->prepareValueForWhere($item)} IS NULL ";

        return $this;
    }

    public function whereNotNull(string $item): Db
    {
        $this->appendToQuery();
        $this->query .= "{$this->prepareValueForWhere($item)} IS NOT NULL ";

        return $this;
    }

    public function orWhereNull(string $item): Db
    {
        $this->appendToQuery(true);
        $this->query .= "{$this->prepareValueForWhere($item)} IS NULL ";

        return $this;
    }

    public function orWhereNotNull(string $item): Db
    {
        $this->appendToQuery(true);
        $this->query .= "{$this->prepareValueForWhere($item)} IS NOT NULL ";

        return $this;
    }

    public function whereIn(string $item, array $items): Db
    {
        $this->appendToQuery();
        
        foreach ($items as $value) {
        	$arr[] = $this->setValue($item, $value);
        }

	    $items = ":".implode(", :", $arr)."";

        $this->query .= "{$this->prepareValueForWhere($item)} IN ({$items}) ";

        return $this;
    }

    public function whereNotIn(string $item, array $items): Db
    {
        $this->appendToQuery();
	
	    foreach ($items as $value) {
		    $arr[] = $this->setValue($item, $value);
	    }
	
	    $items = ":".implode(", :", $arr)."";

        $this->query .= "{$this->prepareValueForWhere($item)} NOT IN ({$items}) ";

        return $this;
    }

    public function whereBetween(string $item, array $items): Db
    {
        $this->appendToQuery();
        $this->query .= "{$this->prepareValueForWhere($item)} BETWEEN
                            :{$this->setValue($item, $items[0])} AND :{$this->setValue($item, $items[1])} ";

        return $this;
    }

    public function raw(string $raw): Db
    {
        $this->query .= ' '.$raw;

        return $this;
    }

    public function bind(array $data): Db
    {
        foreach ($data as $key => $value) {
            $newK = $this->setValue($key, $value);
            $this->query = preg_replace('/:'.$key.'/', ':'.$newK, $this->query, 1);
        }

        return $this;
    }

    public function order(array $by, string $type = 'ASC'): Db
    {
        $this->query .= " ORDER BY {$this->prepareValuesForSelect($by)} {$type}";

        return $this;
    }

    public function group(string $group): Db
    {
        $this->query .= " GROUP BY {$this->prepareValueForWhere($group)}";

        return $this;
    }

    public function limit(int $limit): Db
    {
        $this->query .= " LIMIT {$limit}";

        return $this;
    }

    public function offset(int $offset): Db
    {
        $this->query .= " OFFSET {$offset}";

        return $this;
    }

    public function paginate(int $page): Db
    {
        $this->limit(self::PER_PAGE)
            ->offset(($page - 1) * self::PER_PAGE);

        return $this;
    }

    public function create(array $values): mixed
    {
        $this->insert($values);

        return $this->execute();
    }

    public function first(): mixed
    {
        $this->first = true;

        return $this->execute();
    }

    public function get(): mixed
    {
        return $this->execute();
    }

    public function exist(): mixed
    {
        $res = $this->first();
        
        if (empty($res)) {
	        return null;
        }

        return $res;
    }

    public function count(string $item): Db
    {
        $this->query = "SELECT COUNT({$item}) as count from `{$this->table}`";
        $this->first = true;

        return $this;
    }

    public function increment(string $field, int $increment): Db
    {
        $this->query = "UPDATE `{$this->table}` SET {$field} = {$field} + {$increment} ";

        return $this;
    }

    public function decrement(string $field, int $decrement): Db
    {
        $this->query = "UPDATE `{$this->table}` SET {$field} = {$field} - {$decrement} ";

        return $this;
    }

    public function join(string $table, string $value1, string $by, string $value2, bool $isRigidly = false): Db
    {
        $this->setJoin('INNER', $table, $value1, $by, $value2, $isRigidly);
        return $this;
    }

    public function leftJoin(string $table, string $value1, string $by, string $value2, bool $isRigidly = false): Db
    {
        $this->setJoin('LEFT', $table, $value1, $by, $value2, $isRigidly);
        return $this;
    }

    public function rightJoin(string $table, string $value1, string $by, string $value2, bool $isRigidly = false): Db
    {
        $this->setJoin('RIGHT', $table, $value1, $by, $value2, $isRigidly);
        return $this;
    }

    private function setJoin(
        string $type,
        string $table,
        string $value1,
        string $by,
        string $value2,
        bool $isRigidly = false
    ): void
    {
        if ($isRigidly) {
            $twoValue = Type::get($value2);
        } else {
            $twoValue = $this->prepareValueForWhere($value2);
        }
        
        $this->query .= " {$type} JOIN {$this->prepareValueForWhere($table)} ON
                            {$this->prepareValueForWhere($value1)} {$by} {$twoValue}";
    }

    private function execute(): mixed
    {
	    if (empty($this->query)) {
		    return null;
	    }
	    
        if ($this->debug) {
            $this->develop();
        }

        if (preg_match('/^(INSERT|UPDATE|DELETE)/', $this->query)) {
            try {
                if (self::$db->prepare($this->query)->execute($this->data)) {
	                if (str_starts_with($this->query, 'INSERT')) {
		                static::$lastInsertedIds[$this->modelNamespace] = (int) self::$db->lastInsertId();
	                }
	
	                if ($this->hasTrigger && $this->triggerMethod !== null) {
		                TriggerResolver::resolve($this->model, $this->triggerMethod, $this);
		                $this->triggerMethod = null;
	                }

                    return true;
                }
            } catch (PDOException $e) {
                Handle::throwException($e, $this->develop(true));
            }
            return false;
        } else {
            try {
                $pdo = self::$db->prepare($this->query);
                $pdo->execute($this->data);
	
	            if ($this->first) {
		            if (! $res = $pdo->fetch(PDO::FETCH_OBJ)) {
			            return null;
		            }
		
		            return $this->entity->parse($res);
	            }

                if ($this->selectGroup) {
                    $this->selectGroup = false;
                    return $pdo->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_OBJ);
                } else {
                	return $this->entity->parse($pdo->fetchAll(PDO::FETCH_OBJ));
                }
            } catch (PDOException $e) {
                Handle::throwException($e, $this->develop(true));
            }
        }

        return false;
    }
    
    public function cursor(): \Traversable
    {
	    $pdo = self::$db->prepare($this->query);
	    $pdo->execute($this->data);
	
	    while ($record = $this->entity->parse($pdo->fetch(PDO::FETCH_OBJ))) {
	    	yield $record;
	    	unset($record);
	    }
    }
	
	public function lastId(string $model): int
	{
		return self::$lastInsertedIds[$model] ?? 0;
	}

    public function debug(): Db
    {
        $this->debug = true;

        return $this;
    }

    public function query(string $query): ?array
    {
        $pdo = self::$db->prepare($query);
        $pdo->execute();
        return $pdo->fetchAll(PDO::FETCH_OBJ);
    }

    private function develop($return = false): ?string
    {
        $statement = $this->query;

        foreach ($this->data as $key => $item) {
            $statement = str_replace(':'.$key, "'".$item."'", $statement);
        }

        if ($return) {
            return $statement;
        }

        pd([
            'query' => $statement,
            'raw' => $this->query,
            'params' => $this->data
        ]);

        return null;
    }

    public function getColumnsInfo(): ?array
    {
        return $this->query('DESCRIBE '.$this->table);
    }

    public function startBracket(): Db
    {
        $this->startBracketCount++;
        $this->startBracket = true;
        return $this;
    }

    public function endBracket(): Db
    {
        $this->query .= ')';
        return $this;
    }
	
	public function getEnumValues(string $field): ?array
	{
		$this->first = true;
		$res = $this->query(
			"SELECT SUBSTRING(COLUMN_TYPE,5) as params
					 FROM information_schema.COLUMNS
					   WHERE TABLE_NAME = '{$this->table}'
					   AND COLUMN_NAME = '{$field}'"
			);

		if (isset($res[0]->params)) {
			return explode(',', str_replace(['(', ')', "'"], ['', '', ''], $res[0]->params));
		}
		
		return null;
	}
	
	public function getConnectionName(): string
	{
		return $this->connection;
	}
	
	public function beginTransaction()
	{
		self::$db->beginTransaction();
	}
	
	public function commit()
	{
		self::$db->commit();
	}
}
