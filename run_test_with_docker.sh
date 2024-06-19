#!/bin/bash

docker run --rm --interactive --tty \
  --volume $PWD:/app \
  --entrypoint /bin/bash \
  composer -c "\
      cd /app;\
      rm -rf vendor;\
      composer install;\
      vendor/bin/phpunit --testdox --bootstrap vendor/autoload.php tests
  ";