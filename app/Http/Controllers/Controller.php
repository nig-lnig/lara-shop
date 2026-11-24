<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Reads list filters from the URL (?q=...&status=...), keeping only text
     * values. Used by the admin lists AND their CSV exports, so an export
     * always contains exactly the rows shown on screen.
     *
     * @param  string[]  $keys
     * @return array<string, string>
     */
    protected function filters(Request $request, array $keys): array
    {
        $filters = [];
        foreach ($keys as $key) {
            $value = $request->query($key);
            if (is_string($value) && trim($value) !== '') {
                $filters[$key] = mb_substr(trim($value), 0, 100);
            }
        }

        return $filters;
    }
}
