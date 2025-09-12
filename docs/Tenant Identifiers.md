Tenant Identifiers
===================

Identifiers, is a special class that the package utilize to identify which tenant should be accessed based on a conditions and inputs.
The current and default defined Identifiers classes, utilize Domain, Sub Domain, Header, to identify the tenant, and you can extend the package to run the identifier that you need, and you can disable the identifier that you don't need.

## Identifiers Types

### By Domain
This allows the developer to assign a full domain like "acme.com" and auto identify it.

### By Sub Domain
This is dependent on the base app url, that read at the very begining of booting the Laravel application (usually set via APP_URL env variable), and this will identify the tenant using "acme.base-url.com"

> NOTE: the package automatically copy the config of `app.url` to `app.base_url` before initializing tenants, and this done once at registration phase of the provider, the `app.base_url` saves the original url of the application to be used later for sub domain processes, while the `app.url` is dynamically changed based on current active tenant context.

### By Header
This allows the developer to identify the tenant using specific header, by default the header named to `X-Tenant-Identifier` and its value is the tenant ID, You can configure the header name by the identifier config key `name`


## Configuration

As you already notice the configuration usually consist of two main keys, `mapping` and this allows you to map specific value (whether domain, subdomain, header value) to specific tenant directly without having to rely on database relation, and `excluded` do the opposite, it forbid the value from being used by the identifier, so if we excluded `admin` as subdomain, then it will never find admin as tenant, also by excluding a value we prevent the creator from creating it, so by excluding `admin` we prevent the creator from creating a tenant with sub-domain `admin`.
