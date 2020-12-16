# Overview
[Skill Matrix](https://github.com/the-guitarman/skill_matrix) is an application combining Apache, PHP, NodeJS and MySQL technologies. It is distributed as a traditional application running on a host with the installed stack of technologies.
This document describes a method how it can be packaged in containers and executed locally for testing purposes. Those containers can be deployed on Kubernetes platforms like Openshift or Amazon EKS.

The document assumes that container tools like Docker are installed.

# Containers

Containers are created from freely distributable [Universal Base Images](https://developers.redhat.com/blog/2020/03/24/red-hat-universal-base-images-for-docker-users/) (UBI8) certified to run on Openshift.

## MySQL

Start stand-alone container:

```
docker run -d --name mysql_database \
  -e MYSQL_USER=skill_matrix \
  -e MYSQL_PASSWORD=secret \
  -e MYSQL_DATABASE=skill_matrix \
  -p 3306:3306 registry.redhat.io/rhel8/mysql-80
```

this command start a container, creates skill_matrix empty database and creates user skill_matrix. The running service is exposed on the default MySQL port 3306/TCP. Remote connections are enabled i.e. applications can connect to the DB from the outside of the container.

## Baseline application container

The application requires both NodeJS and PHP. This functionality is not provided by generally available Red Hat containers thus a PHP 7.4 base container is extended with NodeJS 10 runtime.

*Note:* _PHP versions earlier than 7.4 are incompatible with MySQL8._

Build container: `docker build -t php73-node10 . -f Dockerfile.phpnode`

## Skill Matrix App

Build container: `docker build -t skill-matrix .`

# Start/stop

An example docker compose configuration is provided: docker-compose.yaml. It describes both components of the application: db and app with required parameters. Compose scenario is supported by .env.docker application configuration.

Start: `docker compose up`

Stop: `docker compose down`

Open the application in the browser: http://localhost:8000/

Use login names gauss, euler, euclid with password 'password' to login to the application.

TODO: database init. MySQL container is started with an empty DB created. The application provides the DB definition not as sql scripts but as php files. Thus it is to be found out how the DB can be properly initialized. Meanwhile it can be done manually: `docker exec <skill_matrix_container> php artisan migrate`
