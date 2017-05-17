<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Travis;

use Fprochazka\TravisLocalBuild\Docker\Docker;
use Nette\Utils\Strings;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class BuildExecutor
{

	const PHP_BASE_IMAGE = 'travisci/php';
	const CONTAINER_MARKER_LABEL = 'su.fprochazka.travis-local-build';
	const DOCKER_NETWORK = 'travis_ci';

	/** @var \Symfony\Component\Console\Output\OutputInterface */
	private $out;

	/** @var \Symfony\Component\Console\Output\OutputInterface */
	private $err;

	/** @var string */
	private $tempDir;

	/** @var \Symfony\Component\Filesystem\Filesystem */
	private $fs;

	/** @var \Fprochazka\TravisLocalBuild\Docker\Docker */
	private $docker;

	/** @var \Symfony\Component\Console\Terminal */
	private $terminal;

	/** @var \Fprochazka\TravisLocalBuild\Travis\Services */
	private $services;

	public function __construct(OutputInterface $stdOut, string $tempDir, Docker $docker)
	{
		$this->out = $stdOut;
		$this->err = ($stdOut instanceof ConsoleOutput) ? $stdOut->getErrorOutput() : new StreamOutput(fopen('php://stderr', 'wb'));
		$this->tempDir = $tempDir;
		$this->fs = new Filesystem();
		$this->docker = $docker;
		$this->terminal = new Terminal();
		$this->services = new Services($stdOut, $docker, self::DOCKER_NETWORK);
	}

	public function execute(Job $job): bool
	{
		return $this->dockerRun(
			$job,
			$this->dockerBuild($job)
		);
	}

	private function dockerRun(Job $job, string $imageRef): bool
	{
		$volumes = [];
//		foreach ($this->findProjectFiles($job->getProjectDir()) as $file) {
//			$volumes[$file->getPathname()] = '/build/' . $file->getRelativePathname() . ':ro';
//		}

		$this->docker->removeNetwork(self::DOCKER_NETWORK)->wait();
		$this->docker->createNetwork(self::DOCKER_NETWORK)->wait();

		foreach ($job->getServices() as $service) {
			$this->services->startService($service, $job->getProjectName());
		}

		try {
			if (count($job->getCacheDirectories()) !== null) {
				$cacheVolumeName = Strings::webalize($job->getProjectName() . '-cache');
				$this->docker->createVolume($cacheVolumeName)->wait();
				foreach ($job->getCacheDirectories() as $cacheDir) {
					$volumes[$cacheVolumeName] = strtr(
						$cacheDir,
						[
							'$HOME' => '/root',
						]
					);
				}
			}

			$process = $this->docker->run($imageRef, $volumes, self::DOCKER_NETWORK);
			$process->wait(
				function (string $type, $data): void {
					if ($type === Process::OUT) {
						$this->out->write($data);
					} else {
						$this->err->write($data);
					}
				}
			);
			if (!$process->isSuccessful()) {
				$this->out->writeln(sprintf('<error>Build failed</error>'));
			} else {
				$this->out->writeln(sprintf('<info>Build succeeded</info>'));
			}
			$this->out->writeln('');

		} finally {
			foreach ($job->getServices() as $service) {
				$this->services->stopService($service, $job->getProjectName());
			}
		}

		return $process->isSuccessful();
	}

	private function dockerBuild(Job $job): string
	{
		$this->out->writeln(sprintf('Building docker image for job %s', $job->getId()));

		$dockerSteps = $this->buildDockerFile($job);
		$imageName = Strings::lower($job->getProjectName() . ':v' . $job->getId());

		$dockerStepsCount = count($dockerSteps);
		$progress = new ProgressBar($this->out, $dockerStepsCount);
		$progressFormat = $progress::getFormatDefinition($this->determineBestProgressFormat());
		$process = $this->docker->build($imageName, $this->getDockerFile($job));
		$process->wait(function (string $type, $data) use ($progress, $progressFormat, $dockerSteps, $dockerStepsCount): void {
			if ($type === Process::OUT) {
				foreach (explode("\n", $data) as $line) {
					if (preg_match('~^Step\\s+(\\d+)\\/' . $dockerStepsCount . '\\s+:~', $line, $m)) {
						$step = (int) $m[1];
						$stepMessageLength = $this->terminal->getWidth() - 44;
						$progress->setFormat($progressFormat . '  ' . substr($dockerSteps[$step] ?? '', 0, $stepMessageLength));
						$progress->setProgress($step);
					}
				}
			}
		});

		if (!$process->isSuccessful()) {
			$this->out->writeln('');
			throw new ProcessFailedException($process);
		}

		$progress->finish();
		$this->out->writeln("\n");
		$this->out->writeln(sprintf('<info>Successfully built image %s</info>', $imageName));

		return $imageName;
	}

	/**
	 * @return string[]
	 */
	private function buildDockerFile(Job $job): array
	{
		$projectTmpDir = $this->getProjectTmpDir($job);

		$this->copyProject($job, $projectTmpDir);
		$this->writeDockerIgnore($projectTmpDir);

		$dockerBuild = [];
		$dockerBuild[] = sprintf('FROM %s:%s', self::PHP_BASE_IMAGE, $job->getPhpVersion());
		$dockerBuild[] = sprintf('LABEL %s="true"', self::CONTAINER_MARKER_LABEL);

		$dockerBuild[] = 'WORKDIR /build';
		$dockerBuild[] = sprintf('ENV %s %s', 'TRAVIS_PHP_VERSION', $job->getPhpVersion());
		foreach ($job->getEnv() as $key => $val) {
			$dockerBuild[] = sprintf('ENV %s %s', $key, $val);
		}

		$dockerBuild[] = sprintf('COPY src/ /build');

		$entryPointFile = $this->writeEntryPoint($job, $projectTmpDir);
		$dockerBuild[] = sprintf('COPY %s /usr/local/bin/travis-entrypoint', basename($entryPointFile));
		$dockerBuild[] = 'CMD ["/usr/local/bin/travis-entrypoint"]';

		$dockerBuild[] = sprintf('LABEL %s="%s"', self::CONTAINER_MARKER_LABEL . '.project', $job->getProjectName());
		$dockerBuild[] = sprintf('LABEL %s="%s"', self::CONTAINER_MARKER_LABEL . '.job', $job->getId());

		$dockerFileContents = implode("\n", $dockerBuild);
		file_put_contents($this->getDockerFile($job), $dockerFileContents);

		return $dockerBuild;
	}

	private function getProjectTmpDir(Job $job): string
	{
		$dir = $this->tempDir . '/' . $job->getProjectName();
		$this->fs->mkdir($dir);
		return $dir;
	}

	private function getDockerFile(Job $job): string
	{
		return $this->getProjectTmpDir($job) . '/Dockerfile.' . $job->getId();
	}

	private function copyProject(Job $job, string $projectTmpDir): void
	{
		$this->fs->remove($projectTmpDir . '/src/');
		foreach ($this->findProjectFiles($job->getProjectDir()) as $file) {
			$targetDir = $projectTmpDir . '/src/' . $file->getRelativePathname();
			if ($file->isDir()) {
				$this->fs->mirror($file->getPathname(), $targetDir);
			} else {
				$this->fs->copy($file->getPathname(), $targetDir);
			}
		}
	}

	/**
	 * @param string $projectDir
	 * @return \Traversable|\Symfony\Component\Finder\SplFileInfo[]
	 */
	private function findProjectFiles(string $projectDir): \Traversable
	{
		$gitFilesProcess = (new Process('git ls-files', $projectDir))->mustRun();
		$projectFiles = Strings::split(trim($gitFilesProcess->getOutput()), '~[\n\r]+~');

		$result = [];
		foreach ($projectFiles as $file) {
			$relativePath = (strpos($file, DIRECTORY_SEPARATOR) !== false)
				? dirname($file)
				: '';
			$result[] = new SplFileInfo($projectDir . '/' . $file, $relativePath, $file);
		}

		$composerLock = $projectDir . '/composer.lock';
		if (is_file($composerLock)) {
			$result[] = new SplFileInfo($composerLock, '', basename($composerLock));
		}

		return new \ArrayIterator($result);
	}

	private function writeDockerIgnore(string $projectTmpDir): void
	{
		$ignore = [
			'src/.git',
			'src/vendor',
		];
		file_put_contents($projectTmpDir . '/.dockerignore', implode("\n", $ignore));
	}

	private function writeEntryPoint(Job $job, string $projectTmpDir): string
	{
		$cmd = ['#!/bin/bash', 'set -e', ''];
//
		// todo: addons.apt.packages
		// $ sudo -E apt-get -yq --no-install-suggests --no-install-recommends --force-yes install dnsutils

		foreach ($job->getBeforeInstallScripts() as $script) {
			$cmd[] = $this->formatScriptLine($script, 'before install');
			$cmd[] = $script . "\n";
		}

		foreach ($job->getInstallScripts() as $script) {
			$cmd[] = $this->formatScriptLine($script, 'install');
			$cmd[] = $script . "\n";
		}

		foreach ($job->getBeforeScripts() as $script) {
			$cmd[] = $this->formatScriptLine($script, 'before script');
			$cmd[] = $script . "\n";
		}

		foreach ($job->getScripts() as $script) {
			$cmd[] = $this->formatScriptLine($script, 'script');
			$cmd[] = $script . "\n";
		}

		$entryPointFile = $projectTmpDir . '/travis-entrypoint.' . $job->getId();
		file_put_contents($entryPointFile, implode("\n", $cmd));
		$this->fs->chmod($entryPointFile, 0755);
		return $entryPointFile;
	}

	private function formatScriptLine(string $line, string $stage): string
	{
		$message = $this->out->getFormatter()->format(sprintf('<%s>%s > %s</>', 'fg=yellow', $stage, substr(escapeshellarg($line), 1, -1)));
		return sprintf('echo "";echo \'%s\' ;echo "";', $message);
	}

	private function determineBestProgressFormat()
	{
		switch ($this->out->getVerbosity()) {
			// OutputInterface::VERBOSITY_QUIET: display is disabled anyway
			case OutputInterface::VERBOSITY_VERBOSE:
				return 'verbose';
			case OutputInterface::VERBOSITY_VERY_VERBOSE:
				return 'very_verbose';
			case OutputInterface::VERBOSITY_DEBUG:
				return 'debug';
			default:
				return 'normal';
		}
	}

}
