<?php

namespace Core\Interfaces;

interface ArraySerializableInterface {
    
    /**
     * Exchange internal values from provided array
     *
     * @param  array $array
     * @return void
     */
    public function exchangeArray(array $array);

    /**
     * Return an array representation of the object
     *
     * @return array
     */
    public function getArrayCopy();
    
}
