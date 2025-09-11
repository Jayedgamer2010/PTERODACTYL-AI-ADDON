<?php

namespace Blueprint\Extensions\AIAssistant\Tests\Mocks;

class MockSecurityService
{
    public function getServerSecurityStatus($serverId)
    {
        return [
            'firewall' => 'active',
            'ssl' => 'valid',
            'updates' => 'current',
            'firewall_status' => 'active',
            'ssl_certificate' => 'valid'
        ];
    }

    public function validateToken($token)
    {
        return true;
    }

    public function checkAccess($token, $requiredLevel)
    {
        return true;
    }
}
