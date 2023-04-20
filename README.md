# Composer Integrity Plugin

Check your installed composer packages against a list of known correct checksums (provided by Sansec).

This plugin calculates a [one-way hash](https://github.com/Cyan4973/xxHash) of:
- composer.json and composer.lock
- package name and package versions
- file contents of the installed packages (checksum)

These hashes are then tested against a larger database hosted at Sansec. The use of one-way hasing provides a secure way to test your setup, without sharing file contents with a third party. The Sansec API does not store your hashes. 

## Installation

```bash
composer require --dev sansec/composer-integrity-plugin
```

## Usage

```bash
composer integrity
```

You can pass the `--skip-match` flag to only show non-matching checksums.

# Why did we make this?

Sansec specializes in forensic investigations of breached Magento stores. We noticed an increase of cases where malware was hidden in legitimate libraries under `vendor`. Most package managers provide some sort of integrity check for installed software, but composer does not. So, we made this plugin in order to quickly verify the integrity of an installation. 

Alternatively, you could clone the composer files, recreate vendor and run a diff against your installation. But this takes much more time and original dependencies are not always available on production servers. 

# Caveats

The plugin does not consider patches, such as those applied through [composer-patches](https://github.com/cweagans/composer-patches), via a `post-install-cmd` composer script, or editing in `vendor` outright.

In such instances, it is the user's responsibility to assess the situation and take appropriate action.

## License

[MIT License](./LICENSE) - Copyright (c) 2023 Sansec
