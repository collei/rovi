<?php

/**
 * Retrieves data from nested structures like arrays and data objects
 * (such those extracted from JSON). Accepts '.' notation.
 * 
 * @param mixed $data
 * @param string $name
 * @param mixed $default = null
 * @return mixed
 */
function get_nested_data_from($data, string $name, $default = null)
{
	list($here, $further) = (stripos($name,'.') !== false)
		? explode('.', $name, 2)
		: array($name, null);
	
	if (is_numeric($here)) {
		$here = (int) (float) $here;
	}
	
	if (empty($further)) {
		if (is_array($data) && array_key_exists($here, $data)) {
			return $data[$here] ?? $default ?? null;
		}
		
		if (is_object($data) && property_exists($data, $here)) {
			return $data->$here ?? $default ?? null;
		}
		
		return $default ?? null;
	}
	
	if (is_array($data) && array_key_exists($here, $data)) {
		return get_nested_data_from($data[$here], $further);
	}
	
	if (is_object($data) && property_exists($data, $here)) {
		return get_nested_data_from($data->$here, $further);
	}

	return $default ?? null;	
}
