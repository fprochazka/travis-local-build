<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Docker
{

	/** @var string */
	private $executable;

	public function __construct()
	{
		$finder = new ExecutableFinder();
		$this->executable = $finder->find('docker', '/usr/bin/docker');
	}

	public function build(string $imageRef, string $dockerFile): Process
	{
		$build = new Process(
			sprintf(
				'%s build -t %s -f %s .',
				$this->executable,
				$imageRef,
				escapeshellarg(basename($dockerFile))
			),
			dirname($dockerFile)
		);
		$build->setTimeout(null);
		$build->start();
		return $build;
	}

	public function run(string $imageRef, array $volumes)
	{
		$volumeOptions = '';
		foreach ($volumes as $host => $container) {
			$volumeOptions .= sprintf(' -v %s:%s', $host, $container);
		}

		$run = new Process(
			sprintf(
				'%s run --rm %s %s',
				$this->executable,
				$volumeOptions,
				$imageRef
			)
		);
		$run->setTimeout(null);
		$run->start();
		return $run;
	}

	public function createVolume(string $volumeName): Process
	{
		$run = new Process(
			sprintf(
				'%s volume create --label %s %s',
				$this->executable,
				BuildExecutor::CONTAINER_MARKER_LABEL . '="true"',
				$volumeName
			)
		);
		$run->setTimeout(null);
		$run->start();
		return $run;
	}

}
