<?php

use Dormilich\Http\IP;
use PHPUnit\Framework\TestCase;

class IPv4Test extends TestCase
{
    private function ip()
    {
        return new IP( '192.168.65.174' );
    }

    public function testVersion()
    {
        $version = $this->ip()->getVersion();
        $this->assertSame( 4, $version );
    }

    public function testAddress()
    {
        $addr = (string) $this->ip();
        $this->assertSame( '192.168.65.174', $addr );
    }

    public function testInAddr()
    {
        $inAddr = $this->ip()->inAddr();
        $this->assertSame( inet_pton( '192.168.65.174' ), $inAddr );
    }

    public function testBin()
    {
        $ip = $this->ip();
        $bin = $ip->toBin();
        $test = new IP( $bin );

        $this->assertSame( '192.168.65.174', long2ip( bindec( $bin ) ) );
        $this->assertSame( $ip->inAddr(), $test->inAddr() );
    }

    public function testDec()
    {
        $ip = $this->ip();
        $dec = $ip->toDec();
        $test = new IP( $dec );

        $this->assertSame( '192.168.65.174', long2ip( (int) $dec ) );
        $this->assertSame( $ip->inAddr(), $test->inAddr() );
    }

    public function testHex()
    {
        $ip = $this->ip();
        $hex = $ip->toHex();
        $test = new IP( $hex );

        $this->assertSame( '192.168.65.174', long2ip( hexdec( $hex ) ) );
        $this->assertSame( $ip->inAddr(), $test->inAddr() );
    }
}
