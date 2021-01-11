FROM dav1dli/php74-node10
LABEL name="skill_matrix" \
      vendor="Deutsche Bahn" \
      version="0.1.0" \
      release="1" \
      summary="Skill Matrix container image" \
      description="Skill Matrix container image" \
      io.k8s.description="Skill Matrix" \
      io.k8s.display-name="skill_matrix" \
      io.openshift.expose-services="8000:http" \
      io.openshift.tags="php,php74,node,node10,skill_matrix" \
      maintainer="david.liderman@deutschebahn.com"
COPY . /opt/app-root/src
COPY .env.docker /opt/app-root/src/.env
COPY config/ldap.php.example /opt/app-root/src/config/ldap.php
COPY httpd-skill-matrix.conf /opt/app-root/etc/conf.d/skill-matrix.conf
USER 0
RUN chown -R 1001:1 /opt/app-root/src /opt/app-root/src/config/ldap.php /opt/app-root/etc/conf.d/skill-matrix.conf && \
    chmod 664 /opt/app-root/src/.env && \
    chmod 775 /opt/app-root/src /opt/app-root/src/storage
WORKDIR /opt/app-root/src
USER 1001
RUN composer install && \
  npm install || echo $? && \
  npm run dev && \
  npm audit fix && \
  php artisan key:generate
EXPOSE 8000
CMD httpd -D FOREGROUND
