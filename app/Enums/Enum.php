<?php

namespace App\Enums;

abstract class Enum
{
    final public function __construct($value = "")
    {
        $reflectionClass = new \ReflectionClass($this);
        if (! in_array($value, $reflectionClass->getConstants())) {
            throw IllegalArgumentException();
        }
        $this->value = $value;
    }

    final public function __toString()
    {
        return $this->value;
    }

    final public static function all()
    {
        $oClass = new \ReflectionClass(get_called_class());
        return $oClass->getConstants();
    }
}
