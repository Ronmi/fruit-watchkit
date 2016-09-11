<?php

namespace FruitTest\WatchKit;

use Fruit\WatchKit\Watcher;

class WatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testAddFile()
    {
        $i = new FakeInotify();
        $w = new Watcher($i);
        $w->add('test/**/*.test', 'echo ok');
        $this->assertArrayHasKey(getcwd() . '/test/dest/a.test', $i->watchers);
        $this->assertArrayHasKey(getcwd() . '/test/dest/b.test', $i->watchers);
        $this->assertArrayHasKey(getcwd() . '/test/dest/a/a.test', $i->watchers);
    }

    public function testAddDir()
    {
        $i = new FakeInotify();
        $w = new Watcher($i);
        $w->add('test/dest', 'echo ok');
        $this->assertArrayHasKey(getcwd() . '/test/dest', $i->watchers);
        $this->assertArrayHasKey(getcwd() . '/test/dest/a', $i->watchers);
        $this->assertArrayHasKey(getcwd() . '/test/dest/b', $i->watchers);
    }

    public function testFileModified()
    {
        $i = new FakeInotify();
        $w = new Watcher($i);
        $w->add('test/**/*.test', 'echo MagicWord');
        $i->addEvent(getcwd() . '/test/dest/a.test', \IN_CLOSE_WRITE, '');
        $this->expectOutputRegex('/MagicWord/');
        $w->run();
    }

    public function testFileModifiedInDir()
    {
        $i = new FakeInotify();
        $w = new Watcher($i);
        $w->add('test/dest', 'echo MagicWord');
        $i->addEvent(getcwd() . '/test/dest', \IN_CLOSE_WRITE, 'a.test');
        $this->expectOutputRegex('/MagicWord/');
        $w->run();
    }

    public function testNewFileModifiedInDir()
    {
        $i = new FakeInotify();
        $w = new Watcher($i);
        $w->add('test/dest', 'echo MagicWord');
        $i->addEvent(getcwd() . '/test/dest', \IN_CLOSE_WRITE, 'c.test');
        $this->expectOutputRegex('/MagicWord/');
        $w->run();
    }

    public function testNewFileModified()
    {
        // TODO: automatically watch new files matching file pattern like a/*.php
        $this->markTestIncomplete('auto watch new files matching file pattern');
    }

    public function testIgnoreEvent()
    {
        $i = new FakeInotify();
        $w = new Watcher($i);
        $w->add('test/dest', 'echo MagicWord');
        $i->addEvent(getcwd() . '/test/dest/b', \IN_IGNORED, '');
        $i->addEvent(getcwd() . '/test/dest/b', \IN_CLOSE_WRITE, 'a.test');
        ob_start();
        $w->run();
        $out = ob_get_clean();
        $this->assertFalse(strpos($out, 'MagicWord'));
    }

    public function testDirCreatedInDir()
    {
        $i = new FakeInotify();
        $w = new Watcher($i);
        $w->add('test/dest', 'echo MagicWord');
        $i->addEvent(getcwd() . '/test/dest/b', \IN_IGNORED, '');
        $w->run();
        unset($i->watchers[getcwd() . 'test/dest/b']);

        $i->addEvent(getcwd() . '/test/dest', \IN_CREATE, 'b');
        ob_start();
        $w->run();
        $out = ob_get_clean();
        $this->assertArrayHasKey(getcwd() . '/test/dest/b', $i->watchers, var_export($i->watchers, true));
        $this->assertFalse(strpos($out, 'MagicWord'));
    }

    public function testDeleteFile()
    {
        $i = new FakeInotify();
        $w = new Watcher($i);
        $w->add('test/**/*.test', 'echo MagicWord');
        $i->addEvent(getcwd() . '/test/dest/a.test', \IN_DELETE_SELF, '');
        $this->expectOutputRegex('/MagicWord/');
        $w->run();
    }

    public function testDeleteFileInDir()
    {
        $i = new FakeInotify();
        $w = new Watcher($i);
        $w->add('test/dest', 'echo MagicWord');
        $i->addEvent(getcwd() . '/test/dest', \IN_DELETE_SELF, 'a.test');
        $this->expectOutputRegex('/MagicWord/');
        $w->run();
    }

    public function testDeleteDirInDir()
    {
        $i = new FakeInotify();
        $w = new Watcher($i);
        $w->add('test/dest', 'echo MagicWord');
        $i->addEvent(getcwd() . '/test/dest', \IN_DELETE_SELF, 'b');
        $this->expectOutputRegex('/MagicWord/');
        $w->run();
    }
}
