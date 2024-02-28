# ConferenceMapper4SIP
ConferenceMapper4SIP is a web service that informations used to connect Video Conference Room Equipment and Phone to a Web conference tool.
It is used to provied dialin informations and PIN acces code.

ConferenceMapper4SIP is build to respect the Jitsi conference mapping API used by the Jitsi-Meet interface and described here : [https://community.jitsi.org/t/tutorial-self-hosted-conference-mapper-api/53901].


## This project provides : 
 - A PHP web service to get  : the PIN/confereneName mapping and the dialing numbers list
 - A Docker environnemnt to easily deploy a conference mapper.  

## ConferenceMapper4SIP API endpoints : 
The ConferenceMapper4SIP respects the jitsi cloud-api : https://github.com/jitsi/jitsi-meet/blob/master/resources/cloud-api.swagger and add some specific command to help   loadbalancing for VOIP plateforme. 

### GET /conferenceMapper
Description : Conference mapping between conference JID and numeric 

Parameters : 
- id  : a valid id to get the conference name mapped to ti.
- conference : a conference name in format confname@conferenc.jitsi_domaine to get or generate a mapped id.


Return : Json

### GET /phoneNumberList
Description :  Answer with Phone number list and additional information

Parameters : None

Return : Json

### GET /getDialDest
Description : Used to managed multi Jigasi extention on the PBX side to use multilple Jigasi server. It return a element of the JIGASI_EXTANSION_LIST in a round robin scheduling.

Parameters : None

Return : Json 

## Docker Environnement 

### Build Docker Image :
```
docker image build -t conferencemapper4sip .
```

We also provide Docker image on DockerHub : [https://hub.docker.com/r/renater/conferencemapper4sip].


### Docker Compose Configuration :  
```
  confmapper_server:
    image: conferencemapper4sip
    build:
        context: conferencemapper
    container_name: conferencemapper
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
      DB_PASSWORD: XXXXXXXXX
      DB_USER: user
      DB_HOST: host_db
      DB_DATABASE: conf_db
      JITSI_DOMAIN: meet.jit.si
      PHONE_NUMBER_LIST: '0978080000'
      JIGASI_EXTANSION_LIST: "'666','555'"
      LIFETIME_SHORT: 6
      LIFETIME_LONG: 1440

  db:
    container_name: db
    image: mysql
    volumes:
      - db:/var/lib/mysql
    environment:
      MYSQL_HOST=db
      MYSQL_ROOT_PASSWORD=YYYYYYYYY
      MYSQL_DATABASE=conf_db
      MYSQL_USER=user
      MYSQL_PASSWORD=XXXXXXXXX
```