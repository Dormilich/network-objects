<?php

namespace Dormilich\Http;

class Range implements RangeInterface
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

        if ( is_string( $input ) and strpos( $input, '/' ) ) {
            #
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
     * @return NetworkInterface[]
     */
    public function getNetworks()
    {
        return [];
    }
}
