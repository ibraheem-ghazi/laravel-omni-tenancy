Suffixed Paths
==============

## Introduction
Usually when working with multi-tenants application, you will need to separate specific files paths per tenant, most common way for doing this, is by adding a nested folder with a name that represent this tenant, this is what "suffixed paths" feature made for.
It allows you to define a config key that represent a path and it will automatically suffix it with a nested folder path based on what you have configured, lets take an example to make it better clear.

under `tenancy.suffixed_paths.suffix_to_add` we have by default the value `tenants/%id`, this means any path added if not customized it will use this value as fallback to suffix the path so assuming you got a config key like:

```php
    config('my-config.key.here') // = `storage/custom-folder`
```

with this feature the config `my-config.key.here` with be `storage/custom-folder/tenants/23` where `23` refer to the id of the tenant.

Of course, its not tied to just the id as you got few more options like 

```php
%id = $tenant->getId()
%hash = $tenant->getHash()
%name = $tenant->getName()
%slugged-name = Str::slug($tenant->getName())
```

the `config_keys` array should be used like this if you want to follow the fallback way

```php
'config_keys' => [
    //...
    'my-config.key.here',
    //...
],
```

## Customizing for specific config key
You can tell for specific config key to have it own way of naming the nested folder by using key=>value in the array

```php
'config_keys' => [
    //...
    'my-config.key.here' => '%hash', 
    //...
],
```

this will tell the system that for this specific key, it should have custom suffix as defined here `%hash` so it will be `storage/custom-folder/TENANT_GENERATED_HASH`

> NOTE: this feature mainly used for a few known internal paths such as:
    ```php
    'config_keys' => [
        'filesystems.disks.local.root' => '%hash',  //since this is public we dont want to expose the tenant id,
        'filesystems.disks.public.root' => '%hash', //since this is public we dont want to expose the tenant id,
        'cache.stores.file.path',
        'cache.stores.file.lock_path',
        'session.files',
    ],
    ```

> NOTE: this feature is compatible with Servable Files.



