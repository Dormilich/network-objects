<?php

use Dormilich\Http\IP;
use Dormilich\Http\Range;
use PHPUnit\Framework\TestCase;

class RangeTest extends TestCase
{
    public function testCreateRangeFromString()
    {
        $range = new Range( '192.168.31.240 - 192.168.35.193' );

        $this->assertSame( '192.168.31.240', (string) $range->getFirstIP() );
        $this->assertSame( '192.168.35.193', (string) $range->getLastIP() );
    }

    public function testCreateRangeFromRange()
    {
        $src = new Range( '192.168.31.240 - 192.168.35.193' );
        $range = new Range( $src );

        $this->assertNotSame( $src, $range );
        $this->assertSame( '192.168.31.240', (string) $range->getFirstIP() );
        $this->assertSame( '192.168.35.193', (string) $range->getLastIP() );
    }

    public function testCreateRangeFromIPs()
    {
        $range = new Range( '192.168.31.240', '192.168.35.193' );

        $this->assertSame( '192.168.31.240', (string) $range->getFirstIP() );
        $this->assertSame( '192.168.35.193', (string) $range->getLastIP() );
    }

    public function testCreateRangeFromIPObjects()
    {
        $range = new Range( new IP( '192.168.31.240' ), new IP( '192.168.35.193' ) );

        $this->assertSame( '192.168.31.240', (string) $range->getFirstIP() );
        $this->assertSame( '192.168.35.193', (string) $range->getLastIP() );
    }

    public function testCreateSingleIpRange()
    {
        $range = new Range( '192.168.31.193' );

        $this->assertSame( '192.168.31.193', (string) $range->getFirstIP() );
        $this->assertSame( '192.168.31.193', (string) $range->getLastIP() );
    }

    #public function testCreateRangeFromNetwork()

    public function testCreateRangeFromStringifiableObject()
    {
        $obj = $this->createMock( 'Exception' );
        $obj->method( '__toString' )->willReturn( '192.168.31.240 - 192.168.35.193' );

        $range = new Range( $obj );

        $this->assertSame( '192.168.31.240', (string) $range->getFirstIP() );
        $this->assertSame( '192.168.35.193', (string) $range->getLastIP() );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The provided IP addresses do not have the same version.
     */
    public function testCreateWithDifferentIPVersionFails()
    {
        new Range( '192.168.31.240', '::c0a8:23c1' );
    }

    public function testRangeOrdersIPs()
    {
        $range = new Range( '192.168.35.193 - 192.168.31.240' );

        $this->assertSame( '192.168.31.240', (string) $range->getFirstIP() );
        $this->assertSame( '192.168.35.193', (string) $range->getLastIP() );
    }

    public function testRangeToString()
    {
        $range = new Range( '192.168.31.240', '192.168.35.193' );

        $this->assertSame( '192.168.31.240 - 192.168.35.193', (string) $range );
    }

    public function testCountRangeIPv4()
    {
        $range = new Range( '192.168.31.240 - 192.168.35.193' );

        $this->assertSame( '978', $range->count() );
    }

    public function testCountRangeIPv6()
    {
        $range = new Range( '::3bd4 - ::d949' );

        $this->assertSame( '40310', $range->count() );
    }

    #public function testgetNetworksForRange()

    /**
     * @dataProvider dataRangeContains
     */
    public function testRangeContains( $range, $find, $result )
    {
        $test = new Range( $range );

        $this->assertSame( $result, $test->contains( $find ) );
    }

    public function dataRangeContains()
    {
        return [
            [ '192.168.31.240 - 192.168.35.193', '192.168.32.91', true ],
            [ '192.168.31.240 - 192.168.35.193', '192.168.91.32', false ],
            [ '192.168.31.240 - 192.168.35.193', '192.168.32.94 - 192.168.33.43', true ],
            [ '192.168.31.240 - 192.168.35.193', '192.168.193.31 - 192.168.240.35', false ],
            [ '192.168.31.240 - 192.168.35.193', '192.168.32.94 - 192.168.46.12', false ],
            [ '192.168.31.240 - 192.168.35.193', '192.168.29.56 - 192.168.34.13', false ],
        ];
    }

    public function testRangeContainsFailsForDifferentIpVersions()
    {
        $range = new Range( '192.168.31.240 - 192.168.35.193' );

        $this->assertFalse( $range->contains( '::c0a8:23a1' ) ); // ~ 192.168.35.161
    }
}
