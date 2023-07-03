FROM ubuntu:22.04

RUN echo 'APT::Install-Suggests "0";' >> /etc/apt/apt.conf.d/00-docker
RUN echo 'APT::Install-Recommends "0";' >> /etc/apt/apt.conf.d/00-docker

RUN apt update
RUN apt-get -y install wget gnupg2  ca-certificates software-properties-common
RUN DEBIAN_FRONTEND=noninteractive TZ=Etc/UTC apt-get -y install tzdata

RUN apt-get -y install curl gettext-base\
    && apt-get update\
    && apt-get install -y --install-recommends apache2\
    && apt-get -y install php php-mysql php-mbstring php-gmp composer zip unzip php-zip\
    && apt-get -y install python3-mysql.connector mysql-client-core-8.0

RUN mkdir /usr/local/ConferenceMapper
COPY ./src /usr/local/ConferenceMapper/src
COPY ./config/config.php /usr/local/ConferenceMapper/src/policyserver/config/config.php
COPY ./src/conferenceMapper.conf /etc/apache2/sites-available/confmapper.conf
RUN a2ensite confmapper.conf
RUN a2enmod ssl

EXPOSE 80 443

# redirect apache logs to docker stdout/stderr
RUN ln -sf /proc/1/fd/1 /var/log/apache2/access.log
RUN ln -sf /proc/1/fd/2 /var/log/apache2/error.log


COPY entrypoint.sh /var/
RUN chmod +x /var/entrypoint.sh

ENTRYPOINT ["/bin/bash", "/var/entrypoint.sh"]