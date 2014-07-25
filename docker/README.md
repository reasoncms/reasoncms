# Deploying with docker

### Install Docker

 * Go here: http://docs.docker.com/installation/
 * pick your platform and follow the instructions.

### Setup nginx proxy to manage virtual hosts

Start the nginx proxy service:

~~~ sh
docker run -d -p 80:80 -v /var/run/docker.sock:/tmp/docker.sock -t jwilder/nginx-proxy
~~~

See the [github repository](https://github.com/jwilder/nginx-proxy) for more
info on how this works.

### Start the mysql server

~~~
docker run --name reason_mysql -e MYSQL_PASS=ReasonAdminPassword1 -d tutum/mysql
~~~

This starts a mysql server with password "ReasonAdminPassword1" and user "admin".

### Create the Reason docker image

* __Clone the repo:__

  ~~~
  git clone https://github.com/carleton/reason_package.git
  ~~~

* __Build the image:__

  ~~~
  docker build -t docker-registry.example.com/reason .
  ~~~

### Configure and launch app

* __Run the install script:__

  ~~~
  docker run --rm -i -t --link reason_mysql:mysql -v /root/reason_package:/usr/src/app docker-registry.example.com/reason ./install.sh
  ~~~

* __Launch the web app process__

  ~~~
  docker run --name reason -d --link reason_mysql:mysql -v /root/reason_package:/usr/src/app -e VIRTUAL_HOST=reason.example.com docker-registry.example.com/reason
  ~~~
