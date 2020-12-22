# Database
The application depends on MySQL DB.

Default way of MySQL deployment on OCP:
```
oc new-app --name=db \
  -e MYSQL_USER=skill_matrix \
  -e MYSQL_PASSWORD=secret \
  -e MYSQL_DATABASE=skill_matrix \
  registry.redhat.io/rhel8/mysql-80
```

Create the databse from manifests:
```
oc create -f openshift/db-deployment.yaml
oc create -f openshift/db-service.yaml
```

Verify:
* open interactive shell on the DB pod: `oc exec -it svc/db bash`
* connect to DB: mysql -u skill_matrix -p -h db
* check the DB: show databases - expect skill_matrix

## Initialisation
The database is defined using PHP scripts. In order to populate it use `php artisan migrate` on an app pod configured to work with the database service.

# Application
[Skill Matrix](https://github.com/the-guitarman/skill_matrix) is packaged in a container described in Dockerfile and published to docker.io/dav1dli/skill-matrix repository.

The application is configured using *.env* file located in the application root. The application is PHP deployed in Apache HTTPD.

Create the application from manifests:
```
create -f openshift/app-env.yaml
create -f openshift/app-is.yaml
create -f openshift/app-deployment.yaml
oc create -f openshift/app-service.yaml
oc create -f openshift/app-route.yaml
```

As a result the application is available at http://skill-matrix-<project>.<cluster>/

# Helm
TDB

# Issues
## Permissions
Red Hat UBI container image creates /opt/app-root/src with mode 755 and owned by user default with id 1001:0. But pod is running with a random user different from default. This must be taken in account when packaging the application and configuring is for runtime.

## Persistence

## Configuration
The application is configured using *.env* file located in the application root. The file is flat and some of its entries like database authentication is sensitive. Thus this configuration is provided as skill-matrix-env secret. In its data section the key is a name of file and data is base64 encoded content of .env file. The secret is referenced in the deployment configuration in volumes and mounted under specified directory in volumeMounts section.

The secret as a file is mounted under specified directory overriding its content. Thus it cannot be mounted directly under /opt/app-root/src where .env is expected. Life cycle postStart action is used to copy the file to the correct location.
