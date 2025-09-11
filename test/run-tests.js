#!/usr/bin/env node

const { TestSprite } = require('@testsprite/core');
const path = require('path');
const fs = require('fs');

async function runTests() {
    console.log('Starting AI Assistant TestSprite Runner...');
    
    const config = JSON.parse(fs.readFileSync(
        path.join(__dirname, 'testsprite-config.json'),
        'utf8'
    ));

    const testSprite = new TestSprite({
        debug: true,
        timeout: 30000
    });

    // Load test scenarios
    const scenarios = config.testScenarios.aiAssistant;

    console.log('Running Basic Tests...');
    for (const test of scenarios.basic) {
        await testSprite.run({
            name: test.name,
            async test(t) {
                const response = await t.query(test.input);
                
                // Check for expected tags
                test.expectedTags.forEach(tag => {
                    t.assert(
                        response.toLowerCase().includes(tag.toLowerCase()),
                        `Response should contain tag: ${tag}`
                    );
                });
                
                // Validate response structure
                t.assert(response !== null, 'Response should not be null');
                t.assert(response.length > 0, 'Response should not be empty');
            }
        });
    }

    console.log('Running Advanced Tests...');
    for (const test of scenarios.advanced) {
        await testSprite.run({
            name: test.name,
            async test(t) {
                if (test.expectFailover) {
                    // Test provider failover
                    const startProvider = await t.getActiveProvider();
                    await t.simulateProviderFailure(startProvider);
                    const response = await t.query(test.input);
                    const newProvider = await t.getActiveProvider();
                    
                    t.assert(
                        startProvider !== newProvider,
                        'Provider should change after failure'
                    );
                    t.assert(response !== null, 'Failover response should not be null');
                }
                
                if (test.expectWebSocket) {
                    // Test WebSocket functionality
                    const ws = await t.connectWebSocket();
                    const metrics = await t.waitForMetrics(ws);
                    
                    t.assert(metrics !== null, 'Should receive metrics');
                    t.assert(
                        typeof metrics.cpu_usage === 'number',
                        'Should receive CPU metrics'
                    );
                }
            }
        });
    }

    console.log('Running Performance Tests...');
    await testSprite.run({
        name: 'Performance Benchmark',
        async test(t) {
            const start = Date.now();
            const response = await t.query('Quick server status');
            const duration = Date.now() - start;
            
            t.assert(
                duration < 2000,
                'Response time should be under 2 seconds'
            );
        }
    });

    console.log('Running Security Tests...');
    await testSprite.run({
        name: 'Security Validation',
        async test(t) {
            // Test rate limiting
            const results = await Promise.all(
                Array(11).fill().map(() => t.query('test'))
            );
            
            t.assert(
                results.some(r => r === null),
                'Rate limiting should block excessive requests'
            );

            // Test authentication
            const response = await t.queryWithoutAuth('test');
            t.assert(
                response === null,
                'Unauthenticated requests should be blocked'
            );
        }
    });

    console.log('All tests completed!');
}

runTests().catch(console.error);
