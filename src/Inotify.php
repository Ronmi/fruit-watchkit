<?php

namespace Fruit\WatchKit;

interface Inotify {
    function add($path, $mode); // will return a resource, it MUST be blocking default
    function del($resource);
    function len();
    function read(); // this is non-blocking
    function wait(); // this is blocking read
}
