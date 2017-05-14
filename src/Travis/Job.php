<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Travis;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Job
{

	/** @var string */
	private $id;

	/** @var string */
	private $projectName;

	/** @var string */
	private $projectDir;

	/** @var string */
	private $phpVersion;

	/** @var array */
	private $env;

	/** @var array */
	private $beforeInstallScripts;

	/** @var array */
	private $installScripts;

	/** @var array */
	private $beforeScripts;

	/** @var array */
	private $scripts;

	/** @var bool */
	private $allowedFailure = false;

	/** @var array */
	private $cacheDirectories;

	/** @var array */
	private $services;

	public function __construct(
		string $projectName,
		string $projectDir,
		string $phpVersion,
		array $env,
		array $beforeInstallScripts,
		array $installScripts,
		array $beforeScripts,
		array $scripts,
		array $cacheDirectories,
		array $services
	)
	{
		$this->id = substr(md5($phpVersion . json_encode($env)), 0, 6);
		$this->projectName = $projectName;
		$this->projectDir = $projectDir;
		$this->phpVersion = $phpVersion;
		$this->env = $env;
		$this->beforeInstallScripts = $beforeInstallScripts;
		$this->installScripts = $installScripts;
		$this->beforeScripts = $beforeScripts;
		$this->scripts = $scripts;
		$this->cacheDirectories = $cacheDirectories;
		$this->services = $services;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getProjectName(): string
	{
		return $this->projectName;
	}

	public function getProjectDir(): string
	{
		return $this->projectDir;
	}

	public function getPhpVersion(): string
	{
		return $this->phpVersion;
	}

	public function getEnv(): array
	{
		return $this->env;
	}

	public function getEnvLine(): string
	{
		$line = [];
		foreach ($this->env as $key => $val) {
			$line[] = sprintf('%s=%s', $key, $val);
		}

		return implode(' ', $line);
	}

	public function getBeforeInstallScripts(): array
	{
		return $this->beforeInstallScripts;
	}

	public function getInstallScripts(): array
	{
		return $this->installScripts;
	}

	public function getBeforeScripts(): array
	{
		return $this->beforeScripts;
	}

	public function getScripts(): array
	{
		return $this->scripts;
	}

	public function getCacheDirectories(): array
	{
		return $this->cacheDirectories;
	}

	public function getServices(): array
	{
		return $this->services;
	}

	public function isAllowedFailure(): bool
	{
		return $this->allowedFailure;
	}

	public function setAllowedFailure(bool $allowedFailure)
	{
		$this->allowedFailure = $allowedFailure;
	}

}
