<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 4/14/16 00:57
 */

namespace Very\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication {
    /**
     * The output from the previous command.
     *
     * @var \Symfony\Component\Console\Output\BufferedOutput
     */
    protected $lastOutput;

    /**
     * Create a new Artisan console application.
     */
    public function __construct() {
        parent::__construct('Very Framework', app()->version());
        $this->setAutoExit(false);
        $this->setCatchExceptions(false);
    }

    /**
     * Run an cmd console command by name.
     *
     * @param  string $command
     * @param  array  $parameters
     *
     * @return int
     */
    public function call($command, array $parameters = array()) {
        $parameters['command'] = $command;

        $this->lastOutput = new BufferedOutput;

        return $this->find($command)->run(new ArrayInput($parameters), $this->lastOutput);
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output() {
        return $this->lastOutput ? $this->lastOutput->fetch() : '';
    }
}