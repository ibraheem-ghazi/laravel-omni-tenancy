Concept
========
The main rule here is that everything is a Tenant, no matter wether it run as sub-domain or main domain, to support this concept, our tenants system does not require to have a separated database always, a tenant can be created without database, and without even domain, then map its `id` via `tenancy.php` config file via identifiers mapping key, like we did by default with Central and Manager tenants.

> NOTE: for better separation of concerns I highly suggest you create both Central and Manager tenants with separated database and not dropping the load at the main database connection.