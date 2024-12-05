# service-front (actor & viewer)

Please see the [README.md](../../README.md) at the top level for getting started instructions

## Translation Extraction

This is pretty much all handled by the service-front image now with a handy `composer.json` script.

```shell script
# in the service-front/app directory
composer run extract
```

For the above to work, there needs to be in the top level of the opg-use-an-lpa project directory, a file named docker-compose.override.yml
If this file doesn't exist, you need to make one, with a dummy single line in it such as :   version: "3.0"

If you have the appropriate tooling on your system (`php` with `gettext` and `redis` extensions) you
can also run it locally.

```shell script
CONTEXT=actor php console.php translation:update
```

_Note_: due to the differences in file order on the filesystem these two commands may produce a slightly
different POT file. It's probably best to stick to the first '_in container_' run version.

## Translation Implementation

To edit the Welsh in poedit, open messages.po , and do `Translation->Update from POT` and select messages.pot. 
This updates the .po file with any new or modified strings in the pot file.
Now edit any new or edited translations, then save the file.

The poedit software tries to be helpful and replaces all instances
of " with the more linguistically correct “ and ”. This is unwanted within html tags. 

This can be fixed by holding ctrl as you type a quote. (or ctrl shift for double quote)

If you fail to do the above when typing quotes used within html tags, the po file 
will have incorrect quotes, which would break our html output. 

You'll need to reset those by hand before moving onto the next step

You may use a substitution like:
```
s/“|”/\\"/
```

but do not use the above globally, as this could wrongly replace legitimate quotes that aren't within html tags

Alternatively,  do a find replace in the editor of your choice.

Once the new `messages.po` file has been created you need to re-create the matching compile `.mo` file.
This is done with the gettext tooling (which you may need to install).

```shell script
msgfmt messages.po
```

Both these files now need to go in the `LC_MESSAGES` folder of the language these files are for.
