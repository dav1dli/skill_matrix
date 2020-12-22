FROM dav1dli/php74-node10
LABEL name="skill_matrix" \
      vendor="Deutsche Bahn" \
      version="0.1.0" \
      release="1" \
      summary="Skill Matrix container image" \
      description="Skill Matrix container image" \
      io.k8s.description="Skill Matrix" \
      io.k8s.display-name="skill_matrix" \
      io.openshift.expose-services="8080:http" \
      io.openshift.tags="php,php74,node,node10,skill_matrix" \
      maintainer="david.liderman@deutschebahn.com"
COPY --chown=1001:0 . /opt/app-root/src
COPY --chown=1001:0 .env.docker /opt/app-root/src/.env
RUN chmod 664 /opt/app-root/src/.env && \
  chmod 775 /opt/app-root/src /opt/app-root/src/storage
COPY --chown=1001:0 ./config/ldap.php.example /opt/app-root/src/config/ldap.php
COPY --chown=1001:0 httpd-skill-matrix.conf /opt/app-root/etc/conf.d/skill-matrix.conf
WORKDIR /opt/app-root/src
USER 1001
RUN composer install && \
  npm install && \
  npm run dev && \
  npm audit fix && \
  php artisan key:generate
EXPOSE 8080
EXPOSE 8000
CMD httpd -D FOREGROUND
