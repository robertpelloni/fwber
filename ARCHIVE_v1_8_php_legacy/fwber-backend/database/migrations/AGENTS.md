# Database Migrations Context

**Parent Context:** [Backend Root](../../AGENTS.md)

## PURPOSE
This directory manages the database schema evolution for the Laravel application.

## CRITICAL RULES
1.  **Immutable History:** NEVER modify an existing migration file that has already been run in production. Create a NEW migration to fix or change schema.
2.  **Down Methods:** ALWAYS implement the `down()` method to reverse changes.
3.  **Naming:** Use descriptive names like `create_users_table`, `add_is_admin_to_users_table`.
4.  **Spatial Data:** We use spatial types. Ensure extensions (PostGIS/MySQL Spatial) are handled if environment differs, but stick to MySQL 8+ syntax as primary target.
5.  **Foreign Keys:** Always define foreign key constraints for referential integrity. Use `constrained()->onDelete('cascade')` where appropriate.

## COMMON PATTERNS
```php
Schema::create('table_name', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

## ANTI-PATTERNS
- Modifying old migrations to "fix" a bug.
- Forgetting to index foreign keys (Laravel does this automatically with `foreignId`, but verify).
- Using raw SQL without good reason.
