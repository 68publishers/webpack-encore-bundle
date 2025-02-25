FROM php:8.3.12-cli-alpine3.20 AS php83

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apk add --no-cache ${PHPIZE_DEPS} \
    && pecl install pcov \
    && pecl install uopz-7.1.1 \
    && docker-php-ext-enable pcov uopz

CMD tail -f /dev/null

FROM php:8.4.4-cli-alpine3.21 AS php84

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apk add --no-cache ${PHPIZE_DEPS} \
    && mkdir -p /usr/src/php/ext/uopz \
    && curl -fsSL https://github.com/zonuexe/uopz/archive/refs/heads/support/php84-exit.tar.gz | tar xvz -C /usr/src/php/ext/uopz --strip 1 \
    && docker-php-ext-install uopz \
    && pecl install pcov \
    && docker-php-ext-enable pcov

CMD tail -f /dev/null
