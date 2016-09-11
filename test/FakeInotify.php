<?php

namespace FruitTest\WatchKit;

use Fruit\WatchKit\Inotify;

class FakeInotify implements Inotify
{
    public $events;
    public $current;
    public $length;
    public $watchers;
    public $canRead;

    public function __construct()
    {
        $this->events = array();
        $this->current = 0;
        $this->length = 0;
        $this->nextID = 1;
        $this->canRead = false;
    }

    function addEvent($path, $mask, $name)
    {
        $wd = $this->watchers[$path];
        $e = array(
            'wd' => $wd,
            'mask' => $mask,
            'name' => $name,
        );
        array_push($this->events, $e);
        $this->length++;
    }

    function add($path, $mode)
    {
        $this->watchers[$path] = $this->nextID;

        return $this->nextID++;
    }

    function del($resource)
    {
        foreach ($this->watchers as $path => $id) {
            if ($id === $resource) {
                unset($this->watchers[$path]);
                return;
            }
        }
    }

    function len()
    {
        return $this->length;
    }

    function read()
    {
        if ($this->canRead) {
            return $this->wait();
        }
        return false;
    }

    function wait()
    {
        if ($this->length > 0) {
            $ret = $this->events[$this->current];
            $this->length--;
            $this->current++;
            return array($ret);
        }
        return false;
    }
}
