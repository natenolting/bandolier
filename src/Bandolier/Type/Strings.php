<?php

namespace Pbc\Bandolier\Type;

/**
 * Class Strings
 * @package Pbc\Bandolier\Type
 */
class Strings
{

    /**
     * Strip wild slashes in strings, with up to triple slash stripping (anymore than that should be handled elsewhere)
     * Example:
     * use Pbc\Bandolier\Type\String;
     * $string = String::stripSlashes('A string with a bunch of \\\ slashes in it');
     *
     * @param $string
     * @return mixed
     */
    public static function stripSlashes($string)
    {
        $string = stripslashes($string);
        $string = str_replace("\\\\", "\\", $string);
        $string = str_replace("\\\"", "\"", $string);
        $string = str_replace("\\'", "'", $string);
        $string = str_replace("\\\'", "'", $string);
        $string = str_replace('\\\"', '"', $string);
        return $string;
    }

    public static function formatForTitle($string)
    {

        if (!is_string($string) || strlen($string) === 0) {
            return false;
        }
        $string = str_replace('_', ' ', $string);
        return Strings::titleCase($string);
    }

    /**
     * @param       $string
     * @param array $delimiters
     * @param array $exceptions
     *
     * @return bool|mixed|string
     */
    public static function titleCase(
        $string,
        $delimiters = [" ", "-", ".", "'", "O'", "Mc"],
        $exceptions = ["and", "to", "of", "das", "dos", "I", "II", "III", "IV", "V", "VI"]
    ) {
        /*
         * Exceptions in lower case are words you don't want converted
         * Exceptions all in upper case are any words you don't want converted to title case
         *   but should be converted to upper case, e.g.:
         *   king henry viii or king henry Viii should be King Henry VIII
         */
        $string = mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
        foreach ($delimiters as $delimiter) {
            $words = explode($delimiter, $string);
            $newWords = [];
            foreach ($words as $word) {
                if (in_array(mb_strtoupper($word, "UTF-8"), $exceptions)) {
                    // check exceptions list for any words that should be in upper case
                    $word = mb_strtoupper($word, "UTF-8");
                } elseif (in_array(mb_strtolower($word, "UTF-8"), $exceptions)) {
                    // check exceptions list for any words that should be in upper case
                    $word = mb_strtolower($word, "UTF-8");
                } elseif (!in_array($word, $exceptions)) {
                    // convert to uppercase (non-utf8 only)
                    $word = ucfirst($word);
                }
                array_push($newWords, $word);
            }
            $string = join($delimiter, $newWords);
        }
        //foreach
        return $string;
    }

    /**
     * If string starts with
     * http://stackoverflow.com/a/834355/405758
     *
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    /**
     * If string ends with
     * http://stackoverflow.com/a/834355/405758
     *
     * @param $haystack
     * @param $needle
     * @return bool
     * @throws \Exception
     */
    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            throw new \Exception("Needle must be one or more characters");
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * @param string $haystack
     * @param mixed $needle
     * @param bool $caseSensitive
     * @return bool
     */
    public static function contains($haystack, $needle, $caseSensitive = true)
    {
        // if needle is an array then check is one of it's values is in the haystack
        if (is_array($needle)) {
            for ($i = 0, $iCount = count($needle); $i < $iCount; $i++) {
                if (self::contains($haystack, $needle[$i], $caseSensitive) === true) {
                    return true;
                }
            }

            return false;
        }

        if ($caseSensitive) {
            return strlen(strstr($haystack, $needle)) > 0;
        } else {
            return strlen(stristr($haystack, $needle)) > 0;
        }
    }

    /**
     * Strip outer quotes from a string
     * @param $value
     * @return bool|string
     */
    public static function stripOuterQuotes($value)
    {
        $start = (strlen($value) > 1 && self::startsWith($value, '"'))
            || (strlen($value) > 1 && self::startsWith($value, '\''));

        $end = (strlen($value) > 1 && self::endsWith($value, '"'))
            || (strlen($value) > 1 && self::endsWith($value, '\''));

        if ($start && $end) {
            return substr($value, 1, -1);
        }
        return $value;
    }

    /**
     * Convert a string such as "one hundred thousand" to 100000.00.
     * https://stackoverflow.com/a/11219737/405758
     *
     * @param string $data The numeric string.
     *
     * @return float or false on error
     */
    public static function wordsToNumber($data) {
        // Replace all number words with an equivalent numeric value
        $data = strtr(
            $data, array_merge(array_flip(Numbers::toWordDictionary()), ['and' => ''])
        );

        // Coerce all tokens to numbers
        $parts = array_map(
            function ($val) {
                return floatval($val);
            },
            preg_split('/[\s-]+/', $data)
        );

        $stack = new \SplStack; // Current work stack
        $sum   = 0; // Running total
        $last  = null;
        foreach ($parts as $part) {
            if (!$stack->isEmpty()) {
                // We're part way through a phrase
                if ($stack->top() > $part) {
                    // Decreasing step, e.g. from hundreds to ones
                    if ($last >= 1000) {
                        // If we drop from more than 1000 then we've finished the phrase
                        $sum += $stack->pop();
                        // This is the first element of a new phrase
                        $stack->push($part);
                    } else {
                        // Drop down from less than 1000, just addition
                        // e.g. "seventy one" -> "70 1" -> "70 + 1"
                        $stack->push($stack->pop() + $part);
                    }
                } else {
                    // Increasing step, e.g ones to hundreds
                    $stack->push($stack->pop() * $part);
                }
            } else {
                // This is the first element of a new phrase
                $stack->push($part);
            }

            // Store the last processed part
            $last = $part;
        }

        return $sum + $stack->pop();
    }

}
