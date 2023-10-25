# PHP Dependency Scoper For Matomo/Matomo Plugins

## Description

matomo-scoper is a small CLI tool that wraps [php-scoper](https://github.com/humbug/php-scoper) and is used to add namespace to
prefixes to all classes within one or more dependencies of the core Matomo platform code or a Matomo plugin's code.

This allows using the entire platform or individual plugins alongside other PHP software with the same composer
dependencies at different versions.

## License

matomo-scoper is released under the GPL v3 (or later) license, see [LICENSE](LICENSE)

## Using matomo-scoper

### How to scope a Matomo plugin

Pre-requisites:
* make sure you've run composer install inside your plugin and the dependencies you are prefixing are present in the vendor/ folder.
* make sure your plugin uses a plugin.json file.
* make sure you are using PHP 8.1 or greater.

Also note that for Matomo plugins, it is expected that you will add and commit the vendor folder.

1. Clone the matomo-scoper repo somewhere.
2. Run matomo-scoper with the path to your plugin:

  ```bash
  cd /path/to/matomo-scoper
  ./bin/matomo-scoper scope /path/to/matomo/plugins/MyPlugin
  ```

(Note: if your composer phar is not named `composer` and on your `$PATH`, you can specify it via the `--composer-path` option.)

3. After matomo-scoper finished, manually change your plugin code to use the prefixed dependencies. You can do this automatically
  with the tool via the `--rename-references` option, BUT that will also destroy any formatting you applied to your PHP
  code. So it's not recommended.

4. Test the plugin thoroughly to make sure everything was prefixed correctly (a full suite of automated tests would speed this process up).

5. If you find a scoping issue that the matomo-scoper tool did not scope properly (which is expected as there are many things the tool can't
  detect), add a patcher to the `scoper.inc.php` file (this file is generated by the tool).
  [See the php-scoper docs for more information on patchers.](https://github.com/humbug/php-scoper/blob/main/docs/configuration.md#patchers)

6. Repeat steps 4-5 until your plugin works properly.

7. Commit the scoper.inc.php file and all changes to vendor and push to your repository.

Every time you need to update a prefixed dependency or prefix a new one, you will have to go through the same process.

### How to scope Matomo core

Pre-requisites:
* make sure you are using PHP 8.1 or greater.

This is probably not something you will need to do, unless you embed Matomo alongside other PHP software as, for example,
the Matomo for Wordpress wordpress plugin does. If you do, though, this is how you'd do it:

1. Clone the matomo-scoper repo somewhere.
2. Clone the matomo repo and init/update submodules.
3. Run `composer install` inside the matomo repo.
4. Run matomo-scoper with the path to your matomo clone:

  ```bash
  cd /path/to/matomo-scoper
  ./bin/matomo-scoper scope /path/to/matomo --rename-references
  ```
