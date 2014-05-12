<?php

namespace Appcia\Utils;

/**
 * PHP library related helpers
 */
abstract class Php
{
    /**
     * Get PHP setting runtime value with parameter name check
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getSetting($key, $value = null)
    {
        $data = ini_get($key);
        if ($data === false) {
            throw new \InvalidArgumentException(sprintf("Invalid PHP setting '%s'", $key));
        }

        if ($value === null) {
            return $data;
        } else {
            ini_set($key, $value);
        }
    }

    /**
     * Serialize mixed type data
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function encode($value)
    {
        $data = serialize($value);

        return $data;
    }

    /**
     * Unserialize mixed type data
     *
     * @param string $value
     *
     * @return mixed|NULL
     * @throws \InvalidArgumentException
     */
    public static function decode($value)
    {
        if (empty($value)) {
            return null;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf(
                "Value type '%s' is not a PHP string so it cannot be unserialized.",
                gettype($value)
            ));
        }

        $data = unserialize($value);

        return $data;
    }

    /**
     * Check whether specified string is serialized in PHP format
     *
     * @see http://stackoverflow.com/questions/1369936/check-to-see-if-a-string-is-serialized
     *
     * @param string $data
     *
     * @return bool
     */
    public static function isEncoded($data)
    {
        if (!is_string($data)) {
            return false;
        }

        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }

        $match = array();
        if (!preg_match('/^([adObis]):/', $data, $match)) {
            return false;
        }

        switch ($match[1]) {
        case 'a' :
        case 'O' :
        case 's' :
            if (preg_match("/^{$match[1]}:[0-9]+:.*[;}]\$/s", $data)) {
                return true;
            }

            break;
        case 'b' :
        case 'i' :
        case 'd' :
            if (preg_match("/^{$match[1]}:[0-9.E-]+;\$/", $data)) {
                return true;
            }

            break;
        }

        return false;
    }
}