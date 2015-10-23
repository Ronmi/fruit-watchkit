<?php

namespace Fruit\WatchKit\Bin;

use CLIFramework\Application;

class WatcherApp extends Application
{
    public function brief()
    {
        return 'Watch a list of files and run command when they are modified.';
    }

    public function init()
    {
        parent::init();
        $this->command('run', 'Fruit\WatchKit\Bin\RunCommand');
    }
}
