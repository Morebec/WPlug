<?php

namespace WPlug;

class ArrayUtils
{
    /**
     * Retrieve a value from an array, or return a given default if the index isn't set
     */
    public static function getOrDefault($arr, $key, $defaultValue = null) {
        return array_key_exists($key,$arr) ? $arr[$key] : $defaultValue;
    }
}
