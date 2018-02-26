<?php

use Dormilich\Http\IP;
use Dormilich\Http\Network;
use Dormilich\Http\NetworkInterface;
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

    public function testCreateRangeFromNetwork()
    {
        $range = new Range( '192.168.49.3/29' );

        $this->assertSame( '8', $range->count(), 'count' );
        $this->assertSame( '192.168.49.0', (string) $range->getFirstIP() );
        $this->assertSame( '192.168.49.7', (string) $range->getLastIP() );
    }

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

    public function testGetNetworksForRange()
    {
        $range = new Range( '192.168.31.240 - 192.168.35.193' );
        $networks = $range->getNetworks();
        $cidrs = array_map( 'strval', $networks );

        $this->assertCount( 6, $networks );
        $this->assertContainsOnlyInstancesOf( NetworkInterface::class, $networks );

        $this->assertSame( '192.168.31.240/28', $cidrs[ 0 ] );
        $this->assertSame( '192.168.32.0/23',   $cidrs[ 1 ] );
        $this->assertSame( '192.168.34.0/24',   $cidrs[ 2 ] );
        $this->assertSame( '192.168.35.0/25',   $cidrs[ 3 ] );
        $this->assertSame( '192.168.35.128/26', $cidrs[ 4 ] );
        $this->assertSame( '192.168.35.192/31', $cidrs[ 5 ] );
    }

    public function testGetNetworksForNetworkRange()
    {
        $range = new Range( '192.168.49.3/29' );
        $networks = $range->getNetworks();

        $this->assertCount( 1, $networks );
        $this->assertSame( '192.168.49.0/29', (string) current( $networks ) );
    }

    public function testGetSpanNetwork()
    {
        $range = new Range( '192.168.31.240 - 192.168.35.193' );
        $span = $range->getSpanNetwork();

        $this->assertSame( '192.168.0.0/18', (string) $span );
    }

    /**
     * @dataProvider dataRangeContains
     */
    public function testRangeContains( $range, $find, $result )
    {
        $test = new Range( $range );

        $this->assertSame( $result, $test->contains( $find ) );
    }

    public function testRangeContainsFailsForDifferentIpVersions()
    {
        $range = new Range( '192.168.31.240 - 192.168.35.193' );

        $this->assertFalse( $range->contains( '::c0a8:23a1' ) ); // ~ 192.168.35.161
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

    public function testJson()
    {
        $range = new Range( '192.168.31.240 - 192.168.35.193' );

        $expected[ 'first' ] = '192.168.31.240';
        $expected[ 'last' ] = '192.168.35.193';
        $this->assertJsonStringEqualsJsonString( json_encode( $expected ), json_encode( $range ) );
    }
}
