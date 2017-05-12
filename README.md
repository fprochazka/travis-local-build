# Travis-CI local build

[![Build Status](https://travis-ci.org/fprochazka/travis-local-build.svg?branch=master)](https://travis-ci.org/fprochazka/travis-local-build)

This tool

1. computes jobs the Travis-CI would run
2. generates docker container for each job
3. runs each job

## Usage

* clone the project
* install dependencies using composer
* build docker images in `docker-images/` using `docker-compose build`
	* **Pro-Tip:** you can customize them!
* run the executable `bin/travis` in desired directory

![travis-local-build](docs/travis-local.gif)
