# UtilityKit plugin for CakePHP

Reusable utilities for CakePHP 5.

## Requirements

- PHP >= 8.1
- CakePHP ^5.0

## Installation

```
composer require arodu/cakephp-utility-kit
```

## Components

### RedirectComponent

Saves and rewrites a redirect URL coming from the query string or request data,
so you can return the user to a "back" URL after an action.

```php
// In a controller
$this->loadComponent('UtilityKit.Redirect');
// /articles?redirect=/somewhere -> the redirect target is preserved/rewritten
```

### JsonComponent

Builds JSend-style JSON responses (`success` / `fail` / `error`). It handles
AJAX requests, redirects and optional view rendering.

```php
$this->loadComponent('UtilityKit.Json', ['ajaxRequired' => false]);

$data = ['id' => $entity->id];
return $this->JsonComponent->success($data, 'Saved!');
```

### FieldScopeBehavior

Automatically scopes queries by a field and sets that field on new records on
save.

```php
$this->addBehavior('UtilityKit.FieldScope', [
    'fieldName' => 'organization_id',
    'fieldValue' => 1,
]);
```

### LastElementBehavior

Provides a finder that returns the last element within a group (a subquery on a
field group), useful for "current" records (e.g. the current period for a
tenant).

### RegisterScopeDataTrait

Registers and retrieves data scoped by a named scope, with helpers to switch
between scopes.

### Utilities

Small static helpers:

- `Common` — composer metadata helpers (copyright year, package version).
- `DateFormatter` — copyright year range formatting.
- `ComposerManifest` — reads a package version from `composer.lock`.
- `GitInfo` — returns the current git branch, commit id and commit date.

## Testing

```
composer test
composer cs-check
```

---
[© 2025 arodu](https://github.com/arodu)
