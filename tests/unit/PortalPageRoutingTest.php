<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PortalPageRoutingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testPublicHomeAndRoleLoginPagesRenderFromOrganizedViews(): void
    {
        foreach ([
            '/' => 'Welcome to CampusPulse',
            '/student' => 'Student sign in',
            '/instructor' => 'Instructor sign in',
            '/myadmin/' => 'Administrator sign in',
        ] as $path => $heading) {
            $response = $this->get($path);
            $response->assertOK();
            $response->assertSee($heading);
        }
    }
}
