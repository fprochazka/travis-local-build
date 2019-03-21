FROM fprochazka/php:5.5

ENV DEBIAN_FRONTEND=noninteractive
ENV DEBCONF_NONINTERACTIVE_SEEN=true

RUN apt-get update -q \
  && apt-get install unzip git libicu-dev curl wget libcurl4-gnutls-dev libmcrypt-dev -y --no-install-recommends

#RUN pecl install xdebug
COPY conf/xdebug.ini /usr/local/etc/php/conf.d/

RUN pecl install memcache
#RUN pecl install redis

ENV TIMEZONE=Europe/Prague
RUN echo ${TIMEZONE} > /etc/timezone \
	&& dpkg-reconfigure -f noninteractive tzdata

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1
ENV COMPOSER_HOME=/root/.composer
RUN mkdir -p $COMPOSER_HOME \
	&& curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
	# Make sure we're installing what we think we're installing!
	# https://composer.github.io/pubkeys.html
	&& echo "48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5" > /tmp/composer-setup.sig \
	&& php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" \
	&& php /tmp/composer-setup.php --no-ansi --install-dir=/usr/local/bin --filename=composer --snapshot \
	&& rm -f /tmp/composer-setup.* \
	&& composer --version

COPY bin/travis_retry /usr/bin
