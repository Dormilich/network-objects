<?php

namespace Dormilich\Http;

class NetRange extends Range implements \IteratorAggregate
{
    /**
     * @see http://php.net/IteratorAggregate
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator( $this->getNetworks() );
    }
}
