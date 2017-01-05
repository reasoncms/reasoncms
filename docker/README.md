# Run Reason CMS with docker

## Prerequisites
### Install Docker

 * Go here: http://docs.docker.com/installation/
 * pick your platform and follow the instructions.

### Get reason
Checkout `reasoncms` from github (https://github.com/reasoncms/reasoncms)


## build and install using `docker-compose`
Go to your copy of the `reasoncms` repository

First, you will need to set some configuration variables. Docker prefers setting these as environment variables. An easy way to do this is to create a file called `.env` in the root of your `reasoncms` checkout. Be sure is contains the following:

```
MYSQL_ROOT_PASSWORD=ReasonAdminPassword1
MYSQL_DATABASE=reason
MYSQL_USER=reason_user
MYSQL_PASSWORD=some_password
MYSQL_HOST=mysql
```

You can change the settings above if you like.

Now you are ready to run reason using docker:

```
cd path/to/reasoncms

docker-compose up
## this will build and run mariadb and reason from this repository

```
You can see that it is running by executing `docker ps -a`:

```
$ docker ps -a
CONTAINER ID        IMAGE               COMMAND                  CREATED             STATUS              PORTS                NAMES
024dca8d9a87        reasoncms_web       "/entrypoint.sh sh -c"   2 minutes ago       Up 2 minutes        0.0.0.0:80->80/tcp   reasoncms_web_1
59ed96522426        mariadb:5.5         "docker-entrypoint.sh"   15 minutes ago      Up 2 minutes        3306/tcp             reasoncms_db_1
```

After the containers have been built and are running, you need to seed the database, again using docker. *This step is only needed on a fresh install*. It *destroys* any reason data in your database when it is run.

`docker exec -it reasoncms_web_1 ./docker/docker-install.sh`

This should populate the database and then you can go to http://localhost/reason/setup.php to verify the reason environment is functional. Scroll down to the bottom of the page to see how to access the admin area of your new reason installation.
