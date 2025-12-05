<?php

/**
 * Database Helper Functions for PostgreSQL/MySQL compatibility
 */

if (!function_exists('dbIfNull')) {
    /**
     * Returns COALESCE for PostgreSQL or IFNULL for MySQL
     */
    function dbIfNull(string $column, $default = 0): string
    {
        if (config('database.default') === 'pgsql') {
            return "COALESCE($column, $default)";
        }
        return "IFNULL($column, $default)";
    }
}

if (!function_exists('dbDateParts')) {
    /**
     * Get year, month, day parts from date column
     */
    function dbDateParts(string $column): string
    {
        if (config('database.default') === 'pgsql') {
            return "EXTRACT(YEAR FROM $column) as year, EXTRACT(MONTH FROM $column) as month, EXTRACT(DAY FROM $column) as day";
        }
        return "YEAR($column) year, MONTH($column) month, DAY($column) day";
    }
}

if (!function_exists('dbDatePartsWithDayName')) {
    /**
     * Get year, month, day, day_of_week parts from date column
     */
    function dbDatePartsWithDayName(string $column): string
    {
        if (config('database.default') === 'pgsql') {
            return "EXTRACT(YEAR FROM $column) as year, EXTRACT(MONTH FROM $column) as month, EXTRACT(DAY FROM $column) as day, TO_CHAR($column, 'Day') as day_of_week";
        }
        return "YEAR($column) year, MONTH($column) month, DAY($column) day, DAYNAME($column) day_of_week";
    }
}

if (!function_exists('dbDatePartsYearMonth')) {
    /**
     * Get year, month parts from date column
     */
    function dbDatePartsYearMonth(string $column): string
    {
        if (config('database.default') === 'pgsql') {
            return "EXTRACT(YEAR FROM $column) as year, EXTRACT(MONTH FROM $column) as month";
        }
        return "YEAR($column) year, MONTH($column) month";
    }
}

if (!function_exists('dbYear')) {
    /**
     * Extract year from date column
     */
    function dbYear(string $column): string
    {
        if (config('database.default') === 'pgsql') {
            return "EXTRACT(YEAR FROM $column)";
        }
        return "YEAR($column)";
    }
}

if (!function_exists('dbMonth')) {
    /**
     * Extract month from date column
     */
    function dbMonth(string $column): string
    {
        if (config('database.default') === 'pgsql') {
            return "EXTRACT(MONTH FROM $column)";
        }
        return "MONTH($column)";
    }
}

if (!function_exists('dbDay')) {
    /**
     * Extract day from date column
     */
    function dbDay(string $column): string
    {
        if (config('database.default') === 'pgsql') {
            return "EXTRACT(DAY FROM $column)";
        }
        return "DAY($column)";
    }
}

if (!function_exists('dbDateFormat')) {
    /**
     * Format date with cross-database compatibility
     * @param string $column Column name
     * @param string $format MySQL format string (%Y, %M, %W, %d, %D, %m)
     */
    function dbDateFormat(string $column, string $format): string
    {
        if (config('database.default') === 'pgsql') {
            // Convert MySQL format to PostgreSQL TO_CHAR format
            $pgFormat = str_replace(
                ['%Y', '%M', '%W', '%d', '%D', '%m', '%y'],
                ['YYYY', 'Month', 'Day', 'DD', 'DDth', 'MM', 'YY'],
                $format
            );
            return "TO_CHAR($column, '$pgFormat')";
        }
        return "DATE_FORMAT($column, '$format')";
    }
}

if (!function_exists('dbRand')) {
    /**
     * Random function for ordering
     */
    function dbRand(): string
    {
        if (config('database.default') === 'pgsql') {
            return 'RANDOM()';
        }
        return 'RAND()';
    }
}

if (!function_exists('dbGroupConcat')) {
    /**
     * Group concatenation
     */
    function dbGroupConcat(string $column, string $separator = ','): string
    {
        if (config('database.default') === 'pgsql') {
            return "STRING_AGG($column::text, '$separator')";
        }
        return "GROUP_CONCAT($column SEPARATOR '$separator')";
    }
}

if (!function_exists('isPgsql')) {
    /**
     * Check if using PostgreSQL
     */
    function isPgsql(): bool
    {
        return config('database.default') === 'pgsql';
    }
}

if (!function_exists('isMysql')) {
    /**
     * Check if using MySQL
     */
    function isMysql(): bool
    {
        return config('database.default') === 'mysql';
    }
}
