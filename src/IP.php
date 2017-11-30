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
        if ( preg_match( '/^(?:0b)?([01]{32}(?:[01]{96})?)$/', $input, $match ) ) {
            return $this->fromBin( $match[ 1 ] );
        }
        // 32bit or 128bit hex string
        if ( preg_match( '/^(?:0x)?([0-9A-F]{8}(?:[0-9A-F]{24})?)$/i', $input, $match ) ) {
            return pack( 'H*', $match[ 1 ] );
        }
        // < 32bit integer
        $options = [ 'options' => [ 'min_range' => 0, 'max_range' => ( 2 << 32 ) - 1 ] ];
        if ( $num = filter_var( $input , FILTER_VALIDATE_INT, $options ) ) {
            return inet_pton( long2ip( $value ) );
        }
        // decimal string (might overflow)
        if ( ctype_digit( $input ) and strlen( $input ) < 40 ) {
            return $this->fromDec( $input );
        }

        $value = is_scalar( $input ) ? $input . ' ' : '';
        $type = is_object( $input ) ? get_class( $input ) : gettype( $input );
        $msg = "Input {$value}of type [$type] could not be converted into an IPv4 object.";

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
        return array_reduce( unpack( 'C*', $this->in_addr ), function ( $bin, $char ) {
            return $bin . str_pad( decbin( $char ), 8, '0', STR_PAD_LEFT );
        }, '' );
    }

    /**
     * The string representation in decimal format.
     * 
     * @return string
     */
    protected function toDec()
    {
        $octets = strlen( $this->in_addr );

        foreach ( unpack( 'C*', $this->in_addr ) as $char ) {
            $dec = bcadd( $dec, bcmul( $char, bcpow( 256, --$octet ) ) );
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
}
