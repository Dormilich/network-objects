<?php

namespace Dormilich\Http;

class Network implements NetworkInterface, RangeInterface
{
    /**
     * @var IpInterface The network address of the network.
     */
    private $network;

    /**
     * @var IpInterface The netmask of the network.
     */
    private $netmask;

    /**
     * @see `Network::parse()`
     * @param mixed $input 
     * @return self
     */
    public function __construct( $input )
    {
        $this->parse( $input );
    }

    /**
     * Set the network and netmask properties.
     * 
     * @param IpInterface $network Network address.
     * @param IpInterface $netmask Netmask address.
     * @return void
     */
    private function setNetwork( IpInterface $network, IpInterface $netmask )
    {
        $this->network = $network;
        $this->netmask = $netmask;
    }

    /**
     * Convert a CIDR into a network object. If an IP is given, imply a 
     * one-address network (/32 or /128).
     * 
     * @param NetworkInterface|string $input Network object, CIDR, or IP address.
     * @return void
     * @throws RuntimeException Input (or a part thereof) failed to parse as IP.
     */
    private function parse( $input )
    {
        if ( $input instanceof NetworkInterface ) {
            return $this->setNetwork( $input->getNetwork(), $input->getNetmask() );
        }

        if ( is_object( $input ) and method_exists( $input, '__toString' ) ) {
            $input = (string) $input;
        }

        if ( is_string( $input ) and preg_match( '#^[0-9a-f:.]+/\d+$#i', $input ) ) {
            return $this->parseCIDR( $input );
        }

        $ip = new IP( $input );
        $mask = $this->netmaskFromPrefix( $ip, $this->getMaxPrefixLength( $ip ) );

        $this->setNetwork( $ip, $mask );
    }

    /**
     * Parse a CIDR string and determine it's network address and netmask.
     * 
     * @param string $cidr CIDR string.
     * @return void
     * @throws RuntimeException Input (or a part thereof) failed to parse as IP.
     */
    private function parseCIDR( $cidr )
    {
        list( $ip, $prefix ) = explode( '/', $cidr, 2 );
        $ip = new IP( $ip );

        $netmask = $this->netmaskFromPrefix( $ip, $prefix );
        $network_addr = $ip->inAddr() & $netmask->inAddr();
        $network = new IP( inet_ntop( $network_addr ) );

        $this->setNetwork( $network, $netmask );
    }

    /**
     * Get the maximum prefix length for the current type of IP (IPv4/IPv6).
     * 
     * @param IpInterface $ip An IP address.
     * @return integer
     */
    protected function getMaxPrefixLength( IpInterface $ip )
    {
        return 8 * strlen( $ip->inAddr() );
    }

    /**
     * Determine the netmask from the prefix length and network type (IPv4/IPv6).
     * 
     * @param IpInterface $ip An IP object from the current network.
     * @param integer $prefix The prefix length.
     * @return IpInterface
     * @throws RuntimeException Invalid prefix length for the IP version.
     */
    protected function netmaskFromPrefix( IpInterface $ip, $prefix )
    {
        $max = $this->getMaxPrefixLength( $ip );

        if ( $prefix <= $max ) {
            $bin = str_pad( str_repeat( '1', $prefix ), $max, '0', STR_PAD_RIGHT );
            return new IP( $bin );
        }

        $msg = "Prefix length $prefix exceeds the maximum value of $max.";
        throw new \RuntimeException( $msg );
    }

    /**
     * Returns a textual representation of the network.
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getCIDR();
    }

    /**
     * Returns the number of IPs in the network.
     * 
     * @return string
     */
    public function count()
    {
        $shift = $this->getMaxPrefixLength( $this->network ) - $this->getPrefixLength();

        if ( $this->network->getVersion() === 4 ) {
            return (string) ( 1 << $shift );
        }

        return bcpow( 2, $shift );
    }

    /**
     * Returns the network address of the network.
     * 
     * @return IpInterface
     */
    public function getNetwork()
    {
        return clone $this->network;
    }

    /**
     * Returns the broadcast address of the network.
     * 
     * @return IpInterface
     */
    public function getBroadcast()
    {
        $last_addr = $this->network->inAddr() | ~$this->netmask->inAddr();

        return new IP( inet_ntop( $last_addr ) );
    }

    /**
     * Returns the CIDR of the network.
     * 
     * @return string
     */
    public function getCIDR()
    {
        return sprintf( '%s/%s', $this->network, $this->getPrefixLength() );
    }

    /**
     * Returns the prefix length of the network.
     * 
     * @return integer
     */
    public function getPrefixLength()
    {
        return strlen( rtrim( $this->netmask->toBin(), '0' ) );
    }

    /**
     * Returns the netmask of the network.
     * 
     * @return IpInterface
     */
    public function getNetmask()
    {
        return clone $this->netmask;
    }

    /**
     * Returns a list of usable (host) IPs.
     * 
     * @return RangeInterface
     */
    public function getHosts()
    {
        $first = $this->getFirstIP();
        $last  = $this->getLastIP();

        if ( $this->network->getVersion() === 4 ) {
            if ( $this->count() > 2 ) {
                $first = $first->next();
                $last  = $last->prev();
            }
            else {
                $first = $last; # /31 & /32
            }
        }

        return new Range( $first, $last );
    }

    /**
     * Get the start IP of the range.
     * 
     * @return IpInterface
     */
    public function getFirstIP()
    {
        return $this->getNetwork();
    }

    /**
     * Get the end IP of the range.
     * 
     * @return IpInterface
     */
    public function getLastIP()
    {
        return $this->getBroadcast();
    }

    /**
     * Get a list of networks that cover the IP range.
     * 
     * @return NetworkInterface[]
     */
    public function getNetworks()
    {
        return [ clone $this ];
    }
}
