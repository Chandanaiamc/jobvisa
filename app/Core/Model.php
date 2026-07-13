<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Attribute-bag foundation for models.
 *
 * Application models should extend App\Core\BaseModel, which adds PDO access.
 */
abstract class Model
{
    /**
     * Database table name associated with the model.
     */
    protected string $table = '';

    /**
     * Primary key column name.
     */
    protected string $primaryKey = 'id';

    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}
