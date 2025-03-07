<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Test\Downloader;

use Composer\Test\TestCase;

class ArchiveDownloaderTest extends TestCase
{
    /** @var \Composer\Config&\PHPUnit\Framework\MockObject\MockObject */
    protected $config;

    public function testGetFileName()
    {
        $packageMock = $this->getMockBuilder('Composer\Package\PackageInterface')->getMock();
        $packageMock->expects($this->any())
            ->method('getDistUrl')
            ->will($this->returnValue('http://example.com/script.js'))
        ;

        $downloader = $this->getArchiveDownloaderMock();
        $method = new \ReflectionMethod($downloader, 'getFileName');
        $method->setAccessible(true);

        $this->config->expects($this->any())
            ->method('get')
            ->with('vendor-dir')
            ->will($this->returnValue('/vendor'));

        $first = $method->invoke($downloader, $packageMock, '/path');
        $this->assertMatchesRegularExpression('#/vendor/composer/tmp-[a-z0-9]+\.js#', $first);
        $this->assertSame($first, $method->invoke($downloader, $packageMock, '/path'));
    }

    public function testProcessUrl()
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('Requires openssl');
        }

        $downloader = $this->getArchiveDownloaderMock();
        $method = new \ReflectionMethod($downloader, 'processUrl');
        $method->setAccessible(true);

        $expected = 'https://github.com/composer/composer/zipball/master';
        $url = $method->invoke($downloader, $this->getMockBuilder('Composer\Package\PackageInterface')->getMock(), $expected);

        $this->assertEquals($expected, $url);
    }

    public function testProcessUrl2()
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('Requires openssl');
        }

        $downloader = $this->getArchiveDownloaderMock();
        $method = new \ReflectionMethod($downloader, 'processUrl');
        $method->setAccessible(true);

        $expected = 'https://github.com/composer/composer/archive/master.tar.gz';
        $url = $method->invoke($downloader, $this->getMockBuilder('Composer\Package\PackageInterface')->getMock(), $expected);

        $this->assertEquals($expected, $url);
    }

    public function testProcessUrl3()
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('Requires openssl');
        }

        $downloader = $this->getArchiveDownloaderMock();
        $method = new \ReflectionMethod($downloader, 'processUrl');
        $method->setAccessible(true);

        $expected = 'https://api.github.com/repos/composer/composer/zipball/master';
        $url = $method->invoke($downloader, $this->getMockBuilder('Composer\Package\PackageInterface')->getMock(), $expected);

        $this->assertEquals($expected, $url);
    }

    /**
     * @dataProvider provideUrls
     * @param string $url
     */
    public function testProcessUrlRewriteDist($url)
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('Requires openssl');
        }

        $downloader = $this->getArchiveDownloaderMock();
        $method = new \ReflectionMethod($downloader, 'processUrl');
        $method->setAccessible(true);

        $type = strpos($url, 'tar') ? 'tar' : 'zip';
        $expected = 'https://api.github.com/repos/composer/composer/'.$type.'ball/ref';

        $package = $this->getMockBuilder('Composer\Package\PackageInterface')->getMock();
        $package->expects($this->any())
            ->method('getDistReference')
            ->will($this->returnValue('ref'));
        $url = $method->invoke($downloader, $package, $url);

        $this->assertEquals($expected, $url);
    }

    public function provideUrls()
    {
        return array(
            array('https://api.github.com/repos/composer/composer/zipball/master'),
            array('https://api.github.com/repos/composer/composer/tarball/master'),
            array('https://github.com/composer/composer/zipball/master'),
            array('https://www.github.com/composer/composer/tarball/master'),
            array('https://github.com/composer/composer/archive/master.zip'),
            array('https://github.com/composer/composer/archive/master.tar.gz'),
        );
    }

    /**
     * @dataProvider provideBitbucketUrls
     * @param string $url
     * @param string $extension
     */
    public function testProcessUrlRewriteBitbucketDist($url, $extension)
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('Requires openssl');
        }

        $downloader = $this->getArchiveDownloaderMock();
        $method = new \ReflectionMethod($downloader, 'processUrl');
        $method->setAccessible(true);

        $url .= '.' . $extension;
        $expected = 'https://bitbucket.org/davereid/drush-virtualhost/get/ref.' . $extension;

        $package = $this->getMockBuilder('Composer\Package\PackageInterface')->getMock();
        $package->expects($this->any())
            ->method('getDistReference')
            ->will($this->returnValue('ref'));
        $url = $method->invoke($downloader, $package, $url);

        $this->assertEquals($expected, $url);
    }

    public function provideBitbucketUrls()
    {
        return array(
            array('https://bitbucket.org/davereid/drush-virtualhost/get/77ca490c26ac818e024d1138aa8bd3677d1ef21f', 'zip'),
            array('https://bitbucket.org/davereid/drush-virtualhost/get/master', 'tar.gz'),
            array('https://bitbucket.org/davereid/drush-virtualhost/get/v1.0', 'tar.bz2'),
        );
    }

    /**
     * @return \Composer\Downloader\ArchiveDownloader&\PHPUnit\Framework\MockObject\MockObject
     */
    private function getArchiveDownloaderMock()
    {
        return $this->getMockForAbstractClass(
            'Composer\Downloader\ArchiveDownloader',
            array(
                $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock(),
                $this->config = $this->getMockBuilder('Composer\Config')->getMock(),
                new \Composer\Util\HttpDownloader($io, $this->config),
            )
        );
    }
}
