FROM registry.access.redhat.com/ubi8/php-74
LABEL name="php74-node10" \
      vendor="Deutsche Bahn" \
      version="0.1.0" \
      release="1" \
      summary="PHP + NodeJS 10 container image" \
      description="PHP + NodeJS 10 container image" \
      io.k8s.description="PHP 7.4 NodeJS 10" \
      io.k8s.display-name="php74-node10" \
      io.openshift.tags="php,php74,node,node10" \
      maintainer="david.liderman@deutschebahn.com"

USER 0
RUN yum update --disablerepo=* --enablerepo=ubi-8-appstream --enablerepo=ubi-8-baseos -y && \
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
php composer-setup.php --version="1.10.1" --install-dir=/usr/local/bin --filename=composer && \
php -r "unlink('composer-setup.php');" && \
rm -rf .config/composer /opt/app-root/src/.config && \
curl -sL https://rpm.nodesource.com/setup_10.x | bash -
USER 1001
