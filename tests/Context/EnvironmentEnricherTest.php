<?php

namespace Georgeff\Problem\Test\Context;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\Contract\ContextEnricher;
use Georgeff\Problem\Context\EnvironmentEnricher;

class EnvironmentEnricherTest extends TestCase
{
    public function test_implements_context_enricher_contract(): void
    {
        $this->assertInstanceOf(ContextEnricher::class, new EnvironmentEnricher());
    }

    public function test_enrich_adds_hostname(): void
    {
        $result = (new EnvironmentEnricher())->enrich([]);

        $this->assertArrayHasKey('hostname', $result);
        $this->assertIsString($result['hostname']);
    }

    public function test_enrich_adds_pid(): void
    {
        $result = (new EnvironmentEnricher())->enrich([]);

        $this->assertArrayHasKey('pid', $result);
        $this->assertIsInt($result['pid']);
    }

    public function test_enrich_adds_environment_key(): void
    {
        $result = (new EnvironmentEnricher())->enrich([]);

        $this->assertArrayHasKey('environment', $result);
        $this->assertIsArray($result['environment']);
    }

    public function test_enrich_preserves_existing_context(): void
    {
        $result = (new EnvironmentEnricher())->enrich(['existing' => 'value']);

        $this->assertSame('value', $result['existing']);
    }

    public function test_enrich_does_not_overwrite_existing_keys(): void
    {
        $result = (new EnvironmentEnricher())->enrich(['hostname' => 'my-host']);

        $this->assertSame('my-host', $result['hostname']);
    }

    public function test_enrich_captures_set_environment_variables(): void
    {
        putenv('APP_ENV=testing');

        $result = (new EnvironmentEnricher(['APP_ENV']))->enrich([]);

        $this->assertSame('testing', $result['environment']['APP_ENV']);

        putenv('APP_ENV');
    }

    public function test_enrich_omits_unset_environment_variables(): void
    {
        putenv('APP_ENV');

        $result = (new EnvironmentEnricher(['APP_ENV']))->enrich([]);

        $this->assertArrayNotHasKey('APP_ENV', $result['environment']);
    }

    public function test_enrich_accepts_custom_variable_list(): void
    {
        putenv('CUSTOM_VAR=hello');

        $result = (new EnvironmentEnricher(['CUSTOM_VAR']))->enrich([]);

        $this->assertArrayHasKey('CUSTOM_VAR', $result['environment']);

        putenv('CUSTOM_VAR');
    }

    public function test_enrich_defaults_to_app_env_app_name_app_version(): void
    {
        putenv('APP_ENV=production');
        putenv('APP_NAME=myapp');
        putenv('APP_VERSION=1.0.0');

        $result = (new EnvironmentEnricher())->enrich([]);

        $this->assertArrayHasKey('APP_ENV', $result['environment']);
        $this->assertArrayHasKey('APP_NAME', $result['environment']);
        $this->assertArrayHasKey('APP_VERSION', $result['environment']);

        putenv('APP_ENV');
        putenv('APP_NAME');
        putenv('APP_VERSION');
    }
}
