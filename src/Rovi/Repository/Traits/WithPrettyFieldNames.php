<?php
namespace Rovi\Repository\Traits;

/**
 * Add abilities to transform field names.
 */
trait WithPrettyFieldNames
{
    /**
     * Converts from camelCase to snake_case.
     * 
     * @param string $camelCase
     * @return string
     */
    protected function fromCamelCaseField(string $camelCase)
    {
        $result = strtolower(preg_replace('/([A-Z]?[a-z0-9]*)/', '_\1', $camelCase));
        
        return trim($result, '_');
    }

    /**
     * Converts from snake_case to camelCase. 
     * 
     * @param string $snakeCase
     * @return string
     */
    protected function toCamelCaseField(string $snakeCase)
    {
        $pieces = array_map('ucfirst', explode('_', $snakeCase));

        $pieces[0] = strtolower($pieces[0]);

        return implode('', $pieces);
    }
}