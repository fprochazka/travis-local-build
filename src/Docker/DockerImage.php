<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Docker;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class DockerImage
{

	/** @var string */
	private $imageId;

	/** @var array */
	private $details;

	public function __construct(string $imageId, array $details)
	{
		$this->imageId = $imageId;
		$this->details = $details;
	}

	public function getId(): string
	{
		return $this->imageId;
	}

	public function getTag(): string
	{
		return $this->details['RepoTags'][0] ?? '<none>:<none>';
	}

}
