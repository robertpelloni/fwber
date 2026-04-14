# Controllers Context

**Parent Context:** [Backend Root](../../../AGENTS.md)

## PURPOSE
Controllers handle incoming HTTP requests, validate input, delegate business logic to Services, and return JSON responses.

## CRITICAL RULES
1.  **Thin Controllers:** Controllers should be "thin". Complex logic belongs in Service classes (`app/Services`), not here.
2.  **Resourceful Routing:** Prefer standard RESTful methods (`index`, `store`, `show`, `update`, `destroy`).
3.  **Form Requests:** ALWAYS use Form Request classes for validation. NEVER validate directly in the controller method.
4.  **API Resources:** Return data using API Resources (`app/Http/Resources`) to transform models into JSON. NEVER return Eloquent models directly.
5.  **Dependency Injection:** Inject dependencies (Services, Repositories) into the constructor or method.

## COMMON PATTERNS
```php
public function index(Request $request): AnonymousResourceCollection
{
    $items = $this->service->list($request->validated());
    return ItemResource::collection($items);
}
```

## ANTI-PATTERNS
- querying the database directly in the controller (except for simple `find` or scoping).
- returning `response()->json(...)` manually for successful resource responses (use Resources).
- putting validation rules in the controller method.
