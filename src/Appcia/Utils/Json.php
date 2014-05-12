<?php

namespace Appcia\Utils;

use Nette\Utils\JsonException;

/**
 * JSON related helpers
 */
abstract class Json extends \Nette\Utils\Json
{
    /**
     * Check whether string was encoded in JSON format
     *
     * @param string $string
     *
     * @return bool
     */
    public static function isEncoded($string)
    {
        try {
            parent::decode($string);
        } catch (JsonException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function encode($data)
    {
        // Cast objects to string whenever it is possible
        array_walk_recursive($data, function (&$value) {
            if (is_object($value) AND Val::stringable($value)) {
                $value = (string) $value;
            }
        });

        $json = parent::encode($data);

        return $json;
    }

    /**
     * {@inheritdoc}
     */
    public static function decode($json)
    {
        if (!is_string($json)) {
            throw new \InvalidArgumentException(sprintf(
                "Value type '%s' is not a JSON string so it cannot be decoded.",
                gettype($json)
            ));
        }

        $data = parent::decode($json);

        return $data;
    }
}