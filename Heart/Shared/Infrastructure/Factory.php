<?php

namespace Heart\Shared\Infrastructure;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;

abstract class Factory extends EloquentFactory
{
    protected $connection = "testing";
}
