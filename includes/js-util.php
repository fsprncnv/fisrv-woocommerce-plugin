<?php

/**
 * This is a utility class serving static methods.
 * These methods are 'polyfills' (PHP mapping to javascript methods).
 * This is way JS features can be injected, e.g. browser console logs, css selectors. 
 */
class JSUtil
{
    /**
     * Short hand to make a JS log to browser developer console.
     * 
     * @param string $msg Message to be passed to console
     * @param bool $error Optional, if true console will log as JS error
     */
    public static function log(string $msg, bool $error = false): void
    {
        $type = $error ? 'error' : 'log';

        echo '<script>console.' . $type . '("' . $msg . '")</script>';
    }

    /**
     * Save data into browser local storage (leveraging javascript).
     * This serves as cache for generated checkout URLs.
     */
    public static function cache_to_storage(string $value): void
    {
        echo '<script>localStorage.setItem("checkout-url-cache", "' . $value . '");</script>';
    }

    /**
     * Check whether local storage has an entry of a given key
     * @return bool True if key exists in local storage
     * @todo Need to find workaround for the fact that checking local storage
     * occurs in Javascript. However the response from Javascript has to be passed back to PHP somehow.
     */
    public static function is_cached(): bool
    {
        echo '<script>
        if (localStorage.getItem("checkout-url-cache") === null) {
            //
        }
        </script>';

        return false;
    }

    /**
     * Select a DOM element in the document.
     * Notice, no data is passed as return as this is simply a string template.
     * 
     * @param string $id The css ID to be searched for
     */
    public static function elem(string $id): void
    {
        echo 'document.getElementById(\'' . $id . '\')';
    }
}
