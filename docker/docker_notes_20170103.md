# run reason CMS with docker

## Prerequisites
Install docker for mac or get docker and docker-compose on your machine.

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
VIRTUAL_HOST=reason.dev
```

You can change the settings above if you like.

Now you are ready to run reason using docker:

```
cd path/to/reasoncms

docker-compose up
## this will build and run mysql, a simple nginx proxy, and reason from this repository

```
You can see that it is running by executing `docker ps -a`:

```
$ docker ps -a
CONTAINER ID        IMAGE               COMMAND                  CREATED             STATUS              PORTS                NAMES
a71b786203a2        reasoncms_reason    "/entrypoint.sh sh -c"   7 minutes ago       Up 7 minutes        0.0.0.0:80->80/tcp   reasoncms_reason_1
e76387873b8a        mysql:5.7           "docker-entrypoint.sh"   7 minutes ago       Up 7 minutes        3306/tcp             reasoncms_reason-mysql_1
```

after the containers have been build and are running, you need to run the `install.sh` script.

run `bash` on the reason container, not that we use the name `reasoncms_reason_1` that docker-compose has assigned to that container. See output of `docker ps -a` above.

`docker exec -it reasoncms_reason_1 bash`

After running that docker command you will have a shell on the running container:
```
root@b0b550f05e22:/usr/src/app#
```

In that container run the following commands:
```
./install.sh
```

Follow the prompts. When asked `supply a path from the root of your server` you should enter `/usr/src/root`.  When the script asks
```
If you have an account that can create databases and users,
this script can create the users and databases for you.
If not, you'll need to create a database and user for Reason.
Should this script try to create your database and user? (y/n)'
```
you should answer `n`. At that point, the script will use the settings you have set in the `.env` file to prepare the database.

Finally, you can `exit` from the docker container, this will halt this docker container and return you to your local shell.

This should populate the database and then you can go to http://localhost/reason/setup.php to verify the reason environment is functional. Scroll down to the bottom of the page to see how to access the admin area of your new reason installation.
