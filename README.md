# Composer Integrity Plugin

Check your installed composer packages against a list of known correct checksums (provided by Sansec).

This plugin calculates a [one-way hash](https://github.com/Cyan4973/xxHash) of:
- composer.json and composer.lock
- package name and package versions
- file contents of the installed packages (checksum)

These hashes are then tested against a larger database hosted at Sansec. The use of one-way hasing provides a secure way to test your setup, without sharing file contents with a third party. The Sansec API does not store your hashes. 

![image](https://user-images.githubusercontent.com/1145479/233590606-824ae163-19a1-4871-9387-5ce402634150.png)

## Installation & Usage

## Composer Plugin

```bash
composer require sansec/composer-integrity-plugin
```

You can then run it:
```bash
composer integrity
```

## PHAR

Head over to the [releases](https://github.com/sansecio/composer-integrity-plugin/releases) page and download the latest PHAR.

You can then run it:
```bash
php composer-integrity.phar
```

## Configuration

Both the plugin as well as the PHAR take the following optional options:

- `--skip-match`: shows only non-matching checksums
- `--json`: output is in json format instead of a table

# Why did we make this?

Sansec specializes in forensic investigations of breached Magento stores. We noticed an increase of cases where malware was hidden in legitimate libraries under `vendor`. Most package managers provide some sort of integrity check for installed software, but composer does not. So, we made this plugin in order to quickly verify the integrity of an installation. 

Alternatively, you could clone the composer files, recreate vendor and run a diff against your installation. But this takes much more time and original dependencies are not always available on production servers. 

# Caveats

The plugin does not consider patches, such as those applied through [composer-patches](https://github.com/cweagans/composer-patches), via a `post-install-cmd` composer script, or editing in `vendor` outright.

In such instances, it is the user's responsibility to assess the situation and take appropriate action.

## License

[MIT License](./LICENSE) - Copyright (c) 2023 Sansec
