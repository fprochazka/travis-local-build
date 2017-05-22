<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Fprochazka\TravisLocalBuild\Docker;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class DockerNetwork
{

	/** @var string */
	private $networkId;

	/** @var array */
	private $details;

	public function __construct(string $networkId, array $details)
	{
		$this->networkId = $networkId;
		$this->details = $details;
	}

	public function getId(): string
	{
		return $this->networkId;
	}

	public function getName(): string
	{
		return $this->details['Name'];
	}

	public function getSubnet(): ?string
	{
		return $this->details['IPAM']['Config'][0]['Subnet'] ?? NULL;
	}

}
