<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * @internal
 */
final class QrEndpointRemovedTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testQrCodeEndpointIsNoLongerRegistered(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->withBodyFormat('json')
            ->post('/api/Scan_QR_Code', ['ID' => 1, 'Role' => 'Student']);
    }
}
