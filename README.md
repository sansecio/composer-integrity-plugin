# Composer Integrity Plugin

A composer plugin that checks your installed composer packages against a list of known correct checksums.

The hashing algorithm being used is [xxhash64](https://github.com/Cyan4973/xxHash).

We collect the following data:
- A unique installation hash based on composer.json and composer.lock
- Hashes based on package name and package version.
- Hashes based on the contents of the installed packages (checksum).

These hashes are then compared against a larger database.  No sensitive data or contents of files is collected.

