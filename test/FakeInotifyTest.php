<?php

namespace FruitTest\WatchKit;

class FakeInotifyTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $i = new FakeInotify();
        $id = $i->add('test', 0);
        $this->assertArrayHasKey('test', $i->watchers);
        $this->assertEquals($i->watchers['test'], $id);
    }

    public function testDel()
    {
        $i = new FakeInotify();
        $id = $i->add('test', 0);
        $i->del($id);
        $this->assertArrayNotHasKey('test', $i->watchers);
    }

    public function testWait()
    {
        $i = new FakeInotify();
        $id = $i->add('test', 0);
        $i->addEvent('test', \IN_CREATE, 'asd');
        $e = $i->wait();
        $this->assertCount(1, $e);
        $e = $e[0];
        $this->assertEquals($id, $e['wd'], $e);
        $this->assertEquals(\IN_CREATE, $e['mask']);
        $this->assertEquals('asd', $e['name']);
        $this->assertFalse($i->wait());
    }

    public function testRead()
    {
        $i = new FakeInotify();
        $id = $i->add('test', 0);
        $i->addEvent('test', \IN_CREATE, 'asd');
        $this->assertFalse($i->read());
        $i->canRead = true;
        $e = $i->read();
        $this->assertCount(1, $e);
        $e = $e[0];
        $this->assertEquals($id, $e['wd'], $e);
        $this->assertEquals(\IN_CREATE, $e['mask']);
        $this->assertEquals('asd', $e['name']);
        $this->assertFalse($i->wait());
    }

    public function testLen()
    {
        $i = new FakeInotify();
        $this->assertEquals(0, $i->len());
        $i->add('test', 0);
        $i->addEvent('test', \IN_CREATE, 'asd');
        $this->assertEquals(1, $i->len());
        $i->wait();
        $this->assertEquals(0, $i->len());
    }
}
