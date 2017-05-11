<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Command;

use Fprochazka\TravisLocalBuild\BuildExecutor;
use Fprochazka\TravisLocalBuild\BuildMatrix;
use Fprochazka\TravisLocalBuild\Job;
use Nette\Utils\Strings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RunCommand extends \Symfony\Component\Console\Command\Command
{

	const PHP_VERSION_OPTION = 'php';

	const MATRIX_ENV_OPTION = 'env';

	protected function configure()
	{
		$this->setName('run');
		$this->addOption(self::PHP_VERSION_OPTION, null, InputOption::VALUE_REQUIRED, 'Run only tasks with given PHP version');
		$this->addOption(self::MATRIX_ENV_OPTION, null, InputOption::VALUE_REQUIRED, 'Run only tasks that contain given ENV variables');
	}

	public function run(InputInterface $input, OutputInterface $output): int
	{
		$buildMatrix = new BuildMatrix($output);
		$jobs = $buildMatrix->create(getcwd());

		if ($input->hasOption(self::PHP_VERSION_OPTION)) {
			$requiredVersion = $input->getOption(self::PHP_VERSION_OPTION);
			$jobs = array_filter(
				$jobs,
				function (Job $job) use ($requiredVersion): bool {
					return $job->getPhpVersion() === $requiredVersion;
				}
			);
		}

		if ($input->hasOption(self::MATRIX_ENV_OPTION)) {
			$requiredEnv = $input->getOption(self::MATRIX_ENV_OPTION);
			$jobs = array_filter(
				$jobs,
				function (Job $job) use ($requiredEnv): bool {
					return Strings::contains($job->getEnvLine(), $requiredEnv);
				}
			);
		}

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
