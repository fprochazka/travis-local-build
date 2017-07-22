<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Command;

use Fprochazka\TravisLocalBuild\Travis\BuildMatrix;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ListJobsCommand extends \Symfony\Component\Console\Command\Command
{

	const EXECUTABLE_OPTION = 'executable';

	protected function configure()
	{
		$this->setName('list-jobs');
		$this->setDescription('Outputs the list of Travis jobs. If it\'s red, it\'s allowed to fail (on Travis, not here)');
		$this->addOption(self::EXECUTABLE_OPTION, 'e', InputOption::VALUE_NONE, 'List jobs as executable commands');
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$buildMatrix = new BuildMatrix($output);
		$jobs = $buildMatrix->create(getcwd());

		$output->writeln(sprintf('Found %d jobs:', count($jobs)));
		foreach ($jobs as $job) {
			if ($input->getOption(self::EXECUTABLE_OPTION)) {
				$output->writeln(sprintf('* <%s>travis-local run --php %s --env \'%s\'</>', $job->isAllowedFailure() ? 'fg=red' : 'info', $job->getPhpVersion(), $job->getEnvLine()));

			} else {
				$output->writeln(sprintf('* <%s>php:%s %s</>', $job->isAllowedFailure() ? 'fg=red' : 'info', $job->getPhpVersion(), $job->getEnvLine()));
			}
		}
		$output->writeln('');

		return 0;
	}

}
