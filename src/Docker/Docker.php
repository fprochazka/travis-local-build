<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Docker;

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

	/** @var string */
	private $markerLabel;

	public function __construct(bool $noCache, string $markerLabel)
	{
		$finder = new ExecutableFinder();
		$this->executable = $finder->find('docker', '/usr/bin/docker');
		$this->noCache = $noCache;
		$this->markerLabel = $markerLabel;
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

	public function run(string $imageRef, array $volumes, string $networkName, ?string $command = null): Process
	{
		$volumeOptions = [];
		foreach ($volumes as $host => $container) {
			$volumeOptions[] = sprintf('-v %s:%s', $host, $container);
		}

		$run = new Process(
			sprintf(
				'%s run --rm --network %s %s %s %s',
				$this->executable,
				$networkName,
				implode(' ', $volumeOptions),
				$imageRef,
				$command
			)
		);
		$run->setTimeout(null);
		$run->start();
		return $run;
	}

	public function startService(string $imageRef, string $networkName, string $networkAlias, string $containerName, array $environmentVariables): Process
	{
		$environmentOptions = [];
		foreach ($environmentVariables as $key => $val) {
			$environmentOptions[] = sprintf('-e %s=%s', $key, $val);
		}

		$run = new Process(
			sprintf(
				'%s run -d --network %s --network-alias %s --name %s %s %s',
				$this->executable,
				escapeshellarg($networkName),
				escapeshellarg($networkAlias),
				escapeshellarg($containerName),
				implode(' ', $environmentOptions),
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
				$this->markerLabel . '="true"',
				escapeshellarg($volumeName)
			)
		);
		$run->setTimeout(null);
		$run->start();
		return $run;
	}

	public function getImageIds(): array
	{
		$run = new Process(
			sprintf(
				'%s images -q --filter label=%s',
				$this->executable,
				$this->markerLabel
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

	public function getNetworkIds(): array
	{
		$run = new Process(
			sprintf(
				'%s network ls -q --filter label=%s',
				$this->executable,
				$this->markerLabel
			)
		);
		$run->mustRun();

		return array_filter(Strings::split(trim($run->getOutput()), '~[\n\r]+~'));
	}

	public function getNetworkDetails(string $networkId): DockerNetwork
	{
		$run = new Process(
			sprintf(
				'%s network inspect %s',
				$this->executable,
				$networkId
			)
		);
		$run->mustRun();
		$details = Json::decode(trim($run->getOutput()), Json::FORCE_ARRAY);
		return new DockerNetwork($networkId, $details[0]);
	}

	public function isNetworkCreated(string $name): bool
	{
		foreach ($this->getNetworkIds() as $networkId) {
			$network = $this->getNetworkDetails($networkId);
			if ($network->getName() === $name) {
				return TRUE;
			}
		}

		return FALSE;
	}

	public function createNetwork(string $name): Process
	{
		$taken = [];
		foreach ($this->getNetworkIds() as $networkId) {
			$otherNetwork = $this->getNetworkDetails($networkId);
			list(, $b,) = explode('.', $otherNetwork->getSubnet(), 3);
			$taken[(int) $b] = (int) $b;
		}

		$subnetCounter = 25;
		for ($i = $subnetCounter; isset($taken[$i]) ;$i++) {
			$subnetCounter = $i + 1;
		}

		$run = new Process(
			sprintf(
				'%s network create --label %s --subnet 172.%d.0.0/16 %s',
				$this->executable,
				$this->markerLabel . '="true"',
				$subnetCounter,
				escapeshellarg($name)
			)
		);
		$run->mustRun();
		return $run;
	}

	public function removeNetwork(string $name): Process
	{
		$run = new Process(
			sprintf(
				'%s network rm %s',
				$this->executable,
				escapeshellarg($name)
			)
		);
		$run->start();
		return $run;
	}

	public function killAndRemove(string $ref): Process
	{
		$run = new Process(
			sprintf(
				'%s kill %s; %s rm %s',
				$this->executable,
				escapeshellarg($ref),
				$this->executable,
				escapeshellarg($ref)
			)
		);
		$run->start();
		return $run;
	}

}
