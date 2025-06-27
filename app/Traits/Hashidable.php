<?php

namespace App\Traits;

use Vinkla\Hashids\Facades\Hashids;

trait Hashidable
{
    /**
     * Get the value that should be used to route model binding.
     *
     * @return string
     */
    public function getRouteKey()
    {
        return Hashids::encode($this->getKey());
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        $id = $decoded[0] ?? null;

        return $id
            ? $this->where($field ?? $this->getRouteKeyName(), $id)->firstOrFail()
            : abort(404);
    }
}
