<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Functional\Scope;

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Exception\InvalidProviderType;
use Netlogix\Sentry\Scope\Environment\EnvironmentProvider;
use Netlogix\Sentry\Scope\Extra\ExtraProvider;
use Netlogix\Sentry\Scope\Release\ReleaseProvider;
use Netlogix\Sentry\Scope\ScopeProvider;
use Netlogix\Sentry\Scope\Tags\TagProvider;
use Netlogix\Sentry\Scope\User\UserProvider;

class ScopeProviderTest extends UnitTestCase
{

    /**
     * @var ScopeProvider
     */
    private $provider;

    private $objectManagerMock;

    protected function setUp(): void
    {
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $this->provider = new ScopeProvider($this->objectManagerMock);
    }

    /**
     * @test
     */
    public function providers_are_sorted_according_to_settings(): void
    {
        $extra1 = $this->getMockBuilder(ExtraProvider::class)
            ->setMockClassName('ExtraMock1')
            ->getMock();
        $extra1
            ->method('getExtra')
            ->willReturn(['foo' => 'bar']);
        $extra2 = $this->getMockBuilder(ExtraProvider::class)
            ->setMockClassName('ExtraMock2')
            ->getMock();
        $extra2
            ->method('getExtra')
            ->willReturn(['bar' => 'baz']);

        $this->objectManagerMock
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['ExtraMock1'], ['ExtraMock2'])
            ->willReturnOnConsecutiveCalls($extra1, $extra2);

        $this->provider->injectSettings([
            'scope' => [
                'extra' => [
                    get_class($extra2) => 'end 100',
                    get_class($extra1) => 'start 100',
                ]
            ]
        ]);

        $this->provider->initializeObject();

        self::assertSame(
            ['foo' => 'bar', 'bar' => 'baz'],
            $this->provider->collectExtra()
        );
    }

    /**
     * @test
     */
    public function providers_with_falsy_configuration_are_not_used(): void
    {
        $extra1 = $this->getMockBuilder(ExtraProvider::class)
            ->setMockClassName('ExtraMock1')
            ->getMock();
        $extra1
            ->method('getExtra')
            ->willReturn(['foo' => 'bar']);
        $extra2 = $this->getMockBuilder(ExtraProvider::class)
            ->setMockClassName('ExtraMock2')
            ->getMock();
        $extra2
            ->method('getExtra')
            ->willReturn(['foo' => 'baz']);
        $extra3 = $this->getMockBuilder(ExtraProvider::class)
            ->setMockClassName('ExtraMock3')
            ->getMock();
        $extra3
            ->method('getExtra')
            ->willReturn(['foo' => 'foo']);

        $this->objectManagerMock
            ->expects(self::once())
            ->method('get')
            ->with('ExtraMock1')
            ->willReturn($extra1);

        $this->provider->injectSettings([
            'scope' => [
                'extra' => [
                    get_class($extra1) => 'start 100',
                    get_class($extra2) => null,
                    get_class($extra3) => false,
                ]
            ]
        ]);

        $this->provider->initializeObject();

        self::assertSame(['foo' => 'bar'], $this->provider->collectExtra());
    }

    /**
     * @test
     * @dataProvider provideInvalidProviderTypes
     */
    public function providers_must_be_of_the_correct_type(string $scope, object $provider): void
    {
        $this->expectException(InvalidProviderType::class);

        $this->provider->injectSettings([
            'scope' => [
                $scope => [
                    get_class($provider) => '10',
                ]
            ]
        ]);

        $this->provider->initializeObject();
    }

    public function provideInvalidProviderTypes(): iterable
    {
        yield 'environment stdClass' => ['scope' => 'environment', 'provider' => new \stdClass()];
        yield 'environment Extra' => ['scope' => 'environment', 'provider' => $this->getMockBuilder(ExtraProvider::class)->getMock()];
        yield 'environment Release' => ['scope' => 'environment', 'provider' => $this->getMockBuilder(ReleaseProvider::class)->getMock()];
        yield 'environment Tag' => ['scope' => 'environment', 'provider' => $this->getMockBuilder(TagProvider::class)->getMock()];
        yield 'environment User' => ['scope' => 'environment', 'provider' => $this->getMockBuilder(UserProvider::class)->getMock()];

        yield 'extra stdClass' => ['scope' => 'extra', 'provider' => new \stdClass()];
        yield 'extra Environment' => ['scope' => 'extra', 'provider' => $this->getMockBuilder(EnvironmentProvider::class)->getMock()];
        yield 'extra Release' => ['scope' => 'extra', 'provider' => $this->getMockBuilder(ReleaseProvider::class)->getMock()];
        yield 'extra Tag' => ['scope' => 'extra', 'provider' => $this->getMockBuilder(TagProvider::class)->getMock()];
        yield 'extra User' => ['scope' => 'extra', 'provider' => $this->getMockBuilder(UserProvider::class)->getMock()];

        yield 'release stdClass' => ['scope' => 'release', 'provider' => new \stdClass()];
        yield 'release Environment' => ['scope' => 'release', 'provider' => $this->getMockBuilder(EnvironmentProvider::class)->getMock()];
        yield 'release Extra' => ['scope' => 'release', 'provider' => $this->getMockBuilder(ExtraProvider::class)->getMock()];
        yield 'release Tag' => ['scope' => 'release', 'provider' => $this->getMockBuilder(TagProvider::class)->getMock()];
        yield 'release User' => ['scope' => 'release', 'provider' => $this->getMockBuilder(UserProvider::class)->getMock()];

        yield 'tags stdClass' => ['scope' => 'tags', 'provider' => new \stdClass()];
        yield 'tags Environment' => ['scope' => 'tags', 'provider' => $this->getMockBuilder(EnvironmentProvider::class)->getMock()];
        yield 'tags Extra' => ['scope' => 'tags', 'provider' => $this->getMockBuilder(ExtraProvider::class)->getMock()];
        yield 'tags Release' => ['scope' => 'tags', 'provider' => $this->getMockBuilder(ReleaseProvider::class)->getMock()];
        yield 'tags User' => ['scope' => 'tags', 'provider' => $this->getMockBuilder(UserProvider::class)->getMock()];

        yield 'user stdClass' => ['scope' => 'user', 'provider' => new \stdClass()];
        yield 'user Environment' => ['scope' => 'user', 'provider' => $this->getMockBuilder(EnvironmentProvider::class)->getMock()];
        yield 'user Extra' => ['scope' => 'user', 'provider' => $this->getMockBuilder(ExtraProvider::class)->getMock()];
        yield 'user Release' => ['scope' => 'user', 'provider' => $this->getMockBuilder(ReleaseProvider::class)->getMock()];
        yield 'user Tag' => ['scope' => 'user', 'provider' => $this->getMockBuilder(TagProvider::class)->getMock()];
    }

    /**
     * @test
     */
    public function only_last_environment_is_returned(): void
    {
        $environment1 = $this->getMockBuilder(EnvironmentProvider::class)
            ->setMockClassName('EnvironmentMock1')
            ->getMock();
        $environment1
            ->method('getEnvironment')
            ->willReturn('machine-1');
        $environment2 = $this->getMockBuilder(EnvironmentProvider::class)
            ->setMockClassName('EnvironmentMock2')
            ->getMock();
        $environment2
            ->method('getEnvironment')
            ->willReturn('machine-2');

        $this->objectManagerMock
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['EnvironmentMock1'], ['EnvironmentMock2'])
            ->willReturnOnConsecutiveCalls($environment1, $environment2);

        $this->provider->injectSettings([
            'scope' => [
                'environment' => [
                    get_class($environment1) => '10',
                    get_class($environment2) => '20',
                ]
            ]
        ]);

        $this->provider->initializeObject();

        self::assertSame(
            'machine-2',
            $this->provider->collectEnvironment()
        );
    }

    /**
     * @test
     */
    public function extra_is_merged_recursively(): void
    {
        $extra1 = $this->getMockBuilder(ExtraProvider::class)
            ->setMockClassName('ExtraMock1')
            ->getMock();
        $extra1
            ->method('getExtra')
            ->willReturn(['foo' => 'bar']);
        $extra2 = $this->getMockBuilder(ExtraProvider::class)
            ->setMockClassName('ExtraMock2')
            ->getMock();
        $extra2
            ->method('getExtra')
            ->willReturn(['foo' => 'baz']);

        $this->objectManagerMock
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['ExtraMock1'], ['ExtraMock2'])
            ->willReturnOnConsecutiveCalls($extra1, $extra2);

        $this->provider->injectSettings([
            'scope' => [
                'extra' => [
                    get_class($extra1) => '10',
                    get_class($extra2) => '20',
                ]
            ]
        ]);

        $this->provider->initializeObject();

        self::assertSame(
            ['foo' => ['bar', 'baz']],
            $this->provider->collectExtra()
        );
    }

    /**
     * @test
     */
    public function only_last_release_is_returned(): void
    {
        $release1 = $this->getMockBuilder(ReleaseProvider::class)
            ->setMockClassName('ReleaseMock1')
            ->getMock();
        $release1
            ->method('getRelease')
            ->willReturn('release-1');
        $release2 = $this->getMockBuilder(ReleaseProvider::class)
            ->setMockClassName('ReleaseMock2')
            ->getMock();
        $release2
            ->method('getRelease')
            ->willReturn('release-2');

        $this->objectManagerMock
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['ReleaseMock1'], ['ReleaseMock2'])
            ->willReturnOnConsecutiveCalls($release1, $release2);

        $this->provider->injectSettings([
            'scope' => [
                'release' => [
                    get_class($release1) => '10',
                    get_class($release2) => '20',
                ]
            ]
        ]);

        $this->provider->initializeObject();

        self::assertSame(
            'release-2',
            $this->provider->collectRelease()
        );
    }

    /**
     * @test
     */
    public function tags_are_merged_and_override_each_other(): void
    {
        $tag1 = $this->getMockBuilder(TagProvider::class)
            ->setMockClassName('TagMock1')
            ->getMock();
        $tag1
            ->method('getTags')
            ->willReturn(['foo' => 'bar']);
        $tag2 = $this->getMockBuilder(TagProvider::class)
            ->setMockClassName('TagMock2')
            ->getMock();
        $tag2
            ->method('getTags')
            ->willReturn(['foo' => 'baz']);

        $this->objectManagerMock
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['TagMock1'], ['TagMock2'])
            ->willReturnOnConsecutiveCalls($tag1, $tag2);

        $this->provider->injectSettings([
            'scope' => [
                'tags' => [
                    get_class($tag1) => '10',
                    get_class($tag2) => '20',
                ]
            ]
        ]);

        $this->provider->initializeObject();

        self::assertSame(
            ['foo' => 'baz'],
            $this->provider->collectTags()
        );
    }

    /**
     * @test
     */
    public function user_is_merged_recursively(): void
    {
        $user1 = $this->getMockBuilder(UserProvider::class)
            ->setMockClassName('UserMock1')
            ->getMock();
        $user1
            ->method('getUser')
            ->willReturn(['foo' => 'bar']);
        $user2 = $this->getMockBuilder(UserProvider::class)
            ->setMockClassName('UserMock2')
            ->getMock();
        $user2
            ->method('getUser')
            ->willReturn(['foo' => 'baz']);

        $this->objectManagerMock
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['UserMock1'], ['UserMock2'])
            ->willReturnOnConsecutiveCalls($user1, $user2);

        $this->provider->injectSettings([
            'scope' => [
                'user' => [
                    get_class($user1) => '10',
                    get_class($user2) => '20',
                ]
            ]
        ]);

        $this->provider->initializeObject();

        self::assertSame(
            ['foo' => ['bar', 'baz']],
            $this->provider->collectUser()
        );
    }

}
