<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PortalPageRoutingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testShieldLoginAcceptsPortalUsernames(): void
    {
        $this->assertSame(['username'], config('Auth')->validFields);
        $this->assertSame('portal_auth', config('Auth')->sessionConfig['field']);
        $this->assertFalse(config('Auth')->sessionConfig['allowRemembering']);
        $this->assertFalse(config('Auth')->allowRegistration);
        $this->assertFalse(config('Auth')->allowMagicLinkLogins);
    }

    public function testLegacyRoleSessionDoesNotRenderAdminNavigation(): void
    {
        $this->withSession([
            'role' => 'admin',
            'user_id' => 1,
            'display_name' => 'Admin',
            'user' => ['id' => 1],
        ]);

        $response = $this->get('/');
        $response->assertOK();
        $response->assertDontSee('Instructors Attendance');
    }

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

    public function testRolePortalsRequireShieldAuthentication(): void
    {
        foreach ([
            '/myadmin/students',
            '/instructor/attendance',
            '/student/attendance',
        ] as $path) {
            $this->get($path)->assertRedirect();
        }
    }
}
