<?php

namespace Appcia\Utils;

/**
 * Mixed value helpers
 */
abstract class Val
{
    /**
     * Test some condition
     *
     * @param bool  $flag
     * @param mixed $arg1
     * @param mixed $arg2
     *
     * @return null
     */
    public static function test($flag, $arg1 = null, $arg2 = null)
    {
        if ($arg1 === null) {
            return $flag;
        }

        return $flag
            ? $arg1
            : $arg2;
    }

    /**
     * Get chained property
     *
     * @param mixed  $value
     * @param string $chain Chain of properties (dot as separator)
     *
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public static function prop($value, $chain)
    {
        if (empty($chain)) {
            throw new \InvalidArgumentException("Value property name cannot be empty.");
        }

        if (!is_string($chain)) {
            throw new \InvalidArgumentException(sprintf(
                "Value property name must be a string, %s given.",
                gettype($chain)
            ));
        }

        $chain = explode('.', $chain);
        foreach ($chain as $property) {
            if (is_object($value)) {
                $getter = 'get' . ucfirst($property);
                if (is_callable(array($value, $getter))) {
                    $value = $value->$getter();
                } elseif (isset($value->{$property})) {
                    $value = $value->{$property};
                } else {
                    return null;
                }
            } elseif (Arrays::isArray($value)) {
                if (!isset($value[$property])) {
                    return null;
                }
                $value = $value[$property];
            } elseif (is_null($value)) {
                return null;
            } else {
                throw new \InvalidArgumentException(sprintf(
                    "Value property '%s' in chain '%s' has invalid type: '%s'.",
                    $property,
                    $chain,
                    gettype($value)
                ));
            }
        }

        return $value;
    }

    /**
     * Plural form of prop
     *
     * @param mixed $value
     * @param array $chains
     *
     * @see prop()
     *
     * @return array
     */
    public static function props($value, array $chains)
    {
        $props = array();
        foreach ($chains as $chain) {
            $props[$chain] = static::prop($value, $chain);
        }

        return $props;
    }

    /**
     * Filter empty values to be treated as nulls
     *
     * @param mixed $keys
     * @param array $data
     *
     * @return array
     */
    public static function nulls(array $data, $keys = null)
    {
        if ($keys === null) {
            $keys = array_keys($data);
        }

        if (!Arrays::isArray($keys)) {
            $keys = array($keys);
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && empty($data[$key])) {
                $data[$key] = null;
            }
        }

        return $data;
    }

    /**
     * Check whether value could be converted to string
     *
     * @param $value
     *
     * @return bool
     */
    public static function stringable($value)
    {
        return !(!is_scalar($value)
            && !(is_object($value) && method_exists($value, '__toString')));
    }

    /**
     * Get value treated as string
     *
     * @param mixed $value Value
     *
     * @return string|null
     */
    public static function string($value)
    {
        if (static::stringable($value)) {
            $value = (string) $value;
        } else {
            $value = null;
        }

        return $value;
    }

    /**
     * Get value treated as float
     * Wrapper sometimes can be useful for edge case service
     *
     * @param mixed $value
     *
     * @return float
     */
    public static function float($value)
    {
        return floatval($value);
    }

    /**
     * Get value treated as integer
     * Wrapper sometimes can be useful for edge case service
     *
     * @param mixed $value
     *
     * @return float
     */
    public static function integer($value)
    {
        return intval($value);
    }

    /**
     * Get hash for value for any type
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function hash($value)
    {
        $hash = is_object($value)
            ? spl_object_hash($value)
            : md5(Php::encode($value));

        return $hash;
    }

    /**
     * Check whether value is specified (it is not null or empty string)
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function specified($value)
    {
        return !in_array($value, array(null, ''), true);
    }
}