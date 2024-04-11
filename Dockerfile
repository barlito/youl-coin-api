FROM webdevops/php-nginx:8.2

# Install acl package
RUN apt-get update && \
    apt-get install -y acl && \
    rm -rf /var/lib/apt/lists/*

# Copy code inside the image
COPY ./app /app

# Set permissions
RUN chown -R application:application /app
#RUN setfacl -R -m u:application:rwx /app && \
#    setfacl -dR -m u:application:rwx /app

# Copy configuration file inside the image
COPY ./.docker/nginx/conf.d/default.conf /opt/docker/etc/nginx/vhost.conf
COPY ./.docker/supervisor.d/messenger-worker.conf /opt/docker/etc/supervisor.d/messenger-worker.conf

WORKDIR /app

USER application

RUN composer install --no-interaction --no-progress --no-suggest --optimize-autoloader --no-scripts --no-dev

RUN php bin/console assets:install --env=prod --no-debug

RUN php bin/console cache:warmup --env=prod

USER root