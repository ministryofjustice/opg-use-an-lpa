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

## Translation Implementation

At the moment the file that we get back from translation (a `.po` file) has some incorrect syntax in
that breaks our html output. Essentially the poedit software tries to be helpful and replaces all instances
of " with the more linguistically correct “ and ”. You'll need to reset those before moving onto the next
step

You may use a substitution like:
```
s/“|”/\\"/
```

Or do a find replace in the editor of your choice.

Once the new `messages.po` file has been created you need to create the matching compile `.mo` file.
This is done with the gettext tooling (which you may need to install).

```shell script
msgfmt messages.po
```

Both these files now need to go in the `LC_MESSAGES` folder of the language these files are for.
