<?php namespace Orchestra\Foundation\Http\Controllers\TestCase;

use Mockery as m;
use Orchestra\Foundation\Http\Controllers\AdminController;

class AdminControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * Test Orchestra\Foundation\Http\Controllers\AdminController filters.
     *
     * @test
     */
    public function testFilters()
    {
        $stub = new StubAdminController();

        $middleware = [
            'orchestra.installable' => [],
        ];

        $this->assertEquals($middleware, $stub->getMiddleware());
    }
}

class StubAdminController extends AdminController
{
    protected function setupFilters()
    {
        //
    }
}
