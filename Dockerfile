FROM webdevops/php-nginx-dev:8.1

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

RUN setfacl -R -m u:application:rwx -m u:1000:rwx /app/var && \
    setfacl -dR -m u:application:rwx -m u:1000:rwx /app/var && \
    setfacl -R -m u:application:rwx -m u:1000:rwx /app/src && \
    setfacl -dR -m u:application:rwx -m u:1000:rwx /app/src
