<?php

use Dormilich\Http\IP;
use PHPUnit\Framework\TestCase;

class IpSetupTest extends TestCase
{
    public function testPaddedIPv4()
    {
        $ip = new IP( '127.000.000.001' );

        $this->assertSame( '127.0.0.1', (string) $ip );
    }

    public function testIpObject()
    {
        $ip1 = new IP( '192.168.65.174' );
        $ip2 = new IP( $ip1 );

        $this->assertNotSame( $ip1, $ip2 );
        $this->assertSame( $ip1->inAddr(), $ip2->inAddr() );
    }

    public function testStringifiableObject()
    {
        $obj = $this->createMock( 'Exception' );
        $obj->method( '__toString' )->willReturn( '192.168.65.174' );

        $ip = new IP( $obj );

        $this->assertSame( '192.168.65.174', (string) $ip );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Input of type [stdClass] could not be converted into an IP object.
     */
    public function testInvalidObjectFails()
    {
        new IP( new stdClass );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Input '-3.14' of type [double] could not be converted into an IP object.
     */
    public function testInvalidScalarFails()
    {
        new IP( -3.14 );
    }
}