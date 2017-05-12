<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Docker;

use Fprochazka\TravisLocalBuild\BuildExecutor;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Docker
{

	/** @var string */
	private $executable;

	/** @var bool */
	private $noCache;

	public function __construct(bool $noCache)
	{
		$finder = new ExecutableFinder();
		$this->executable = $finder->find('docker', '/usr/bin/docker');
		$this->noCache = $noCache;
	}

	public function build(string $imageRef, string $dockerFile): Process
	{
		$build = new Process(
			sprintf(
				'%s build %s -t %s -f %s .',
				$this->executable,
				$this->noCache ? '--no-cache' : '',
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

	public function getImageIdsWithLabel(string $containerMarkerLabel, string $value): array
	{
		$run = new Process(
			sprintf(
				'%s images -q --filter label=%s=%s',
				$this->executable,
				$containerMarkerLabel,
				$value
			)
		);
		$run->mustRun();

		return array_filter(Strings::split(trim($run->getOutput()), '~[\n\r]+~'));
	}

	public function getImageDetails(string $imageId): DockerImage
	{
		$run = new Process(
			sprintf(
				'%s inspect %s',
				$this->executable,
				$imageId
			)
		);
		$run->mustRun();
		$details = Json::decode(trim($run->getOutput()), Json::FORCE_ARRAY);
		return new DockerImage($imageId, $details[0]);
	}


	public function removeImage(string $imageId): Process
	{
		$run = new Process(
			sprintf(
				'%s rmi --force %s',
				$this->executable,
				$imageId
			)
		);
		$run->start();
		return $run;
	}

}
