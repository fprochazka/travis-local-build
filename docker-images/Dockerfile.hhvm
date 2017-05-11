FROM ubuntu:16.04

ENV DEBIAN_FRONTEND=noninteractive
ENV DEBCONF_NONINTERACTIVE_SEEN=true

RUN apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x5a16e7281be7a449 \
  && apt-get update -y && apt-get install -y software-properties-common \
  && add-apt-repository "deb http://dl.hhvm.com/ubuntu xenial main" && apt-get update -y \
  && apt-get install -y hhvm

RUN apt-get update -q \
  && apt-get install unzip git libicu-dev curl wget libcurl4-gnutls-dev libmcrypt-dev -y --no-install-recommends

ENV TIMEZONE=Europe/Prague
RUN echo ${TIMEZONE} > /etc/timezone \
	&& dpkg-reconfigure -f noninteractive tzdata

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1
ENV COMPOSER_HOME=/usr/local/share/composer
RUN mkdir -p /usr/local/share/composer \
	&& curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
	# Make sure we're installing what we think we're installing!
	# https://composer.github.io/pubkeys.html
	&& echo "669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410" > /tmp/composer-setup.sig \
	&& php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" \
	&& php /tmp/composer-setup.php --no-ansi --install-dir=/usr/local/bin --filename=composer --snapshot \
	&& rm -f /tmp/composer-setup.* \
	&& composer --version

COPY bin/travis_retry /usr/bin
