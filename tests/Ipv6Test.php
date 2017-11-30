<?php

use Dormilich\Http\IP;
use PHPUnit\Framework\TestCase;

class Ipv6Test extends TestCase
{
    private function ip()
    {
        return new IP( '2001:0db8:85a3:08d3:1319:8a2e:0370:7347' );
    }

    public function testVersion()
    {
        $version = $this->ip()->getVersion();
        $this->assertSame( 6, $version );
    }

    public function testAddress()
    {
        $addr = (string) $this->ip();
        $this->assertSame( '2001:db8:85a3:8d3:1319:8a2e:370:7347', $addr );
    }

    public function testInAddr()
    {
        $inAddr = $this->ip()->inAddr();
        $this->assertSame( inet_pton( '2001:db8:85a3:8d3:1319:8a2e:370:7347' ), $inAddr );
    }

    // the following test do not directly assert the to...() methods,
    // but their reversability as I am not confident to determine the exact 
    // expected values by myself without the help of the code under test

    public function testBin()
    {
        $ip = $this->ip();
        $bin = $ip->toBin();
        $test = new IP( $bin );

        $this->assertNotRegExp( '/[^01]/', $bin, 'invalid string composition' );
        $this->assertSame( 128, strlen( $bin ), 'invalid string length' );
        $this->assertSame( $ip->inAddr(), $test->inAddr() );
    }

    public function testDec()
    {
        $ip = $this->ip();
        $dec = $ip->toDec();
        $test = new IP( $dec );

        $this->assertTrue( ctype_digit( $dec ), 'invalid string composition' );
        $this->assertLessThan( 40, strlen( $dec ), 'invalid string length' );
        $this->assertSame( $ip->inAddr(), $test->inAddr() );
    }

    public function testHex()
    {
        $ip = $this->ip();
        $hex = $ip->toHex();
        $test = new IP( $hex );

        $this->assertTrue( ctype_xdigit( $hex ), 'invalid string composition' );
        $this->assertSame( 32, strlen( $hex ), 'invalid string length' );
        $this->assertSame( $ip->inAddr(), $test->inAddr() );
    }
}
