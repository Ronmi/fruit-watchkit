<?php

namespace Fruit\WatchKit;

use Fruit\PathKit\Glob;
use Fruit\PathKit\Path;
use Fruit\DSKit\Bijection;
use Fruit\DSKit\Set;

/**
 * Watcher watches some files or directories, and run shell commands when they
 * are modified.
 */
class Watcher
{
    private static $msg = array(
        \IN_ACCESS        => 'IN_ACCESS',
        \IN_MODIFY        => 'IN_MODIFY',
        \IN_ATTRIB        => 'IN_ATTRIB',
        \IN_CLOSE_WRITE   => 'IN_CLOSE_WRITE',
        \IN_CLOSE_NOWRITE => 'IN_CLOSE_NOWRITE',
        \IN_OPEN          => 'IN_OPEN',
        \IN_MOVED_TO      => 'IN_MOVED_TO',
        \IN_MOVED_FROM    => 'IN_MOVED_FROM',
        \IN_CREATE        => 'IN_CREATE',
        \IN_DELETE        => 'IN_DELETE',
        \IN_DELETE_SELF   => 'IN_DELETE_SELF',
        \IN_MOVE_SELF     => 'IN_MOVE_SELF',
        \IN_CLOSE         => 'IN_CLOSE',
        \IN_MOVE          => 'IN_MOVE',
        \IN_ALL_EVENTS    => 'IN_ALL_EVENTS',
        \IN_UNMOUNT       => 'IN_UNMOUNT',
        \IN_Q_OVERFLOW    => 'IN_Q_OVERFLOW',
        \IN_IGNORED       => 'IN_IGNORED',
        \IN_ISDIR         => 'IN_ISDIR',
        \IN_ONLYDIR       => 'IN_ONLYDIR',
        \IN_DONT_FOLLOW   => 'IN_DONT_FOLLOW',
        \IN_MASK_ADD      => 'IN_MASK_ADD',
        \IN_ONESHOT       => 'IN_ONESHOT',
    );

    private function dumpEvent($e)
    {
        $e['msg'] = array();
        foreach (self::$msg as $k => $m) {
            if ($e['mask'] & $k !== 0) {
                array_push($e['msg'], $m);
            }
        }
        if ($this->watched->hasv($e['wd'])) {
            $e['path'] = $this->watched->getv($e['wd']);
        }
        return var_export($e, true);
    }

    private $inotify;

    // a bijection mapping path and wd (watch descriptor)
    private $watched;
    // a pattern to info map
    // info is array(script, glob, regexp)
    private $patterns;

    public function __construct(Inotify $i = null)
    {
        $this->inotify = $i;
        if ($i == null) {
            $this->inotify = new PECLInotify();
        }

        $this->watched = new Bijection();
        $this->patterns = array();
    }

    private function addFile($path)
    {
        // for files, listen to CLOSE_WRITE and DELETE_SELF event
        $wd = $this->inotify->add($path, \IN_CLOSE_WRITE | \IN_DELETE_SELF);
        $this->watched->set($path, $wd);
    }

    /**
     * @return bool
     */
    private function doAddDir($path)
    {
        if ($this->watched->has($path)) {
            return false;
        }
        // for directories, listen to CLOSE_WRITE, MOVE, DELETE_SELF and CREATE event
        $wd = $this->inotify->add($path, \IN_CLOSE_WRITE | \IN_DELETE_SELF | \IN_MOVE_SELF | \IN_CREATE);
        $this->watched->set($path, $wd);
        return true;
    }

    private function addDirectory($path)
    {
        if (!$this->doAddDir($path)) {
            return;
        }

        // recursively add all child directories
        $p = (new Path($path))->relative(getcwd());
        $g = new Glob($p . DIRECTORY_SEPARATOR . '**');
        foreach ($g->iterate() as $p) {
            if(is_dir($p)) {
                $this->doAddDir($p);
            }
        }
    }

    /**
     * Add a globbing pattern to list.
     */
    public function add($pattern, $script)
    {
        if (isset($this->patterns[$pattern])) {
            if ($script !== $this->patterns[$pattern]['script']) {
                // fail fast
                die($pattern . ' has registered!');
            }

            // duplicated pattern, just ignore it
            return;
        }
        $g = new Glob($pattern);
        $this->patterns[$pattern] = array(
            'script' => $script,
            'regexp' => $g->regex(getcwd()),
        );
        // test if $pattern resolves to single directory
        $isDir = false;
        $cnt = 0;
        foreach ($g->iterate() as $path) {
            if (!is_dir($path) or $cnt > 1) {
                break;
            }
            $isDir = true;
            $cnt++;
        }
        if ($isDir && $cnt == 1) {
            $this->patterns[$pattern]['regexp'] = (new Glob($pattern . DIRECTORY_SEPARATOR . '**'))->regex(getcwd());
        }

        // register to inotify
        $cnt = 0;
        foreach ($g->iterate() as $path) {
            if ($this->watched->has($path)) {
                continue;
            }
            if (is_dir($path)) {
                $this->addDirectory($path);
            } else {
                $this->addFile($path);
            }
        }
    }

    private function handleCreation($e)
    {
        if (!$this->watched->hasv($e['wd'])) {
            // unknown create event, fail fast
            die('unknown create event: ' . $this->dumpEvent($e));
        }

        $path = $this->watched->getv($e['wd']);
        if (!is_dir($path)) {
            // we only listen to create event on directory, fail fast
            die('create event on file: ' . $this->dumpEvent($e));
        }
        $created = (new Path($e['name'], $path . DIRECTORY_SEPARATOR))->expand();
        if (!is_dir($created)) {
            // file, nothing to do
            return;
        }

        $this->doAddDir($created);
    }

    private function handleDelete($e)
    {
        if (!$this->watched->hasv($e['wd'])) {
            // unknown delete event, fail fast
            die('unknown delete event: ' . $this->dumpEvent($e));
        }
        $path = $this->watched->getv($e['wd']);
        if (isset($e['name']) && $e['name'] != '') {
            $path = (new Path($e['name'], $path . DIRECTORY_SEPARATOR))->expand();
        }
        // try to remove watcher anyway
        @$this->inotify->del($e['wd']);
        $this->watched->removev($e['wd']);
        return $path;
    }

    private function parseEvent($events, $paths)
    {
        foreach ($events as $cur) {
            $m = $cur['mask'];
            if (($m & \IN_CREATE) !== 0) {
                $this->handleCreation($cur);
            } elseif (($m & \IN_DELETE_SELF) !== 0 or ($m & \IN_MOVE_SELF) !== 0) {
                $paths[$this->handleDelete($cur)] = 'DELETED';
            } elseif (($m & \IN_CLOSE_WRITE) !== 0) {
                if (!$this->watched->hasv($cur['wd'])) {
                    // record not found, this might because record has been removed above
                    // so it should be safe to skip
                    continue;
                }
                $path = $this->watched->getv($cur['wd']);
                if (isset($cur['name']) && $cur['name'] != '') {
                    $path = (new Path($cur['name'], $path . DIRECTORY_SEPARATOR))->expand();
                }
                $paths[$path] = 'CHANGED';
            } elseif (($m & \IN_IGNORED) !== 0) {
                // watcher is removed, clear records
                $this->watched->removev($cur['wd']);
            } else {
                die('unknown event: ' . $this->dumpEvent($cur));
            }
        }

        return $paths;
    }

    private function parsePaths($paths, $scripts)
    {
        foreach ($paths as $path => $cause) {
            // check against all patterns
            foreach ($this->patterns as $p) {
                $regexp = $p['regexp'];
                if (preg_match($regexp, $path) === 1) {
                    $scripts[$p['script']] = array($path, $cause);
                }
            }
        }
        return $scripts;
    }

    /**
     * Main loop
     */
    public function run()
    {
        while (($events = $this->inotify->wait()) !== false) {
            // paths pending to be parsed
            $paths = $this->parseEvent($events, array());
            if (count($paths) < 1) {
                continue;
            }
            // scripts pending to be executed
            $scripts = $this->parsePaths($paths, array());
            while (count($scripts) > 0) {
                $s = array_keys($scripts)[0];
                list($path, $cause) = $scripts[$s];
                unset($scripts[$s]);

                echo sprintf("\n[%s] %s has %s, running [%s]\n", $cause, $path, $cause, $s);
                $res = explode("\n", shell_exec($s));
                echo '> ' . implode("\n> ", $res) . "\n";

                // update event queue, merge into pending lists,
                // so we might not need to execute too many times
                $e = $this->inotify->read();
                if ($e === false) {
                    // no events
                    continue;
                }
                $paths = $this->parseEvent($events, array());
                if (count($paths) < 1) {
                    continue;
                }
                $scripts = $this->parsePaths($paths, $scripts);
            }
        }
    }
}
