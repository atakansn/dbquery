<?php

namespace DBQuery;

use PDO;
use PDOException;

class Builder
{

    /**
     * @var string
     */
    private string $table;

    /**
     * @var
     */
    private PDO $connection;

    /**
     * @var
     */
    private $query;

    /**
     * @var
     */
    private $bind;

    /**
     * @var bool
     */
    private bool $useWhere = false;

    /**
     * @var
     */
    private $wheres;

    /**
     * @var
     */
    private $select;

    /**
     * @var bool
     */
    private $isSelectted = false;

    /**
     * @var
     */
    private $orderBy;

    /**
     * @var bool
     */
    private bool $distinct = false;

    /**
     * @var
     */
    private $limit;

    /**
     * @var
     */
    private $groupBy;

    /**
     * @var
     */
    private $join;

    /**
     * @var
     */
    private $having;

    /**
     * @var array
     */
    private array $bindings;

    /**
     * @var bool
     */
    private bool $whereNull = false;


    /**
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->connectPdo($params);
    }

    /**
     * @param $name
     * @param $alias
     * @return $this
     */
    public function table($name)
    {
        $this->table = $name;
        return $this;
    }

    /**
     * @param $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $fields = implode(',', $columns);

        $this->select = 'SELECT ' . $fields;

        return $this;
    }

    /**
     * @param array $data
     * @return false|int|void
     */
    public function insert(array $data)
    {

        $insertSQL = $this->sqlInsertStatement($data);

        return $this->stetament($insertSQL, $this->bindings);
    }

    /**
     * @param $data
     * @return string
     */
    private function sqlInsertStatement($data)
    {
        $mergeData = array_merge(
            $data,
            [
                'created_at' => date('Y-m-d'),
                'updated_at' => date('Y-m-d')
            ]
        );

        $columns = [];

        foreach ($mergeData as $columnName => $values) {
            $columns[] = $columnName;
            $this->bindings[":$columnName"] = $values;
        }

        return 'INSERT INTO ' . $this->getTable() . ' (' . implode(', ', $columns) . ')' . ' VALUES(' . implode(', ', array_keys($this->bindings)) . ')';

    }

    /**
     * @param array $data
     * @return false|int|void
     */
    public function update(array $data = [])
    {
        $query = $this->sqlUpdateStatement($data);

        return $this->stetament($query, $this->bind);
    }

    /**
     * @param $data
     * @return string
     */
    private function sqlUpdateStatement($data)
    {
        $data['updated_at'] = date('Y-m-d');

        $columns = [];

        foreach ($data as $columnName => $values) {
            $columns[] = "$columnName=:$columnName";
            $this->bind[$columnName] = $values;
        }

        return 'UPDATE ' . $this->getTable() . ' SET ' . implode(', ', $columns) . $this->wheres;

    }

    /**
     * @param int|null $id
     * @return false|int|void
     */
    public function delete(int $id = null)
    {
        if (!is_null($id)) {
            $this->where('id', $id);
        }

        $sql = $this->sqlDeleteStatement();

        return $this->stetament($sql, $this->bind);

    }

    /**
     * @return string
     */
    private function sqlDeleteStatement()
    {
        $query = 'DELETE FROM ' . $this->getTable();

        if ($this->wheres !== null) {
            $query .= $this->wheres;
        }

        return $query;
    }

    /**
     * @return bool
     */
    public function isSelectted(): bool
    {
        return $this->isSelectted;
    }

    /**
     * @return mixed
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param $column
     * @return $this
     */
    public function orderByAsc($column = 'id')
    {
        $this->setOrderBy(" ORDER BY $column ASC ");
        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    public function orderByDesc($column = 'id')
    {
        $this->setOrderBy(" ORDER BY $column ASC ");
        return $this;
    }

    /**
     * @param $column
     * @param $key
     * @return $this
     */
    public function orderBy($column = null, $key = 'ASC')
    {
        $this->orderBy = ' ORDER BY ' . $column . ' ' . $key;
        return $this;
    }

    public function randomOrder()
    {
        return $this->orderBy(null, 'RAND()')->first();
    }

    /**
     * @param $table
     * @param $first
     * @param $operator
     * @param $second
     * @param $type
     * @return $this
     */
    private function joinQuery($table, $first, $operator, $second, $type)
    {
        $this->join .= " $type $table ON $first$operator$second";
        return $this;
    }

    /**
     * @param $table
     * @param $first
     * @param $operator
     * @param $second
     * @return $this
     */
    public function join($table, $first, $operator, $second)
    {
        return $this->joinQuery($table, $first, $operator, $second, 'INNER JOIN');
    }

    /**
     * @param $table
     * @param $first
     * @param $operator
     * @param $second
     * @return $this
     */
    public function leftJoin($table, $first, $operator, $second)
    {
        return $this->joinQuery($table, $first, $operator, $second, 'LEFT JOIN');
    }

    /**
     * @param $table
     * @param $first
     * @param $operator
     * @param $second
     * @return $this
     */
    public function rightJoin($table, $first, $operator, $second)
    {
        return $this->joinQuery($table, $first, $operator, $second, 'RIGHT JOIN');
    }

    /**
     * @return $this
     */
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * @param $limit
     * @param $end
     * @return $this
     */
    public function limit($limit, $end = null)
    {
        if ($end === null) {
            $this->limit = " LIMIT $limit";
        } else {
            $this->limit = " LIMIT $limit,$end";
        }

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function offset($value)
    {
        $this->limit .= " OFFSET $value";
        return $this;
    }

    /**
     * @param $column
     * @param $amount
     * @param $where
     * @return false|int|void
     */
    public function increment($column, $amount, $where = [])
    {
        $columns = array_merge([$column => "$column=$column+$amount"]);

        $c = [];
        $value = [];
        foreach ($where as $k => $v) {
            $c[] = $k;
            $value[] = $v;
        }

        return $this->stetament('UPDATE ' . $this->getTable() . ' SET ' . implode($columns) . ' WHERE ' . implode($c) . '=' . implode($value));
    }

    /**
     * @param $column
     * @param $amount
     * @param $where
     * @return false|int|void
     */
    public function decrement($column, $amount, $where = [])
    {
        $columns = array_merge([$column => "$column=$column-$amount"]);

        $c = [];
        $value = [];
        foreach ($where as $k => $v) {
            $c[] = $k;
            $value[] = $v;
        }

        return $this->stetament('UPDATE ' . $this->getTable() . ' SET ' . implode($columns) . ' WHERE ' . implode($c) . '=' . implode($value));
    }

    /**
     * @param mixed $orderBy
     */
    public function setOrderBy($orderBy): void
    {
        $this->orderBy = $orderBy;
    }

    /**
     * @param bool $isSelectted
     */
    public function setIsSelectted(bool $selected): void
    {
        $this->isSelectted = $selected;
    }

    /**
     * @param $column
     * @return mixed
     */
    public function count($column = '*')
    {
        $stmt = $this->getConnection()->query("SELECT COUNT({$column}) AS count FROM {$this->getTable()} ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * @return mixed
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * @param $column
     * @return false|mixed|null
     */
    public function value($column)
    {
        $result = (array)$this->first($column);

        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * @param int $id
     * @param $columns
     * @return mixed
     */
    public function find(int $id, $columns = '*')
    {
        return $this->where('id', $id)->first($columns);
    }

    /**
     * @param $column
     * @return $this
     */
    private function maxQuery($column)
    {
        if (!$this->isSelectted()) {
            $this->select = "SELECT MAX($column) AS max";
        } else {
            $this->select .= ",MAX($column) AS max";
        }

        $this->setIsSelectted(true);
        return $this;
    }

    /**
     * @param $column
     * @return mixed
     */
    public function max($column = 'id')
    {
        $this->maxQuery($column);
        $this->setQuery($this->createSelectQuery($column));
        return $this->getConnection()->query($this->getQuery())->fetch(PDO::FETCH_ASSOC);
    }

    public function sum($column)
    {
        $this->getSumQuery($column);
        $this->setQuery($this->createSelectQuery($column));

        return $this->getConnection()->query($this->getQuery())->fetch(PDO::FETCH_ASSOC);
    }

    public function getSumQuery($data)
    {
        if(!$this->isSelectted){
            $this->select = "SELECT SUM($data) AS sum";
        } else {
            $this->select .= ", SUM($data) AS sum";
        }

        $this->isSelectted = true;

        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    private function minQuery($column)
    {
        if (!$this->isSelectted()) {
            $this->select = "SELECT MIN($column) AS min";
        } else {
            $this->select .= ",MIN($column) AS min";
        }

        $this->isSelectted = true;
        return $this;
    }

    /**
     * @param $column
     * @return mixed
     */
    public function min($column = 'id')
    {
        $this->minQuery($column);
        $this->setQuery($this->createSelectQuery($column));
        return $this->getConnection()->query($this->getQuery())->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return mixed|string
     */
    public function findOrFail($id)
    {
        return $this->find($id) ?: require 'errors/404.php';
    }


    /**
     * @param array|string $columns
     * @return string
     */
    private function createSelectQuery(array|string $columns)
    {
        if (empty($this->getSelectQuery())) {
            $columns = implode(',', $columns) ?: '*';
            $this->setSelectQuery("SELECT $columns");
        }

        if ($this->distinct) {
            $this->setSelectQuery("SELECT DISTINCT $columns");
        }

        return "{$this->getSelectQuery()} FROM {$this->getTable()} {$this->join} {$this->wheres} {$this->groupBy} {$this->having} {$this->orderBy} {$this->limit}";
    }

    /**
     * @param $column
     * @return $this
     */
    public function whereNull($column)
    {
        $this->whereNull = true;
        $this->setQuery("SELECT {$column} FROM {$this->getTable()} WHERE {$column} IS NULL ");
        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    public function whereNotNull($column)
    {
        $this->whereNull = true;
        $this->setQuery("SELECT {$column} FROM {$this->getTable()} WHERE {$column} IS NOT NULL ");
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSelectQuery()
    {
        return $this->select;
    }

    /**
     * @param mixed $selectQuery
     */
    public function setSelectQuery($selectQuery): void
    {
        $this->select = $selectQuery;
    }

    /**
     * @return false|int
     */
    public function truncate()
    {
        return $this->getConnection()->exec("TRUNCATE TABLE {$this->getTable()}");
    }

    /**
     * @param $column
     * @param $value
     * @param $operator
     * @return $this
     */
    public function where($column, $value = null, $operator = '=')
    {
        if ($this->useWhere) {
            $this->wheres .= " AND $column$operator:$column";
        } else {
            $this->wheres = " WHERE $column$operator:$column";
        }

        $this->bind[":$column"] = $value;
        $this->useWhere = true;

        return $this;
    }

    /**
     * @param ...$columns
     * @return mixed
     */
    public function first(...$columns)
    {
        $columns = $columns ?: ['*'];

        $this->setQuery($this->createSelectQuery($columns));
        $stmt = $this->getConnection()->prepare($this->getQuery());
        $stmt->execute($this->bind);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * @return bool
     */
    public function exists()
    {
        $this->setQuery("SELECT * FROM {$this->getTable()} {$this->getWheres()}");
        $stmt = $this->getConnection()->prepare($this->getQuery());
        $stmt->execute($this->getBind());

        if ($stmt->rowCount() > 0) {
            return true;
        }

        return false;
    }


    /**
     * @param $column
     * @return mixed
     */
    public function avg($column)
    {
        $this->avgQuery($column);

        $this->setQuery($this->createSelectQuery($column));
        return $this->getConnection()->query($this->getQuery())->fetch(PDO::PARAM_INT);
    }

    /**
     * @param $column
     * @return $this
     */
    private function avgQuery($column)
    {
        if (!$this->isSelectted()) {
            $this->select = "SELECT AVG($column) AS avg";
        } else {
            $this->select .= ",AVG($column) AS avg";
        }

        $this->setIsSelectted(true);
        return $this;
    }

    /**
     * @param $values
     * @return false|string
     */
    public function insertGetId(array $values)
    {
        $sql = $this->sqlInsertStatement($values);
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($this->bindings);
        return $this->getConnection()->lastInsertId('last_insert_id');
    }

    /**
     * @return mixed
     */
    public function getWheres()
    {
        return $this->wheres;
    }

    /**
     * @return mixed
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * @param $column
     * @param $values
     * @return $this
     */
    public function whereIn($column, $values)
    {
        $arrFill = array_fill(0, count($values), '?');
        $imp = implode(',', $arrFill);
        if ($this->useWhere) {
            $this->wheres .= " AND $column IN ($imp) ";
        } else {
            $this->wheres = "WHERE $column IN ($imp) ";
        }

        $this->bind = $values;
        $this->useWhere = true;

        return $this;
    }

    /**
     * @param $first
     * @param $second
     * @return bool|int|void
     */
    public function updateOrInsert($first, $second = [])
    {
        foreach ($first as $k => $v) {
            if (!$this->where($k, $v)->exists()) {
                return $this->insert(array_merge($first, $second));
            }

            if (is_null($second)) {
                return true;
            }
        }

        return $this->update($second);

    }

    /**
     * @param $dsn
     * @param $userName
     * @param $passowrd
     * @return PDO|string
     */
    private function connectPdo(array $params)
    {
        try {

            [$userName, $password] = [
                $params['user'] ?? null,
                $params['password'] ?? null
            ];

            extract($params, EXTR_SKIP);

            $dsn = isset($port) ?
                "mysql:dbname={$dbname};host={$host};port={$port};" :
                "mysql:dbname={$dbname};host={$host}";

            $this->connection = new PDO($dsn, $userName, $password);

        } catch (PDOException $e) {
            return $e->getMessage();
        }

        return $this->connection;
    }

    /**
     * @return string
     */
    private function getTable()
    {
        return $this->table;
    }

    /**
     * @param $columns
     * @return array|false|int|mixed|void
     */
    public function get($columns = ['*'])
    {
        if ($this->whereNull) {
            return $this->stetament(
                $this->getQuery()
            );
        }

        $select = $this->select ?: "SELECT *";

        if ($this->useWhere) {

            $stmt = $this->getConnection()->prepare("{$select} FROM {$this->getTable()} {$this->wheres} {$this->groupBy} {$this->having} {$this->orderBy}");
            $stmt->execute($this->bind);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        }


        $this->setQuery($this->createSelectQuery(is_array($columns) ? $columns : func_get_args()));
        return $this->getConnection()->query($this->getQuery())->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param ...$column
     * @return $this
     */
    public function groupBy(...$column)
    {
        if (!empty($column)) {
            $columns = implode(', ', $column);
            $this->groupBy = " GROUP BY $columns";
        }

        return $this;
    }


    /**
     * @return mixed
     */
    private function getBindings()
    {
        return $this->bindings;
    }


    /**
     * @param $query
     * @return $this
     */
    private function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return array
     */
    private function getBind(): array
    {
        return $this->bind;
    }

    /**
     * @return mixed
     */
    private function getQuery()
    {
        return $this->query;
    }

    /**
     * @return string
     */
    private function getDefaultPrimaryKey()
    {
        return 'id';
    }

    /**
     * @param $sql
     * @param $params
     * @return false|int|void
     */
    public function stetament($sql, $params = [])
    {
        try {
            if (count($params) > 0) {

                $stmt = $this->getConnection()->prepare($sql);
                $stmt->execute($params);

                return $stmt->rowCount();
            }

            return $this->getConnection()->exec($sql);
        } catch (PDOException $e) {
            echo $e->getMessage();
            echo $e->getLine();
        }
    }


}