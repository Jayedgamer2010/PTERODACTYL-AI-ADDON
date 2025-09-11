<?php

namespace Blueprint\Extensions\AIAssistant\Tests\Mocks;

class MockAIService
{
    protected $activeProvider = 'mock';
    protected $requestCount = 0;
    protected $rateLimit = 10;

    public function processUserQuery($query, $context = [])
    {
        // Rate limit check
        $this->requestCount++;
        if ($this->requestCount > $this->rateLimit) {
            throw new \Exception('Rate limit exceeded');
        }

        // Input validation
        if (empty($query) || !isset($context['user_id']) || !isset($context['server_id'])) {
            throw new \Exception('Invalid input parameters');
        }

        $response = "This is a mock AI response for query: " . $query;
        
        if (strpos($query, 'server') !== false) {
            $response .= "\nTo start your server, use the control panel.";
        }
        
        return $response;
    }

    public function getActiveProvider()
    {
        return $this->activeProvider;
    }
    
    public function setActiveProvider($provider)
    {
        $this->activeProvider = $provider;
    }
}
