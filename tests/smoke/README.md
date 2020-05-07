Behat Smoke Test Suite
======

```shell script
# ensure your use-an-lpa environment is running already
# use PHPStorm or a method devised in the top level README

# run tests when wanted
$ composer behat

# create feature step definitions (output to CLI)
$ composer behat -- --snippets-for

# create feature step definitions (append to specified context class)
# please note that the class namespaces must be escaped with double '\\'
$ composer behat -- --snippets-for Test\\Context\\AccountContext --append-snippets
```