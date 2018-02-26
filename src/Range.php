<?php

namespace Dormilich\Http;

class Range implements RangeInterface, \JsonSerializable
{
    /**
     * @var IpInterface First IP of the range.
     */
    private $first;

    /**
     * @var IpInterface Last IP of the range.
     */
    private $last;

    /**
     * @see `Range::parse()`
     * @param mixed $input An IP (range) expression.
     * @param type|null $ip An IP expression.
     * @return self
     */
    public function __construct( $input, $ip = null )
    {
        $this->parse( $input, $ip );
    }

    /**
     * 
     * 
     * @param string|RangeInterface|IpInterface $input An IP (range) expression.
     * @param string|IpInterface|null $ip An IP expression.
     * @return void
     */
    private function parse( $input, $ip )
    {
        if ( $input instanceof RangeInterface ) {
            return $this->setIPs( $input->getFirstIP(), $input->getLastIP() );
        }

        if ( $ip ) {
            return $this->setIPs( $input, $ip );
        }

        if ( is_object( $input ) and method_exists( $input, '__toString' ) ) {
            $input = (string) $input;
        }

        if ( is_string( $input ) and strpos( $input, '-' ) ) {
            list( $first, $last ) = explode( '-', $input, 2 );
            return $this->setIPs( trim( $first ), trim( $last ) );
        }

        if ( is_string( $input ) and preg_match( '#^[0-9a-f:.]+/\d+$#i', $input ) ) {
            $network = new Network( $input );
            return $this->setIPs( $network->getFirstIP(), $network->getLastIP() );
        }

        $this->setIPs( $input, $input );
    }

    /**
     * Set the class properties. If the given IPs are in the wrong order, fix 
     * that as well.
     * 
     * @param string|IpInterface $first First IP of the range.
     * @param string|IpInterface $last Last IP of the range.
     * @return void
     */
    private function setIPs( $first, $last )
    {
        $first = new IP( $first );
        $last  = new IP( $last );

        if ( $first->getVersion() !== $last->getVersion() ) {
            $msg = 'The provided IP addresses do not have the same version.';
            throw new \RuntimeException( $msg );
        }

        if ( strcmp( $first->inAddr(), $last->inAddr() ) > 0 ) {
            list( $last, $first ) = [ $first, $last ];
        }

        $this->first = $first;
        $this->last  = $last;
    }

    /**
     * Returns the textual representation of the IP range.
     * 
     * @return string
     */
    public function __toString()
    {
        return sprintf( '%s - %s', $this->first, $this->last );
    }

    /**
     * Get the start IP of the range.
     * 
     * @return IpInterface
     */
    public function getFirstIP()
    {
        return clone $this->first;
    }

    /**
     * Get the end IP of the range.
     * 
     * @return IpInterface
     */
    public function getLastIP()
    {
        return clone $this->last;
    }

    /**
     * Returns the number of IPs in the range.
     * 
     * @return string
     */
    public function count()
    {
        return bcadd( bcsub( $this->last->toDec(), $this->first->toDec() ), 1 );
    }

    /**
     * Test if an IP/Range/Network is contained in this range.
     * 
     * @see `Range::parse()`
     * @param mixed $input Any valid IP or range expression.
     * @return boolean
     */
    public function contains( $input )
    {
        $range = new self( $input );

        $first = $range->getFirstIP();
        $last  = $range->getLastIP();

        return strcmp( $first->inAddr(), $this->first->inAddr() ) >= 0
            && strcmp( $last->inAddr(), $this->last->inAddr() ) <= 0;
    }

    /**
     * Get a list of networks that cover the IP range.
     * 
     * @return Network[]
     */
    public function getNetworks()
    {
        $networks = [];
        $spanPrefix = $this->getSpanPrefix();
        $end = $this->last->inAddr();

        $ip = $this->getFirstIP();

        do {
            $prefix = max( $spanPrefix, $this->getMaxIpPrefix( $ip ) );

            do {
                $last = $this->getLastNetworkIp( $ip, $prefix );
                $continue = ( strcmp( $last->inAddr(), $end ) > 0 );
                $prefix += (int) $continue;
            } while ( $continue );

            $networks[] = new Network( $ip . '/' . $prefix );

            $ip = $last->next();

        } while ( strcmp( $last->inAddr(), $end ) < 0 );


        return $networks;
    }

    /**
     * Stripped down version of `new Network(IP/prefix)->getLastIP()` (omitting 
     * the validation steps as the input is always valid).
     * 
     * @param IpInterface $ip 
     * @param integer $prefix 
     * @return IpInterface
     */
    private function getLastNetworkIp( IpInterface $ip, $prefix )
    {
        $max = 8 * strlen( $ip->inAddr() );
        $mask = str_pad( str_repeat( '1', $prefix ), $max, '0', STR_PAD_RIGHT );

        $last = $ip->inAddr() | ~(new IP( $mask ))->inAddr();

        return new IP( $last );
    }

    /**
     * Get the smallest possible prefix length (netmask) for the given IP.
     * 
     * @param IpInterface $ip 
     * @return integer
     */
    private function getMaxIpPrefix( IpInterface $ip )
    {
        $bin = $ip->toBin();

        preg_match( '/0*$/', $bin, $match );

        return strlen( $bin ) - strlen( $match[ 0 ] );
    }

    /**
     * Get the prefix length of the smallest network that contains all IPs of 
     * this IP range.
     * 
     * @return integer
     */
    private function getSpanPrefix()
    {
        $xor = inet_ntop( $this->first->inAddr() ^ $this->last->inAddr() );
        $bin = (new IP( $xor ))->toBin();

        preg_match( '/^0*/', $bin, $match );

        return strlen( $match[ 0 ] );
    }

    /**
     * Get the smallest network that contains all IPs of this IP range.
     * 
     * @return Network
     */
    public function getSpanNetwork()
    {
        $cidr = sprintf( '%s/%d', $this->first, $this->getSpanPrefix() );

        return new Network( $cidr );
    }

    /**
     * @see http://php.net/JsonSerializable
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars( $this );
    }
}
