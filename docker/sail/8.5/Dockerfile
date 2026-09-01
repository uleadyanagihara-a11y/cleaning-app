# Project-owned Laravel Sail runtime based on laravel/sail (MIT).
FROM ubuntu:24.04@sha256:561618e2c15bf2397621dd04f96926663a3b5616c189cf7e38db7e82f5c538ea

LABEL maintainer="Cleaning App"

ARG WWWGROUP=1000
ARG PHP_VERSION=8.5
ARG PHP_RUNTIME_VERSION=8.5.9
ARG NODE_VERSION=24
ARG NODE_RUNTIME_VERSION=24.19.0
ARG NODE_PACKAGE_VERSION=24.19.0-1nodesource1
ARG COMPOSER_VERSION=2.10.2
ARG NPM_VERSION=12.0.2
ARG PNPM_VERSION=11.21.0
ARG BUN_VERSION=1.3.14
ARG COREPACK_VERSION=0.35.0
ARG YARN_VERSION=4.18.0
ARG PLAYWRIGHT_VERSION=1.62.1
ARG MYSQL_CLIENT="mysql-client"
ARG POSTGRES_VERSION=18
ARG PHP_EXTENSIONS=""

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV LANG=C.UTF-8
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"
ENV PLAYWRIGHT_BROWSERS_PATH=0

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

RUN apt-get update \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python3 dnsutils librsvg2-bin fswatch ffmpeg nano fonts-ipaexfont-gothic \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y \
        libgd3 \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-dev \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-sqlite3 \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-mongodb \
        php${PHP_VERSION}-imap \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-readline \
        php${PHP_VERSION}-ldap \
        php${PHP_VERSION}-msgpack \
        php${PHP_VERSION}-igbinary \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-swoole \
        php${PHP_VERSION}-memcached \
        php${PHP_VERSION}-pcov \
        php${PHP_VERSION}-imagick \
        php${PHP_VERSION}-xdebug \
    && curl -sLS https://getcomposer.org/installer | php -- --version="$COMPOSER_VERSION" --install-dir=/usr/bin/ --filename=composer \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_VERSION}.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs="$NODE_PACKAGE_VERSION" \
    && npm install -g npm@"$NPM_VERSION" bun@"$BUN_VERSION" corepack@"$COREPACK_VERSION" \
    && corepack enable \
    && corepack prepare pnpm@"$PNPM_VERSION" --activate \
    && corepack prepare yarn@"$YARN_VERSION" --activate \
    && npx -y playwright@"$PLAYWRIGHT_VERSION" install-deps \
    && curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor | tee /etc/apt/keyrings/pgdg.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt noble-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y $MYSQL_CLIENT \
    && apt-get install -y postgresql-client-$POSTGRES_VERSION \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN if [ -n "$PHP_EXTENSIONS" ]; then \
    apt-get update \
    && apt-get install -y $(for extension in $PHP_EXTENSIONS; do echo "php${PHP_VERSION}-$extension"; done) \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*; \
fi

RUN test "$(php -r 'echo PHP_VERSION;')" = "$PHP_RUNTIME_VERSION" \
    && test "$(node --version)" = "v$NODE_RUNTIME_VERSION" \
    && test "$(npm --version)" = "$NPM_VERSION" \
    && test "$(pnpm --version)" = "$PNPM_VERSION" \
    && test "$(bun --version)" = "$BUN_VERSION" \
    && test "$(corepack --version)" = "$COREPACK_VERSION" \
    && test "$(yarn --version)" = "$YARN_VERSION" \
    && composer --version --no-ansi | grep -q "Composer version $COMPOSER_VERSION " \
    && npx --offline playwright --version | grep -q "Version $PLAYWRIGHT_VERSION"

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php${PHP_VERSION}

RUN userdel -r ubuntu
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail
RUN git config --global --add safe.directory /var/www/html

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.5/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80/tcp

ENTRYPOINT ["start-container"]
