# webhole

A gopherhole to website proxy with the focus on exposing your own gopherhole to
the web, not being a general proxy for Gopher.

## Setup

Setting up this proxy is as simple as opening the `index.php` and editing the
variables at the top. If you want you can also customize the `index.php` file
to create the layout you want for the page.

You will also be required to generate the `mimes.php` file, which is used to
determine the [MIME type](https://en.wikipedia.org/wiki/Media_type) of some
files returned from the proxied Gopher server. This can be done by running the
following command from the command line:

```shell
php mimetype.php > mimes.php
```

Apart from these two requirements, all you have to do now is point your favorite
HTTP server to this folder, and it should just work.

### Configuration

If you need to tweak any of the user-configurable options, this can be done in
the `index.php` file using the `$config->set('key', 'value')` function. The
included file already has some example of how to do so for some options.

If you want to know all the available options that can be tweaked, consult the
`GopherConfig` class constructor in the `gopher.php` file. This constructor sets
all the defaults for the configuration handler, and you can tweak any of these
values from the `index.php` file.

### URL Rewriting

If you have [mod_rewrite](https://httpd.apache.org/docs/2.4/rewrite/) installed
you can use the supplied `.htaccess` and enable the pretty URLs by changing the
`rewrite` configuration in the `ìndex.php` with the following line:

```php
$config->set('rewrite', true);
```

This enables that all URLs provided by the application will be in a format that
is exactly the same as Gopher URLs, allowing you to simply change the URL scheme
and switch between your gopherhole and the HTTP proxy of it.

## License

Everything related to the source code (how the website is actually built) is
free software; you may redistribute and/or modify it under the terms of the
[Mozilla Public License 2.0](https://www.mozilla.org/en-US/MPL/2.0/).

The actual content of the website, unless otherwise noted, is licensed under the
[CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/) license.
