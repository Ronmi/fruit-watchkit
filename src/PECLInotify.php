<?php

namespace Fruit\WatchKit;

class PECLInotify implements Inotify
{
    protected $inotify;
    public function __contruct()
    {
        $this->inotify = inotify_init();
        stream_set_blocking($this->inotify, true);
    }

    public function add($path, $mode)
    {
        return inotify_add_watch($this->inotify, $path, $mode);
    }

    public function del($resource)
    {
        return inotify_rm_watch($this->inotify, $resource);
    }

    public function len()
    {
        return inotify_queue_len($this->inotify);
    }

    public function read()
    {
        // should just call $this->len(), but copy-paste
        // here to reduce one function call since the logic
        // in len() is simple
        if (inotify_queue_len($this->inotify) < 1) {
            return false;
        }

        // should just call $this->wait(), but copy-paste
        // here to reduce one function call since the logic
        // in wait() is simple
        return inotify_read($this->inotify);
    }

    public function wait()
    {
        return inotify_read($this->inotify);
    }
}
