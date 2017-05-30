<?php

namespace Orchestra\Foundation\TestCase\Traits;

use Mockery as m;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Orchestra\Foundation\Traits\Timezone;

class TimezoneTest extends TestCase
{
    use Timezone;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        date_default_timezone_set('UTC');
    }

    /**
     * Teardown the test environment.
     */
    protected function tearDown()
    {
        m::close();
    }

    /**
     * Test Orchestra\Foundation\Traits\Timezone::toLocalTime() method given date as
     * string.
     *
     * @test
     * @group support
     */
    public function testToLocalTimeGivenDateAsString()
    {
        $this->config = m::mock('\Illuminate\Contracts\Config\Repository');
        $this->auth = m::mock('\Illuminate\Contracts\Auth\Guard');
        $this->memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $this->config->shouldReceive('get')->once()->with('app.timezone', 'UTC')->andReturn('UTC');
        $this->auth->shouldReceive('guest')->once()->andReturn(true);

        $stub = $this->toLocalTime('2012-01-01 00:00:00');

        $this->assertEquals(new \DateTimeZone('UTC'), $stub->getTimezone());
    }

    /**
     * Test Orchestra\Foundation\Traits\Timezone::toLocalTime() method return proper
     * datetime when is guest.
     *
     * @test
     * @group support
     */
    public function testToLocalTimeReturnProperDateTimeWhenIsGuest()
    {
        $this->config = m::mock('\Illuminate\Contracts\Config\Repository');
        $this->auth = m::mock('\Illuminate\Contracts\Auth\Guard');
        $this->memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $this->config->shouldReceive('get')->once()->with('app.timezone', 'UTC')->andReturn('UTC');
        $this->auth->shouldReceive('guest')->once()->andReturn(true);

        $stub = $this->toLocalTime(new Carbon('2012-01-01 00:00:00'));

        $this->assertEquals(new \DateTimeZone('UTC'), $stub->getTimezone());
    }

    /**
     * Test Orchestra\Foundation\Traits\Timezone::toLocalTime() method return proper
     * datetime when is user.
     *
     * @test
     * @group support
     */
    public function testToLocalTimeReturnProperDateTimeWhenIsUser()
    {
        $this->config = m::mock('\Illuminate\Contracts\Config\Repository');
        $this->auth = m::mock('\Illuminate\Contracts\Auth\Guard');
        $this->memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $this->config->shouldReceive('get')->with('app.timezone', 'UTC')->andReturn('UTC');
        $this->auth->shouldReceive('guest')->once()->andReturn(false)
            ->shouldReceive('user')->once()->andReturn((object) ['id' => 1]);
        $this->memory->shouldReceive('get')->once()->with('timezone.1', 'UTC')->andReturn('Asia/Kuala_Lumpur');

        $stub = $this->toLocalTime(new Carbon('2012-01-01 00:00:00'));

        $this->assertEquals(new \DateTimeZone('Asia/Kuala_Lumpur'), $stub->timezone);
        $this->assertEquals('2012-01-01 08:00:00', $stub->toDateTimeString());
    }

    /**
     * Test Orchestra\Foundation\Traits\Timezone::toGlobalTime() method return proper
     * datetime when is guest.
     *
     * @test
     * @group support
     */
    public function testFromLocalTimeReturnProperDateTimeWhenIsGuest()
    {
        $this->config = m::mock('\Illuminate\Contracts\Config\Repository');
        $this->auth = m::mock('\Illuminate\Contracts\Auth\Guard');
        $this->memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $this->config->shouldReceive('get')->once()->with('app.timezone', 'UTC')->andReturn('UTC');
        $this->auth->shouldReceive('guest')->once()->andReturn(true);

        $stub = $this->fromLocalTime('2012-01-01 00:00:00');

        $this->assertEquals(new \DateTimeZone('UTC'), $stub->timezone);
    }

    /**
     * Test Orchestra\Foundation\Traits\Timezone::fromLocalTime() method return proper
     * datetime when is user.
     *
     * @test
     * @group support
     */
    public function testFromLocalTimeReturnProperDateTimeWhenIsUser()
    {
        $this->config = m::mock('\Illuminate\Contracts\Config\Repository');
        $this->auth = m::mock('\Illuminate\Contracts\Auth\Guard');
        $this->memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $this->config->shouldReceive('get')->with('app.timezone', 'UTC')->andReturn('UTC');
        $this->auth->shouldReceive('guest')->once()->andReturn(false)
            ->shouldReceive('user')->once()->andReturn((object) ['id' => 1]);
        $this->memory->shouldReceive('get')->once()->with('timezone.1', 'UTC')->andReturn('Asia/Kuala_Lumpur');

        $stub = $this->fromLocalTime('2012-01-01 08:00:00');

        $this->assertEquals(new \DateTimeZone('UTC'), $stub->timezone);
        $this->assertEquals('2012-01-01 00:00:00', $stub->toDateTimeString());
    }

    /**
     * Test Orchestra\Foundation\Traits\Timezone::convertToDateTime() method when
     * timezone is null.
     *
     * @test
     */
    public function testConvertToDateTimeMethodWhenTimezoneIsNull()
    {
        $this->config = m::mock('\Illuminate\Contracts\Config\Repository');
        $this->auth = m::mock('\Illuminate\Contracts\Auth\Guard');
        $this->memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $stub = $this->convertToDateTime('2012-01-01 08:00:00');

        $this->assertInstanceOf('\Carbon\Carbon', $stub);
        $this->assertEquals('2012-01-01 08:00:00', $stub->toDateTimeString());
    }
}
