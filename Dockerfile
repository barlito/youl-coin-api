FROM dunglas/frankenphp:php8.2

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    vim \
	acl \
    supervisor \
	&& rm -rf /var/lib/apt/lists/*

# add additional extensions here:
RUN install-php-extensions \
    @composer \
	pdo_pgsql \
	gd \
	intl \
	zip \
	opcache \
    apcu \
    pcntl \
    bcmath \
    amqp


# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1

#COPY ./.docker/supervisor.d /etc/supervisor

CMD ["/usr/bin/supervisord"]