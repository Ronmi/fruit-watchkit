<?php

namespace Fruit\WatchKit;

use Fruit\PathKit\Path;

class Watcher
{
    private $watches;
    private $res;

    public function __construct(array $watches)
    {
        $this->res = inotify_init();

        if ($this->res) {
            $this->init($watches);
        }
    }

    private function init(array $watches)
    {
        $arr = array();
        foreach ($watches as $file => $cmd) {
            $fn = (new Path($file))->normalize();
            $wd = inotify_add_watch($this->res, $fn, \IN_MODIFY | \IN_CREATE);
            $arr[$wd] = array($fn, $cmd);
        }
        $this->watches = $arr;
    }

    public function watch()
    {
        if (! $this->res) {
            return;
        }
        stream_set_blocking($this->res, 1);
        while (true) {
            $result = inotify_read($this->res);
            if (!$result) {
                continue;
            }

            $pending = array();
            foreach ($result as $event) {
                $pending[$event['wd']] = true;
            }

            foreach (array_keys($pending) as $wd) {
                list($fn, $cmd) = $this->watches[$wd];
                echo sprintf("%s: %s\n", $fn, $cmd);
                echo shell_exec($cmd) . "\ndone.\n";
            }
        }
    }
}
