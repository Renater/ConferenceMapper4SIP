# ConferenceMapper4SIP



# Build Docker Image :
```
docker image build -t conferencemapper .
```

# Docker Compose Config sample :  

  confmapper_server:
    image: conferencemapper
    build:
        context: conferencemapper
    container_name: conferencemapper
    volumes:
      - ./config/config_sample.php:/usr/local/ConferenceMapper/src/policyserver/config/config.php
    ports:
      - "8085:80"
      - "8445:443"
    logging:
      driver: syslog
      options:
        tag: "conferencemapper"
    depends_on:
      - db
    environment:
      DB_PASSWORD: rdv_password
      DB_USER: rdv_user
      DB_HOST: db
