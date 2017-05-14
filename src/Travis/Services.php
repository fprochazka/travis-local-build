<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Travis;

use Fprochazka\TravisLocalBuild\Docker\Docker;
use Nette\Utils\Strings;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Services
{

	const WAIT_TIMEOUT = 60;

	/** @var array */
	private static $servicesMapping = [
		'mongodb' => [
			'repository' => 'mongo',
			'tag' => '2.6',
			'port' => null,
		],
		'mysql' => [
			'repository' => 'mysql',
			'tag' => '5.7',
			'port' => 3306,
			'environment' => [
				'MYSQL_ALLOW_EMPTY_PASSWORD' => 'yes',
				'MYSQL_USER' => 'travis',
				'MYSQL_PASSWORD' => '""',
			],
		],
		'postgresql' => [
			'repository' => 'postgres',
			'tag' => '9.6',
			'port' => 5432,
		],
		'couchdb' => [
			'repository' => 'fedora/couchdb',
			'tag' => 'latest',
			'port' => null,
		],
		'rabbitmq' => [
			'repository' => 'dockerfile/rabbitmq',
			'tag' => 'latest',
			'port' => null,
		],
		'memcached' => [
			'repository' => 'sylvainlasnier/memcached',
			'tag' => 'latest',
			'port' => null,
		],
		'redis-server' => [
			'repository' => 'redis',
			'tag' => '2.8',
			'port' => null,
		],
		'cassandra' => [
			'repository' => 'spotify/cassandra',
			'tag' => 'latest',
			'port' => null,
		],
		'neo4j' => [
			'repository' => 'tpires/neo4j',
			'tag' => 'latest',
			'port' => null,
		],
		'elasticsearch' => [
			'repository' => 'dockerfile/elasticsearch',
			'tag' => 'latest',
			'port' => null,
		],
	];

	/** @var \Symfony\Component\Console\Output\OutputInterface */
	private $out;

	/** @var \Fprochazka\TravisLocalBuild\Docker\Docker */
	private $docker;

	/** @var string */
	private $networkName;

	/** @var \Symfony\Component\Process\Process[][] */
	private $services = [];

	public function __construct(OutputInterface $stdOut, Docker $docker, string $networkName)
	{
		$this->docker = $docker;
		$this->networkName = $networkName;
		$this->out = $stdOut;
	}

	public function __destruct()
	{
		foreach ($this->services as $projectName => $services) {
			foreach ($services as $serviceName => $_) {
				$this->stopService($serviceName, $projectName);
			}
		}
	}

	public function startService(string $service, string $projectName): void
	{
		if (!array_key_exists($service, self::$servicesMapping)) {
			throw new \InvalidArgumentException(sprintf('Unknown service %s', $service));
		}

		$details = self::$servicesMapping[$service];
		$imageRef = sprintf('%s:%s', $details['repository'], $details['tag']);
		$this->services[$projectName][$service] = $this->docker->startService(
			$imageRef,
			$this->networkName,
			$service,
			$this->getContainerName($service, $projectName),
			$details['environment'] ?? []
		);

		try {
			if ($details['port'] !== null) {
				$wait = $this->docker->run(
					'travisci/wait:latest',
					[],
					$this->networkName,
					sprintf('-h %s -p %d -t %d', $service, $details['port'], self::WAIT_TIMEOUT)
				);
				$wait->setTimeout(self::WAIT_TIMEOUT);
				$this->out->writeln(sprintf('<comment>Waiting for %s to start up</comment>', $service));
				$wait->wait();

			} else {
				$this->out->writeln(sprintf('<comment>Started %s service</comment>', $service));
			}

		} catch (ProcessTimedOutException $e) {
			// starting of service took too long
			$this->stopService($service, $projectName);
			throw $e;
		}
	}

	public function stopService(string $service, string $projectName): void
	{
		$this->out->writeln(sprintf('<comment>Terminating service %s</comment>', $service));
		$this->services[$projectName][$service]->stop(0);
		unset($this->services[$projectName][$service]);
		$this->docker->killAndRemove($this->getContainerName($service, $projectName))->wait();
	}

	private function getContainerName(string $service, string $projectName): string
	{
		$lName = Strings::lower($projectName . '_' . $service);
		return trim(preg_replace('~[^a-z0-9_]+~', '_', $lName), '_');
	}

}
