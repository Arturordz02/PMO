<?php

namespace App\Core;

/**
 * Fluent query builder.
 */
class QueryBuilder
{
    private \PDO $pdo;
    private string $table;
    
    private array $columns = ['*'];
    private array $wheres = [];
    private array $bindings = [];
    private ?string $orderBy = null;
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(\PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $this->sanitizeColumn($table);
    }

    private function sanitizeColumn(string $column): string
    {
        if ($column !== '*' && !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            throw new \InvalidArgumentException("Invalid column name: $column");
        }
        return $column;
    }

    public function select(string ...$columns): self
    {
        $this->columns = array_map([$this, 'sanitizeColumn'], $columns);
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->addWhere($column, $operator, $value, 'AND');
        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $this->addWhere($column, $operator, $value, 'OR');
        return $this;
    }

    private function addWhere(string $column, string $operator, mixed $value, string $boolean): void
    {
        $column = $this->sanitizeColumn($column);
        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];
        $this->bindings[] = $value;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $column = $this->sanitizeColumn($column);
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy = "$column $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $total = $this->count();
        $lastPage = (int) max(1, ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $this->limit($perPage)->offset($offset);
        $data = $this->get();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total > 0 ? $offset + 1 : null,
                'to' => $total > 0 ? $offset + count($data) : null,
            ]
        ];
    }

    private function buildSelect(): string
    {
        $columns = implode(', ', $this->columns);
        $sql = "SELECT $columns FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . $this->buildWheres();
        }

        if ($this->orderBy !== null) {
            $sql .= " ORDER BY {$this->orderBy}";
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    private function buildWheres(): string
    {
        $whereSql = '';
        foreach ($this->wheres as $index => $where) {
            if ($index > 0) {
                $whereSql .= " {$where['boolean']} ";
            }
            $whereSql .= "{$where['column']} {$where['operator']} ?";
        }
        return $whereSql;
    }

    private function executeQuery(string $sql, array $bindings = []): \PDOStatement
    {
        $start = microtime(true);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        
        $elapsedMs = (microtime(true) - $start) * 1000;
        if ($elapsedMs > 500) {
            $this->logSlowQuery($sql, $bindings, $elapsedMs);
        }

        return $stmt;
    }

    public function get(): array
    {
        $sql = $this->buildSelect();
        $stmt = $this->executeQuery($sql, $this->bindings);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $result = $this->get();
        return $result ? $result[0] : null;
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . $this->buildWheres();
        }
        $stmt = $this->executeQuery($sql, $this->bindings);
        return (int) $stmt->fetchColumn();
    }

    public function insert(array $data): int|false
    {
        $columns = implode(', ', array_map([$this, 'sanitizeColumn'], array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $this->executeQuery($sql, array_values($data));
        
        $id = $this->pdo->lastInsertId();
        return $id === false ? false : (int) $id;
    }

    public function update(array $data): int
    {
        $sets = [];
        $bindings = [];
        foreach ($data as $column => $value) {
            $column = $this->sanitizeColumn($column);
            $sets[] = "$column = ?";
            $bindings[] = $value;
        }

        $setSql = implode(', ', $sets);
        $sql = "UPDATE {$this->table} SET $setSql";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . $this->buildWheres();
            $bindings = array_merge($bindings, $this->bindings);
        }

        $stmt = $this->executeQuery($sql, $bindings);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . $this->buildWheres();
        }

        $stmt = $this->executeQuery($sql, $this->bindings);
        return $stmt->rowCount();
    }

    private function logSlowQuery(string $sql, array $params, float $elapsedMs): void
    {
        $date = date('Y-m-d');
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        $logFile = $logDir . '/slow-queries-' . $date . '.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = sprintf(
            "[%s] Slow Query (%.2fms): %s | Bindings: %s\n",
            date('Y-m-d H:i:s'),
            $elapsedMs,
            $sql,
            json_encode($params)
        );

        error_log($logEntry, 3, $logFile);
    }
}
