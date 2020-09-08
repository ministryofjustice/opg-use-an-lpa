# service-front (actor & viewer)

Please see the [README.md](../../README.md) at the top level for getting started instructions

## Translation Extraction

This is pretty much all handled by the service-front image now with a handy `composer.json` script.

```shell script
# in the service-front/app directory
composer run extract
```

If you have the appropriate tooling on your system (`php` with `gettext` and `redis` extensions) you
can also run it locally.

```shell script
CONTEXT=actor php console.php translation:update
```

_Note_: due to the differences in file order on the filesystem these two commands may produce a slightly
different POT file. It's probably best to stick to the first '_in container_' run version.
