<?php

namespace Dormilich\Http;

class IP implements IpInterface
{
    /**
     * @return string The (binary) packed in_addr representation of the IP address.
     */
    private $in_addr;

    /**
     * Parse input representing an IP.
     * 
     * @param mixed $input 
     * @return self
     */
    public function __construct( $input )
    {
        $this->in_addr = $this->getInAddr( $input );
    }

    /**
     * Convert IP address / binary / decimal / hexadecimal into in_addr format. 
     * 
     * @param mixed $input IP value.
     * @return string
     * @throws RuntimeException IP is not a valid IPv4 representation.
     */
    private function getInAddr( $input )
    {
        if ( $input instanceof IpInterface ) {
            return $input->inAddr();
        }

        if ( is_object( $input ) and method_exists( $input, '__toString' ) ) {
            $input = (string) $input;
        }
        // unpad padded IPv4 (used by ARIN RegRWS)
        if ( is_string( $input ) and preg_match( '/^\d+(\.\d+){3}$/', $input ) ) {
            $input = implode( '.', sscanf( $input, '%d.%d.%d.%d' ) );
        }
        // IP address
        if ( $ip = filter_var( $input , FILTER_VALIDATE_IP ) ) {
            return inet_pton( $ip );
        }
        // 32bit or 128bit (binary) string
        if ( is_scalar( $input ) and preg_match( '/^(?:0b)?([01]{32}(?:[01]{96})?)$/', $input, $match ) ) {
            return $this->fromBin( $match[ 1 ] );
        }
        // 32bit or 128bit hex string
        if ( is_scalar( $input ) and preg_match( '/^(?:0x)?([0-9A-F]{8}(?:[0-9A-F]{24})?)$/i', $input, $match ) ) {
            return pack( 'H*', $match[ 1 ] );
        }
        // 32bit integer
        if ( is_int( $input ) and $input >= 0 and $input < ( 1 << 32 ) ) {
            return inet_pton( long2ip( $input ) );
        }
        // decimal string (stops after 128 bit)
        if ( ctype_digit( $input ) and strlen( $input ) < 40 ) {
            return $this->fromDec( $input );
        }
        // usually emits an E_WARNING if the input is invalid
        if ( @inet_ntop( $input ) !== false ) {
            return $input;
        }

        $value = is_scalar( $input ) ? sprintf( "'%s' ", $input ) : '';
        $type = is_object( $input ) ? get_class( $input ) : gettype( $input );
        $msg = "Input {$value}of type [$type] could not be converted into an IP object.";

        throw new \RuntimeException( $msg );
    }

    /**
     * Parse in_addr representation from a valid binary-formatted input.
     * 
     * @param string $value 
     * @return string
     */
    protected function fromBin( $value )
    {
        $dec = array_map( 'bindec', str_split( $value, 8 ) );

        return array_reduce( $dec, function ( $addr, $char ) {
            return $addr . pack( 'C*', $char );
        }, '' );
    }

    /**
     * Parse in_addr representation from an IPv6 number.
     * 
     * @param string $value 
     * @return string
     */
    protected function fromDec( $value )
    {
        $binary = [];
        $octets = 16;

        while ( $octets-- ) {
            $binary[] = bcmod( $value, 256 );
            $value = bcdiv( $value, 256, 0 );
        }

        $binary[] = 'C*';

        return call_user_func_array( 'pack', array_reverse( $binary ) );
    }

    /**
     * Helper function to get the bytes.
     * 
     * @return integer[]
     */
    private function bytes()
    {
        // note: 1-indexed array!
        return unpack( 'C*', $this->in_addr );
    }

    /**
     * Returns the textual representation of the IP address.
     * 
     * @return string
     */
    public function __toString()
    {
        return inet_ntop( $this->in_addr );
    }

    /**
     * Get the version number of the IP address.
     * 
     * @return integer
     */
    public function getVersion()
    {
        return strlen( $this->in_addr ) === 4 ? 4 : 6;
    }

    /**
     * The (binary) packed in_addr representation of the IP address.
     * 
     * @return string
     */
    public function inAddr()
    {
        return $this->in_addr;
    }

    /**
     * The string representation in binary format.
     * 
     * @return string
     */
    public function toBin()
    {
        $bytes = $this->bytes();

        return array_reduce( $bytes, function ( $bin, $int ) {
            return $bin . str_pad( decbin( $int ), 8, '0', STR_PAD_LEFT );
        }, '' );
    }

    /**
     * The string representation in decimal format. IPv4 numbers are returned as 
     * integers to be able to differentiate them from 32 bit IPv6 numbers 
     * (e.g. `::1`).
     * 
     * @return string|integer
     */
    public function toDec()
    {
        // much faster than bcmath
        if ( 4 === $this->getVersion() ) {
            return ip2long( inet_ntop( $this->in_addr ) );
        }

        $bytes = $this->bytes();
        $dec = 0;

        for ( $i = count( $bytes ); $i--; ) {
            $int = array_shift( $bytes );
            $dec = bcadd( $dec, bcmul( $int, bcpow( 256, $i ) ) );
        }

        return $dec;
    }

    /**
     * The string representation in hexadecimal format.
     * 
     * @return string
     */
    public function toHex()
    {
        return bin2hex( $this->in_addr );
    }

    /**
     * Convenience function to test if this IPs is equal to the test IP.
     * 
     * @param IpInterface $ip 
     * @return boolean
     */
    public function is( IpInterface $ip )
    {
        return strcmp( $this->in_addr, $ip->inAddr() ) === 0;
    }

    /**
     * Convenience function to test if this IPs is greater than the test IP.
     * 
     * @param IpInterface $ip 
     * @return boolean
     */
    public function gt( IpInterface $ip )
    {
        return strcmp( $this->in_addr, $ip->inAddr() ) > 0;
    }

    /**
      * Convenience function to test if this IPs is less than the test IP.
     * 
     * @param IpInterface $ip 
     * @return boolean
     */
    public function lt( IpInterface $ip )
    {
        return strcmp( $this->in_addr, $ip->inAddr() ) < 0;
    }

    /**
     * Get the following IP address.
     * 
     * @return IpInterface
     */
    public function next()
    {
        $bytes = $this->bytes();

        for ( $i = count( $bytes ); $i > 0; $i-- ) {
            if ( $bytes[ $i ] === 255 ) {
                $bytes[ $i ] = 0;
            }
            else {
                $bytes[ $i ]++;
                break;
            }
        }

        array_unshift( $bytes, 'C*' );
        $binary = call_user_func_array( 'pack', $bytes );

        return new static( $binary );
    }

    /**
     * Get the preceding IP address.
     * 
     * @return IpInterface
     */
    public function prev()
    {
        $bytes = $this->bytes();

        for ( $i = count( $bytes ); $i > 0; $i-- ) {
            if ( $bytes[ $i ] === 0 ) {
                $bytes[ $i ] = 255;
            }
            else {
                $bytes[ $i ]--;
                break;
            }
        }

        array_unshift( $bytes, 'C*' );
        $binary = call_user_func_array( 'pack', $bytes );

        return new static( $binary );
    }
}
