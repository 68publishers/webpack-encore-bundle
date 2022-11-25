FROM php:7.4.33-cli-alpine3.16 AS php74

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD tail -f /dev/null

FROM php:8.0.25-cli-alpine3.16 AS php80

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD tail -f /dev/null

FROM php:8.1.12-cli-alpine3.16 AS php81

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD tail -f /dev/null

FROM php:8.2.0RC6-cli-alpine3.16 AS php82

CMD ["/bin/sh"]
WORKDIR /var/www/html

RUN apk add --no-cache --update git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD tail -f /dev/null
