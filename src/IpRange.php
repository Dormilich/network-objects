<?php

namespace Dormilich\Http;

class IpRange extends Range implements \IteratorAggregate
{
    /**
     * Just because you can iterate over IPv6 ranges does not mean it's a good idea.
     * 
     * @see http://php.net/IteratorAggregate
     * @see http://php.net/Generator
     * @return Generator
     */
    public function getIterator()
    {
        $ip = $this->getFirstIP();
        $last = $this->getLastIP();

        do {
            yield $ip;
            $ip = $ip->next();
        } while ( ! $last->lt( $ip ) );
    }
}
