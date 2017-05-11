<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Command;

use Fprochazka\TravisLocalBuild\BuildExecutor;
use Fprochazka\TravisLocalBuild\BuildMatrix;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RunCommand extends \Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('run');
	}

	public function run(InputInterface $input, OutputInterface $output): int
	{
		$buildMatrix = new BuildMatrix($output);
		$jobs = $buildMatrix->create(getcwd());

		$executor = new BuildExecutor(
			$output,
			__DIR__ . '/../../tmp'
		);
		$output->writeln(sprintf('Found %d jobs:', count($jobs)));
		foreach ($jobs as $job) {
			$output->writeln(sprintf('- php:%s %s', $job->getPhpVersion(), $job->getEnvLine()));
		}
		$output->writeln('');

		foreach ($jobs as $job) {
			$output->write("\n\n\n");
			$output->writeln(sprintf('<info>Job php:%s %s</info>', $job->getPhpVersion(), $job->getEnvLine()));
			$executor->execute($job);
		}

		return 0;
	}

}
