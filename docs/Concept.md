# Concept

The core principle of this package is that **everything is a Tenant**—regardless of whether it operates on a subdomain or a primary domain. To support this, the tenancy system is designed with maximum flexibility: tenants are not required to have a dedicated database or even a domain. You can create tenants without these attributes and map their `id` using the identifiers `mapping` key in the `tenancy.php` configuration file, as demonstrated by the default setup for Central and Manager tenants.

> **Recommendation:** For optimal separation of concerns and improved scalability, it is highly recommended to provision separate databases for both Central and Manager tenants, rather than relying on the main database connection.