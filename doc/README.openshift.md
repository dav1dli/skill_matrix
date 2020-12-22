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
* check the DB: show dstabases - expect skill_matrix

## Initialisation
`php artisan migrate`

# Application
Application is configured using *.env* file located in the application root. The application is PHP deployed in Apache HTTPD.

Create the application from manifests:
```
create -f openshift/app-deployment.yaml
oc create -f openshift/app-service.yaml
oc create -f openshift/app-ingress.yaml
```

As a result the application is available at http://skill-matrix.<cluster>/

# Helm
