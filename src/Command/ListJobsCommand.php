<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Command;

use Fprochazka\TravisLocalBuild\BuildMatrix;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ListJobsCommand extends \Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('list-jobs');
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$buildMatrix = new BuildMatrix($output);
		$jobs = $buildMatrix->create(getcwd());

		$output->writeln(sprintf('Found %d jobs:', count($jobs)));
		foreach ($jobs as $job) {
			$output->writeln(sprintf('- php:%s %s', $job->getPhpVersion(), $job->getEnvLine()));
		}
		$output->writeln('');

		return 0;
	}

}
