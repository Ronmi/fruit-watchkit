<?php

namespace Fruit\WatchKit\Bin;

use CLIFramework\Command;
use Fruit\WatchKit\Watcher;

class RunCommand extends Command
{
    public function options($opt)
    {
        $desc = 'watch list, defaults to "watch.list"';
        $opt->add('l|list?', $desc)->isa('file')->defaultValue('watch.list');
    }

    public function execute()
    {
        $listFile = $this->options->list;

        $listStr = file_get_contents($listFile);
        $lists = explode("\n", $listStr);

        $watches = array();
        foreach ($lists as $watch) {
            $arr = explode("|", trim($watch));
            if (count($arr) < 2) {
                echo "Syntax error: $watch\n";
                return;
            }

            $file = array_shift($arr);
            $cmd = implode("|", $arr);
            $watches[$file] = $cmd;
        }

        $watcher = new Watcher($watches);
        $watcher->watch();
    }
}
