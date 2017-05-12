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

		$this->assertEquals('7.0', $jobs[0]->getPhpVersion());
		$this->assertEquals('', $jobs[0]->getEnvLine());
		$this->assertFalse($jobs[0]->isAllowedFailure());

		$this->assertEquals('7.0', $jobs[1]->getPhpVersion());
		$this->assertEquals('COMPOSER_DEPENDENCIES_OPTIONS="--prefer-lowest --prefer-stable"', $jobs[1]->getEnvLine());
		$this->assertFalse($jobs[1]->isAllowedFailure());

		$this->assertEquals('7.1', $jobs[2]->getPhpVersion());
		$this->assertEquals('', $jobs[2]->getEnvLine());
		$this->assertFalse($jobs[2]->isAllowedFailure());

		$this->assertEquals('7.1', $jobs[3]->getPhpVersion());
		$this->assertEquals('COMPOSER_DEPENDENCIES_OPTIONS="--prefer-lowest --prefer-stable"', $jobs[3]->getEnvLine());
		$this->assertFalse($jobs[3]->isAllowedFailure());

		$this->assertEquals('nightly', $jobs[4]->getPhpVersion());
		$this->assertEquals('', $jobs[4]->getEnvLine());
		$this->assertTrue($jobs[4]->isAllowedFailure());

		$this->assertEquals('nightly', $jobs[5]->getPhpVersion());
		$this->assertEquals('COMPOSER_DEPENDENCIES_OPTIONS="--prefer-lowest --prefer-stable"', $jobs[5]->getEnvLine());
		$this->assertTrue($jobs[5]->isAllowedFailure());

		$this->assertEquals('hhvm', $jobs[6]->getPhpVersion());
		$this->assertEquals('', $jobs[6]->getEnvLine());
		$this->assertTrue($jobs[6]->isAllowedFailure());

		$this->assertEquals('hhvm', $jobs[7]->getPhpVersion());
		$this->assertEquals('COMPOSER_DEPENDENCIES_OPTIONS="--prefer-lowest --prefer-stable"', $jobs[7]->getEnvLine());
		$this->assertTrue($jobs[7]->isAllowedFailure());
	}

	public function testKdybyAnnotations()
	{
		$jobs = $this->matrix->computeJobs('kdyby/annotations', __DIR__, $this->parseConfig(__DIR__ . '/../configs/kdyby-annotations.yml'));
		$this->assertCount(10, $jobs);

		$this->assertEquals('5.4', $jobs[0]->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.3', $jobs[0]->getEnvLine());
		$this->assertFalse($jobs[0]->isAllowedFailure());

		$this->assertEquals('5.5', $jobs[1]->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.3', $jobs[1]->getEnvLine());
		$this->assertFalse($jobs[1]->isAllowedFailure());

		$this->assertEquals('5.6', $jobs[2]->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.4-dev', $jobs[2]->getEnvLine());
		$this->assertFalse($jobs[2]->isAllowedFailure());

		$this->assertEquals('5.6', $jobs[3]->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.4', $jobs[3]->getEnvLine());
		$this->assertFalse($jobs[3]->isAllowedFailure());

		$this->assertEquals('5.6', $jobs[4]->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.3', $jobs[4]->getEnvLine());
		$this->assertFalse($jobs[4]->isAllowedFailure());

		$this->assertEquals('5.6', $jobs[5]->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.3 COMPOSER_EXTRA_ARGS="--prefer-lowest --prefer-stable"', $jobs[5]->getEnvLine());
		$this->assertFalse($jobs[5]->isAllowedFailure());

		$this->assertEquals('7.0', $jobs[6]->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.4-dev', $jobs[6]->getEnvLine());
		$this->assertFalse($jobs[6]->isAllowedFailure());

		$this->assertEquals('7.0', $jobs[7]->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.4', $jobs[7]->getEnvLine());
		$this->assertFalse($jobs[7]->isAllowedFailure());

		$this->assertEquals('7.0', $jobs[8]->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.3', $jobs[8]->getEnvLine());
		$this->assertFalse($jobs[8]->isAllowedFailure());

		$this->assertEquals('7.0', $jobs[9]->getPhpVersion());
		$this->assertEquals('NETTE=nette-2.4 COVERAGE="--coverage ./coverage.xml --coverage-src ./src" TESTER_RUNTIME="phpdbg"', $jobs[9]->getEnvLine());
		$this->assertTrue($jobs[9]->isAllowedFailure());
	}

	public function testKdybyDoctrineCache()
	{
		$jobs = $this->matrix->computeJobs('kdyby/doctrine-cache', __DIR__, $this->parseConfig(__DIR__ . '/../configs/kdyby-doctrine-cache.yml'));
		$this->assertCount(10, $jobs);

		$this->assertEquals('5.6', $jobs[0]->getPhpVersion());
		$this->assertEquals('', $jobs[0]->getEnvLine());
		$this->assertTrue($jobs[0]->isAllowedFailure());

		$this->assertEquals('5.6', $jobs[1]->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable"', $jobs[1]->getEnvLine());
		$this->assertFalse($jobs[1]->isAllowedFailure());

		$this->assertEquals('5.6', $jobs[2]->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-lowest --prefer-stable"', $jobs[2]->getEnvLine());
		$this->assertFalse($jobs[2]->isAllowedFailure());

		$this->assertEquals('7.0', $jobs[3]->getPhpVersion());
		$this->assertEquals('', $jobs[3]->getEnvLine());
		$this->assertTrue($jobs[3]->isAllowedFailure());

		$this->assertEquals('7.0', $jobs[4]->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable"', $jobs[4]->getEnvLine());
		$this->assertFalse($jobs[4]->isAllowedFailure());

		$this->assertEquals('7.0', $jobs[5]->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-lowest --prefer-stable"', $jobs[5]->getEnvLine());
		$this->assertFalse($jobs[5]->isAllowedFailure());

		$this->assertEquals('7.1', $jobs[6]->getPhpVersion());
		$this->assertEquals('', $jobs[6]->getEnvLine());
		$this->assertTrue($jobs[6]->isAllowedFailure());

		$this->assertEquals('7.1', $jobs[7]->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable"', $jobs[7]->getEnvLine());
		$this->assertFalse($jobs[7]->isAllowedFailure());

		$this->assertEquals('7.1', $jobs[8]->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-lowest --prefer-stable"', $jobs[8]->getEnvLine());
		$this->assertFalse($jobs[8]->isAllowedFailure());

		$this->assertEquals('7.1', $jobs[9]->getPhpVersion());
		$this->assertEquals('COMPOSER_EXTRA_ARGS="--prefer-stable" COVERAGE="--coverage ./coverage.xml --coverage-src ./src" TESTER_RUNTIME="phpdbg"', $jobs[9]->getEnvLine());
		$this->assertTrue($jobs[9]->isAllowedFailure());
	}

	private function parseConfig(string $config): array
	{
		return Yaml::parse(file_get_contents($config));
	}

}
