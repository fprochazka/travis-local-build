<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Command;

use Fprochazka\TravisLocalBuild\BuildExecutor;
use Fprochazka\TravisLocalBuild\BuildMatrix;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

		$failed = [];
		foreach ($jobs as $job) {
			$output->write("\n\n");
			$output->writeln(sprintf('<info>Job php:%s %s</info>', $job->getPhpVersion(), $job->getEnvLine()));
			if (!$executor->execute($job)) {
				$failed[] = $job;
			}
		}

		$output->write("\n\n");
		$output->writeln(sprintf('<info>Summary: %d out of %d jobs failed</info>', count($failed), count($jobs)));
		foreach ($failed as $failedJob) {
			$output->writeln(sprintf('<error>- php:%s %s</error>', $failedJob->getPhpVersion(), $failedJob->getEnvLine()));
		}

		return 0;
	}

}
