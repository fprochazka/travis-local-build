<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Travis;

use Nette\Utils\Arrays;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class BuildMatrix
{

	public const DEFAULT_PHP = '7.1';

	/** @var \Symfony\Component\Console\Output\OutputInterface */
	private $out;

	public function __construct(OutputInterface $output)
	{
		$this->out = $output;
	}

	/**
	 * @return \Fprochazka\TravisLocalBuild\Travis\Job[]
	 */
	public function create(string $projectDir): array
	{
		$configFile = $projectDir . '/.travis.yml';
		if (!is_file($configFile)) {
			throw new \RuntimeException(sprintf('The .travis.yml was not found in %s', $projectDir));
		}

		$composerFile = $projectDir . '/composer.json';
		if (!is_file($composerFile)) {
			throw new \RuntimeException(sprintf('The composer.json was not found in %s', $projectDir));
		}
		$composerConfig = Json::decode(file_get_contents($composerFile), Json::FORCE_ARRAY);
		$projectName = $composerConfig['name'];

		return $this->computeJobs($projectName, $projectDir, Yaml::parse(file_get_contents($configFile)));
	}

	/**
	 * @return \Fprochazka\TravisLocalBuild\Travis\Job[]
	 */
	public function computeJobs(string $projectName, string $projectDir, array $config): array
	{
		/** @var \Fprochazka\TravisLocalBuild\Travis\Job[][] $jobs */
		$jobs = [/* PHP Version => ENV => Job */];

		$beforeInstallScripts = $this->getConfigScripts($config, 'before_install');
		$installScripts = $this->getConfigScripts($config, 'install');
		$beforeScripts = $this->getConfigScripts($config, 'before_script');
		$scripts = $this->getConfigScripts($config, 'script');

		$services = $this->getConfigScripts($config, 'services');
		$cacheDirectories = $this->getConfigValue($config, ['cache', 'directories'], 'array') ?: [];

		$phpConfig = $this->getConfigValue($config, ['php'], 'array') ?? [self::DEFAULT_PHP];
		$envMatrixConfig = $this->getConfigValue($config, ['env', 'matrix'], 'array') ?? $this->getConfigValue($config, ['env'], 'array') ?? [''];
		foreach ($phpConfig as $phpVersion) {
			$phpVersion = self::formatPhpVersion($phpVersion);
			foreach ($envMatrixConfig as $matrixEnv) {
				$matrixEnv = $matrixEnv ?? '';
				$jobs[$phpVersion][$matrixEnv] = new Job(
					$projectName,
					$projectDir,
					$phpVersion,
					$this->parseEnvironmentLine($matrixEnv),
					$beforeInstallScripts,
					$installScripts,
					$beforeScripts,
					$scripts,
					$cacheDirectories,
					$services
				);
			}
		}

		$includeConfig = $this->getConfigValue($config, ['matrix', 'include'], 'array') ?: [];
		foreach ($includeConfig as $i => $include) {
			$phpVersion = $this->getConfigValue($include, ['php'], 'string|float');
			$matrixEnv = $this->getConfigValue($include, ['env'], 'string') ?? '';
			if ($phpVersion === null) {
				throw new \RuntimeException(sprintf('Missing php version for matrix.include.%d: %s', $i, json_encode($include)));
			}

			$phpVersion = self::formatPhpVersion($phpVersion);
			$jobs[$phpVersion][$matrixEnv] = new Job(
				$projectName,
				$projectDir,
				$phpVersion,
				$this->parseEnvironmentLine($matrixEnv),
				$beforeInstallScripts,
				$installScripts,
				$beforeScripts,
				$scripts,
				$cacheDirectories,
				$services
			);
		}

		$excludeConfig = $this->getConfigValue($config, ['matrix', 'exclude'], 'array') ?: [];
		foreach ($excludeConfig as $i => $exclude) {
			$phpVersion = $this->getConfigValue($exclude, ['php'], 'string|float');
			$matrixEnv = array_key_exists('env', $exclude)
				? ($this->getConfigValue($exclude, ['env'], 'string') ?? '')
				: null;
			$matches = self::createVersionEnvMatcher($matrixEnv, $phpVersion);

			/** @var \Fprochazka\TravisLocalBuild\Travis\Job $job */
			foreach (Arrays::flatten($jobs) as $job) {
				if ($matches($job)) {
					unset($jobs[$job->getPhpVersion()][$job->getEnvLine()]);
				}
			}
		}

		$allowFailuresConfig = $this->getConfigValue($config, ['matrix', 'allow_failures'], 'array') ?: [];
		foreach ($allowFailuresConfig as $i => $allowFailures) {
			$phpVersion = $this->getConfigValue($allowFailures, ['php'], 'string|float');
			$matrixEnv = array_key_exists('env', $allowFailures)
				? ($this->getConfigValue($allowFailures, ['env'], 'string') ?? '')
				: null;
			$matches = self::createVersionEnvMatcher($matrixEnv, $phpVersion);

			/** @var \Fprochazka\TravisLocalBuild\Travis\Job $job */
			foreach (Arrays::flatten($jobs) as $job) {
				if ($matches($job)) {
					$job->setAllowedFailure(true);
				}
			}
		}

		ksort($jobs);

		return Arrays::flatten($jobs);
	}

	public function parseEnvironmentLine(string $env): array
	{
		$pairs = [];
		if (trim($env) === '') {
			return $pairs;
		}

		foreach (Strings::matchAll($env, '~([^=\t ]+)=("[^"]+"|[^\t ]+)~') as $match) {
			$pairs[$match[1]] = $match[2];
		}

		return $pairs;
	}

	public function getConfigScripts(array $config, string $key): array
	{
		if (!array_key_exists($key, $config)) {
			return [];
		}

		return is_array($config[$key]) ? $config[$key] : [$config[$key]];
	}

	public function getConfigValue(array $config, array $keys, string $type)
	{
		$value = Arrays::get($config, $keys, null);
		if ($value !== null) {
			Validators::assert($value, $type);
		}

		return $value;
	}

	public function filterOnlyPhpVersion(array $jobs, string $requiredVersion): array
	{
		return array_filter(
			$jobs,
			self::createPhpVersionFilter($requiredVersion)
		);
	}

	public function filterOnlyEnvContains(array $jobs, string $requiredEnv): array
	{
		return array_filter(
			$jobs,
			function (Job $job) use ($requiredEnv): bool {
				return Strings::contains($job->getEnvLine(), $requiredEnv);
			}
		);
	}

	private static function createVersionEnvMatcher(?string $requiredEnv, $requiredPhpVersion): \Closure
	{
		$envFilter = function (Job $job) use ($requiredEnv): bool {
			return $requiredEnv === null || $job->getEnvLine() === $requiredEnv;
		};
		$phpFilter = self::createPhpVersionFilter($requiredPhpVersion);

		return function (Job $job) use ($envFilter, $phpFilter): bool {
			return $envFilter($job) && $phpFilter($job);
		};
	}

	private static function createPhpVersionFilter($requiredVersion): \Closure
	{
		$requiredVersion = (trim((string) $requiredVersion) !== '')
			? self::formatPhpVersion($requiredVersion)
			: null;

		return function (Job $job) use ($requiredVersion): bool {
			return $requiredVersion === '' || $requiredVersion === null || $job->getPhpVersion() === $requiredVersion;
		};
	}

	private static function formatPhpVersion($version): string
	{
		if (!is_numeric($version)) {
			return (string) $version;
		}

		if (substr_count((string) $version, '.') > 1) { // this is gonna explode later bro
			return (string) $version;
		}

		return number_format((float) $version, 1, '.', '');
	}

}
