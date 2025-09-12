# Domains

## Overview

In Omni Tenancy, **domains** are a core concept for tenant identification and routing. Each tenant can be associated with one or more domains, including subdomains, which are used to resolve the tenant context for incoming requests. Domains are stored centrally and managed through dedicated models and migrations.

---

## Domain Model

Domains are represented by the [`IbraheemGhazi\OmniTenancy\Models\Domain`](src/Models/Domain.php) model. Each domain record includes:

- `id`: Primary key.
- `domain`: The full domain or subdomain string (e.g., `acme.com`, `client1.example.com`).
- `is_main`: Boolean flag indicating if this is the main domain for the tenant.
- `tenant_id`: Foreign key referencing the owning tenant.
- Timestamps for creation and updates.

The model includes relationships:
- `tenant()`: Belongs to a [`Tenant`](src/Models/Tenant.php).
- `is_subdomain`: Accessor to check if the domain is a subdomain.

## Database Structure

Domains are stored in the `domains` table, created by the migration [src/Database/2025_06_07_065553_domains.php](src/Database/2025_06_07_065553_domains.php):

- Each domain is unique.
- Each tenant can have multiple domains, but only one main domain (`is_main`).
- Foreign key constraints ensure referential integrity with the `tenants` table.

## Main Domain vs. Additional Domains

- **Main Domain**: Each tenant can have one main domain (`is_main = true`). This is typically used for primary routing and identification.
- **Additional Domains**: Tenants can have multiple domains (e.g., aliases, subdomains), but only one can be marked as main.

The [`mainDomain()`](src/Models/Tenant.php) and [`domains()`](src/Models/Tenant.php) relationships on the [`Tenant`](src/Models/Tenant.php) model allow easy access to these.

## Domain Management

Domains can be created and managed via the [`Tenant`](src/Models/Tenant.php) model:

```php
$tenant->createMainDomain('client1.example.com');
```

The `activate()` method on the `Domain` model sets the domain as the main domain for its tenant, ensuring only one main domain per tenant.

## Domain Identification
Tenant identification by domain is handled by the `TenantIdentifierByDomain` and `TenantIdentifierBySubdomain` classes. These use the incoming request's host to resolve the tenant, supporting both full domains and subdomains.

Mappings and exclusions can be configured in `config/tenancy.php` under the `identifications.methods` key.

**Example: Assigning Domains**

```php
// Assign a main domain to a tenant
$tenant->createMainDomain('acme.com');

// Add an additional domain
$tenant->domains()->create(['domain' => 'shop.acme.com', 'is_main' => false]);
```

## Querying Domains

- Get all domains for a tenant:
```php 
$tenant->domains;
```

- Get the main domain:
```php
$tenant->mainDomain;
```

- Query tenants by domain:
```php
Tenant::whereHas('domains', fn($q) => $q->where('domain', 'acme.com'))->first();
```

## Subdomain Support
Subdomains are supported and can be used for tenant identification. The accessor is_subdomain and the static method checkIsSubdomain() help determine if a domain string is a subdomain.

## Best Practices
- Always ensure each tenant has a unique main domain.
- Use the provided relationships and methods for managing domains.
- Configure domain mappings and exclusions as needed for your application's identification strategy.
For more details, see the [Tenant Identifiers](Tenant Identifiers.md) and [Concept](Concept.md) documentation.

