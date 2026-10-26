<?php

if (! function_exists('selected')) {
    /**
     * Check if the given value should be selected.
     *
     * @param  mixed  $value  The value to compare.
     * @param  string  $oldKey  The key used in the old() function.
     * @return string "selected" if the values match, otherwise an empty string.
     */
    function selected($value, $oldValue)
    {
        // $oldValue = old($oldValue);

        if (is_array($oldValue)) {
            return in_array($value, $oldValue) ? 'selected' : '';
        }

        return $oldValue == $value ? 'selected' : '';
    }
}

if (! function_exists('milisecond_to_date')) {
    function milisecond_to_date(string $mili)
    {
        return date('Y-m-d H:i:s', $mili / 1000);
    }
}

if (! function_exists('date_to_milisecond')) {
    function date_to_milisecond(string $date)
    {
        // Date format = Y-m-d H:i:s or Y-m-d
        return strtotime($date) * 1000;
    }
}
