FROM debian:bookworm-slim

RUN apt update && apt-get -y install curl gettext-base\
    && apt-get install -y --install-recommends apache2\
    && apt-get -y install php php-mysql php-mbstring php-gmp zip unzip php-zip\
    && apt-get -y install default-mysql-client-core
    
RUN apt-get -y install python3 python3-pip
RUN pip3 install mysql-connector-python --break-system-packages

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