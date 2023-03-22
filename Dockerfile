FROM webdevops/php-nginx-dev:8.1

# Install acl package
# todo remove the || true when the tideways packages sources are fixed
RUN apt-get update || true && \
    apt-get install -y acl && \
    rm -rf /var/lib/apt/lists/*

# Copy code inside the image
COPY ./app /app

# Copy configuration file inside the image
COPY ./.docker/nginx/conf.d/default.conf /opt/docker/etc/nginx/vhost.conf
COPY ./app/config/messenger-worker.conf /opt/docker/etc/supervisor.d/messenger-worker.conf

WORKDIR /app