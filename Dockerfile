FROM webdevops/php-nginx:8.1

# Install acl package
RUN apt-get update && \
    apt-get install -y acl && \
    rm -rf /var/lib/apt/lists/*

# Copy code inside the image
COPY ./app /app

# Copy configuration file inside the image
COPY ./.docker/nginx/conf.d/default.conf /opt/docker/etc/nginx/vhost.conf
COPY ./.docker/supervisor.d/messenger-worker.conf /opt/docker/etc/supervisor.d/messenger-worker.conf

WORKDIR /app

RUN composer install --no-interaction --no-progress --no-suggest --optimize-autoloader --no-scripts --no-dev

RUN php bin/console assets:install --env=prod --no-debug

RUN php bin/console cache:warmup --env=prod

RUN setfacl -R -m u:application:rwx /app/var && \
    setfacl -dR -m u:application:rwx /app/var && \
    setfacl -R -m u:application:rwx /app/bin && \
    setfacl -dR -m u:application:rwx /app/bin && \
    setfacl -R -m u:application:rwx /app/src && \
    setfacl -dR -m u:application:rwx /app/src
