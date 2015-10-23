# WatchKit

This package is part of Fruit Framework.

WatchKit is a tool helping you develope your web application with Fruit framework or any other which needs to generate php code dynamically.

It is still under developement, anything could be changed later.

## Usage

List the file and command, 1 file per line, use `|` to separate file from command, and save them into a file:

```
my_route.php|make route
../benchmarks/|cd ..;vendor/bin/bench run benchmarks > benchmark.log 2>&1
/home/cooperator/src/some.php|cd /our/project/tools;php send_notify.php me@example.com
```

And pass it to command line tool `watcher`

```sh
watcher run -l my_watch_list_file
```

Never forget taking care of file path, never.

## License

Any version of MIT, GPL or LGPL.
