<?php

namespace ArtemsWay\CommerceML\Models;

abstract class Model
{
    /**
     * Primary key.
     *
     * @var string $id
     */
    public $id;

    /**
     * Get object value.
     *
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        return null;
    }

    /**
     * Set object value.
     *
     * @param string $key
     * @param mixed $val
     * @return bool
     */
    public function set($key, $val)
    {
        if (property_exists($this, $key)) {
            $this->{$key} = $val;
            return true;
        }

        return false;
    }
}
