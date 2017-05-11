<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild;

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
	 * @return \Fprochazka\TravisLocalBuild\Job[]
	 */
	public function create(string $projectDir): array
	{
		$configFile = $projectDir . '/.travis.yml';
		if (!file_exists($configFile)) {
			throw new \RuntimeException(sprintf('The .travis.yml was not found in %s', $projectDir));
		}

		$composerFile = $projectDir . '/composer.json';
		if (!file_exists($composerFile)) {
			throw new \RuntimeException(sprintf('The composer.json was not found in %s', $projectDir));
		}
		$composerConfig = Json::decode(file_get_contents($composerFile), Json::FORCE_ARRAY);
		$projectName = $composerConfig['name'];

		return $this->computeJobs($projectName, $projectDir, Yaml::parse(file_get_contents($configFile)));
	}

	private function computeJobs(string $projectName, string $projectDir, array $config): array
	{
		/** @var \Fprochazka\TravisLocalBuild\Job[][] $jobs */
		$jobs = [/* PHP Version => ENV => Job */];

		$beforeInstallScripts = $this->getConfigScripts($config, 'before_install');
		$installScripts = $this->getConfigScripts($config, 'install');
		$beforeScripts = $this->getConfigScripts($config, 'before_script');
		$scripts = $this->getConfigScripts($config, 'script');

		$phpConfig = $this->getConfigValue($config, ['php'], 'array') ?? [self::DEFAULT_PHP];
		$envMatrixConfig = $this->getConfigValue($config, ['env', 'matrix'], 'array') ?? [''];
		foreach ($phpConfig as $phpVersion) {
			$phpVersion = $this->formatPhpVersion($phpVersion);
			foreach ($envMatrixConfig as $matrixEnv) {
				$jobs[$phpVersion][$matrixEnv] = new Job(
					$projectName,
					$projectDir,
					(string) $phpVersion,
					$this->parseEnvironmentLine($matrixEnv),
					$beforeInstallScripts,
					$installScripts,
					$beforeScripts,
					$scripts
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

			$phpVersion = $this->formatPhpVersion($phpVersion);
			$jobs[$phpVersion][$matrixEnv] = new Job(
				$projectName,
				$projectDir,
				$phpVersion,
				$this->parseEnvironmentLine($matrixEnv),
				$beforeInstallScripts,
				$installScripts,
				$beforeScripts,
				$scripts
			);
		}

		$excludeConfig = $this->getConfigValue($config, ['matrix', 'exclude'], 'array') ?: [];
		foreach ($excludeConfig as $i => $exclude) {
			$phpVersion = $this->getConfigValue($exclude, ['php'], 'string|float');
			$matrixEnv = $this->getConfigValue($exclude, ['env'], 'string') ?: '';
			if ($phpVersion === null) {
				throw new \RuntimeException(sprintf('Missing php version for matrix.exclude.%d: %s', $i, json_encode($exclude)));
			}

			$phpVersion = $this->formatPhpVersion($phpVersion);
			if (!isset($jobs[$phpVersion][$matrixEnv])) {
				continue;
			}

			unset($jobs[$phpVersion][$matrixEnv]);
		}

		$allowFailuresConfig = $this->getConfigValue($config, ['matrix', 'allow_failures'], 'array') ?: [];
		foreach ($allowFailuresConfig as $i => $allowFailures) {
			$phpVersion = $this->getConfigValue($allowFailures, ['php'], 'string|float');
			$matrixEnv = $this->getConfigValue($allowFailures, ['env'], 'string');
			if ($phpVersion === null) {
				throw new \RuntimeException(sprintf('Missing php version for matrix.allow_failures.%d: %s', $i, json_encode($allowFailures)));
			}

			$phpVersion = $this->formatPhpVersion($phpVersion);
			foreach ($jobs[$phpVersion] as $jobEnv => $job) {
				if ($matrixEnv === null || $matrixEnv === $jobEnv) {
					$job->setAllowedFailure(true);
				}
			}
		}

		return Arrays::flatten($jobs);
	}

	private function parseEnvironmentLine(string $env): array
	{
		$pairs = [];
		if (trim($env) === '') {
			return $pairs;
		}

		foreach (Strings::matchAll($env, '~([A-Z]+)=("[^"]+"|[^\t ]+)~') as $match) {
			$pairs[$match[1]] = $match[2];
		}

		return $pairs;
	}

	private function getConfigScripts(array $config, string $key): array
	{
		if (!array_key_exists($key, $config)) {
			return [];
		}

		return is_array($config[$key]) ? $config[$key] : [$config[$key]];
	}

	private function getConfigValue(array $config, array $keys, string $type)
	{
		$value = Arrays::get($config, $keys, NULL);
		if ($value !== null) {
			Validators::assert($value, $type);
		}

		return $value;
	}

	private function formatPhpVersion($version): string
	{
		if (!is_numeric($version)) {
			return (string) $version;
		}

		if (substr_count((string) $version, '.') > 1) { // this is gonna explode later bro
			return (string) $version;
		}

		return number_format($version, 1, '.', '');
	}

}
