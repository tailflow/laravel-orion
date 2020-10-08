<?php

namespace Orion\Commands;

use Illuminate\Foundation\Console\RequestMakeCommand;

class OrionRequestMakeCommand extends RequestMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'orion:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Orion request class';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/orion.request.stub';
    }
}
