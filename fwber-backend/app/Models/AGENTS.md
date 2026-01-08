# Eloquent Models Context

**Parent Context:** [Backend Root](../../AGENTS.md)

## PURPOSE
Models represent database tables and encapsulate data-related logic, relationships, and accessors.

## CRITICAL RULES
1.  **Mass Assignment:** Always define `$fillable` (whitelist) to protect against mass assignment vulnerabilities.
2.  **Relationships:** Define relationships explicitly (`hasMany`, `belongsTo`, etc.) with correct return types.
3.  **Scopes:** Use Local Scopes (`scopeActive`, `scopePopular`) for reusable query logic.
4.  **Accessors/Mutators:** Use the modern `Attribute` syntax (Laravel 9+ style) for accessors and mutators.
5.  **Spatial Traits:** If the model uses spatial data, ensure the correct Trait (e.g., from `MatanYadaev\EloquentSpatial`) is used and casts are defined.

## COMMON PATTERNS
```php
protected function name(): Attribute
{
    return Attribute::make(
        get: fn (string $value) => ucfirst($value),
    );
}
```

## ANTI-PATTERNS
- Business logic in models (keep them focused on data structure).
- Modifying the global state or performing side effects in accessors.
- Forgetting to eager load relationships (`with()`) leading to N+1 query problems.
