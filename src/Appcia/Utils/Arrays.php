<?php

namespace Appcia\Utils;

/**
 * Array helpers
 *
 * Some functions are from Kohana_Arr class which is part of Kohana Framework
 * @see http://kohanaframework.org/3.3/guide-api/Arr
 */
class Arrays extends \Nette\Utils\Arrays
{
    const PATH_DELIMITER = '.';

    const TEXT_DELIMITER = '=>';

    /**
     * Tests if an array is associative or not.
     *
     * @param array $array array to check
     *
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);
        $assoc = (array_keys($keys) !== $keys);

        return $assoc;
    }

    /**
     * Test if a value is an array with an additional check for array-like objects.
     *
     * @param mixed $value value to check
     *
     * @return bool
     */
    public static function isArray($value)
    {
        if (is_array($value)) {
            return true;
        } else {
            return (is_object($value) AND $value instanceof \Traversable);
        }
    }

    /**
     * Recursive array search
     *
     * @param mixed $needle
     * @param array $haystack
     * @param array $indexes
     *
     * @throws \InvalidArgumentException
     * @return bool
     */
    public static function search($needle, $haystack, &$indexes = array())
    {
        if (!static::isArray($haystack)) {
            throw new \InvalidArgumentException("Values haystack is not an array.");
        }

        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $indexes[] = $key;
                $status = static::search($needle, $value, $indexes);
                if ($status) {
                    return true;
                } else {
                    $indexes = array();
                }
            } else if ($value == $needle) {
                $indexes[] = $key;

                return true;
            }
        }

        return false;
    }

    /**
     * Replace array values using specified map
     *
     * @param array $values
     * @param array $map
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function replace($values, array $map)
    {
        if (!static::isArray($values)) {
            throw new \InvalidArgumentException("Values to be replaced is not an array.");
        }

        $res = array();
        foreach ($values as $key => $value) {
            if (isset($map[$value])) {
                $res[$key] = $map[$value];
            }
        }

        return $res;
    }

    /**
     * Clear multiple keys of array
     *
     * @param array $data
     * @param array $keys
     *
     * @return array
     */
    public static function clear($data, $keys)
    {
        if (empty($keys)) {
            return $data;
        }
        if (!static::isArray($keys)) {
            $keys = array($keys);
        }

        foreach ($keys as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    /**
     * Gets a value from an array using a dot separated path
     *
     * @param   array  $array     Array to search
     * @param   mixed  $path      Key path string (delimiter separated) or array of keys
     * @param   mixed  $default   Default value if the path is not set
     * @param   string $delimiter Key path delimiter
     *
     * @return  mixed
     */
    public static function getPath($array, $path, $default = null, $delimiter = null)
    {
        if (!static::isArray($array)) {
            return $default;
        }

        if (is_array($path)) {
            // The path has already been separated into keys
            $keys = $path;
        } else {
            if (array_key_exists($path, $array)) {
                // No need to do extra processing
                return $array[$path];
            }

            if ($delimiter === null) {
                // Use the default delimiter
                $delimiter = static::PATH_DELIMITER;
            }

            // Remove starting delimiters and spaces
            $path = ltrim($path, "{$delimiter} ");

            // Remove ending delimiters, spaces, and wildcards
            $path = rtrim($path, "{$delimiter} *");

            // Split the keys by delimiter
            $keys = explode($delimiter, $path);
        }

        do {
            $key = array_shift($keys);

            if (ctype_digit($key)) {
                // Make the key an integer
                $key = (int) $key;
            }

            if (isset($array[$key])) {
                if ($keys) {
                    if (static::isArray($array[$key])) {
                        // Dig down into the next part of the path
                        $array = $array[$key];
                    } else {
                        // Unable to dig deeper
                        break;
                    }
                } else {
                    // Found the path requested
                    return $array[$key];
                }
            } elseif ($key === '*') {
                // Handle wildcards

                $values = array();
                foreach ($array as $arr) {
                    if ($value = static::getPath($arr, implode('.', $keys))) {
                        $values[] = $value;
                    }
                }

                if ($values) {
                    // Found the values requested
                    return $values;
                } else {
                    // Unable to dig deeper
                    break;
                }
            } else {
                // Unable to dig deeper
                break;
            }
        } while ($keys);

        // Unable to find the value requested
        return $default;
    }

    /**
     * Set a value on an array by path.
     *
     * @see static::path()
     *
     * @param array  $array     Array to update
     * @param string $path      Path
     * @param mixed  $value     Value to set
     * @param string $delimiter Path delimiter
     */
    public static function setPath(&$array, $path, $value, $delimiter = null)
    {
        if ($delimiter === null) {
            $delimiter = static::PATH_DELIMITER;
        }

        // The path has already been separated into keys
        $keys = $path;
        if (!is_array($path)) {
            // Split the keys by delimiter
            $keys = explode($delimiter, $path);
        }

        // Set current $array to inner-most array path
        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (ctype_digit($key)) {
                // Make the key an integer
                $key = (int) $key;
            }

            if (!isset($array[$key])) {
                $array[$key] = array();
            }

            $array = & $array[$key];
        }

        // Set key on inner-most array
        $array[array_shift($keys)] = $value;
    }

    /**
     * Parse text, treat as array, single record per line, format: 'key => value'
     * Converts string representation of array to native
     *
     * @param $text
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public static function parse($text)
    {
        if (static::isArray($text)) {
            return $text;
        } elseif (!is_scalar($text)) {
            throw new \InvalidArgumentException(sprintf(
                "Data to be treated as array should be scalar, specified type is '%s'",
                gettype($text)
            ));
        }

        $text = trim($text);
        if (empty($text)) {
            return array();
        }

        $data = array();
        $lines = explode(PHP_EOL, $text);
        foreach ($lines as $index => $line) {
            $parts = explode(static::TEXT_DELIMITER, $line);
            if (count($parts) == 2) {
                list ($index, $line) = array_map(function ($value) {
                    return trim($value);
                }, $parts);
            }
            $data[$index] = $line;
        }

        return $data;
    }

    /**
     * Compose text from data
     * Converts native array to string representation
     *
     * @param array $data
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function compose($data)
    {
        if (empty($data)) {
            return '';
        }

        if (!static::isArray($data)) {
            throw new \InvalidArgumentException(sprintf(
                "Data is not an array, specified type is '%s'",
                gettype($data)
            ));
        }

        $lines = array();
        foreach ($data as $key => $value) {
            if (!is_scalar($value)) {
                throw new \InvalidArgumentException("Data value to be composed as text should be a scalar.");
            }

            $lines[] = sprintf('%s %s %s', $key, static::TEXT_DELIMITER, $value);
        }
        $text = implode(PHP_EOL, $lines);

        return $text;
    }

    /**
     * @param $arr
     * @param $callback
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function filter($arr, $callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException("Filter callback is not callable.");
        }

        foreach ($arr as $key => $value) {
            if (!$callback($value, $key)) {
                unset($arr[$key]);
            }
        }

        return $arr;
    }

    /**
     * Get flattened data
     * Keys are concatenated using '.'
     *
     * @param array  $data
     * @param string $glue Multidimensional key glue
     *
     * @return array
     */
    public static function glue($data, $glue = '.')
    {
        $data = static::glue_recursive($data, '', $glue);

        return $data;
    }

    /**
     * Recursive helper for data flattening
     *
     * @param array  $array  Data
     * @param string $prefix Key prefix
     * @param string $glue   Key glue
     *
     * @return array
     */
    protected static function glue_recursive($array, $prefix, $glue)
    {
        $result = array();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, static::glue_recursive($value, $prefix . $key . $glue, $glue));
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }

    /**
     * Similar to array_map but takes key as second parameter
     *
     * @param array    $array    Data
     * @param callable $callback Callback for returning value, can also group elements via third parameter / reference
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function pass($array, $callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException("Array mapping key callback is not callable.");
        }

        $res = array();
        foreach ($array as $key => $value) {
            $group = null;
            $name = null;
            $value = $callback($value, $key, $group, $name);

            if ($group !== null) {
                if (!isset($res[$group])) {
                    $res[$group] = array();
                }
                if ($key !== null) {
                    $res[$group][$name] = $value;
                } else {
                    $res[$group][] = $value;
                }
            } else {
                $res[] = $value;
            }
        }

        return $res;
    }

    /**
     * Get some part of an array which has always same element count
     *
     * @param array $array Data
     * @param int   $count Elements per part
     * @param int   $which Which part to get, from 0 to [ ceil (data count / elements per part) ]
     *
     * @return array
     */
    public static function sliceEquinum($array, $count, $which = 0)
    {
        $parts = floor(count($array) / $count);
        $which = max(0, min($which, $parts));
        $part = array_slice($array, $count * ((int) $which), $count);

        return $part;
    }

    /**
     * Get some part of an array which has always same part count
     *
     * @param array $array Data
     * @param int   $count Part count
     * @param int   $which Which part to get, from 0 to [part count - 1]
     *
     * @return array
     */
    public static function slicePrecise($array, $count, $which = 0)
    {
        $per = count($array) / $count;
        $length = (($which + 1) == $count)
            ? null
            : $per;

        $part = array_slice($array, $which * $per, $length);

        return $part;
    }

    /**
     * Skip first (or last) elements of an array (indexed from 0-n)
     *
     * @param $array
     * @param $n
     * @param $tail
     *
     * @return mixed
     */
    public static function skip($array, $n, $tail = false)
    {
        $c = count($array);

        if ($tail) {
            for ($i = $c - $n; $i < $c; $i++) {
                unset($array[$i]);
            }
        } else {
            for ($i = 0; $i < $n; $i++) {
                unset($array[$i]);
            }
        }

        return $array;
    }

    /**
     * Shuffle an array, preserve associative keys
     * Not in place like in shuffle()
     *
     * @param $array
     *
     * @return mixed
     */
    public static function shuffle($array)
    {
        $keys = array_keys($array);

        shuffle($keys);

        $res = array();
        foreach ($keys as $key) {
            $res[$key] = $array[$key];
        }

        return $res;
    }

    /**
     * Merge two arrays recursive
     *
     *
     * Overwrite values with associative keys
     * Append values with integer keys
     *
     * @param array $arr1 First array
     * @param array $arr2 Second array
     *
     * @return array
     */
    public static function extend(array $arr1, array $arr2)
    {
        if (empty($arr1)) {
            return $arr2;
        } else if (empty($arr2)) {
            return $arr1;
        }

        foreach ($arr2 as $key => $value) {
            if (is_int($key)) {
                $arr1[] = $value;
            } elseif (is_array($arr2[$key])) {
                if (!isset($arr1[$key])) {
                    $arr1[$key] = array();
                }

                if (is_int($key)) {
                    $arr1[] = static::extend($arr1[$key], $value);
                } else {
                    $arr1[$key] = static::extend($arr1[$key], $value);
                }
            } else {
                $arr1[$key] = $value;
            }
        }

        return $arr1;
    }

    /**
     * Filter first level of values of an array
     *
     * @param array $arr
     *
     * @return array
     */
    public static function unique(array $arr)
    {
        foreach ($arr as $key => $val) {
            if (static::occurrences($arr, $val) > 1) {
                unset($arr[$key]);
            }
        }

        return $arr;
    }

    /**
     * Count value occurrences in array
     *
     * @param array  $arr
     * @param string $search
     *
     * @return int
     */
    public static function occurrences(array $arr, $search)
    {
        $count = 0;
        $test = Val::hash($search);

        foreach ($arr as $val) {
            $compare = Val::hash($val);
            if ($test == $compare) {
                $count++;
            }
        }

        return $count;
    }
}