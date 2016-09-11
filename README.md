# WatchKit

![I'm watching you](https://cdn.meme.am/instances/500x/24694170.jpg)

This package is part of Fruit Framework.

WatchKit is a tool helping you develope your web application with Fruit framework or any other which needs to generate php code dynamically.

It is still under developement, anything could be changed later.

[![Build Status](https://travis-ci.org/Ronmi/fruit-watchkit.svg?branch=master)](https://travis-ci.org/Ronmi/fruit-watchkit)

## Usage

Create a `watcher.json` in project directory.

```js
{
  // pattern => shell script
  "src/**/*.php": "make test",
  "test": "make test"
}
```

and run `bin/watcher` (or `vendor/bin/watcher` when installed via composer).

## More detail

WatchKit parses your pattern, use inotify to watch the changes made to it, and execute shell script when changed.

If the pattern resolves to some directory, WatchKit will also watch for child directories recursively. All changes does to decent files will trigger an execution. So newly created files are under monitoring.

## Known bug

When pattern resolves to files, newly created files will not be watched.

## License

Any version of MIT, GPL or LGPL.
