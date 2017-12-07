<?php

use Dormilich\Http\IP;
use Dormilich\Http\Network;
use Dormilich\Http\Range;
use PHPUnit\Framework\TestCase;

class NetworkTest extends TestCase
{
    public function testCreateIPv4NetworkFromCIDR()
    {
        $net = new Network( '192.168.49.3/29' );

        $this->assertSame( '8', $net->count(), 'count' );
        $this->assertSame( 29, $net->getPrefixLength(), 'prefix' );
        $this->assertSame( '192.168.49.0/29', $net->getCIDR(), 'cidr' );
        $this->assertSame( '192.168.49.0', (string) $net->getNetwork(), 'network' );
        $this->assertSame( '192.168.49.7', (string) $net->getBroadcast(), 'broadcast' );
        $this->assertSame( '255.255.255.248', (string) $net->getNetmask(), 'netmask' );
    }

    public function testCreateIPv6NetworkFromCIDR()
    {
        $net = new Network( '2001:0db8:85a3:08d3:1319:8a2e:0370:7347/64' );

        $this->assertSame( '18446744073709551616', $net->count(), 'count' );
        $this->assertSame( 64, $net->getPrefixLength(), 'prefix' );
        $this->assertSame( '2001:db8:85a3:8d3::/64', $net->getCIDR(), 'cidr' );
        $this->assertSame( '2001:db8:85a3:8d3::', (string) $net->getNetwork(), 'network' );
        $this->assertSame( '2001:db8:85a3:8d3:ffff:ffff:ffff:ffff', (string) $net->getBroadcast(), 'broadcast' );
        $this->assertSame( 'ffff:ffff:ffff:ffff::', (string) $net->getNetmask(), 'netmask' );
    }

    public function testCreateNetworkFromIPv4()
    {
        $net = new Network( '192.168.2.1' );

        $this->assertSame( '1', $net->count(), 'count' );
        $this->assertSame( 32, $net->getPrefixLength(), 'prefix' );
        $this->assertSame( '192.168.2.1/32', $net->getCIDR(), 'cidr' );
        $this->assertSame( '192.168.2.1', (string) $net->getNetwork(), 'network' );
        $this->assertSame( '192.168.2.1', (string) $net->getBroadcast(), 'broadcast' );
        $this->assertSame( '255.255.255.255', (string) $net->getNetmask(), 'netmask' );
    }

    public function testCreateNetworkFromIPv6()
    {
        $net = new Network( '2001:0db8:85a3:08d3:1319:8a2e:0370:7347' );

        $this->assertSame( '1', $net->count(), 'count' );
        $this->assertSame( 128, $net->getPrefixLength(), 'prefix' );
        $this->assertSame( '2001:db8:85a3:8d3:1319:8a2e:370:7347/128', $net->getCIDR(), 'cidr' );
        $this->assertSame( '2001:db8:85a3:8d3:1319:8a2e:370:7347', (string) $net->getNetwork(), 'network' );
        $this->assertSame( '2001:db8:85a3:8d3:1319:8a2e:370:7347', (string) $net->getBroadcast(), 'broadcast' );
        $this->assertSame( 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff', (string) $net->getNetmask(), 'netmask' );
    }

    public function testCreatePeerNetwork()
    {
        $net = new Network( '192.168.2.1/31' );

        $this->assertSame( '2', $net->count(), 'count' );
        $this->assertSame( 31, $net->getPrefixLength(), 'prefix' );
        $this->assertSame( '192.168.2.0/31', $net->getCIDR(), 'cidr' );
        $this->assertSame( '192.168.2.0', (string) $net->getNetwork(), 'network' );
        $this->assertSame( '192.168.2.1', (string) $net->getBroadcast(), 'broadcast' );
        $this->assertSame( '255.255.255.254', (string) $net->getNetmask(), 'netmask' );
    }

    public function testCreateNetworkFromNetworkObject()
    {
        $src = new Network( '192.168.49.3/29' );
        $net = new Network( $src );

        $this->assertSame( '192.168.49.0/29', $net->getCIDR() );
    }

    public function testCreateNetworkFromStringifiableObject()
    {
        $obj = $this->createMock( 'Exception' );
        $obj->method( '__toString' )->willReturn( '192.168.49.3/29' );

        $net = new Network( $obj );

        $this->assertSame( '192.168.49.0/29', $net->getCIDR() );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Input '192.168.31.240 - 192.168.35.193' of type [string] could not be converted into an IP object.
     */
    public function testCreateNetworkFromRangeFails()
    {
        new Network( '192.168.31.240 - 192.168.35.193' );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Prefix length 64 exceeds the maximum value of 32.
     */
    public function testCreateNetworkFromInvalidCidrFails()
    {
        new Network( '192.168.49.3/64' );
    }

    // since Network implements RangeInterface
    public function testGetNetworks()
    {
        $net = new Network( '192.168.49.3/29' );
        $list = $net->getNetworks();

        $this->assertCount( 1, $list );
        $this->assertSame( '192.168.49.0/29', (string) current( $list ) );
    }

    public function testGetHostsForPeerNetwork()
    {
        $net = new Network( '192.168.49.6/31' );
        $hosts = $net->getHosts();

        $this->assertSame( '1', $hosts->count() );
        $this->assertSame( '192.168.49.7', (string) $hosts->getFirstIP() );
        $this->assertSame( '192.168.49.7', (string) $hosts->getlastIP() );
    }

    public function testGetIPv4Hosts()
    {
        $net = new Network( '192.168.49.3/29' );
        $hosts = $net->getHosts();

        $this->assertInstanceOf( Range::class, $hosts );
        $this->assertSame( '6', $hosts->count() );
        $this->assertSame( '192.168.49.1', (string) $hosts->getFirstIP() );
        $this->assertSame( '192.168.49.6', (string) $hosts->getlastIP() );
    }

    public function testGetIPv6Hosts()
    {
        $net = new Network( '2001:db8:85a3:8d3::/64' );
        $hosts = $net->getHosts();

        $this->assertInstanceOf( Range::class, $hosts );
        $this->assertSame( '18446744073709551616', $hosts->count() );
        $this->assertSame( '2001:db8:85a3:8d3::', (string) $hosts->getFirstIP() );
        $this->assertSame( '2001:db8:85a3:8d3:ffff:ffff:ffff:ffff', (string) $hosts->getlastIP() );
    }
}
