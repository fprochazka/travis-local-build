<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Command;

use Fprochazka\TravisLocalBuild\Docker\Docker;
use Fprochazka\TravisLocalBuild\Travis\BuildExecutor;
use Fprochazka\TravisLocalBuild\Travis\BuildMatrix;
use Fprochazka\TravisLocalBuild\Travis\Job;
use Nette\Utils\Strings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RunCommand extends \Symfony\Component\Console\Command\Command
{

	const PHP_VERSION_OPTION = 'php';
	const MATRIX_ENV_OPTION = 'env';
	const NO_CACHE_OPTION = 'no-cache';

	protected function configure()
	{
		$this->setName('run');
		$this->setDescription('Executes the Travis jobs locally, in custom Docker containers');
		$this->addOption(self::PHP_VERSION_OPTION, null, InputOption::VALUE_REQUIRED, 'Run only tasks with given PHP version');
		$this->addOption(self::MATRIX_ENV_OPTION, null, InputOption::VALUE_REQUIRED, 'Run only tasks that contain given ENV variables');
		$this->addOption(self::NO_CACHE_OPTION, null, InputOption::VALUE_NONE, 'Do not use cache when building the Docker image');
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);

		$buildMatrix = new BuildMatrix($output);
		$jobs = $buildMatrix->create(getcwd());

		if ($input->getOption(self::PHP_VERSION_OPTION) !== null) {
			$jobs = $buildMatrix->filterOnlyPhpVersion($jobs, $input->getOption(self::PHP_VERSION_OPTION));
		}

		if ($input->getOption(self::MATRIX_ENV_OPTION) !== null) {
			$jobs = $buildMatrix->filterOnlyEnvContains($jobs, $input->getOption(self::MATRIX_ENV_OPTION));
		}

		$executor = new BuildExecutor(
			$output,
			__DIR__ . '/../../tmp',
			new Docker($input->hasOption(self::NO_CACHE_OPTION))
		);

		$failed = [];
		/** @var \Fprochazka\TravisLocalBuild\Travis\Job $job */
		foreach ($jobs as $job) {
			$output->write('');
			$style->block(
				sprintf('php:%s %s', $job->getPhpVersion(), $job->getEnvLine()),
				'JOB',
				'fg=black;bg=green',
				' ',
				true
			);

			if (!$executor->execute($job)) {
				$failed[] = $job;
			}
		}

		$style->block(
			(count($failed) > 0)
				? sprintf('Summary: %d out of %d jobs failed', count($failed), count($jobs))
				: sprintf('Summary: %d jobs succeeded', count($jobs)),
			(count($failed) > 0) ? 'FAIL' : 'OK',
			(count($failed) > 0) ? 'fg=white;bg=red' : 'fg=black;bg=green',
			' ',
			true
		);
		foreach ($failed as $failedJob) {
			$output->writeln(sprintf('<error>- php:%s %s</error>', $failedJob->getPhpVersion(), $failedJob->getEnvLine()));
		}

		return 0;
	}

}
