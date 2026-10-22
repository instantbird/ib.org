# www.instantbird.org

Static version of the old PHP site, served by GitHub Pages from `docs/`.

- `php/`: the old site's PHP sources (updated to run on PHP 8).
- `build/build.py ib.org`: regenerates `docs/`. Only needs `php` and `python3`: it runs the PHP pages with PHP's built-in server (`build/router.php` emulates the old `.htaccess` rewrites) for each locale and OS.

Pages without a `/<locale>/` prefix are en-US for Windows. A small script sends JS-enabled visitors to their language, and adapts download links and screenshots for Mac and Linux.
