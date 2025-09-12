Servable Files
==============

You probably wonder, I have some tenant specific files, that i dont want other tenants to be able to access, but paths are accessible even in root domain, well, "Servable Files" is here to solve this issue, by using a config key (usually but not necessary relates to filesystems disks, for example: filesystems.disks.public.root), it allows you to define a route that responsible for serving files from same url path, but from tenant specific route or the select path.

The configuration consist of the route prefix configuration like what is the general prefix for it, and then the paths where the arrsy key is the nested url path key and the value of the array which folder it serve files from.

Of course it allow the developer to turn off this feature if he does not need it.

```php
 'servable_paths' => [
        'enabled' => true,
        
        'route_prefix' => 'files/',

        'paths' => [
            'local'     =>  'filesystems.disks.local.root',
            'public'    =>  'filesystems.disks.public.root',
        ]
    ],
```

so per the current configuration, as you see the route is prefixed by general prefix 'files', and we got keys of 'local' and 'public', those, it mean the url path will be like `files/(local|public)/file.png` or `files/(local|public)/deep/nested/folder/file.png`


> NOTE: this is custom auto registered and handled route, so it dependent on the application and not directly serve files through php like any normal path, so use this with cautions.

> NOTE: this feature is compatible with Suffixed Paths.
