<?php

namespace Fprochazka\TravisLocalBuild\Travis;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Yaml\Yaml;

class BuildMatrixTest extends TestCase
{

	/** @var \Symfony\Component\Console\Output\BufferedOutput */
	private $consoleOutput;

	/** @var \Fprochazka\TravisLocalBuild\Travis\BuildMatrix */
	private $matrix;

	protected function setUp()
	{
		$this->consoleOutput = new BufferedOutput();
		$this->matrix = new BuildMatrix($this->consoleOutput);
	}

	public function testConsistence()
	{
		$jobs = $this->matrix->computeJobs('consistence/consistence', __DIR__, $this->parseConfig(__DIR__ . '/../configs/consistence.yml'));
		$this->assertCount(8, $jobs);

		$job = array_shift($jobs);
		$this->assertEquals('7.0', $job->getPhpVersion());
		$this->assertEquals('', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.0', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_DEPENDENCIES_OPTIONS="--prefer-lowest --prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_DEPENDENCIES_OPTIONS="--prefer-lowest --prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('hhvm', $job->getPhpVersion());
		$this->assertEquals('', $job->getEnvLine());
		$this->assertTrue($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('hhvm', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_DEPENDENCIES_OPTIONS="--prefer-lowest --prefer-stable"', $job->getEnvLine());
		$this->assertTrue($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('nightly', $job->getPhpVersion());
		$this->assertEquals('', $job->getEnvLine());
		$this->assertTrue($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('nightly', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_DEPENDENCIES_OPTIONS="--prefer-lowest --prefer-stable"', $job->getEnvLine());
		$this->assertTrue($job->isAllowedFailure());

		$this->assertEmpty($jobs);
	}

	public function testKdybyAnnotations()
	{
		$jobs = $this->matrix->computeJobs('kdyby/annotations', __DIR__, $this->parseConfig(__DIR__ . '/../configs/kdyby-annotations.yml'));
		$this->assertCount(10, $jobs);

		$job = array_shift($jobs);
		$this->assertEquals('5.4', $job->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.3', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('5.5', $job->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.3', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('5.6', $job->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.4-dev', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('5.6', $job->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.4', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('5.6', $job->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.3', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('5.6', $job->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.3 COMPOSER_EXTRA_ARGS="--prefer-lowest --prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.0', $job->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.4-dev', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.0', $job->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.4', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.0', $job->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.3', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.0', $job->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.4 COVERAGE="--coverage ./coverage.xml --coverage-src ./src" TESTER_RUNTIME="phpdbg"', $job->getEnvLine());
		$this->assertTrue($job->isAllowedFailure());

		$this->assertEmpty($jobs);
	}

	public function testKdybyDoctrineCache()
	{
		$jobs = $this->matrix->computeJobs('kdyby/doctrine-cache', __DIR__, $this->parseConfig(__DIR__ . '/../configs/kdyby-doctrine-cache.yml'));
		$this->assertCount(10, $jobs);

		$job = array_shift($jobs);
		$this->assertEquals('5.6', $job->getPhpVersion());
		$this->assertEquals('', $job->getEnvLine());
		$this->assertTrue($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('5.6', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('5.6', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-lowest --prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.0', $job->getPhpVersion());
		$this->assertEquals('', $job->getEnvLine());
		$this->assertTrue($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.0', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.0', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-lowest --prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('', $job->getEnvLine());
		$this->assertTrue($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-lowest --prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable" COVERAGE="--coverage ./coverage.xml --coverage-src ./src" TESTER_RUNTIME="phpdbg"', $job->getEnvLine());
		$this->assertTrue($job->isAllowedFailure());

		$this->assertEmpty($jobs);
	}

	public function testKdybyDateTimeProvider()
	{
		$jobs = $this->matrix->computeJobs('kdyby/datetime-provider', __DIR__, $this->parseConfig(__DIR__ . '/../configs/kdyby-datetime-provider.yml'));
		$this->assertCount(6, $jobs);

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('', $job->getEnvLine());
		$this->assertTrue($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-lowest --prefer-stable"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable" COVERAGE="--coverage ./coverage.xml --coverage-src ./src" TESTER_RUNTIME="phpdbg"', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable" PHPSTAN=1', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$job = array_shift($jobs);
		$this->assertEquals('7.1', $job->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable" CODING_STANDARD=1', $job->getEnvLine());
		$this->assertFalse($job->isAllowedFailure());

		$this->assertEmpty($jobs);
	}

	private function parseConfig(string $config): array
	{
		return Yaml::parse(file_get_contents($config));
	}

}
