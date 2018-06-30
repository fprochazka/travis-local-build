# Travis-CI local build

[![Build Status](https://travis-ci.org/fprochazka/travis-local-build.svg?branch=master)](https://travis-ci.org/fprochazka/travis-local-build)

This tool

1. computes jobs the Travis-CI would run
2. generates docker image (that can be customized) for each job
3. runs each job

What it means:

* no switching of PHP versions to test your library
* no forgetting to run a specific job
* prepare everything locally and verify the build passes without hassle
* clean testing environment every time

This does not replace a build system (which would be able to run identical tasks on CI and local),
but even a build system wouldn't be able to guarantee executing full Travis matrix with all jobs.

## Usage

* clone the project
* install dependencies using composer
* build docker images in `docker-images/` using `docker-compose build`
	* **Pro-Tip:** you can customize them!
* run the executable `bin/travis-local` in desired directory

![travis-local-build](docs/travis-local.gif)
